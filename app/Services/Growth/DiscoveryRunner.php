<?php

namespace App\Services\Growth;

use App\Models\OutreachTarget;
use App\Services\Growth\Discovery\BusinessDiscoverySource;
use App\Services\Growth\Discovery\DiscoveredBusiness;
use App\Support\Growth\GrowthCatalog;
use App\Support\Growth\RegionPolicy;

/**
 * Keşif hattını uçtan uca yürütür: sorgu üret → kaynaktan işletme çek →
 * tekilleştir → Türk tespiti → bölge kapısı → outreach_targets'a upsert.
 *
 * "Türk değil" sonucu alanlar KAYDEDİLMEZ (havuz Türk + sınırda adaylarla sınırlı
 * kalır). Gönderim durumu (marketing_status) RegionPolicy ile işlenir — AB/TR
 * dahil her ülke keşfedilebilir ama yalnız izinli bölgeler gönderime açılır.
 */
final class DiscoveryRunner
{
    public function __construct(
        private readonly BusinessDiscoverySource $source,
        private readonly TurkishBusinessDetector $detector,
        private readonly QueryPermutationEngine $queries,
    ) {}

    /**
     * Bir ülke için katalogdaki şehir/mesleklerle keşif çalıştırır.
     *
     * @return array{source:string, queries:int, discovered:int, turkish:int, ambiguous:int, blocked:int, saved:int, created:int}
     */
    public function runForCountry(string $country, int $tradeLimit = 3, int $perQuery = 20, bool $useLlm = false): array
    {
        $country = strtoupper($country);
        $cities = GrowthCatalog::CITIES[$country] ?? [];
        $trades = array_slice(GrowthCatalog::tradesForCountry($country), 0, $tradeLimit);
        $queryList = $this->queries->build($cities, $trades);

        // Kaynaktan çek + external_id ile tekilleştir (aynı işletme birden çok
        // sorguda çıkabilir; tespiti bir kez yapmak için önce toplarız).
        /** @var array<string, DiscoveredBusiness> $found */
        $found = [];
        foreach ($queryList as $query) {
            foreach ($this->source->search($query, $country, $perQuery) as $business) {
                $found[$business->id()] = $business;
            }
        }

        $stats = ['turkish' => 0, 'ambiguous' => 0, 'blocked' => 0, 'saved' => 0, 'created' => 0];

        foreach ($found as $business) {
            $result = $useLlm
                ? $this->detector->detect($business->toSignal())
                : $this->detector->screen($business->toSignal());

            // "Türk değil" adayları havuza girmez.
            if ($result->band === DetectionResult::BAND_NOT) {
                continue;
            }

            $marketing = RegionPolicy::marketingStatus($business->country);

            $record = OutreachTarget::updateOrCreate(
                ['source' => $this->source->name(), 'external_id' => $business->id()],
                [
                    'name' => $business->name,
                    'category' => $business->category,
                    'owner_name' => $business->ownerName,
                    'country' => $business->country,
                    'city' => $business->city,
                    'sector' => $business->sector,
                    'website' => $business->website,
                    'detection_band' => $result->band,
                    'detection_confidence' => (int) round($result->confidence * 100),
                    'detection_method' => $result->method,
                    'detection_signals' => $result->signals,
                    'detection_reasoning' => $result->reasoning,
                    'needs_review' => $result->needsHumanReview(),
                    'marketing_status' => $marketing,
                ],
            );

            $stats['saved']++;
            $stats['created'] += $record->wasRecentlyCreated ? 1 : 0;
            $stats[$result->band === DetectionResult::BAND_TURKISH ? 'turkish' : 'ambiguous']++;
            $stats['blocked'] += $marketing === RegionPolicy::BLOCKED ? 1 : 0;
        }

        return [
            'source' => $this->source->name(),
            'queries' => count($queryList),
            'discovered' => count($found),
            ...$stats,
        ];
    }
}

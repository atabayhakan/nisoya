<?php

namespace App\Services\Growth;

use App\Models\OutreachTarget;
use App\Services\Growth\Discovery\BusinessDiscoverySource;
use App\Services\Growth\Discovery\DiscoveredBusiness;
use App\Services\Growth\Discovery\KesifKaynagiHatasi;
use App\Support\Growth\GrowthCatalog;
use App\Support\Growth\RegionPolicy;
use Illuminate\Support\Facades\Log;

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
    ) {}

    /**
     * Bir ülke için katalogdaki TÜM şehir × meslek kombinasyonlarında keşif
     * çalıştırır (senkron — CLI içindir; panel yavaşlamamak için kombo başına
     * kuyruk işi kullanır, bkz. App\Jobs\RunDiscoveryJob).
     *
     * @return array{source:string, queries:int, failed:int, discovered:int, turkish:int, ambiguous:int, blocked:int, saved:int, created:int}
     */
    public function runForCountry(string $country, int $tradeLimit = 3, int $perQuery = 20, bool $useLlm = false): array
    {
        $country = strtoupper($country);
        $cities = GrowthCatalog::CITIES[$country] ?? [];
        $trades = array_slice(GrowthCatalog::tradesForCountry($country), 0, $tradeLimit);

        $totals = ['queries' => 0, 'failed' => 0, 'discovered' => 0, 'turkish' => 0, 'ambiguous' => 0, 'blocked' => 0, 'saved' => 0, 'created' => 0];

        foreach ($cities as $city) {
            foreach ($trades as $trade) {
                foreach ($this->runForCityTrade($country, $city, $trade, $perQuery, $useLlm) as $key => $value) {
                    $totals[$key] += $value;
                }
            }
        }

        return ['source' => $this->source->name(), ...$totals];
    }

    /**
     * Tek bir şehir × meslek kombinasyonu için keşif+tespit+kalıcılaştırma.
     * Kısa sürer (birkaç ağ çağrısı) — kuyruk işlerinin çalıştırdığı birim budur.
     *
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     * @return array{queries:int, failed:int, discovered:int, turkish:int, ambiguous:int, blocked:int, saved:int, created:int}
     */
    public function runForCityTrade(string $country, string $city, array $trade, int $perQuery = 20, bool $useLlm = false): array
    {
        $country = strtoupper($country);
        $bos = ['queries' => 1, 'failed' => 0, 'discovered' => 0, 'turkish' => 0, 'ambiguous' => 0, 'blocked' => 0, 'saved' => 0, 'created' => 0];

        // Kaynaktan çek + external_id ile tekilleştir (aynı işletme birden çok
        // dil varyantında çıkabilir; tespiti bir kez yapmak için önce toplarız).
        /** @var array<string, DiscoveredBusiness> $found */
        $found = [];

        /*
         * ARIZA ≠ SONUÇSUZLUK. Kaynak yanıt veremediğinde tur DURMAZ (bir
         * şehrin arızası diğerlerini iptal etmemeli) ama sayılır ve rapora
         * kırmızı düşer. Eskiden bu durum sessizce "0 sonuç" olarak
         * görünüyordu — bkz. KesifKaynagiHatasi.
         */
        try {
            foreach ($this->source->discover($city, $country, $trade, $perQuery) as $business) {
                $found[$business->id()] = $business;
            }
        } catch (KesifKaynagiHatasi $e) {
            Log::warning('Growth: keşif kaynağı yanıt veremedi', [
                'sehir' => $city, 'ulke' => $country, 'meslek' => $trade['key'] ?? '?', 'sebep' => $e->getMessage(),
            ]);

            return [...$bos, 'failed' => 1];
        }

        $stats = [...$bos, 'discovered' => count($found)];

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

        return $stats;
    }
}

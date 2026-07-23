<?php

namespace App\Services\Growth;

use App\Models\OutreachTarget;
use App\Support\Growth\RegionPolicy;

/**
 * Zenginleştirme hattı: web sitesi olan ama iletişim e-postası olmayan Türk/sınırda
 * adaylarda ContactEnricher'ı çalıştırıp contact_email'i doldurur.
 *
 * GDPR korkuluğu: kişisel veri (adlı e-posta) YALNIZCA gönderim-izinli bölgelerde
 * toplanır. AB/TR/RU (region_blocked) adayları ATLANIR — onlar için sadece
 * işletme+konum düzeyi saklanır (bkz. docs/06-tanitim-agenti-plani.md §1.1).
 */
final class EnrichmentRunner
{
    public function __construct(private readonly ContactEnricher $enricher) {}

    /**
     * @return array{candidates:int, enriched:int, skipped_blocked:int, no_email:int}
     */
    public function run(?string $country = null, int $limit = 50): array
    {
        $query = OutreachTarget::query()
            ->whereNull('contact_email')
            ->whereNotNull('website')
            ->where('detection_band', '!=', DetectionResult::BAND_NOT);

        if ($country !== null) {
            $query->where('country', strtoupper($country));
        }

        $candidates = $query->limit($limit)->get();

        $enriched = 0;
        $skippedBlocked = 0;
        $noEmail = 0;

        foreach ($candidates as $target) {
            // GDPR: gönderim engelli bölgede kişisel iletişim TOPLAMA.
            if ($target->marketing_status !== RegionPolicy::ALLOWED) {
                $skippedBlocked++;

                continue;
            }

            $email = $this->enricher->enrich($target->website);

            if ($email === null) {
                $noEmail++;

                continue;
            }

            $target->update(['contact_email' => $email]);
            $enriched++;
        }

        return [
            'candidates' => $candidates->count(),
            'enriched' => $enriched,
            'skipped_blocked' => $skippedBlocked,
            'no_email' => $noEmail,
        ];
    }
}

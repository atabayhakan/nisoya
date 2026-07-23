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
        $candidates = OutreachTarget::query()
            ->pendingEnrichment()
            ->when($country !== null, fn ($q) => $q->where('country', strtoupper((string) $country)))
            ->limit($limit)
            ->get();

        $stats = ['candidates' => $candidates->count(), 'enriched' => 0, 'skipped_blocked' => 0, 'no_email' => 0];

        foreach ($candidates as $target) {
            match ($this->enrichOne($target)) {
                'enriched' => $stats['enriched']++,
                'blocked' => $stats['skipped_blocked']++,
                default => $stats['no_email']++,
            };
        }

        return $stats;
    }

    /**
     * Tek bir adayı zenginleştirir (kuyruk işinin birimi). GDPR: gönderim engelli
     * bölgede kişisel iletişim TOPLANMAZ.
     *
     * @return 'enriched'|'blocked'|'no_email'
     */
    public function enrichOne(OutreachTarget $target): string
    {
        if ($target->marketing_status !== RegionPolicy::ALLOWED) {
            return 'blocked';
        }

        if ($target->website === null || $target->contact_email !== null) {
            return 'no_email';
        }

        $email = $this->enricher->enrich($target->website);

        if ($email === null) {
            return 'no_email';
        }

        $target->update(['contact_email' => $email]);

        return 'enriched';
    }
}

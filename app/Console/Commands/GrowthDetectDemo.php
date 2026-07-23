<?php

namespace App\Console\Commands;

use App\Services\Growth\BusinessSignal;
use App\Services\Growth\DetectionResult;
use App\Services\Growth\QueryPermutationEngine;
use App\Services\Growth\TurkishBusinessDetector;
use App\Support\Growth\GrowthCatalog;
use Illuminate\Console\Command;

/**
 * Büyüme Ajanı keşif + tespit motorunun canlı demosu. API anahtarı gerektirmez —
 * yalnızca deterministik ön-eleme katmanını gerçek örneklerde çalıştırır ve
 * sorgu permütasyon motorunun ürettiği aramaları gösterir.
 *
 *   php artisan growth:detect-demo
 */
class GrowthDetectDemo extends Command
{
    protected $signature = 'growth:detect-demo';

    protected $description = 'Türk işletme tespit + sorgu permütasyon motorunu örnek verilerle gösterir (API anahtarı gerekmez).';

    public function handle(TurkishBusinessDetector $detector, QueryPermutationEngine $queries): int
    {
        $this->components->info('1) SORGU PERMÜTASYON MOTORU — Almaty (KZ), örnek meslekler');

        $sample = $queries->build(
            ['Almaty'],
            array_slice(GrowthCatalog::tradesForCountry('KZ'), 0, 3),
        );
        foreach ($sample as $q) {
            $this->line('   • '.$q);
        }
        $this->newLine();
        $this->components->info('2) TÜRK TESPİT MOTORU (deterministik ön-eleme) — 10 örnek işletme');

        $rows = [];
        foreach ($this->samples() as $signal) {
            $r = $detector->screen($signal);
            $rows[] = [
                $signal->name,
                $signal->country,
                $this->bandLabel($r),
                number_format($r->confidence * 100).'%',
                $r->needsHumanReview() ? 'EVET' : '—',
                $this->short($r->signals),
            ];
        }

        $this->table(
            ['İşletme', 'Ülke', 'Sonuç', 'Güven', 'İnceleme?', 'Sinyaller'],
            $rows,
        );

        $turkish = count(array_filter($rows, fn ($r) => str_contains($r[2], 'TÜRK') && ! str_contains($r[2], 'DEĞİL')));
        $review = count(array_filter($rows, fn ($r) => $r[4] === 'EVET'));

        $this->components->info(sprintf(
            '%d işletmeden %d tanesi Türk/sınırda işaretlendi, %d tanesi LLM+insan onayına yönlendirildi.',
            count($rows),
            $turkish,
            $review,
        ));
        $this->line('   → Sınırdakiler (ambiguous) canlıda OpenRouter LLM ile doğrulanır; kesinler otomatik geçer.');

        return self::SUCCESS;
    }

    /** Demo için gerçekçi işletme örnekleri (hedef ülkelerden). */
    private function samples(): array
    {
        return [
            new BusinessSignal('Anadolu Kebap House', 'Restaurant', country: 'US'),
            new BusinessSignal('Mehmet Yılmaz Auto Repair', 'Auto repair', 'Mehmet Yılmaz', 'US'),
            new BusinessSignal('Turkish Delight Restaurant', 'Turkish restaurant', country: 'TH'),
            new BusinessSignal('Öztürk Construction', 'Contractor', 'Ömer Öztürk', 'US'),
            new BusinessSignal('Bishkek Mobilya', 'Furniture', 'Ahmet Kaya', 'KG'),
            new BusinessSignal('Kaya Sushi Bar', 'Sushi restaurant', country: 'US'),
            new BusinessSignal('Istanbul Cafe', 'Cafe', country: 'TH'),
            new BusinessSignal('Almaty Electric Solutions', 'Electrician', country: 'KZ'),
            new BusinessSignal('Golden Dragon Restaurant', 'Chinese restaurant', country: 'TH'),
            new BusinessSignal('Sunrise Bakery', 'Bakery', country: 'US'),
        ];
    }

    private function bandLabel(DetectionResult $r): string
    {
        return match ($r->band) {
            DetectionResult::BAND_TURKISH => '<fg=green>✓ TÜRK</>',
            DetectionResult::BAND_NOT => '<fg=gray>✗ TÜRK DEĞİL</>',
            default => '<fg=yellow>? SINIRDA</>',
        };
    }

    /** @param  list<string>  $signals */
    private function short(array $signals): string
    {
        if ($signals === []) {
            return '—';
        }

        $joined = implode('; ', $signals);

        return mb_strlen($joined) > 60 ? mb_substr($joined, 0, 57).'...' : $joined;
    }
}

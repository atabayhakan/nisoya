<?php

namespace App\Console\Commands;

use App\Models\OutreachTarget;
use App\Services\Growth\DiscoveryRunner;
use App\Support\Growth\GrowthCatalog;
use Illuminate\Console\Command;

/**
 * Bir ülke için keşif çalıştırır ve sonuçları outreach_targets'a yazar. Google
 * Places anahtarı yoksa fixture kaynağıyla uçtan uca çalışır (gerçek DB kaydı).
 *
 *   php artisan growth:discover US
 *   php artisan growth:discover TH --trades=5 --llm
 */
class GrowthDiscover extends Command
{
    protected $signature = 'growth:discover {country : Ülke kodu (US, KZ, KG, TH...)}
        {--trades=3 : Kaç meslek taransın}
        {--llm : Sınırdakileri LLM ile doğrula (OpenRouter)}';

    protected $description = 'Bir ülkede Türk işletmeleri keşfeder, tespit eder ve outreach_targets havuzuna yazar.';

    public function handle(DiscoveryRunner $runner): int
    {
        $country = strtoupper($this->argument('country'));

        if (! isset(GrowthCatalog::CITIES[$country])) {
            $this->components->error("Katalogda '{$country}' için şehir yok. Mevcut: ".implode(', ', array_keys(GrowthCatalog::CITIES)));

            return self::FAILURE;
        }

        $this->components->info("Keşif başlıyor: {$country}");

        $stats = $runner->runForCountry(
            $country,
            (int) $this->option('trades'),
            useLlm: (bool) $this->option('llm'),
        );

        $this->newLine();
        $this->components->twoColumnDetail('Kaynak', $stats['source']);
        $this->components->twoColumnDetail('Üretilen sorgu', (string) $stats['queries']);

        /*
         * ARIZA SATIRI SIFIRSA BASILMAZ, sıfır değilse KIRMIZI.
         * "0 işletme bulundu" ile "15 sorgunun 15'i yanıt alamadı" apayrı
         * şeyler; ikincisi birincisi gibi görününce sahip yanlış sonuç çıkarır
         * ("demek ki orada Türk işletmesi yok").
         */
        if ($stats['failed'] > 0) {
            $this->components->twoColumnDetail(
                '<fg=red>Yanıt alınamayan sorgu</>',
                $stats['failed'].' / '.$stats['queries'].' — sebep: sunucu kayıtlarında'
            );
        }
        $this->components->twoColumnDetail('Bulunan işletme', (string) $stats['discovered']);
        $this->components->twoColumnDetail('<fg=green>Türk</>', (string) $stats['turkish']);
        $this->components->twoColumnDetail('<fg=yellow>Sınırda (inceleme)</>', (string) $stats['ambiguous']);
        $this->components->twoColumnDetail('Havuza yazılan', (string) $stats['saved'].' (yeni: '.$stats['created'].')');
        $this->components->twoColumnDetail('<fg=red>Gönderim engelli (AB/TR/RU)</>', (string) $stats['blocked']);

        $this->newLine();
        $this->components->info('Havuzdaki '.$country.' kayıtları:');

        $rows = OutreachTarget::query()
            ->where('country', $country)
            ->orderByDesc('detection_confidence')
            ->get(['name', 'city', 'detection_band', 'detection_confidence', 'marketing_status', 'needs_review'])
            ->map(fn (OutreachTarget $t): array => [
                $t->name,
                $t->city,
                $this->band($t->detection_band),
                $t->detection_confidence.'%',
                $t->marketing_status === 'allowed' ? '<fg=green>gönderilebilir</>' : '<fg=red>engelli</>',
                $t->needs_review ? 'EVET' : '—',
            ])
            ->all();

        $this->table(['İşletme', 'Şehir', 'Sonuç', 'Güven', 'Gönderim', 'İnceleme?'], $rows);

        return self::SUCCESS;
    }

    private function band(string $band): string
    {
        return match ($band) {
            'turkish' => '<fg=green>✓ TÜRK</>',
            'not_turkish' => '<fg=gray>✗ değil</>',
            default => '<fg=yellow>? SINIRDA</>',
        };
    }
}

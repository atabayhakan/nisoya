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
 *   php artisan growth:discover DE --source=overpass   (bu koşuya özel kaynak)
 */
class GrowthDiscover extends Command
{
    protected $signature = 'growth:discover {country : Ülke kodu (US, KZ, KG, TH...)}
        {--trades=3 : Kaç meslek taransın}
        {--source= : Bu koşu için kaynak (overpass|google|fixture) — panel ayarını geçer}
        {--llm : Sınırdakileri LLM ile doğrula (OpenRouter)}';

    protected $description = 'Bir ülkede Türk işletmeleri keşfeder, tespit eder ve outreach_targets havuzuna yazar.';

    /** `--source` ile seçilebilen kaynaklar (AppServiceModel bağlamasıyla aynı). */
    private const KAYNAKLAR = ['overpass', 'google', 'fixture', 'auto'];

    /**
     * DiscoveryRunner METOT İMZASINDA DEĞİL, GÖVDEDE ÇÖZÜLÜR — bilerek.
     *
     * Metot enjeksiyonu handle() çağrılmadan ÖNCE çalışır; kaynağı gövdede
     * ayarlayıp sonra enjekte edilmiş runner'ı kullanmak, ayarı geç yapmak
     * olurdu (bağlama config'i resolve anında okuyor).
     */
    public function handle(): int
    {
        $country = strtoupper($this->argument('country'));

        if (! isset(GrowthCatalog::CITIES[$country])) {
            $this->components->error("Katalogda '{$country}' için şehir yok. Mevcut: ".implode(', ', array_keys(GrowthCatalog::CITIES)));

            return self::FAILURE;
        }

        if (! $this->kaynagiSec()) {
            return self::FAILURE;
        }

        $runner = app(DiscoveryRunner::class);

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

    /**
     * `--source` verildiyse bu koşu için kaynağı sabitler.
     *
     * -----------------------------------------------------------------------
     * NEDEN GEREKLİ (2026-08-08, canlı taramada bulundu)
     *
     * `GROWTH_SOURCE=overpass php artisan growth:discover DE` yazıldı, çıktı
     * "Kaynak: google_places" dedi. Ön ek İKİ KEZ kaybediyor:
     *
     *   1. `config/growth.php` kaynağı `env()` ile okuyor; üretimde config
     *      CACHE'Lİ, yani `env()` çalışma anını değil cache anını yansıtır.
     *   2. `AppServiceProvider::mergeGrowthConfig()` panel ayarını (DB) config
     *      üzerine yazıyor — DB > env, bu bilinçli ve doğru.
     *
     * Yani ortam değişkeniyle tek seferlik kaynak seçmenin YOLU YOKTU; komut
     * sessizce panelde seçili kaynağı kullanıyor, kullanıcı ise başka bir şey
     * çalıştırdığını sanıyordu. Bedeli somut: ücretsiz Overpass yerine kotalı
     * Google Places harcandı.
     *
     * Açık bir bayrak doğru katman: uygulama için DB > env kalsın, ama TEK BİR
     * KOŞU için komut satırı ikisini de geçsin. Çıktıdaki "Kaynak" satırı
     * zaten gerçekte ne kullanıldığını yazıyor — hatayı da o yakalatmıştı.
     */
    private function kaynagiSec(): bool
    {
        $secim = $this->option('source');

        if ($secim === null || $secim === '') {
            return true;
        }

        $secim = strtolower(trim((string) $secim));

        if (! in_array($secim, self::KAYNAKLAR, true)) {
            $this->components->error(
                "Bilinmeyen kaynak '{$secim}'. Geçerli: ".implode(', ', self::KAYNAKLAR)
            );

            return false;
        }

        config(['growth.source' => $secim]);

        return true;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\MediaRendition;
use App\Services\Medya\MedyaTuretici;
use Illuminate\Console\Command;
use Throwable;

/**
 * Slot türevlerini ana kopyalardan yeniden üretir.
 *
 *   php artisan media:yeniden-turet                 # YALNIZ SAYAR
 *   php artisan media:yeniden-turet --uygula        # tüm slotlar
 *   php artisan media:yeniden-turet hero_masaustu --uygula
 *
 * ---------------------------------------------------------------------------
 * BU KOMUT TASARIMIN KARŞILIĞIDIR
 *
 * "Ana kopyayı sakla" kararının somut getirisi budur: slotun boyutu/oranı
 * değişirse ya da odak kayarsa YENİDEN YÜKLEME GEREKMEZ. 2026-08-09'da
 * yaşanan "1800×1200 yüklendi ama slot 2400×1200 istiyor" durumu tek komutla
 * çözülür — ana kopya 1800'den büyükse.
 *
 * Varsayılan KURU KOŞU: üretimde dosya yazan bir komutun varsayılanı "yaz"
 * olamaz (bkz. growth:ulke-duzelt'te aynı kural).
 */
class MedyaYenidenTuret extends Command
{
    protected $signature = 'media:yeniden-turet
        {slot? : Yalnız bu slot (boşsa mevcut tüm türevler)}
        {--uygula : Sayma, gerçekten üret (varsayılan yalnız sayar)}';

    protected $description = 'Slot türevlerini ana kopyalardan yeniden üretir (varsayılan: kuru koşu).';

    public function handle(MedyaTuretici $turetici): int
    {
        $uygula = (bool) $this->option('uygula');
        $slot = $this->argument('slot');

        if ($slot !== null && ! is_array(config("media_slots.{$slot}"))) {
            $this->components->error("Tanımsız slot: {$slot}");
            $this->line('  Tanımlılar: '.implode(', ', array_keys((array) config('media_slots', []))));

            return self::FAILURE;
        }

        $sorgu = MediaRendition::query()->with('asset');
        if ($slot !== null) {
            $sorgu->where('slot', $slot);
        }

        $isler = $sorgu->get();

        $this->newLine();
        $this->components->twoColumnDetail('Yeniden üretilecek türev', (string) $isler->count());
        $this->components->twoColumnDetail('Slot', $slot ?? '(hepsi)');

        if ($isler->isEmpty()) {
            $this->components->info('Yapılacak iş yok.');

            return self::SUCCESS;
        }

        if (! $uygula) {
            $this->newLine();
            $this->components->warn('KURU KOŞU — hiçbir dosya üretilmedi. Üretmek için: --uygula');

            return self::SUCCESS;
        }

        $basarili = 0;
        $hatali = 0;

        foreach ($isler as $is) {
            $asset = $is->asset;

            // KAPALI DÜŞEN DÖNGÜ: ana kopyası olmayan türev atlanır, silinmez.
            // (Silmek, düzeltilebilir bir tutarsızlığı geri alınamaz hâle getirir.)
            if (! $asset instanceof MediaAsset) {
                $hatali++;
                $this->components->warn("#{$is->id} — ana kopya kaydı yok, atlandı.");

                continue;
            }

            try {
                $yeni = $turetici->turet($asset, $is->slot);
                $basarili++;

                if (! $yeni->hedefiTutuyorMu()) {
                    $this->components->warn(
                        "#{$asset->id} {$is->slot} — ağırlık hedefi tutmadı (".
                        round($yeni->bayt / 1024).' KB). Dosya yine de üretildi.'
                    );
                }

                if ($asset->slotIcinKucukMu($is->slot)) {
                    $this->components->warn(
                        "#{$asset->id} {$is->slot} — ana kopya {$asset->en}px, slot ".
                        config("media_slots.{$is->slot}.en").'px istiyor; retinada yumuşak görünecek.'
                    );
                }
            } catch (Throwable $e) {
                $hatali++;
                $this->components->error("#{$asset->id} {$is->slot} — ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Üretilen</>', (string) $basarili);
        $this->components->twoColumnDetail($hatali > 0 ? '<fg=red>Başarısız</>' : 'Başarısız', (string) $hatali);

        return $hatali > 0 ? self::FAILURE : self::SUCCESS;
    }
}

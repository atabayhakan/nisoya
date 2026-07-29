<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoDefteri;
use App\Services\Demo\DemoTemizleyici;
use Illuminate\Console\Command;

/**
 * Örnek (demo) veriyi geri alır.
 *
 *   php artisan demo:sil 2026-07-30-a1b2c3   # tek parti
 *   php artisan demo:sil --hepsi             # bütün demo veri
 *
 * ÜRETİM KAPISI YOK — bilerek. `demo:uret` üretimde `--force` ister ama silme
 * her zaman kolay olmalı: temizlenmesi zor olan bir demo makinesi, geri
 * alınamayan bir demo makinesidir.
 */
class DemoSil extends Command
{
    protected $signature = 'demo:sil
                            {parti? : Silinecek parti kimliği}
                            {--hepsi : Bütün demo partilerini sil}';

    protected $description = 'Örnek (demo) veriyi kayıtlarıyla ve dosyalarıyla birlikte siler';

    public function handle(DemoDefteri $defter, DemoTemizleyici $temizleyici): int
    {
        if ($this->option('hepsi')) {
            $sonuc = $temizleyici->hepsiniSil();

            $this->components->info("{$sonuc['parti_sayisi']} parti silindi, {$sonuc['dosya']} dosya kaldırıldı.");
            $this->dokumYaz($sonuc['silinen']);
            $this->artikUyar($sonuc['artik']);

            return self::SUCCESS;
        }

        $parti = $this->argument('parti');

        if (! is_string($parti) || $parti === '') {
            $this->components->error('Parti kimliği ver ya da --hepsi kullan.');
            $this->line('  Partileri görmek için: php artisan demo:durum');

            return self::FAILURE;
        }

        if (! $defter->partiVarMi($parti)) {
            $this->components->error("Defterde [{$parti}] diye bir parti yok.");

            return self::FAILURE;
        }

        $sonuc = $temizleyici->sil($parti);

        $this->components->info("Parti silindi: {$parti} — {$sonuc['dosya']} dosya kaldırıldı.");
        $this->dokumYaz($sonuc['silinen']);

        if ($sonuc['bulunamayan'] > 0) {
            // Sessizce geçilmez: defterde yazan ama artık var olmayan kayıt,
            // ya elle silinmiş ya da beklenmedik bir cascade almış demektir.
            $this->components->warn("{$sonuc['bulunamayan']} kayıt zaten yoktu (elle silinmiş ya da cascade almış olabilir).");
        }

        $this->artikUyar($sonuc['artik']);

        return self::SUCCESS;
    }

    /** @param  array<string, int>  $dokum */
    private function dokumYaz(array $dokum): void
    {
        if ($dokum === []) {
            $this->line('  Silinecek kayıt yoktu.');

            return;
        }

        foreach ($dokum as $ad => $adet) {
            $this->line("  {$ad}: {$adet}");
        }
    }

    private function artikUyar(int $artik): void
    {
        if ($artik > 0) {
            $this->components->warn(
                "Defterde olmayan {$artik} işaretli demo kaydı kaldı. ".
                'Defter dışında demo veri üretilmiş ya da bir silme yarım kalmış olabilir.'
            );
        }
    }
}

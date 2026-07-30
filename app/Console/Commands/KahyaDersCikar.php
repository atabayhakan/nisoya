<?php

namespace App\Console\Commands;

use App\Models\KahyaCalismasi;
use App\Services\Kahya\DersCikarici;
use Illuminate\Console\Command;

/**
 * Haftalık öğrenme koşusu (F5).
 *
 *     php artisan kahya:ders-cikar
 *
 * Günlük rapor komutuyla aynı disiplin: koşu HER durumda deftere yazılır
 * (sessizce ölen zamanlanmış iş, hiç olmayan işten kötüdür) ve komut LLM
 * hatasında ÇÖKMEZ — hata defterde durur, zamanlayıcı kirlenmez.
 */
class KahyaDersCikar extends Command
{
    protected $signature = 'kahya:ders-cikar';

    protected $description = 'Sahibin son haftadaki kararlarından ders damıtıp Kâhya hafızasına yazar';

    public function handle(DersCikarici $cikarici): int
    {
        $baslangic = microtime(true);
        $hata = null;
        $sonuc = null;

        try {
            $sonuc = $cikarici->calis();

            $this->line("Durum: {$sonuc['durum']} · sinyal: {$sonuc['sinyal']} · üretilen ders: {$sonuc['uretilen']}");

            foreach ($sonuc['dersler'] as $ders) {
                $this->line("  · {$ders}");
            }
        } catch (\Throwable $e) {
            report($e);
            $hata = mb_substr($e->getMessage(), 0, 200);
            $this->error('Ders çıkarma başarısız: '.$hata);
        }

        KahyaCalismasi::create([
            'tur' => 'ders_cikar',
            'gonderildi' => false,
            'ozet' => $sonuc,
            'hata' => $hata,
            'sure_ms' => (int) ((microtime(true) - $baslangic) * 1000),
        ]);

        return self::SUCCESS;
    }
}

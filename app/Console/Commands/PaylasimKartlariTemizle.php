<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Services\PaylasimKartiUretici;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Paylaşım kartı önbelleğinin süpürücüsü.
 *
 * Kartlar `{ilan_id}-{imza}.png` adıyla saklanıyor ve imza hem ilanın
 * içeriğini hem çizim SURUM'unu özetliyor. Yani her başlık/fiyat düzenlemesi
 * ve her görünüm değişikliği yeni bir dosya doğuruyor, eskisi diskte kalıyor
 * (kart başına ~800KB).
 *
 * PaylasimKartiUretici yeni kart yazarken o ilanın eskilerini zaten siliyor.
 * Bu komut o mekanizmanın ULAŞAMADIĞI iki durumu topluyor:
 *
 *   1. Kartı bir daha hiç istenmeyen ilanlar — dosya sonsuza dek durur.
 *   2. Silinmiş ilanların kartları — sahibi olmayan yetim dosyalar.
 *
 * Güncel kart SİLİNMEZ: amaç çöp toplamak, önbelleği boşaltmak değil.
 */
class PaylasimKartlariTemizle extends Command
{
    protected $signature = 'paylasim-kartlari:temizle {--rapor : Hiçbir şey silme, yalnız ne silineceğini yaz}';

    protected $description = 'Geçersiz ve yetim ilan paylaşım kartlarını diskten temizler';

    public function handle(PaylasimKartiUretici $uretici): int
    {
        $disk = Storage::disk('public');
        $rapor = (bool) $this->option('rapor');

        /** @var array<int, list<string>> $idyeGore */
        $idyeGore = [];

        foreach ($disk->files(PaylasimKartiUretici::KLASOR) as $dosya) {
            // Desen bilgisi TEK yerde (üretici) — anında temizlikle aynı kural.
            $id = PaylasimKartiUretici::dosyaAdindanIlanId($dosya);

            if ($id === null) {
                continue; // Bizim üretmediğimiz dosyaya dokunma.
            }

            $idyeGore[$id][] = $dosya;
        }

        if ($idyeGore === []) {
            $this->info('Temizlenecek kart yok.');

            return self::SUCCESS;
        }

        $silinecek = [];
        $yetim = 0;

        // Güncel yol hesabı ilişkileri okuduğu için parça parça ilerliyoruz;
        // kart sayısı ilan sayısıyla büyür ve tek sorguda toplamak istemeyiz.
        foreach (array_chunk($idyeGore, 200, true) as $parca) {
            $ilanlar = Listing::query()
                ->whereIn('id', array_keys($parca))
                ->with(['coverImage', 'images', 'category'])
                ->get()
                ->keyBy('id');

            foreach ($parca as $id => $dosyalar) {
                $ilan = $ilanlar->get($id);

                if (! $ilan instanceof Listing) {
                    // İlan silinmiş: kartlarının tamamı yetim.
                    $silinecek = array_merge($silinecek, $dosyalar);
                    $yetim += count($dosyalar);

                    continue;
                }

                $guncel = $uretici->yol($ilan);

                $silinecek = array_merge(
                    $silinecek,
                    array_values(array_filter($dosyalar, fn (string $d): bool => $d !== $guncel))
                );
            }
        }

        if ($silinecek === []) {
            $this->info('Temizlenecek kart yok — hepsi güncel.');

            return self::SUCCESS;
        }

        $bayt = 0;

        foreach ($silinecek as $dosya) {
            $bayt += $disk->size($dosya);
        }

        $mb = round($bayt / 1048576, 1);
        $bayat = count($silinecek) - $yetim;

        if ($rapor) {
            $this->info("RAPOR (silinmedi): {$bayat} bayat + {$yetim} yetim = ".count($silinecek)." dosya, ~{$mb} MB");

            foreach ($silinecek as $dosya) {
                $this->line('  '.$dosya);
            }

            return self::SUCCESS;
        }

        $disk->delete($silinecek);

        $this->info("Silinen: {$bayat} bayat + {$yetim} yetim = ".count($silinecek)." dosya, ~{$mb} MB");

        return self::SUCCESS;
    }
}

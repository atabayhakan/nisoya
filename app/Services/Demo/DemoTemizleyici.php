<?php

namespace App\Services\Demo;

use App\Models\DemoKaydi;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demo verisini GERİ ALIR — bu sistemin en önemli parçası.
 *
 * Sahte veri üretmek kolaydır; asıl iş onu eksiksiz geri almaktır. Geride tek
 * bir yetim satır ya da diskte kalan tek bir dosya, "temizledim" cümlesini
 * yalan yapar — ve yalan olduğu FARK EDİLMEZ.
 *
 * ---------------------------------------------------------------------------
 * SİLME SIRASI: DEFTERİN TERSİ
 *
 * Defter üretim sırasını saklar; burada ters sırada gidilir, yani çocuklar
 * ebeveynlerinden önce silinir. Bu bir zarafet meselesi değil, zorunluluk:
 *
 *   · Ebeveyn önce silinseydi veritabanı cascade'i devreye girer, ELOQUENT
 *     BAYPAS EDİLİR ve model olayları hiç tetiklenmez. `ListingImageObserver`
 *     çalışmaz, görsel dosyaları diskte kalır. (Bu depo aynı sorunu
 *     `ListingController::destroy` ve `ProfileSettingsController` içinde elle
 *     telafi ediyor; `Listing` için bir gözlemci yok.)
 *
 *   · `listings` silinince `conversations.listing_id`, `reviews.listing_id` ve
 *     `deals.listing_id` NULL'lanır ama SATIRLAR KALIR (nullOnDelete). Yani
 *     yanlış sıra tam olarak yetim üretir.
 *
 * ---------------------------------------------------------------------------
 * DOSYALAR: GÖZLEMCİYE GÜVENİLMEZ
 *
 * Model silindikten SONRA defterdeki dosya yolları ayrıca silinir. Sıra
 * bilinçli: veritabanı doğruluk kaynağıdır. Dosya silme başarısız olursa
 * diskte artık kalır (zararsız, sonradan bulunabilir); tersi olsaydı kayıt
 * hâlâ dururken dosyası gitmiş olurdu — yani kırık görsel.
 *
 * Avatarlar için bu şart: `UserObserver`'ın deleting kancası hiç yok ve
 * avatarla hiç ilgilenmiyor.
 *
 * ---------------------------------------------------------------------------
 * FK'SİZ ARTIKLAR
 *
 * `notifications`, `sessions` ve `password_reset_tokens` tablolarının
 * `users` ile FK bağı YOK — kullanıcı silinse de satırları kalır. Demo üretimi
 * bildirim üretmiyor ve demo hesaplar giriş yapmıyor, yani bu tablolar normalde
 * boş kalır; yine de temizleniyor, çünkü "normalde" bir garanti değildir.
 */
class DemoTemizleyici
{
    public function __construct(private readonly DemoDefteri $defter) {}

    /**
     * Bir partiyi geri alır.
     *
     * @return array{parti: string, silinen: array<string, int>, dosya: int, bulunamayan: int, artik: int}
     */
    public function sil(string $parti): array
    {
        $kayitlar = $this->defter->silmeSirasi($parti);

        $silinen = [];
        $dosyaSayisi = 0;
        $bulunamayan = 0;
        $epostalar = [];
        $kullaniciIdleri = [];

        /*
         * Etkinlik günlüğü kapalı: silme işlemi demo kayıtlar için
         * activity_log satırı üretmemeli — o tablonun FK'si yok, yani
         * temizlikten SONRA kalırdı.
         */
        activity()->withoutLogs(function () use ($kayitlar, &$silinen, &$dosyaSayisi, &$bulunamayan, &$epostalar, &$kullaniciIdleri): void {
            foreach ($kayitlar as $kayit) {
                $model = $this->modeliBul($kayit);

                if ($model instanceof User) {
                    $epostalar[] = $model->email;
                    $kullaniciIdleri[] = $model->id;
                }

                if ($model === null) {
                    // Zaten yok: ya elle silinmiş ya da bir cascade almış.
                    // Hata değil, ama sayılır — "eksiksiz sildim" iddiasının
                    // ne kadarının bu koşuda gerçekleştiği görünsün.
                    $bulunamayan++;
                } else {
                    $model->delete();
                    $ad = class_basename($kayit->model_turu);
                    $silinen[$ad] = ($silinen[$ad] ?? 0) + 1;
                }

                // Dosyalar modelden SONRA: veritabanı doğruluk kaynağıdır.
                foreach ($kayit->dosyalar ?? [] as $yol) {
                    if (Storage::disk('public')->exists($yol)) {
                        Storage::disk('public')->delete($yol);
                    }

                    $dosyaSayisi++;
                }

                $kayit->delete();
            }
        });

        $this->fkSizArtiklariTemizle($kullaniciIdleri, $epostalar);

        return [
            'parti' => $parti,
            'silinen' => $silinen,
            'dosya' => $dosyaSayisi,
            'bulunamayan' => $bulunamayan,
            'artik' => $this->artikSayisi(),
        ];
    }

    /**
     * Bütün partileri geri alır.
     *
     * @return array{parti_sayisi: int, silinen: array<string, int>, dosya: int, artik: int}
     */
    public function hepsiniSil(): array
    {
        $toplamSilinen = [];
        $toplamDosya = 0;
        $partiler = array_column($this->defter->partiler(), 'parti');

        foreach ($partiler as $parti) {
            $sonuc = $this->sil($parti);
            $toplamDosya += $sonuc['dosya'];

            foreach ($sonuc['silinen'] as $ad => $adet) {
                $toplamSilinen[$ad] = ($toplamSilinen[$ad] ?? 0) + $adet;
            }
        }

        return [
            'parti_sayisi' => count($partiler),
            'silinen' => $toplamSilinen,
            'dosya' => $toplamDosya,
            'artik' => $this->artikSayisi(),
        ];
    }

    /**
     * DEFTERDE OLMAYAN ama `is_demo` işaretli kayıt sayısı.
     *
     * Sıfırdan farklı olması bir arızadır: ya defter dışında demo veri
     * üretilmiş ya da bir silme yarım kalmış demektir. `demo:durum` bunu
     * gösterir; sessizce sıfır varsaymak bu sistemin bütün amacına aykırı.
     */
    public function artikSayisi(): int
    {
        return $this->defterDisiSayisi(User::class)
            + $this->defterDisiSayisi(Listing::class);
    }

    /**
     * `is_demo` işaretli ama defterde KARŞILIĞI OLMAYAN kayıt sayısı.
     *
     * Defterdekileri düşmek şart: aksi hâlde sayaç her sağlıklı partiyi de
     * "artık" sayar ve uyarı her zaman yanar. Her zaman yanan bir uyarı,
     * hiç yanmayan bir uyarıdır.
     *
     * @param  class-string<Model>  $sinif
     */
    private function defterDisiSayisi(string $sinif): int
    {
        return $sinif::query()
            ->where('is_demo', true)
            ->whereNotIn('id', DemoKaydi::query()->where('model_turu', $sinif)->select('model_id'))
            ->count();
    }

    private function modeliBul(DemoKaydi $kayit): ?Model
    {
        /** @var class-string<Model> $sinif */
        $sinif = $kayit->model_turu;

        if (! class_exists($sinif) || ! is_subclass_of($sinif, Model::class)) {
            return null;
        }

        return $sinif::query()->find($kayit->model_id);
    }

    /**
     * `users` ile yabancı anahtar bağı OLMAYAN tabloları temizler.
     *
     * @param  array<int, int>  $kullaniciIdleri
     * @param  array<int, string>  $epostalar
     */
    private function fkSizArtiklariTemizle(array $kullaniciIdleri, array $epostalar): void
    {
        if ($kullaniciIdleri !== []) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $kullaniciIdleri)
                ->delete();

            DB::table('sessions')->whereIn('user_id', $kullaniciIdleri)->delete();
        }

        if ($epostalar !== []) {
            DB::table('password_reset_tokens')->whereIn('email', $epostalar)->delete();
        }
    }
}

<?php

namespace App\Services\Kahya;

use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Veritabanının "var" dediği medya dosyaları diskte gerçekten var mı?
 *
 * NEDEN VAR: 2026-07-29'da canlıda bir ilanın kapak görselinin üç varyantı da
 * 404 veriyordu ve bunu ancak dışarıdan elle tarayarak bulduk. Veritabanı
 * kaydı sapasağlamdı; eksik olan diskteki dosyaydı. Hiçbir ekran, hiçbir log,
 * hiçbir uyarı bunu göstermiyordu — ziyaretçi kırık görsel görüyordu, sahip
 * hiçbir şey görmüyordu.
 *
 * Bu sınıf o boşluğu kapatır: kayıtları gezip `Storage::exists()` ile
 * karşılaştırır. Kayıp dosya bulursa günlük raporun "ne bozuk" bölümüne düşer.
 *
 * TERS YÖNDE TARAMA YAPMAZ (diskte olup DB'de olmayan). O da bir sorundur
 * ama farklı bir sorundur (yetim dosya = disk israfı, kırık görsel = kullanıcı
 * hatası) ve ayrı ele alınmalı; burada kullanıcının gördüğü hataya odaklanılır.
 *
 * MALİYET: her varyant için bir `exists()` çağrısı. Yerel diskte ucuz. Tarama
 * `$limit` ile sınırlanır çünkü rapor günlük çalışır ve tam tarama büyük
 * kütüphanede yavaşlar; en yeni kayıtlar önceliklidir (yeni yüklenen görselin
 * kırık olması, iki yıllık bir kaydınkinden daha acildir).
 */
class MedyaDogrulayici
{
    public const VARSAYILAN_LIMIT = 500;

    /**
     * @return array{taranan: int, kayip: int, ornekler: list<array{tur: string, id: int, eksik: list<string>, sahip: string}>}
     */
    public function dogrula(int $limit = self::VARSAYILAN_LIMIT): array
    {
        $disk = Storage::disk('public');
        $taranan = 0;
        $kayip = 0;
        $ornekler = [];

        ListingImage::query()
            ->with('listing:id,title,user_id')
            ->latest('id')
            ->limit($limit)
            ->chunkById(100, function ($gorseller) use ($disk, &$taranan, &$kayip, &$ornekler) {
                foreach ($gorseller as $gorsel) {
                    $taranan++;
                    $eksik = [];

                    foreach (['path_thumb' => 'thumb', 'path_medium' => 'medium', 'path_large' => 'large'] as $kolon => $ad) {
                        $yol = $gorsel->{$kolon};

                        // Kolon boşsa "kayıp dosya" değil "hiç üretilmemiş varyant";
                        // ikisi farklı sorunlar, karıştırılmamalı.
                        if ($yol && ! $disk->exists($yol)) {
                            $eksik[] = $ad;
                        }
                    }

                    if ($eksik === []) {
                        continue;
                    }

                    $kayip++;

                    if (count($ornekler) < 10) {
                        $ornekler[] = [
                            'tur' => 'ilan_gorseli',
                            'id' => (int) $gorsel->id,
                            'eksik' => $eksik,
                            // FK kısıtı nedeniyle ilişki null olamaz (ilan
                            // silinince görsel de cascade ile gider).
                            'sahip' => (string) $gorsel->listing->title,
                        ];
                    }
                }
            });

        // Avatarlar: profil sayfasının ilk gördüğü şey.
        User::query()
            ->whereNotNull('avatar_path')
            ->latest('id')
            ->limit($limit)
            ->chunkById(100, function ($kullanicilar) use ($disk, &$taranan, &$kayip, &$ornekler) {
                foreach ($kullanicilar as $kullanici) {
                    $taranan++;

                    if ($disk->exists($kullanici->avatar_path)) {
                        continue;
                    }

                    $kayip++;

                    if (count($ornekler) < 10) {
                        $ornekler[] = [
                            'tur' => 'avatar',
                            'id' => (int) $kullanici->id,
                            'eksik' => ['avatar'],
                            'sahip' => (string) $kullanici->username,
                        ];
                    }
                }
            });

        return ['taranan' => $taranan, 'kayip' => $kayip, 'ornekler' => $ornekler];
    }
}

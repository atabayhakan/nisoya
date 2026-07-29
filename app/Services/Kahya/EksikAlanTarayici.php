<?php

namespace App\Services\Kahya;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Support\Settings;

/**
 * Doldurulmayı bekleyen alanlar — "neyin eksik olduğu".
 *
 * NEDEN İKİ SINIF: `config/site_defaults.php` 112 alan tanımlıyor ve bunların
 * çoğunun boş olması TAMAMEN NORMALDİR (telefon, adres, ikinci CTA metni...).
 * Hepsini "eksik" diye listelemek raporu okunmaz yapar ve okunmayan rapor
 * yazılmamış rapordur.
 *
 *   A SINIFI (kritik) — boş olması siteyi gözle görülür biçimde bozan ya da
 *   bir özelliği tamamen susturan alanlar. Rapora GİRER.
 *
 *   B SINIFI (isteğe bağlı) — boş olması bilinçli bir tercih olabilir.
 *   Rapora yalnız SAYI olarak girer, tek tek listelenmez.
 *
 * "AÇIKLAMASIZ KATEGORİ" ÖRNEĞİ BUGÜNKÜ ŞEMADA UYGULANAMAZ: `categories`
 * tablosunda açıklama sütunu yok (2026_06_30_100004). Onun yerine gerçekten
 * ölçülebilen ve gerçekten değerli olan şey taranıyor: İLANI OLMAYAN aktif
 * kategori. Bu sayfalar bugün `noindex` alıyor (bkz. BosKategoriIndekslenmezTest)
 * — yani boş kalmaları SEO'yu bozmuyor ama pazaryerinin nerede boş olduğunu
 * gösteriyor.
 */
class EksikAlanTarayici
{
    /**
     * Boş olması gerçekten bir şeyi bozan ayarlar.
     *
     * @var array<string, string>
     */
    private const KRITIK = [
        'genel.site_adi' => 'Site adı — her sayfa başlığında geçer',
        'seo.default_title' => 'Varsayılan SEO başlığı — arama sonuçlarında görünür',
        'seo.default_description' => 'Varsayılan SEO açıklaması — arama sonuçlarında görünür',
        'seo.og_image' => 'Paylaşım görseli — link paylaşıldığında görünen resim',
        'gorunum.logo' => 'Logo — üst menüde marka kimliği',
        'iletisim.eposta' => 'İletişim e-postası — form bildirimleri buraya gider',
    ];

    /**
     * @return array{
     *   kritik: list<array{anahtar: string, neden: string}>,
     *   istege_bagli_sayi: int,
     *   ilansiz_kategori: array{sayi: int, ornekler: list<string>}
     * }
     */
    public function tara(): array
    {
        $kritik = [];

        foreach (self::KRITIK as $anahtar => $neden) {
            if (trim((string) Settings::get($anahtar, '')) === '') {
                $kritik[] = ['anahtar' => $anahtar, 'neden' => $neden];
            }
        }

        return [
            'kritik' => $kritik,
            'istege_bagli_sayi' => $this->istegeBagliBosSayisi(),
            'ilansiz_kategori' => $this->ilansizKategoriler(),
        ];
    }

    /**
     * Kritik olmayan boş alanların SAYISI — tek tek listelenmez, çünkü
     * çoğunun boş olması bilinçli bir tercihtir.
     */
    private function istegeBagliBosSayisi(): int
    {
        $alanlar = config('site_defaults.fields', []);
        $sayi = 0;

        foreach (array_keys($alanlar) as $anahtar) {
            if (isset(self::KRITIK[$anahtar])) {
                continue;
            }

            if (trim((string) Settings::get($anahtar, '')) === '') {
                $sayi++;
            }
        }

        return $sayi;
    }

    /**
     * @return array{sayi: int, ornekler: list<string>}
     */
    private function ilansizKategoriler(): array
    {
        // `withCount(...)->having(...)` KULLANILMAZ: SQLite alt sorgu kolonu
        // üzerinde HAVING kabul etmiyor ("HAVING clause on a non-aggregate
        // query") ve testler SQLite'ta koşuyor. `whereDoesntHave` NOT EXISTS
        // üretir; hem SQLite'ta hem MySQL'de aynı çalışır ve tek sorgudur.
        // `->active()` scope'u BURADA ÇAĞRILAMAZ: `whereDoesntHave` closure'ına
        // gelen builder jenerik `Builder<Model>`dir, PHPStan modeli çözemez ve
        // scope'u bulamaz. Koşul düz `where` ile yazılır — kod tabanının geri
        // kalanındaki whereHas kullanımlarıyla aynı biçim. Değerin kaynağı yine
        // enum, yani "aktif" tanımı tek yerde kalıyor (Listing::scopeActive de
        // aynı satırı yazar).
        $bos = Category::query()
            ->where('is_active', true)
            ->whereDoesntHave('listings', fn ($q) => $q->where('status', ListingStatus::Aktif->value))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'sayi' => $bos->count(),
            'ornekler' => $bos->take(5)->pluck('name')->all(),
        ];
    }
}

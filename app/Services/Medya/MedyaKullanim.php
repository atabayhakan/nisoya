<?php

namespace App\Services\Medya;

use App\Models\MediaRendition;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Bu dosya nerede kullanılıyor?"
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § D adımı
 *
 * ---------------------------------------------------------------------------
 * BAĞ TABLOSU DEĞİL, TERS TARAMA — VE BU BİLİNÇLİ
 *
 * Tasarımda "bir görsel bir yüzeye bağlandığında bağ KAYIT ALTINA alınır"
 * yazıyordu ve hemen yanında kendi kusuru da yazılıydı: bağ tablosu ancak YENİ
 * sistemden geçen bağlantıları görür, eskiden elle girilmiş yollar görünmez.
 * Bu yüzden etiketin "silinebilir" değil "bağ bulunamadı" olması gerekiyordu.
 *
 * Ters tarama bu kusuru baştan ortadan kaldırıyor: dosyanın yolunu, referansın
 * GERÇEKTEN yaşadığı yerlerde (ayarlar, vurgu kartları, sayfalar) arıyor.
 * Migration yok, geriye dönük doldurma yok, "yeni sistemden geçmemiş" kör
 * noktası yok.
 *
 * YİNE DE "GÜVENLE SİLİNEBİLİR" DEMİYOR. Tarama veritabanındaki içeriği görür;
 * bir Blade dosyasına ya da harici bir yere elle yazılmış yolu göremez. Sonuç
 * "kullanan bulunamadı" olarak sunulur — yokluk kanıtı değil, arama sonucu.
 */
class MedyaKullanim
{
    /**
     * Bir dosya yolunu kullanan yerlerin insan tarafından okunur listesi.
     *
     * @param  string  $yol  public diskteki göreli yol (ör. "medya/hero_masaustu/x.webp")
     * @return array<int, string>
     */
    public function nerede(string $yol): array
    {
        $yol = ltrim($yol, '/');

        if ($yol === '') {
            return [];
        }

        return array_values(array_unique(array_merge(
            $this->ayarlarda($yol),
            $this->vurguKartlarinda($yol),
            $this->sayfalarda($yol),
        )));
    }

    /** Kullanan var mı? (listeyi kurmadan hızlı cevap) */
    public function kullaniliyorMu(string $yol): bool
    {
        return $this->nerede($yol) !== [];
    }

    /**
     * Site ayarları — hero görselleri, OG görseli, marka logosu.
     *
     * Ayar DEĞERLERİ şifreli olabildiği için (bkz. Settings::SIFRELI) ham SQL
     * `LIKE` güvenilmez; değerler `Settings` üzerinden çözülerek karşılaştırılır.
     *
     * @return array<int, string>
     */
    private function ayarlarda(string $yol): array
    {
        $bulunan = [];

        $etiketler = [
            'hero.gorsel_masaustu' => 'Hero — masaüstü görseli',
            'hero.gorsel_mobil' => 'Hero — mobil görseli',
            'seo.og_image' => 'SEO — paylaşım görseli',
            'gorunum.logo' => 'Marka logosu',
            'marka.logo' => 'Marka logosu',
        ];

        foreach ($etiketler as $anahtar => $etiket) {
            if (Settings::get($anahtar) === $yol) {
                $bulunan[] = $etiket;
            }
        }

        return $bulunan;
    }

    /**
     * Ana sayfa vurgu kartları (`home_highlights.media` JSON).
     *
     * @return array<int, string>
     */
    private function vurguKartlarinda(string $yol): array
    {
        if (! Schema::hasTable('home_highlights')) {
            return [];
        }

        $bulunan = [];

        foreach (DB::table('home_highlights')->select('id', 'title', 'media')->get() as $kart) {
            if (self::iceriyorMu($kart->media, $yol)) {
                $bulunan[] = 'Vurgu kartı: '.($kart->title ?: '#'.$kart->id);
            }
        }

        return $bulunan;
    }

    /**
     * Yönetilebilir sayfalar (içerik blokları JSON).
     *
     * @return array<int, string>
     */
    private function sayfalarda(string $yol): array
    {
        if (! Schema::hasTable('pages')) {
            return [];
        }

        $sutunlar = Schema::getColumnListing('pages');
        $icerikSutunu = in_array('content', $sutunlar, true) ? 'content'
            : (in_array('blocks', $sutunlar, true) ? 'blocks' : null);

        if ($icerikSutunu === null) {
            return [];
        }

        $bulunan = [];

        foreach (DB::table('pages')->select('id', 'title', $icerikSutunu)->get() as $sayfa) {
            if (self::iceriyorMu($sayfa->{$icerikSutunu}, $yol)) {
                $bulunan[] = 'Sayfa: '.($sayfa->title ?: '#'.$sayfa->id);
            }
        }

        return $bulunan;
    }

    /**
     * JSON içinde yol araması — EĞİK ÇİZGİ KAÇIŞINI hesaba katar.
     *
     * Laravel bir diziyi JSON'a çevirirken `json_encode`'un varsayılan
     * bayraklarını kullanır ve `/` karakterini `\/` olarak KAÇIRIR. Yani
     * vurgu kartının `media` sütununda yol "medya\/hero_masaustu\/x.webp"
     * biçiminde durur; düz `str_contains($json, 'medya/hero_masaustu/x.webp')`
     * HİÇBİR ZAMAN eşleşmez.
     *
     * İlk yazımda tam bu oldu: tarama sessizce "kullanan bulunamadı" diyordu
     * ve kütüphanede kullanımdaki bir görsel "kullanılmıyor" görünecekti —
     * yani yanlış tarafta hata veren, silmeye davet eden bir sinyal.
     * Testim yakaladı.
     */
    private static function iceriyorMu(mixed $icerik, string $yol): bool
    {
        if (! is_string($icerik) || $icerik === '') {
            return false;
        }

        return str_contains(str_replace('\/', '/', $icerik), $yol);
    }

    /**
     * Dosya boru hattından geçmiş bir TÜREV mi? (kütüphanede rozet için)
     *
     * Türevler `medya/` altında durur ve ana kopyaları saklıdır — yani
     * silinseler bile yeniden üretilebilirler. Ham dosyalar öyle değil.
     */
    public function turevMi(string $yol): bool
    {
        return str_starts_with($yol, 'medya/')
            && MediaRendition::query()->where('yol', $yol)->exists();
    }
}

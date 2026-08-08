<?php

namespace App\Services\Medya;

use App\Models\MediaRendition;
use App\Support\Settings;
use Throwable;

/**
 * Hero'ya yüklenen görseli boru hattından geçirir ve karartmayı ÖLÇEREK yazar.
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § B adımı
 *
 * ---------------------------------------------------------------------------
 * MEVCUT SÖZLEŞME KORUNUYOR
 *
 * `Hero::arkaplanGorseli()` ayarlardan bir YOL okuyor ve ön yüz onu basıyor.
 * Bu sınıf o sözleşmeyi DEĞİŞTİRMİYOR — yalnız aynı ayara ham yükleme yerine
 * OPTİMİZE EDİLMİŞ TÜREVİN yolunu yazıyor. Böylece `home.blade.php`,
 * `Hero` sınıfı ve Vitrin görünümlerinde tek satır değişmeden 402 KB'lik ham
 * dosya yerine slot boyutunda WebP servis edilir.
 *
 * (Yeni bir alan uydurup ön yüzü de değiştirmek, aynı işi iki kat riskle
 * yapmak olurdu. Bu depoda "ekran yeni alana yazıyor ama ön yüz eskisini
 * okuyor" hatası daha önce yaşandı.)
 */
class HeroMedyaBaglayici
{
    public function __construct(
        private readonly MedyaDeposu $depo,
        private readonly HeroKontrast $kontrast,
    ) {}

    /**
     * Kaydetme sonrası çağrılır: yüklenenleri işler, mobili türetir, karartmayı ölçer.
     *
     * @return array<int, string> panelde gösterilecek bilgi satırları
     */
    public function isle(?int $yukleyenId = null): array
    {
        if (Settings::get('hero.arkaplan_tipi') !== 'gorsel') {
            return [];
        }

        $satirlar = [];
        $masaustu = $this->slotaAl('hero.gorsel_masaustu', 'hero_masaustu', $yukleyenId, $satirlar);

        if ($masaustu === null) {
            return $satirlar;
        }

        /*
         * MOBİL: ayrı dosya yüklendiyse O KAZANIR; yoksa aynı ana kopyadan
         * dikey kare türetilir. Sahibin kararı (2026-08-09): "tek yükleme +
         * akıllı kırpma, istersen ez".
         */
        $mobilAyri = $this->slotaAl('hero.gorsel_mobil', 'hero_mobil', $yukleyenId, $satirlar);

        $mobil = $mobilAyri;

        if ($mobil === null) {
            try {
                $mobil = app(MedyaTuretici::class)->turet($masaustu->asset, 'hero_mobil');
                Settings::setMany(['hero.gorsel_mobil' => $mobil->yol]);
                $satirlar[] = 'Mobil görsel aynı görselden otomatik türetildi ('.$mobil->en.'×'.$mobil->boy.').';
            } catch (Throwable $e) {
                $satirlar[] = 'Mobil türetilemedi: '.$e->getMessage();
            }
        }

        // KARARTMA ÖLÇÜLÜR, TAHMİN EDİLMEZ. Masaüstü ve mobil AYRI —
        // 2026-08-09'da tek sayı ikisine birden uygulandığı için mobil eşiğin
        // altında kalmıştı.
        $olcumler = array_filter([
            'Masaüstü' => $masaustu,
            'Mobil' => $mobil,
        ]);

        $gerekenKarartma = 0;
        $panelGerekli = false;

        foreach ($olcumler as $ad => $rendition) {
            $sonuc = $this->kontrast->olc($rendition);

            // İKİSİNİN DE GEÇMESİ LAZIM — bu yüzden en yükseği alınır.
            $gerekenKarartma = max($gerekenKarartma, $sonuc['karartma']);
            $panelGerekli = $panelGerekli || $sonuc['panel_gerekli'];

            $satirlar[] = $ad.': karartma %'.$sonuc['karartma'].', kontrast '.$sonuc['kontrast']
                .($sonuc['gecti'] ? ' ✓' : ' — BU GÖRSELLE METİN OKUNMUYOR, daha koyu bir kare seç');
        }

        Settings::setMany([
            'hero.overlay' => (string) $gerekenKarartma,
            'hero.metin_paneli' => $panelGerekli ? '1' : '0',
        ]);

        if ($panelGerekli) {
            $satirlar[] = 'Karartma üst sınırda (%'.HeroKontrast::AZAMI_KARARTMA
                .'); metnin arkasına okunabilirlik paneli açıldı.';
        }

        return $satirlar;
    }

    /**
     * Bir ayardaki HAM yüklemeyi boru hattından geçirir ve ayarı türevle günceller.
     *
     * Zaten işlenmişse (yol `medya/` ile başlıyorsa) dokunmaz — kaydet düğmesine
     * ikinci kez basmak dosyayı yeniden işlemesin diye.
     *
     * @param  array<int, string>  $satirlar
     */
    private function slotaAl(string $ayar, string $slot, ?int $yukleyenId, array &$satirlar): ?MediaRendition
    {
        $yol = (string) Settings::get($ayar);

        if ($yol === '') {
            return null;
        }

        if (str_starts_with($yol, 'medya/')) {
            // Zaten türev — mevcut kaydı bul ve olduğu gibi kullan.
            return MediaRendition::query()->where('yol', $yol)->first();
        }

        try {
            $rendition = $this->depo->alPublicYoldan($yol, $slot, $yukleyenId);
        } catch (Throwable $e) {
            $satirlar[] = 'Görsel işlenemedi: '.$e->getMessage();

            return null;
        }

        if ($rendition === null) {
            return null;
        }

        Settings::setMany([$ayar => $rendition->yol]);

        $asset = $rendition->asset;
        $satirlar[] = config("media_slots.{$slot}.etiket").': '
            .$asset->en.'×'.$asset->boy.' → '.$rendition->en.'×'.$rendition->boy
            .' ('.round($rendition->bayt / 1024).' KB)';

        if ($asset->slotIcinKucukMu($slot)) {
            $satirlar[] = 'Uyarı: yüklenen görsel '.$asset->en.'px, slot '
                .config("media_slots.{$slot}.en").'px istiyor — retina ekranda yumuşak görünecek.';
        }

        return $rendition;
    }
}

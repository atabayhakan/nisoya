<?php

namespace App\Services\Medya;

use App\Models\MediaRendition;
use Illuminate\Support\Facades\Storage;

/**
 * Hero metninin okunabilirliği için gereken karartmayı ÖLÇEREK bulur.
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § 4
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Karartma bir AYARDI: açık bir fotoğraf da koyu bir fotoğraf da aynı perdeyi
 * alıyordu. 2026-08-09'da elle %48 → %69 → %60 gidip gelindi ve mobil yine
 * eşiğin altında kaldı. Ölçüm (tarayıcıda, gerçek piksellerden):
 *
 *     %60 karartmada  masaüstü 4.72 / 5.21 / 4.17   mobil 3.71 / 3.71 / 3.84
 *     (H1 eşiği 3.0 · küçük metin eşiği 4.5)
 *
 * Yani masaüstü geçerken mobil kalıyordu — çünkü MOBİL GÖRSEL DAHA PARLAKTI ve
 * ikisi ayrı ölçülmemişti. Bu sınıf ikisini AYRI hesaplar.
 *
 * Karartma artık bir ayar değil, ölçümün sonucudur.
 */
class HeroKontrast
{
    /** WCAG AA: büyük metin (H1) 3.0, normal metin 4.5. */
    public const ESIK_BUYUK = 3.0;

    public const ESIK_NORMAL = 4.5;

    /**
     * Karartmanın üst sınırı.
     *
     * Aşılmaz: görselin tamamını neredeyse siyah bir perdenin arkasına almak,
     * o görseli seçme sebebini yok eder. Yetmezse metin bloğunun arkasına
     * gradyan açılır (sahibin kararı, 2026-08-09).
     */
    public const AZAMI_KARARTMA = 55;

    /** stone-950 (#0c0a09) doğrusal luminansı — karartma katmanının rengi. */
    private const PERDE_LUMINANS = 0.0102;

    /**
     * Bir türev için gereken karartmayı ve sonucu hesaplar.
     *
     * @return array{karartma: int, kontrast: float, panel_gerekli: bool, gecti: bool}
     */
    public function olc(MediaRendition $rendition): array
    {
        $enParlak = $this->metinBolgesininEnParlakNoktasi($rendition);

        if ($enParlak === null) {
            // Görsel okunamadı: karartmayı üst sınıra çek ve paneli aç.
            // Bilinmeyeni "sorun yok" saymak, tam da kaçınılan hata.
            return ['karartma' => self::AZAMI_KARARTMA, 'kontrast' => 0.0, 'panel_gerekli' => true, 'gecti' => false];
        }

        // EN DÜŞÜK geçerli karartmayı ara — gereğinden fazla karartmak, görseli
        // boşuna öldürmek demek.
        for ($k = 0; $k <= self::AZAMI_KARARTMA; $k++) {
            $kontrast = $this->kontrast($enParlak, $k / 100);

            if ($kontrast >= self::ESIK_NORMAL) {
                return ['karartma' => $k, 'kontrast' => round($kontrast, 2), 'panel_gerekli' => false, 'gecti' => true];
            }
        }

        // Üst sınırda da küçük metin geçmedi → metnin arkasına panel.
        $ustSinirdaki = $this->kontrast($enParlak, self::AZAMI_KARARTMA / 100);

        return [
            'karartma' => self::AZAMI_KARARTMA,
            'kontrast' => round($ustSinirdaki, 2),
            'panel_gerekli' => true,
            // Panelle birlikte okunur sayılır; ama H1 bile geçmiyorsa bu
            // görselle hiçbir şey kurtarmaz — sahibe söylenmesi gereken durum.
            'gecti' => $ustSinirdaki >= self::ESIK_BUYUK,
        ];
    }

    /**
     * Metnin oturduğu bölgenin EN PARLAK noktası (karartmasız, doğrusal luminans).
     *
     * En parlak nokta seçiliyor, ortalama değil: ortalama geçse bile tek bir
     * parlak leke metnin bir kelimesini okunmaz yapar. Muhafazakâr taraf budur.
     *
     * BÖLGE YAKLAŞIKTIR: hero metni solda ve dikeyde ortada duruyor (ölçüldü:
     * H1 kutusu 768×187, hero 1265×603). Piksel piksel kesinlik gerekmiyor —
     * amaç güvenli bir alt sınır bulmak, tasarımı taklit etmek değil.
     */
    private function metinBolgesininEnParlakNoktasi(MediaRendition $rendition): ?float
    {
        if (! Storage::disk('public')->exists($rendition->yol)) {
            return null;
        }

        /*
         * DOĞRUDAN GD — Intervention'ın piksel okuma API'si sürümler arasında
         * değişiyor (`pickColor` bu sürümde YOK, `read()` de yok; ilk yazımda
         * ikisi de denendi ve sessizce 0 kontrast üretti). Türev her zaman WebP
         * ve GD'nin WebP desteği kanıtlı — kodlamayı zaten GD yapıyor.
         */
        $gd = @imagecreatefromwebp(Storage::disk('public')->path($rendition->yol));

        if ($gd === false) {
            return null;
        }

        $en = imagesx($gd);
        $boy = imagesy($gd);

        // Sol %70, dikeyde %20–%85: metin bloğunun kapsadığı alan (ölçüldü —
        // H1 kutusu 768×187, hero 1265×603). Yaklaşıktır ve öyle olmalı: amaç
        // güvenli bir alt sınır bulmak, tasarımı taklit etmek değil.
        $x1 = (int) ($en * 0.70);
        $y0 = (int) ($boy * 0.20);
        $y1 = (int) ($boy * 0.85);

        $enParlak = 0.0;
        $adimX = max(1, (int) ($x1 / 40));
        $adimY = max(1, (int) (($y1 - $y0) / 20));

        for ($x = 0; $x < $x1; $x += $adimX) {
            for ($y = $y0; $y < $y1; $y += $adimY) {
                $rgb = imagecolorat($gd, $x, $y);

                $L = 0.2126 * $this->srgb(($rgb >> 16) & 0xFF)
                    + 0.7152 * $this->srgb(($rgb >> 8) & 0xFF)
                    + 0.0722 * $this->srgb($rgb & 0xFF);

                if ($L > $enParlak) {
                    $enParlak = $L;
                }
            }
        }

        imagedestroy($gd);

        return $enParlak;
    }

    /** Karartma uygulanmış zemine karşı BEYAZ metnin kontrast oranı. */
    private function kontrast(float $hamLuminans, float $karartma): float
    {
        $etkin = $hamLuminans * (1 - $karartma) + self::PERDE_LUMINANS * $karartma;

        return 1.05 / ($etkin + 0.05);
    }

    /** sRGB kanalını doğrusal ışığa çevirir (WCAG tanımı). */
    private function srgb(int $deger): float
    {
        $v = $deger / 255;

        return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }
}

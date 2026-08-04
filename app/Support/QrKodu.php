<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Sayfaya gömülebilen SVG QR kodu.
 *
 * ---------------------------------------------------------------------------
 * NEDEN SVG, NEDEN GÖMÜLÜ
 *
 * Imagick sunucuda YOK (bkz. PaylasimKartiUretici — orada QR matrisi elle
 * çizilmek zorunda kalındı). SvgImageBackEnd yalnız ext-xmlwriter ister ve o
 * mevcut; ayrıca çıktı vektörel olduğu için her ekran yoğunluğunda net.
 *
 * Gömülü basılır çünkü alternatifler kötü: harici bir QR servisine URL
 * göndermek 2FA GİZLİ ANAHTARINI üçüncü bir tarafa sızdırırdı (yaygın ve
 * tehlikeli bir alışkanlık), diske yazmak da geçici bir sır için kalıcı
 * dosya bırakırdı.
 */
class QrKodu
{
    /**
     * HTML içine doğrudan basılabilir SVG döndürür.
     *
     * XML bildirimi ATILIR: `<?xml ... ?>` HTML gövdesinin ortasında geçersizdir
     * ve `<?` kısa etiket yorumlamasına açık bir kapı bırakır.
     */
    public static function svg(string $veri, int $boyut = 280): string
    {
        /*
         * SESSİZ BÖLGE (quiet zone) 4 MODÜL — standardın istediği değer.
         *
         * Kütüphanenin örneklerinde sık görülen `1`, QR'ı çevreleyen beyaz
         * boşluğu spesifikasyonun dörtte birine indirir; bazı telefon
         * kameraları o hâli hiç kilitleyemez. Görsel olarak "çalışıyor gibi"
         * durduğu için de fark edilmesi zordur — kullanıcı okutamayınca
         * kendi telefonunu suçlar.
         *
         * Boyut buna göre seçildi: 127 karakterlik bir otpauth URL'i 41x41
         * modül üretir, kenarlarla 49 eder; 280px'te modül başına ~5.7px
         * düşer ve dizüstü ekranına tutulan telefon rahatça okur.
         */
        $writer = new Writer(new ImageRenderer(new RendererStyle($boyut, 4), new SvgImageBackEnd));

        $svg = $writer->writeString($veri);

        return preg_replace('/^<\?xml.*?\?>\s*/s', '', $svg) ?? $svg;
    }
}

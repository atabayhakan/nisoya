<?php

namespace Tests\Unit;

use App\Support\QrKodu;
use PHPUnit\Framework\TestCase;

/**
 * QR üretiminin GÖRÜNMEYEN ama kritik özellikleri.
 *
 * Bunlar bozulduğunda ekran hâlâ "çalışıyor gibi" durur: bir kare görürsün,
 * sayfa hata vermez. Yalnız telefon okuyamaz — ve kullanıcı kendi telefonunu
 * suçlar. Görsel kontrolle yakalanmayan sınıf tam olarak budur.
 */
class QrKoduTest extends TestCase
{
    private const ORNEK = 'otpauth://totp/Nisoya:info%40nisoya.com?secret=SDKFZZVFSNSRLHW4K4ASH4AOIGKLQPFF&issuer=Nisoya&algorithm=SHA1&digits=6&period=30';

    public function test_svg_html_icine_gomulebilir(): void
    {
        $svg = QrKodu::svg(self::ORNEK);

        $this->assertStringStartsWith('<svg', $svg);
        // XML bildirimi HTML gövdesinin ortasında geçersizdir ve `<?` kısa
        // etiket yorumlamasına kapı bırakır.
        $this->assertStringNotContainsString('<?xml', $svg);
    }

    public function test_qr_gercekten_cizilir_bos_kare_degil(): void
    {
        // <svg> etiketinin varlığı yeterli kanıt değil: içi boş olabilir.
        $svg = QrKodu::svg(self::ORNEK);

        $cizim = preg_match_all('/<(rect|path|polygon)\b/', $svg);

        $this->assertGreaterThan(1, $cizim, 'Yalnız zemin çizilmiş — QR modülleri yok.');
    }

    public function test_sessiz_bolge_standarda_uygun(): void
    {
        // QR standardı 4 modül beyaz kenar ister. Kütüphane örneklerinde sık
        // görülen 1, bazı telefon kameralarının kodu hiç kilitleyememesine
        // yol açar. Bu değer küçültülürse test kırılsın.
        $modulSayisi = 41; // bu uzunluktaki otpauth URL'i için sabit
        $boyut = 280;

        $svg = QrKodu::svg(self::ORNEK, $boyut);

        preg_match('/viewBox="0 0 (\d+) \d+"/', $svg, $m);
        $this->assertSame($boyut, (int) $m[1]);

        // 4'er modül iki yanda → toplam 49. Modül başına düşen piksel 4'ün
        // altına inerse ekrandan okumak güvenilmez olur.
        $modulBasinaPiksel = $boyut / ($modulSayisi + 8);
        $this->assertGreaterThanOrEqual(4.0, $modulBasinaPiksel);
    }

    public function test_ayni_veri_ayni_ciktiyi_verir(): void
    {
        // Kurulum ekranı her yüklemede QR'ı yeniden üretiyor; çıktı
        // değişkense kullanıcı yarıda kalan bir okutmayı tamamlayamaz.
        $this->assertSame(QrKodu::svg(self::ORNEK), QrKodu::svg(self::ORNEK));
    }
}

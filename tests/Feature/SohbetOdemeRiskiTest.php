<?php

namespace Tests\Feature;

use App\Support\OdemeRiskiIsareti;
use App\Support\SohbetUyarisi;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Sohbette PARA KAYBETTİREN kalıpların uyarısı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN EKLENDİ — CANLIDA ÖLÇÜLDÜ (2026-08-13)
 *
 * Var olan uyarı (PlatformDisiIsaret) gerçek mesajlarla sınandığında şu tablo
 * çıktı:
 *
 *   IBAN paylaşımı .................. UYARIYOR   (oysa burada NORMAL)
 *   telefon paylaşımı ............... UYARIYOR   (oysa burada NORMAL)
 *   Western Union / MoneyGram ....... sessiz
 *   hediye kartı .................... sessiz
 *   kripto .......................... sessiz
 *   PayPal "arkadaş ve aile" ........ sessiz
 *   doğrulama kodu isteme ........... sessiz
 *   "yurt dışındayım, parayı yolla" . sessiz
 *
 * Uyarı dürüst çoğunlukta çalışıyor, paranın gerçekten kaybedildiği yerde
 * susuyordu.
 *
 * ---------------------------------------------------------------------------
 * BU TEST İKİ YÖNLÜ — ASIL DEĞERİ BU
 *
 * Yalnız "tehlikeli kalıp yakalanıyor mu" sorsaydım, HER MESAJA uyarı basan
 * bir kod da testi geçerdi. Uyarı yorgunluğu bu özelliğin en büyük düşmanı:
 * her mesajda uyarı çıkarsa kimse okumaz ve gerçek uyarı da görünmez olur.
 * Bu yüzden normal satıcı davranışının SESSİZ kaldığı da ölçülüyor.
 */
class SohbetOdemeRiskiTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function tehlikeliKaliplar(): array
    {
        return [
            ['Kaporayi Western Union ile gonder, adres veririm', 'geri_alinamaz_kanal'],
            ['MoneyGram uzerinden yollarsan hemen kargolarim', 'geri_alinamaz_kanal'],
            ['Ödemeyi Google Play hediye kartı olarak gönder', 'geri_alinamaz_kanal'],
            ['PayPal ile arkadaş ve aile seçeneğiyle gönder, komisyon olmasın', 'paypal_arkadas'],
            ['Sana gelen doğrulama kodunu bana ilet', 'hesap_ele_gecirme'],
            ['Kart numaranı ve CVV kodunu yaz yeter', 'hesap_ele_gecirme'],
            ['Bitcoin ile ödeme yaparsan indirim var', 'kripto'],
            ['Yurt dışındayım, parayı yolla anahtarı kargoyla göndereyim', 'gormeden_odeme'],
        ];
    }

    #[DataProvider('tehlikeliKaliplar')]
    public function test_tehlikeli_kalip_uyari_veriyor(string $metin, string $beklenen): void
    {
        $this->assertSame($beklenen, OdemeRiskiIsareti::tespit($metin),
            "Para kaybettiren kalıp yakalanmadı: {$metin}");
    }

    /**
     * @return list<array{0: string}>
     */
    public static function normalDavranis(): array
    {
        return [
            // Nisoya ödemeye aracılık etmiyor: bunlar satıcının TEK yolu.
            ['IBAN TR33 0006 1005 1978 6457 8413 26 hesabıma yatırabilirsiniz'],
            ['Ödemeyi elden teslimde alıyorum'],
            ['Kiralamada kapora alıyoruz, standart uygulama'],
            ['Kapora bir aylık kira tutarında, sözleşmede yazıyor'],
            ['Fiyat 50 euro, pazarlık payı yok'],
            ['Havale ya da elden, ikisi de olur'],
            // Kripto kelimesi geçiyor ama ÖDEME bağlamı yok — hizmet konusu.
            ['Kripto konusunda danışmanlık veriyorum, 10 yıllık tecrübe'],
            // "Göremezsin" yok, sadece uzakta olmak — tek başına şüpheli değil.
            ['Şu an şehir dışındayım, pazartesi dönüyorum'],
        ];
    }

    #[DataProvider('normalDavranis')]
    public function test_normal_davranis_uyari_vermiyor(string $metin): void
    {
        $this->assertNull(OdemeRiskiIsareti::tespit($metin),
            "Dürüst satıcı davranışı şüpheli sayıldı: {$metin}");
    }

    public function test_odeme_riski_platform_disi_uyarisini_eziyor(): void
    {
        /*
         * Mesajda hem WhatsApp hem Western Union geçiyor. İki amber şerit
         * basılsaydı kullanıcı ikisini de okumazdı ve para kaybettiren asıl
         * kalıp ikinci sıraya düşerdi. Bir mesajda TEK uyarı.
         */
        $uyari = SohbetUyarisi::bul('WhatsApp\'tan devam edelim, parayı Western Union ile yolla');

        $this->assertNotNull($uyari);
        $this->assertSame('geri_alinamaz_kanal', $uyari['tur']);
        $this->assertStringContainsString('GERİ ALINAMAZ', $uyari['metin']);
    }

    public function test_platform_disi_uyarisi_hala_calisiyor(): void
    {
        // Eski davranış korunmalı — yeni sezgi onun yerine geçmiyor, üstüne biniyor.
        $uyari = SohbetUyarisi::bul('WhatsApp\'tan yazalım, numaram şu');

        $this->assertNotNull($uyari);
        $this->assertSame('platform_disi', $uyari['tur']);
    }

    public function test_bos_mesaj_uyari_vermiyor(): void
    {
        $this->assertNull(SohbetUyarisi::bul(''));
        $this->assertNull(SohbetUyarisi::bul(null));
    }

    public function test_her_kategorinin_metni_var(): void
    {
        // Anahtar döndürüp metni olmayan bir kural, kullanıcıya BOŞ şerit basar.
        foreach (self::tehlikeliKaliplar() as [$metin, $anahtar]) {
            $this->assertNotSame('', OdemeRiskiIsareti::metin($anahtar),
                "Kategori metinsiz: {$anahtar}");
        }
    }
}

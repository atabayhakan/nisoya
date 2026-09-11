<?php

namespace Tests\Feature;

use App\Support\HassasKonuBekcisi;
use Tests\TestCase;

/**
 * Kâhya Telegram'ın hassas-konu bekçisi.
 *
 * NE KORUYOR: vize/deport/mahkeme gibi ciddi bir soruya modelin kendi
 * tahmininden yanlış bir "hukuki tavsiye" üretmesi gerçek zarar verebilir
 * (bkz. Rusya grubunda gözlemlenen gerçek bir deport vakası). Bu sınıf
 * cevabı ENGELLEMEZ, yalnız işaretler — testler bu ayrımı doğrular.
 */
class HassasKonuBekcisiTest extends TestCase
{
    public function test_vize_ve_hukuk_kelimeleri_yakalaniyor(): void
    {
        $bekci = new HassasKonuBekcisi;

        $this->assertSame('vize_hukuk', $bekci->tespit('Vizem bitti, deport oldum, ne yapmalıyım?'));
        $this->assertSame('vize_hukuk', $bekci->tespit('Mahkemeye mi vermeliyim?'));
    }

    public function test_para_kelimeleri_yakalaniyor(): void
    {
        $bekci = new HassasKonuBekcisi;

        $this->assertSame('para', $bekci->tespit('İşveren paramı ödemedi ne yapabilirim?'));
    }

    public function test_sradan_metin_isaretlenmiyor(): void
    {
        $bekci = new HassasKonuBekcisi;

        $this->assertNull($bekci->tespit('Bugün hava çok güzel, kim market biliyor?'));
        $this->assertNull($bekci->tespit(null));
        $this->assertNull($bekci->tespit(''));
    }

    public function test_her_kategori_icin_bos_olmayan_uyari_notu_var(): void
    {
        $bekci = new HassasKonuBekcisi;

        $this->assertNotSame('', $bekci->uyariNotu('vize_hukuk'));
        $this->assertNotSame('', $bekci->uyariNotu('para'));
    }
}

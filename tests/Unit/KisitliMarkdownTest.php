<?php

namespace Tests\Unit;

use App\Support\KisitliMarkdown;
use PHPUnit\Framework\TestCase;

/**
 * Kısıtlı markdown'ın iki yüzü sınanır: desteklenen üç biçim (kalın/italik/
 * bağlantı) DOĞRU çevrilmeli, geri kalan HER ŞEY düz metin kalmalı. İkinci
 * yüz güvenliğin kendisi — sohbet balonuna giren metin bir yapay zekâ
 * modelinden gelir ve {!! !!} ile basılır; buradaki testler o kapının
 * bekçisidir.
 */
class KisitliMarkdownTest extends TestCase
{
    private function cevir(string $metin): string
    {
        return (string) KisitliMarkdown::cevir($metin);
    }

    // ------------------------------------------------------- Desteklenenler

    public function test_kalin_cevrilir(): void
    {
        $this->assertSame('<strong>Ülke Rehberi</strong> hazır.', $this->cevir('**Ülke Rehberi** hazır.'));
    }

    public function test_italik_cevrilir(): void
    {
        $this->assertSame('Bu <em>önemli</em> bir not.', $this->cevir('Bu *önemli* bir not.'));
    }

    public function test_site_ici_baglanti_ayni_sekmede(): void
    {
        $this->assertSame(
            '<a href="/yonetim/tags">Etiketler</a>',
            $this->cevir('[Etiketler](/yonetim/tags)'),
        );
    }

    public function test_dis_baglanti_yeni_sekmede(): void
    {
        $this->assertSame(
            '<a href="https://ornek.com/sayfa" target="_blank" rel="noopener noreferrer">örnek</a>',
            $this->cevir('[örnek](https://ornek.com/sayfa)'),
        );
    }

    public function test_baglanti_etiketinde_kalin_calisir(): void
    {
        $this->assertSame(
            '<a href="/yonetim/tags"><strong>Etiketler</strong></a>',
            $this->cevir('[**Etiketler**](/yonetim/tags)'),
        );
    }

    public function test_satir_sonlari_metinde_kalir(): void
    {
        // <br> üretilmez — balondaki <p> whitespace-pre-line ile basar.
        $this->assertSame("İlk satır\nikinci <strong>satır</strong>", $this->cevir("İlk satır\nikinci **satır**"));
    }

    // ------------------------------------------------- Dokunulmayacak olanlar

    public function test_matematiksel_yildiz_dokunulmaz(): void
    {
        $this->assertSame('3 * 4 = 12 ve 2 ** 8', $this->cevir('3 * 4 = 12 ve 2 ** 8'));
    }

    public function test_kapanmamis_yildizlar_dokunulmaz(): void
    {
        $this->assertSame('**yarım kalan ve *öteki', $this->cevir('**yarım kalan ve *öteki'));
    }

    // ------------------------------------------------------------- Güvenlik

    public function test_html_kacirilir(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt; ve &lt;b&gt;ham html&lt;/b&gt;',
            $this->cevir('<script>alert(1)</script> ve <b>ham html</b>'),
        );
    }

    public function test_javascript_semasi_baglanti_olmaz(): void
    {
        $sonuc = $this->cevir('[tıkla](javascript:alert(1))');

        // Bağlantı doğmaz; yazı olduğu gibi düz metin kalır.
        $this->assertStringNotContainsString('<a ', $sonuc);
        $this->assertStringContainsString('[tıkla](javascript:alert(1))', $sonuc);
    }

    public function test_protokol_goreli_adres_baglanti_olmaz(): void
    {
        // //kotu.site tarayıcıda https://kotu.site'ye çözülür — site içi sanılmamalı.
        $this->assertStringNotContainsString('<a ', $this->cevir('[tıkla](//kotu.site/x)'));
    }

    public function test_baglanti_adresiyle_nitelik_enjeksiyonu_olmaz(): void
    {
        $sonuc = $this->cevir('[x](/yonetim/a"onmouseover="alert(1))');

        // Ya hiç bağlantı olmaz ya da tırnak kaçırılmış hâlde kalır —
        // her iki durumda da yeni bir HTML niteliği doğamaz.
        $this->assertStringNotContainsString('onmouseover="', $sonuc);
    }

    public function test_yer_tutucu_taklidi_calismaz(): void
    {
        // 0x1F ayracını metne sokup bağlantı tablosundan sızdırma denemesi.
        $sonuc = $this->cevir("\x1F0\x1F [a](/yonetim/tags)");

        $this->assertStringContainsString('<a href="/yonetim/tags">a</a>', $sonuc);
        // Taklit ayraç temizlendi; başa ikinci bir bağlantı kopyası gelmedi.
        $this->assertSame(1, substr_count($sonuc, '<a '));
    }

    public function test_bos_ve_null_bos_doner(): void
    {
        $this->assertSame('', $this->cevir(''));
        $this->assertSame('', (string) KisitliMarkdown::cevir(null));
    }
}

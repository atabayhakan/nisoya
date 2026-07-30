<?php

namespace Tests\Feature;

use App\Services\Kahya\PanelHaritasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel haritası — Kâhya'nın "X nerede?" sorusuna verdiği cevabın kaynağı.
 *
 * Harita elle yazılmaz, kayıtlı Filament panelinden türetilir; bu test de
 * o türetmenin üç sözünü sınar: gruplar sidebar sırasında, ekranlar adresli,
 * ve menüde olmayan ekran haritada yok.
 */
class PanelHaritasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_harita_gruplari_ekranlari_ve_adresleri_bilir(): void
    {
        $metin = app(PanelHaritasi::class)->metin();

        // Sahibin en sık aradıkları: ekran adı + tıklanabilir adres.
        $this->assertStringContainsString('### Pazaryeri & Ticaret', $metin);
        $this->assertStringContainsString('Etiketler', $metin);
        $this->assertStringContainsString('/yonetim/tags', $metin);

        // Yeni gruplar da haritada — Kâhya kendi evini tarif edebilmeli.
        $this->assertStringContainsString('### Kâhya & Yapay Zekâ', $metin);
        $this->assertStringContainsString('### Pazarlama & Büyüme', $metin);
        $this->assertStringContainsString('SEO', $metin);
    }

    public function test_grup_sirasi_sidebar_ile_ayni(): void
    {
        $metin = app(PanelHaritasi::class)->metin();

        // Sidebar "Pazaryeri"yi en üstte, "Sistem & Araçlar"ı en altta gösterir;
        // Kâhya'nın tarifi başka bir düzen anlatırsa sahip yanlış yere bakar.
        $this->assertLessThan(
            strpos($metin, '### Sistem & Araçlar'),
            strpos($metin, '### Pazaryeri & Ticaret'),
        );
    }
}

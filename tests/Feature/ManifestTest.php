<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PWA manifest'i + guest theme-color marka uyumu (açık işler envanteri:
 * canlıda ölçülen uyuşmazlık — site mavi, PWA çubuğu yeşil).
 *
 * Sözleşme: manifest artık statik dosya değil; theme_color, tarayıcı
 * sekmesindeki theme-color ve favicon ile AYNI kaynaktan (brandColorHex)
 * gelir — marka rengi panelden değişince ya da Vitrin aktifken hepsi
 * birlikte döner.
 */
class ManifestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    public function test_manifest_varsayilan_marka_rengini_tasir(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'short_name' => 'Nisoya',
                'theme_color' => '#059669',
                'display' => 'standalone',
            ])
            ->assertJsonCount(3, 'icons');
    }

    public function test_manifest_panelden_secilen_rengi_izler(): void
    {
        Settings::setMany(['gorunum.marka_rengi' => 'blue']);

        $this->get('/manifest.webmanifest')->assertOk()->assertJson(['theme_color' => '#2563eb']);
    }

    public function test_manifest_vitrin_temasinda_vitrin_rengini_izler(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->get('/manifest.webmanifest')->assertOk()->assertJson(['theme_color' => '#3E63F0']);
    }

    public function test_guest_sayfalari_da_marka_rengini_izler(): void
    {
        // Giriş sayfası guest iskeletini kullanır; theme-color/favicon sabit
        // zümrüt gömülüydü — marka değişince eski renkte kalıyordu.
        Settings::setMany(['gorunum.marka_rengi' => 'blue']);

        $this->get('/giris')
            ->assertOk()
            ->assertSee('content="#2563eb"', false)
            ->assertDontSee('content="#059669"', false);
    }

    public function test_vitrin_guest_sayfasi_vitrin_rengini_izler(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->get('/giris')
            ->assertOk()
            ->assertSee('content="#3E63F0"', false);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TasarimAyarlari;
use App\Models\User;
use App\Support\Settings;
use App\Support\TemaJetonlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hareketli logo yazısı (2026-08-19, sahibin isteği): başlıktaki "Nisoya"
 * el yazısıyla çizilerek açılır, admin panelden renk/font seçilir ve
 * kapatılabilir.
 *
 * ---------------------------------------------------------------------------
 * ÇİZİM YOLU NEDEN SABİT
 *
 * SVG path'i (TemaJetonlari::EL_YAZISI_FONTLAR) opentype.js ile DERLEME
 * ZAMANINDA üretildi, tarayıcıda değil — reddedilen React örneğinin aksine
 * (framer-motion + opentype.js + her ziyarette font indirme). Bu testler
 * tarayıcıda hiçbir şey render etmiyor, yalnız Blade çıktısını ölçüyor;
 * "harfler gerçekten çiziliyor mu" sorusunun cevabı görsel doğrulamada
 * (bkz. konuşma özeti — üç fonttan ikisi PNG'ye render edilip gözle elendi).
 */
class HareketliLogoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_kapaliyken_duz_metin_gosterilir(): void
    {
        Settings::setMany(['gorunum.logo_animasyon' => '0']);

        $icerik = (string) $this->get('/')->getContent();

        $this->assertStringNotContainsString('hareketli-logo-cizgi', $icerik);
        $this->assertStringContainsString('>Nisoya<', $icerik);
    }

    public function test_aciksa_svg_dogru_renk_ve_yolla_gosterilir(): void
    {
        Settings::setMany([
            'gorunum.logo_animasyon' => '1',
            'gorunum.logo_rengi' => '#ff0000',
            'gorunum.logo_yazi_tipi' => 'dancing-script',
        ]);

        $icerik = (string) $this->get('/')->getContent();

        $this->assertStringContainsString('hareketli-logo-cizgi', $icerik);
        $this->assertStringContainsString('stroke="#ff0000"', $icerik);
        $this->assertStringContainsString(
            TemaJetonlari::EL_YAZISI_FONTLAR['dancing-script']['yol'],
            $icerik,
            'Dancing Script seçiliyken Indie Flower yolu (ya da başka bir font) basılıyor.'
        );
    }

    /**
     * GÜVENLİK AĞI: çizim yolu yalnız "Nisoya" kelimesi için üretildi. Site
     * adı değişirse (panelden mümkün) animasyon açık kalsa bile düz metne
     * düşülmeli — aksi hâlde ekranda yanlış kelime çizilmiş gibi görünür.
     */
    public function test_site_adi_degisirse_animasyon_acik_olsa_da_duz_metne_duser(): void
    {
        Settings::setMany([
            'gorunum.logo_animasyon' => '1',
            'genel.site_adi' => 'Başka Bir İsim',
        ]);

        $icerik = (string) $this->get('/')->getContent();

        $this->assertStringNotContainsString('hareketli-logo-cizgi', $icerik);
    }

    public function test_gecersiz_font_anahtari_varsayilana_duser(): void
    {
        $font = TemaJetonlari::elYazisiFontu('uydurma-font');

        $this->assertSame(TemaJetonlari::EL_YAZISI_FONTLAR['indie-flower'], $font);
    }

    /** Diğer görünüm ayarlarının aksine bu üçü Vitrin temasında da çalışmalı. */
    public function test_vitrin_temasinda_da_calisir(): void
    {
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'gorunum.logo_animasyon' => '1',
        ]);

        $icerik = (string) $this->get('/')->getContent();

        $this->assertStringContainsString('hareketli-logo-cizgi', $icerik);
    }

    public function test_panelden_kaydetme_ayarlari_kalici_yazar(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->set('logoAnimasyon', true)
            ->set('logoRenk', '#123456')
            ->set('logoFont', 'dancing-script')
            ->call('kaydetCustom');

        $this->assertSame('1', Settings::get('gorunum.logo_animasyon'));
        $this->assertSame('#123456', Settings::get('gorunum.logo_rengi'));
        $this->assertSame('dancing-script', Settings::get('gorunum.logo_yazi_tipi'));
    }

    /** Yeni bölüm gerçekten render oluyor mu — klasik-only fieldset'in dışında. */
    public function test_panel_bolumu_gorunur_ve_her_iki_font_secenegini_listeler(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->assertSee('Hareketli logo yazısı')
            ->assertSee('Her iki temada geçerli')
            ->assertSee('Indie Flower')
            ->assertSee('Dancing Script')
            ->assertSeeHtml('wire:model.live="logoAnimasyon"')
            ->assertSeeHtml('wire:model.live="logoRenk"')
            ->assertSeeHtml('wire:model.live="logoFont"');
    }

    public function test_sifirlama_hareketli_logoyu_da_fabrikaya_dondurur(): void
    {
        Settings::setMany([
            'gorunum.logo_animasyon' => '1',
            'gorunum.logo_rengi' => '#123456',
        ]);

        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('sifirla');

        $this->assertSame('0', Settings::get('gorunum.logo_animasyon'));
        $this->assertSame('#059669', Settings::get('gorunum.logo_rengi'));
    }
}

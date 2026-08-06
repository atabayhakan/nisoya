<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TasarimAyarlari;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasarimModuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_default_mode_is_eski(): void
    {
        $this->assertSame('eski', setting('gorunum.tasarim_modu', 'eski'));
    }

    public function test_admin_can_open_tasarim_page(): void
    {
        $this->actingAs($this->admin())->get('/yonetim/tasarim-ayarlari')->assertOk();
    }

    public function test_member_cannot_open_tasarim_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)->get('/yonetim/tasarim-ayarlari')->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_switch_to_yeni_mode(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secPreset', 'yeni')
            ->assertSet('aktifMod', 'yeni');

        $this->assertSame('yeni', setting('gorunum.tasarim_modu'));
        $this->assertDatabaseHas('site_settings', ['key' => 'gorunum.tasarim_modu', 'value' => 'yeni']);
    }

    public function test_admin_can_switch_back_to_eski_mode(): void
    {
        Settings::setMany(['gorunum.tasarim_modu' => 'yeni']);

        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secPreset', 'eski')
            ->assertSet('aktifMod', 'eski');

        $this->assertSame('eski', setting('gorunum.tasarim_modu'));
    }

    public function test_secpreset_ignores_invalid_value(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secPreset', 'gecersiz')
            ->assertSet('aktifMod', 'eski');

        $this->assertSame('eski', setting('gorunum.tasarim_modu', 'eski'));
    }

    public function test_homepage_applies_yeni_tasarim_overrides(): void
    {
        // "yeni" preset'i (secPreset) tüm anahtarları yazar; renk artık moddan
        // değil primary_color'dan türetilir, o yüzden preset'i eksiksiz kur.
        Settings::setMany([
            'gorunum.tasarim_modu' => 'yeni',
            'gorunum.primary_color' => '#0f5c42',
            'gorunum.font_family' => 'serif',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('#0f5c42', false)
            ->assertSee('font-serif', false);
    }

    public function test_homepage_has_no_overrides_in_eski_mode(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('#0f5c42', false);
    }

    public function test_homepage_shows_pulse_map_only_in_yeni_mode(): void
    {
        // assertSee 2. parametresiz varsayılan olarak metni HTML-escape eder
        // (apostrof → &#039;) — bileşendeki ham apostrofla eşleşmesi için
        // escape=false gerekiyor.
        $this->get('/')->assertOk()->assertDontSee("Nisoya'nın Nabzı", false);

        Settings::setMany(['gorunum.tasarim_modu' => 'yeni']);

        $this->get('/')->assertOk()->assertSee("Nisoya'nın Nabzı", false);
    }

    public function test_custom_primary_color_drives_full_emerald_ramp(): void
    {
        Settings::setMany(['gorunum.primary_color' => '#7c3aed']);

        $html = $this->get('/')->assertOk()->getContent();

        // emerald-600 birincil renge, ara tonlar color-mix ile türetilmeli
        $this->assertStringContainsString('--color-emerald-600: #7c3aed', $html);
        $this->assertStringContainsString('color-mix(in srgb, #7c3aed 85%, white)', $html);
        $this->assertStringContainsString('color-mix(in srgb, #7c3aed 80%, black)', $html);
    }

    public function test_default_primary_color_leaves_tailwind_emerald_untouched(): void
    {
        // Varsayılan (#059669) → Tailwind'in kendi OKLCH tonları korunmalı,
        // özel rampa YAZILMAMALI (dokunulmamış siteler için no-op).
        $this->get('/')->assertOk()->assertDontSee('--color-emerald-600: #', false);
    }

    public function test_serif_font_overrides_font_sans_token(): void
    {
        Settings::setMany(['gorunum.font_family' => 'serif']);

        $this->get('/')->assertOk()->assertSee("--font-sans: 'Instrument Serif'", false);
    }

    public function test_pill_radius_overrides_radius_scale(): void
    {
        Settings::setMany(['gorunum.border_radius' => 'pill']);

        $this->get('/')->assertOk()->assertSee('--radius-xl: 18px', false);
    }

    public function test_glassmorphism_off_disables_backdrop_blur(): void
    {
        Settings::setMany(['gorunum.glassmorphism' => '0']);

        $this->get('/')->assertOk()->assertSee('backdrop-filter: none', false);
    }

    public function test_smooth_animations_off_emits_reduced_motion(): void
    {
        /*
         * SÖZLEŞME GENİŞLETİLDİ (2026-08-06). Eski hâli "açıkken böyle bir
         * kural yok" diyordu; artık ziyaretçinin kendi tercihine bağlı bir
         * kural HER ZAMAN basılıyor (bkz. aşağıdaki test), o yüzden ayrım
         * dizeyle değil SAYIYLA yapılır — iki blok aynı seçiciyi kullanıyor.
         *
         *   açık   → 1 kez (yalnız prefers-reduced-motion içinde)
         *   kapalı → 2 kez (koşulsuz blok + medya sorgusu)
         */
        $acik = (string) $this->get('/')->assertOk()->getContent();
        $this->assertSame(1, substr_count($acik, 'transition-duration: 0.01ms'));

        Settings::setMany(['gorunum.smooth_animations' => '0']);

        $kapali = (string) $this->get('/')->assertOk()->getContent();
        $this->assertSame(2, substr_count($kapali, 'transition-duration: 0.01ms'));
    }

    public function test_ziyaretcinin_hareket_tercihi_ayardan_bagimsiz_uygulanir(): void
    {
        /*
         * ERİŞİLEBİLİRLİK AÇIĞIYDI: `prefers-reduced-motion` sıfırlaması yalnız
         * sahip "Yumuşak geçişler"i KAPATTIĞINDA basılıyordu. Varsayılan AÇIK
         * olduğu için canlıda hiç çalışmıyordu — yani işletim sisteminde
         * "hareketi azalt" açık olan ziyaretçi (vestibüler rahatsızlığı olan
         * biri) sitede yine de tüm geçişleri alıyordu.
         *
         * Sahibin düğmesi "herkese kapat", medya sorgusu "isteyene kapat".
         * İkisi ayrı sorulardır; biri diğerinin yerini tutmaz.
         */
        $this->get('/')->assertOk()->assertSee('@media (prefers-reduced-motion: reduce)', false);
    }

    public function test_obsidian_mode_locks_dark_and_hides_theme_toggle(): void
    {
        // Varsayılan modda: koyu-mod kilidi yok, tema değiştir butonu var.
        $this->get('/')->assertOk()
            ->assertSee('const FORCE_DARK = false', false)
            ->assertSee('Karanlık/aydınlık tema değiştir', false);

        // Obsidian: koyu moda kilitlenir (isim/önizleme ile tutarlı) ve artık
        // no-op olacak tema-değiştir butonu gizlenir.
        Settings::setMany(['gorunum.tasarim_modu' => 'obsidian']);

        $this->get('/')->assertOk()
            ->assertSee('const FORCE_DARK = true', false)
            ->assertDontSee('Karanlık/aydınlık tema değiştir', false);
    }

    public function test_obsidian_no_longer_hijacks_stone_50(): void
    {
        // Denetim #4: obsidian artık stone-50'yi near-black yapmamalı.
        Settings::setMany(['gorunum.tasarim_modu' => 'obsidian']);

        $this->get('/')->assertOk()->assertDontSee('#090d16', false);
    }

    public function test_invalid_primary_color_is_not_emitted(): void
    {
        // Denetim #10: geçersiz/zararlı hex değeri <style>'a sızmamalı.
        Settings::setMany(['gorunum.primary_color' => 'red;}body{display:none}']);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('display:none}', $html);
        $this->assertStringNotContainsString('red;}', $html);
    }
}

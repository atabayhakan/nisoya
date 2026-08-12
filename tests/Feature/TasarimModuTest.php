<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TasarimAyarlari;
use App\Models\User;
use App\Support\Settings;
use App\Support\TemaJetonlari;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
         * SÖZLEŞME DARALTILDI (2026-08-12). 2026-08-06'da ziyaretçi tercihi
         * de bu Blade dosyasına yazılmıştı, bu yüzden sayaç 1/2 idi. O blok
         * `resources/css/app.css`'e taşındı (gerekçe: temaya bağlıydı ve
         * vitrinde hiç basılmıyordu) — HTML'de artık YALNIZ sahibin düğmesi
         * kalıyor.
         *
         *   açık   → 0 kez (sahip kapatmadı, HTML'de kural yok)
         *   kapalı → 1 kez (koşulsuz blok)
         *
         * Ziyaretçi tercihinin bekçisi ayrı testte:
         * test_hareket_azaltma_temadan_bagimsiz
         */
        $acik = (string) $this->get('/')->assertOk()->getContent();
        $this->assertSame(0, substr_count($acik, 'transition-duration: 0.01ms'));

        Settings::setMany(['gorunum.smooth_animations' => '0']);

        $kapali = (string) $this->get('/')->assertOk()->getContent();
        $this->assertSame(1, substr_count($kapali, 'transition-duration: 0.01ms'));
    }

    public function test_hareket_azaltma_temadan_bagimsiz(): void
    {
        /*
         * İKİ KEZ AÇILAN AYNI AÇIK — ikincisini bu test kapatıyor.
         *
         * 1. tur (2026-08-06): `prefers-reduced-motion` sıfırlaması yalnız
         *    sahip "Yumuşak geçişler"i KAPATTIĞINDA basılıyordu; varsayılan
         *    açık olduğu için canlıda hiç çalışmıyordu.
         * 2. tur (2026-08-12): düzeltme `tasarim-theme.blade.php`'ye konmuştu,
         *    ama o dosyanın TAMAMI unless(vitrinMi()) ile sarılı ve canlı tema
         *    vitrin. Canlıdan ölçüldü: ne HTML'de ne de 210 KB'lık derlenmiş
         *    CSS'te `0.01ms` geçiyordu.
         *
         * ESKİ BEKÇİ BUNU GÖREMEZDİ: `assertSee('@media (prefers-reduced-motion:
         * reduce)')` diyordu, ama o dize `vitrin-theme.blade.php` içinde de
         * geçiyor — asıl sıfırlama hiç basılmasa bile yeşil verirdi.
         *
         * Yeni bekçi dizeye değil YAPIYA bakıyor: kural, her iki iskeletin de
         * yüklediği dosyada mı? Bu soruya "evet" ise temaya bağlanması
         * imkânsız.
         */
        $css = File::get(resource_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{\s*\*,\s*\*::before,\s*\*::after\s*\{[^}]*transition-duration:\s*0\.01ms\s*!important/s',
            $css,
            'Küresel hareket-azaltma sıfırlaması app.css\'te yok. '.
            'Tema dosyasına taşınmış olabilir — orada temaya bağlanır ve vitrinde ölür.'
        );

        // Kuralın ERİŞİMİ: her iki iskelet de bu dosyayı yüklüyor mu?
        foreach (TemaJetonlari::ISKELETLER as $tema => $yol) {
            $this->assertStringContainsString(
                'resources/css/app.css',
                File::get(base_path($yol)),
                "'{$tema}' iskeleti app.css yüklemiyor — hareket-azaltma kuralı o temada geçersiz kalır."
            );
        }
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

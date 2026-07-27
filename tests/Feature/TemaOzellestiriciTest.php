<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Settings;
use App\Support\TemaJetonlari;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Canlı tema özelleştirici (2026-07-28).
 *
 * Önizleme istemcide olduğu için buradaki testler iki şeyi mühürler:
 * (1) widget'ın KİME ve NEREDE göründüğü, (2) kaydetme ucunun yetki,
 * doğrulama ve çakışma davranışı.
 */
class TemaOzellestiriciTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    // ------------------------------------------------ Görünürlük

    public function test_admin_halka_acik_sayfada_ozellestiriciyi_gorur(): void
    {
        $this->actingAs($this->admin())->get('/')->assertOk()->assertSee('temaOzellestirici', false);
    }

    public function test_normal_uye_ve_misafir_gormez(): void
    {
        $uye = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($uye)->get('/')->assertOk()->assertDontSee('temaOzellestirici', false);
        $this->get('/')->assertOk()->assertDontSee('temaOzellestirici', false);
    }

    public function test_moderator_gormez(): void
    {
        // Görünüm sahibin işidir; moderatör içerik moderasyonu için var.
        $moderator = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($moderator)->get('/')->assertOk()->assertDontSee('temaOzellestirici', false);
    }

    public function test_panel_sayfalarinda_hic_basilmaz(): void
    {
        // Bağış FAB'ı tam olarak burada mobilde form/aksiyon butonlarının
        // üstüne bindiği için kaldırılmıştı; aynı hataya düşmüyoruz.
        $this->actingAs($this->admin())->get('/panel/profil')->assertOk()->assertDontSee('temaOzellestirici', false);
    }

    public function test_ozellestirici_her_iki_temanin_iskeletinde_de_basilir(): void
    {
        // Bir düzene eklemeyi unutmak, o temada özelliğin sessizce yok olması
        // demektir (vitrin/README.md'nin uyardığı sürüklenme tuzağı).
        foreach (TemaJetonlari::ISKELETLER as $tema => $iskelet) {
            $this->assertStringContainsString(
                '<x-tema-ozellestirici',
                File::get(base_path($iskelet)),
                "'{$tema}' iskeleti özelleştiriciyi basmıyor."
            );
        }
    }

    // ------------------------------------------------ Yetki

    public function test_kaydetme_yalniz_admine_acik(): void
    {
        $uye = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($uye)->postJson(route('panel.gorunum.kaydet'), ['vitrin_aksan' => 'mor'])->assertForbidden();
        $this->actingAs($uye)->postJson(route('panel.gorunum.sifirla'))->assertForbidden();
    }

    public function test_misafir_kaydedemez(): void
    {
        // Uygulama sözleşmesi: panel rotaları auth middleware'i altında,
        // giriş yapmamış kullanıcı JSON 401 değil giriş sayfasına yönlendirilir.
        $this->postJson(route('panel.gorunum.kaydet'), ['vitrin_aksan' => 'mor'])
            ->assertRedirect(route('login'));

        $this->assertNull(Settings::get('gorunum.vitrin_aksan'));
    }

    // ------------------------------------------------ Kaydetme

    public function test_vitrin_aksani_kaydedilir(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('panel.gorunum.kaydet'), ['vitrin_aksan' => 'kiremit'])
            ->assertOk();

        $this->assertSame('kiremit', Settings::get('gorunum.vitrin_aksan'));
    }

    public function test_gecersiz_aksan_reddedilir(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('panel.gorunum.kaydet'), ['vitrin_aksan' => 'uydurma'])
            ->assertStatus(422);
    }

    public function test_renk_tek_kontroldur_aile_secilince_ozel_renk_sifirlanir(): void
    {
        // Klasik iskelet hem brand-theme hem tasarim-theme basıyor ve ikincisi
        // birinciyi eziyor. İkisi bağımsız bırakılsa biri her zaman ölü olurdu.
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('panel.gorunum.kaydet'), ['primary_color' => '#123456'])->assertOk();
        $this->assertSame('#123456', Settings::get('gorunum.primary_color'));
        $this->assertSame('emerald', Settings::get('gorunum.marka_rengi'));

        $this->actingAs($admin)->postJson(route('panel.gorunum.kaydet'), ['marka_rengi' => 'violet'])->assertOk();
        $this->assertSame('violet', Settings::get('gorunum.marka_rengi'));
        $this->assertSame('#059669', Settings::get('gorunum.primary_color'), 'Aile seçilince özel renk nötrlenmeli, yoksa tasarim-theme onu ezer');
    }

    public function test_css_enjeksiyonu_denemesi_reddedilir(): void
    {
        // Renk değeri doğrudan <style> bloğuna basılıyor.
        foreach (['red;} body{display:none', '#fff;}*{color:red', 'javascript:alert(1)', 'var(--x)'] as $kotu) {
            $this->actingAs($this->admin())
                ->postJson(route('panel.gorunum.kaydet'), ['primary_color' => $kotu])
                ->assertStatus(422);
        }

        $this->assertNotSame('red;} body{display:none', Settings::get('gorunum.primary_color'));
    }

    public function test_olu_font_secenekleri_kabul_edilmez(): void
    {
        // vite.config.js yalnız Instrument Sans/Serif ve Plus Jakarta'yı
        // self-host ediyor; inter/outfit sessizce sistem sans'ına düşüyordu.
        $this->actingAs($this->admin())
            ->postJson(route('panel.gorunum.kaydet'), ['font_family' => 'inter'])
            ->assertStatus(422);

        $this->actingAs($this->admin())
            ->postJson(route('panel.gorunum.kaydet'), ['font_family' => 'serif'])
            ->assertOk();
    }

    public function test_sifirlama_varsayilanlara_doner(): void
    {
        Settings::setMany([
            'gorunum.vitrin_aksan' => 'mor',
            'gorunum.primary_color' => '#123456',
            'gorunum.font_family' => 'serif',
        ]);

        $this->actingAs($this->admin())->postJson(route('panel.gorunum.sifirla'))->assertOk();

        $this->assertSame('deniz', Settings::get('gorunum.vitrin_aksan'));
        $this->assertSame('#059669', Settings::get('gorunum.primary_color'));
        $this->assertSame('sans', Settings::get('gorunum.font_family'));
    }

    public function test_kaydedilen_aksan_vitrin_ciktisinda_gorunur(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin', 'gorunum.vitrin_aksan' => 'kiremit']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--color-emerald-600: #c9491f', false)
            ->assertDontSee('--color-emerald-600: #3E63F0', false);
    }
}

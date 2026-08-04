<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Yönetim paneli giriş kapısı ve 2FA zorunluluğu (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * BU DOSYANIN VAROLUŞ SEBEBİ — kanıtlanmış bir baypas
 *
 * 2FA sitede vardı, çalışıyordu ve iyi test edilmişti (TwoFactorTest,
 * TwoFactorChallengeTest). Ama panelin KENDİ giriş ekranı vardı ve o ekran
 * Filament'in varsayılanıydı: yalnız e-posta+parola doğruluyordu.
 *
 * Sonuç: 2FA'sı AÇIK bir yönetici /yonetim/login'den kod girmeden içeri
 * giriyordu. Aynı kullanıcı /giris'ten girmeye çalışsa misafir kalıyordu.
 * Yani ikinci faktör, saldırganın hangi kapıyı seçtiğine bağlıydı.
 *
 * Kök neden İKİ AYRI kimlik doğrulama yolu olmasıydı. İkincisine 2FA eklemek
 * yerine ikinci yol kaldırıldı — çünkü çoğaltılmış auth mantığı bu hatanın
 * doğduğu yerdir ve tekrar doğurur.
 */
class YonetimGirisiTest extends TestCase
{
    use RefreshDatabase;

    private function ikiFaktorluAdmin(string $secret = 'GIZLIANAHTARTEST'): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'password' => bcrypt('parola-1234'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    // ------------------------------------------------------- Tek kapı

    public function test_panelin_kendi_giris_ekrani_artik_yok(): void
    {
        // Baypasın kök nedeni buydu. Rota geri gelirse bu test kırılır.
        $this->assertNull(
            Route::getRoutes()->getByName('filament.admin.auth.login'),
            'Panel giriş rotası geri gelmiş — 2FA baypası yeniden açılmış olabilir.'
        );

        $this->get('/yonetim/login')->assertNotFound();
    }

    public function test_misafir_panelden_site_girisine_yonlendirilir(): void
    {
        $this->get('/yonetim')->assertRedirect(route('login'));
    }

    public function test_iki_faktorlu_admin_panel_yolunda_kod_girmeden_iceri_giremez(): void
    {
        $admin = $this->ikiFaktorluAdmin();

        // Panele girmeye çalış → giriş ekranına düşer (hedef URL saklanır).
        $this->get('/yonetim')->assertRedirect(route('login'));

        // Parola doğru ama 2FA açık → oturum AÇILMAZ, challenge'a gider.
        $this->post('/giris', ['email' => $admin->email, 'password' => 'parola-1234'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();

        // Ve challenge geçilmeden panele erişilemez.
        $this->get('/yonetim')->assertRedirect(route('login'));
    }

    public function test_kod_girildikten_sonra_hedeflenen_panele_geri_doner(): void
    {
        // Tek kapıya indirmenin işe yaraması, hedef URL'nin korunmasına bağlı.
        // Korunmazsa kullanıcı her seferinde /panel'e düşer ve elle /yonetim
        // yazmak zorunda kalır — sessizce bozulan tam olarak budur.
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();
        $admin = $this->ikiFaktorluAdmin($secret);

        $this->get('/yonetim')->assertRedirect(route('login'));
        $this->post('/giris', ['email' => $admin->email, 'password' => 'parola-1234']);

        $this->post('/iki-faktor-dogrula', ['code' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect(url('/yonetim'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_panel_cikisi_site_giris_ekranina_goturur(): void
    {
        $this->actingAs($this->ikiFaktorluAdmin())
            ->post('/yonetim/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // --------------------------------------------- 2FA zorunluluğu

    public function test_iki_faktorsuz_admin_kurulum_sayfasina_yonlendirilir(): void
    {
        $admin = User::factory()->ikiFaktorsuz()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/yonetim')
            ->assertRedirect(route('panel.profile.2fa'));
    }

    public function test_yonlendirme_403_degil_ki_kilitlenme_olmasin(): void
    {
        // 403 dönseydi: 2FA'yı açacağı sayfaya ulaşmak için panele girmesi,
        // panele girmek için 2FA'sının olması gerekirdi. Kapalı döngü.
        $admin = User::factory()->ikiFaktorsuz()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/yonetim')->assertStatus(302);
        $this->actingAs($admin)->get(route('panel.profile.2fa'))->assertOk();
    }

    public function test_iki_faktorlu_admin_panele_girebilir(): void
    {
        $this->actingAs($this->ikiFaktorluAdmin())
            ->get('/yonetim')
            ->assertSuccessful();
    }

    public function test_moderator_iki_faktorsuz_girebilir(): void
    {
        // Sahibin KARARI (2026-08-05): zorunluluk yalnız admin'i kapsar.
        // Kapsam genişletilirse bu test bilinçle güncellenmeli — sessizce
        // kırılmasın diye burada duruyor.
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        $this->actingAs($moderator)->get('/yonetim')->assertSuccessful();
    }

    public function test_zorunluluk_sitenin_geri_kalanini_kilitlemez(): void
    {
        // Middleware yalnız panel yığınında. 2FA'sız bir admin siteyi ve kendi
        // üye panelini normal kullanabilmeli.
        $admin = User::factory()->ikiFaktorsuz()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/')->assertOk();
        $this->actingAs($admin)->get('/panel')->assertSuccessful();
    }
}

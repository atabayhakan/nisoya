<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Google ile giriş / kayıt.
 *
 * ---------------------------------------------------------------------------
 * EN ÖNEMLİ İDDİA: 2FA ATLANAMAZ
 *
 * Bu depoda bir kez oldu: Filament panelinin kendi giriş kapısı mevcut 2FA'yı
 * baypas ediyordu (PR #100). Yeni bir giriş yolu eklemek aynı hatayı tekrar
 * yapmanın en kolay yoludur, çünkü baypas SESSİZDİR — giriş çalışır, yalnız
 * ikinci faktör sorulmaz ve kimse fark etmez.
 *
 * Aşağıdaki `test_iki_faktor_acik_hesap_google_ile_baypas_edilemez` bu turun
 * asıl bekçisi; diğerleri onu destekler.
 */
class GoogleIleGirisTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** Google'ı açar; anahtarlar dolu olmadan özellik kapalı sayılır. */
    private function googleyiAc(): void
    {
        Settings::setMany([
            'giris.google_aktif' => '1',
            'giris.google_client_id' => 'test-client-id',
            'giris.google_client_secret' => 'test-client-secret',
        ]);
        Cache::flush();
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);
    }

    /** Socialite'ı sahte bir Google kullanıcısıyla değiştirir. */
    private function googleKullanicisi(string $eposta, string $ad = 'Ali Veli', bool $dogrulanmis = true): void
    {
        $socialiteUser = Mockery::mock(AbstractUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($eposta);
        $socialiteUser->shouldReceive('getName')->andReturn($ad);
        $socialiteUser->shouldReceive('getRaw')->andReturn(['email_verified' => $dogrulanmis]);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }

    private function ulkeVeParaBirimi(): void
    {
        // Factory yok (ikisi de referans tablosu, seeder'dan gelir) —
        // testin ihtiyacı olan iki satır doğrudan yazılır.
        Country::query()->firstOrCreate(
            ['code' => 'DE'],
            ['name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'default_currency' => 'EUR', 'is_active' => true]
        );
        Currency::query()->firstOrCreate(
            ['code' => 'EUR'],
            ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]
        );
    }

    // -----------------------------------------------------------------
    // ASIL BEKÇİ
    // -----------------------------------------------------------------

    public function test_iki_faktor_acik_hesap_google_ile_baypas_edilemez(): void
    {
        $this->googleyiAc();

        $user = User::factory()->create([
            'email' => 'iki@faktor.test',
            'two_factor_secret' => encrypt('SECRET'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->googleKullanicisi('iki@faktor.test');

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('two-factor.login'));

        // KRİTİK: challenge'a yönlendirmek yetmez — kullanıcı GİRİŞ YAPMIŞ
        // OLMAMALI. Bu satır olmadan test, yönlendirdiği hâlde oturumu da
        // açan bir kodu geçirirdi.
        $this->assertGuest();
        $this->assertSame($user->id, session('login.2fa.user_id'));
    }

    public function test_iki_faktor_kapali_hesap_dogrudan_girer(): void
    {
        // Yukarıdaki testin ters yönü: 2FA kapısı HER girişe takılsaydı
        // o test yine geçerdi ama özellik bozuk olurdu.
        $this->googleyiAc();

        $user = User::factory()->create(['email' => 'duz@giris.test']);
        $this->googleKullanicisi('duz@giris.test');

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    // -----------------------------------------------------------------
    // E-POSTA DOĞRULAMASI
    // -----------------------------------------------------------------

    public function test_dogrulanmamis_eposta_reddedilir(): void
    {
        // Saldırgan kendi Google hesabına kurbanın adresini yazıp mevcut
        // Nisoya hesabına giremesin.
        $this->googleyiAc();

        User::factory()->create(['email' => 'kurban@ornek.test']);
        $this->googleKullanicisi('kurban@ornek.test', dogrulanmis: false);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // -----------------------------------------------------------------
    // YENİ KAYIT İKİ ADIMLI
    // -----------------------------------------------------------------

    public function test_yeni_kisi_icin_kullanici_henuz_yaratilmaz(): void
    {
        $this->googleyiAc();
        $this->googleKullanicisi('yeni@kisi.test');

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('register.google.complete'));

        // Ülkesi olmayan yarım hesap yaratılmamalı.
        $this->assertDatabaseMissing('users', ['email' => 'yeni@kisi.test']);
        $this->assertGuest();
    }

    public function test_tamamlama_formu_hesabi_olusturur_ve_girer(): void
    {
        $this->googleyiAc();
        $this->ulkeVeParaBirimi();
        $this->googleKullanicisi('yeni@kisi.test', 'Ayşe Yılmaz');

        $this->get(route('login.google.callback'));

        $this->post(route('register.google.store'), [
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'yeni@kisi.test',
            'name' => 'Ayşe Yılmaz',
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
        ]);

        // Google adresi doğruladı; ikinci bir doğrulama beklenmemeli.
        $this->assertNotNull(User::where('email', 'yeni@kisi.test')->value('email_verified_at'));
    }

    public function test_tamamlama_kosul_onayi_olmadan_reddedilir(): void
    {
        $this->googleyiAc();
        $this->ulkeVeParaBirimi();
        $this->googleKullanicisi('yeni@kisi.test');
        $this->get(route('login.google.callback'));

        $this->post(route('register.google.store'), [
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            // terms YOK
        ])->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'yeni@kisi.test']);
    }

    public function test_tamamlama_ekrani_gercekten_render_edilir(): void
    {
        /*
         * Diğer testler tamamlama ekranına POST atıyor ya da yönlendirmeyi
         * ölçüyor; hiçbiri Blade'i BASMIYORDU. Şablondaki bir hata (bu depoda
         * `@php(...)` kısa formu ve yorum içindeki direktif yüzünden iki kez
         * yaşandı) o testlerin hepsinden sızardı. Bu test sayfayı gerçekten
         * render eder.
         */
        $this->googleyiAc();
        $this->ulkeVeParaBirimi();
        $this->googleKullanicisi('yeni@kisi.test', 'Ayşe Yılmaz');

        $this->get(route('login.google.callback'));

        $this->get(route('register.google.complete'))
            ->assertOk()
            ->assertSee('Ayşe Yılmaz')
            ->assertSee('yeni@kisi.test')
            ->assertSee('Almanya')          // ülke listesi doldu
            ->assertSee('Euro')             // para birimi listesi doldu
            ->assertSee('Hesabı oluştur');
    }

    public function test_oturumda_taslak_yoksa_tamamlama_ekrani_kayda_yollar(): void
    {
        $this->googleyiAc();

        $this->get(route('register.google.complete'))
            ->assertRedirect(route('register'));
    }

    // -----------------------------------------------------------------
    // KAPALIYKEN
    // -----------------------------------------------------------------

    public function test_kapaliyken_google_yolu_calismaz(): void
    {
        Settings::setMany(['giris.google_aktif' => '0']);
        Cache::flush();

        $this->get(route('login.google'))->assertRedirect(route('login'));
        $this->get(route('login.google.callback'))->assertRedirect(route('login'));
    }

    public function test_anahtar_girilmemisse_acik_olsa_bile_kapalidir(): void
    {
        // "Açık ama anahtarsız" hâli kullanıcıyı Google'ın hata sayfasına
        // gönderirdi; düğme de hiç basılmamalı.
        Settings::setMany([
            'giris.google_aktif' => '1',
            'giris.google_client_id' => '',
            'giris.google_client_secret' => '',
        ]);
        Cache::flush();
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('login.google'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertDontSee('Google ile giriş yap');
    }

    public function test_acikken_giris_ve_kayit_sayfasinda_dugme_gorunur(): void
    {
        $this->googleyiAc();

        $this->get(route('login'))->assertOk()->assertSee('Google ile giriş yap');
        $this->get(route('register'))->assertOk()->assertSee('Google ile kayıt ol');
    }
}

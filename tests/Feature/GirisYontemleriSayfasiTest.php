<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\GirisYontemleri;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Giriş Yöntemleri yönetim ekranı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * İlk sürümde "Authorized redirect URI" kutusu CANLIDA BOŞ çıktı ve sahip
 * "buraya ne yazacağım" diye sormak zorunda kaldı. Sebep ince: alanın
 * `default()` değeri vardı ama `mount()` içinde `form->fill()` açık bir
 * diziyle çağrılıyor ve bu durumda bileşen varsayılanları UYGULANMIYOR.
 *
 * Ekranın hiç testi olmadığı için boş kutu fark edilmeden yayına gitti.
 * Buradaki testler o boşluğu kapatıyor.
 */
class GirisYontemleriSayfasiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_yonlendirme_adresi_kutusu_dolu_gelir(): void
    {
        // ASIL BEKÇİ: kutu boş gelirse sahip ne yazacağını bilemez.
        $this->actingAs($this->admin());

        Livewire::test(GirisYontemleri::class)
            ->assertSet('data.yonlendirme', url('/giris/google/callback'));
    }

    public function test_yonlendirme_adresi_callback_rotasiyla_ayni(): void
    {
        /*
         * Ekranda gösterilen adres ile uygulamanın GERÇEKTEN dinlediği adres
         * ayrışırsa, sahip Google'a doğru sandığı yanlış adresi girer ve hata
         * `redirect_uri_mismatch` olarak Google tarafında çıkar — yani en zor
         * teşhis edilecek yerde. İkisini birbirine bağlıyoruz.
         */
        $this->actingAs($this->admin());

        Livewire::test(GirisYontemleri::class)
            ->assertSet('data.yonlendirme', route('login.google.callback'));
    }

    public function test_ayarlar_kaydedilir(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(GirisYontemleri::class)
            ->set('data.google_aktif', true)
            ->set('data.google_client_id', 'abc.apps.googleusercontent.com')
            ->set('data.google_client_secret', 'gizli-deger')
            ->call('save');

        $this->assertSame('1', Settings::get('giris.google_aktif'));
        $this->assertSame('abc.apps.googleusercontent.com', Settings::get('giris.google_client_id'));
        $this->assertSame('gizli-deger', Settings::get('giris.google_client_secret'));
    }

    public function test_yonlendirme_alani_ayar_olarak_kaydedilmez(): void
    {
        // Gösterge alanı; yanlışlıkla bir ayara dönüşürse çöp veri birikir.
        $this->actingAs($this->admin());

        Livewire::test(GirisYontemleri::class)
            ->set('data.google_client_id', 'abc.apps.googleusercontent.com')
            ->call('save');

        $this->assertNull(Settings::get('giris.yonlendirme'));
    }

    public function test_yalniz_yonetici_erisebilir(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(GirisYontemleri::canAccess());

        $this->actingAs(User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]));
        $this->assertFalse(GirisYontemleri::canAccess());
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Panelden üye/yönetici oluşturma formu.
 *
 * ---------------------------------------------------------------------------
 * GERÇEK BİR ÇÖKME (2026-08-05)
 *
 * Sahip ikinci yöneticiyi eklerken "Oluştur"a bastı ve hata aldı. Sebebi:
 * formda `email` ve `username` için BENZERSİZLİK DOĞRULAMASI yoktu, oysa
 * veritabanında ikisi de unique.
 *
 * Sonuç: doğrulama katmanı veriyi geçiriyor, INSERT veritabanı seviyesinde
 * patlıyor (SQLSTATE 23000) ve kullanıcı "bu e-posta zaten kayıtlı" yerine
 * çıplak bir sunucu hatası görüyor. Girdiği form da kaybolmuş oluyor.
 *
 * Bu, doğrulamanın veritabanı kısıtlarıyla hizalanmamasının klasik sonucu:
 * kısıt zaten VAR, yalnız kullanıcıya nazikçe anlatan katman eksikti.
 */
class UyeOlusturFormuTest extends TestCase
{
    use RefreshDatabase;

    private function adminOlarak(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_kayitli_eposta_ile_olusturmak_cokme_degil_uyari_verir(): void
    {
        $this->adminOlarak();
        User::factory()->create(['email' => 'atabayhakan@outlook.com']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Hakan Atabay',
                'email' => 'atabayhakan@outlook.com',
                'password' => 'guclu-parola-1234',
                'preferred_currency' => 'EUR',
                'role' => UserRole::Admin->value,
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);

        // Ve kayıt oluşmamalı.
        $this->assertSame(1, User::where('email', 'atabayhakan@outlook.com')->count());
    }

    public function test_kayitli_kullanici_adi_ile_olusturmak_cokme_degil_uyari_verir(): void
    {
        $this->adminOlarak();
        User::factory()->create(['username' => 'atabayhakan']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Hakan Atabay',
                'email' => 'bambaska@nisoya.test',
                'password' => 'guclu-parola-1234',
                'username' => 'atabayhakan',
                'preferred_currency' => 'EUR',
                'role' => UserRole::Admin->value,
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasFormErrors(['username']);
    }

    public function test_gecerli_bilgilerle_ikinci_yonetici_olusur(): void
    {
        $this->adminOlarak();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'İkinci Yönetici',
                'email' => 'ikinci@nisoya.test',
                'password' => 'guclu-parola-1234',
                'username' => 'ikinci-yonetici',
                'preferred_currency' => 'EUR',
                'role' => UserRole::Admin->value,
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $yeni = User::where('email', 'ikinci@nisoya.test')->first();

        $this->assertNotNull($yeni);
        $this->assertTrue($yeni->isAdmin());
        // Parola model cast'i ile hash'lenir; düz metin kaydedilmemeli.
        $this->assertNotSame('guclu-parola-1234', $yeni->password);
    }

    public function test_mevcut_kullaniciyi_duzenlemek_kendi_epostasina_takilmaz(): void
    {
        // `unique()` kuralının en riskli yanı bu: ignoreRecord olmadan, bir
        // kullanıcıyı düzenleyip kaydetmek kendi e-postasını çakışma sanar ve
        // hiçbir düzenleme kaydedilemez. Çözerken yeni bir kilit yaratmadık.
        $this->adminOlarak();
        $hedef = User::factory()->create(['email' => 'mevcut@nisoya.test', 'username' => 'mevcut']);

        Livewire::test(EditUser::class, ['record' => $hedef->getKey()])
            ->fillForm(['name' => 'Yeni Ad'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Yeni Ad', $hedef->fresh()->name);
    }

    public function test_mevcut_uyeyi_yoneticiye_yukseltmek_calisir(): void
    {
        // Sahibin gerçek ihtiyacı bu çıktı: ikinci yönetici için YENİ hesap
        // açmaya gerek yok, kendi üye hesabı zaten vardı.
        $this->adminOlarak();
        $uye = User::factory()->create(['role' => UserRole::Uye, 'email' => 'zaten-var@nisoya.test']);

        Livewire::test(EditUser::class, ['record' => $uye->getKey()])
            ->fillForm(['role' => UserRole::Admin->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($uye->fresh()->isAdmin());
    }

    public function test_kullanici_adi_bos_birakilabilir(): void
    {
        // username DB'de nullable; birden fazla NULL unique index'i bozmaz.
        // Doğrulama eklerken bunu yanlışlıkla zorunlu kılmadığımızı mühürler.
        $this->adminOlarak();
        User::factory()->create(['username' => null]);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Adsız',
                'email' => 'adsiz@nisoya.test',
                'password' => 'guclu-parola-1234',
                'username' => null,
                'preferred_currency' => 'EUR',
                'role' => UserRole::Uye->value,
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }
}

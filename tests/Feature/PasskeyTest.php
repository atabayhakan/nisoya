<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;

/**
 * Passkey akışı — laravel/passkeys'e geçiş sonrası (2026-08-02; eski
 * laragear/webauthn resmen terk edilmişti, halefi Packagist'te ilan edilmişti).
 *
 * WebAuthn kripto törenleri gerçek cihaz ister; burada tören DIŞI sözleşme
 * sınanır: uçların kimlik kapıları, seçeneklerin şekli, sahiplik (başkasının
 * passkey'i silinemez) ve arayüz yüzeyleri. Uçlar artık paketin
 * varsayılanları: /passkeys/login/* (misafir) + /user/passkeys* (oturum).
 */
class PasskeyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    /** Sahte bir passkey satırı ekler (WebAuthn kripto akışı olmadan). */
    private function fakeCredential(User $user, string $ad = 'Test Cihazı'): Passkey
    {
        // İlişki üzerinden: user_id paket modelinde fillable değil.
        return $user->passkeys()->create([
            'name' => $ad,
            'credential_id' => $this->faker->unique()->sha256(),
            'credential' => ['publicKeyCredentialId' => 'test', 'credentialPublicKey' => 'test-key'],
        ]);
    }

    public function test_kayit_secenekleri_oturum_ister(): void
    {
        $this->get('/user/passkeys/options')->assertRedirect('/giris');
    }

    public function test_uye_kayit_seceneklerini_alir(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/user/passkeys/options')
            ->assertOk()
            ->assertJsonStructure(['options' => ['challenge', 'rp', 'user']]);
    }

    public function test_misafir_giris_seceneklerini_alir(): void
    {
        $this->getJson('/passkeys/login/options')
            ->assertOk()
            ->assertJsonStructure(['options' => ['challenge']]);
    }

    public function test_uye_kendi_passkeyini_silebilir(): void
    {
        $user = User::factory()->create();
        $passkey = $this->fakeCredential($user);

        $this->actingAs($user)
            ->delete('/user/passkeys/'.$passkey->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
    }

    public function test_uye_baskasinin_passkeyini_silemez(): void
    {
        $owner = User::factory()->create();
        $passkey = $this->fakeCredential($owner);

        $this->actingAs(User::factory()->create())
            ->delete('/user/passkeys/'.$passkey->id)
            ->assertForbidden();

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    public function test_guvenlik_sayfasi_passkeyleri_listeler(): void
    {
        $user = User::factory()->create();
        $this->fakeCredential($user);

        $this->actingAs($user)
            ->get('/panel/profil/iki-faktor')
            ->assertOk()
            ->assertSee('Test Cihazı')
            ->assertSee('Passkey');
    }

    public function test_giris_sayfasinda_passkey_dugmesi_var(): void
    {
        $this->get('/giris')
            ->assertOk()
            ->assertSee('passkeyLogin', false)
            ->assertSee('Parmak izi / Yüz tanıma ile gir');
    }
}

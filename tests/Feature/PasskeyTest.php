<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    /** Sahte bir passkey satırı ekler (WebAuthn kripto akışı olmadan). */
    private function fakeCredential(User $user, string $id = 'test-credential-id'): string
    {
        DB::table('webauthn_credentials')->insert([
            'id' => $id,
            'authenticatable_type' => User::class,
            'authenticatable_id' => $user->id,
            'user_id' => (string) Str::uuid(),
            'alias' => 'Test Cihazı',
            'rp_id' => 'localhost',
            'origin' => 'http://localhost',
            'public_key' => 'test-public-key',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_attestation_options_requires_auth(): void
    {
        $this->post('/panel/profil/passkey/secenekler')->assertRedirect('/giris');
    }

    public function test_user_gets_attestation_options(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/panel/profil/passkey/secenekler')
            ->assertOk()
            ->assertJsonStructure(['challenge', 'rp', 'user']);
    }

    public function test_guest_gets_assertion_options(): void
    {
        $this->postJson('/webauthn/giris/secenekler')
            ->assertOk()
            ->assertJsonStructure(['challenge']);
    }

    public function test_assertion_options_with_email(): void
    {
        $user = User::factory()->create();
        $this->fakeCredential($user);

        $this->postJson('/webauthn/giris/secenekler', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['challenge']);
    }

    public function test_user_can_delete_own_passkey(): void
    {
        $user = User::factory()->create();
        $id = $this->fakeCredential($user);

        $this->actingAs($user)
            ->delete('/panel/profil/passkey/'.$id)
            ->assertRedirect();

        $this->assertDatabaseMissing('webauthn_credentials', ['id' => $id]);
    }

    public function test_user_cannot_delete_others_passkey(): void
    {
        $owner = User::factory()->create();
        $id = $this->fakeCredential($owner);

        $this->actingAs(User::factory()->create())
            ->delete('/panel/profil/passkey/'.$id)
            ->assertRedirect();

        $this->assertDatabaseHas('webauthn_credentials', ['id' => $id]);
    }

    public function test_security_page_lists_passkeys(): void
    {
        $user = User::factory()->create();
        $this->fakeCredential($user);

        $this->actingAs($user)
            ->get('/panel/profil/iki-faktor')
            ->assertOk()
            ->assertSee('Test Cihazı')
            ->assertSee('Passkey');
    }

    public function test_login_page_has_passkey_button(): void
    {
        $this->get('/giris')
            ->assertOk()
            ->assertSee('passkeyLogin', false)
            ->assertSee('Parmak izi / Yüz tanıma ile gir');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_gets_a_referral_code_automatically(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->referral_code);
        $this->assertSame(8, strlen($user->referral_code));
    }

    public function test_referral_codes_are_unique(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->assertNotSame($a->referral_code, $b->referral_code);
    }

    public function test_registering_through_a_referral_link_sets_referred_by(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        $inviter = User::factory()->create();

        // Davet bağlantısıyla kayıt sayfasına gel (kod oturuma yazılır)
        $this->get('/kayit?ref='.$inviter->referral_code)->assertOk();

        $this->post('/kayit', [
            'name' => 'Davetli Üye',
            'email' => 'davetli@example.com',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $invited = User::where('email', 'davetli@example.com')->first();

        $this->assertNotNull($invited);
        $this->assertSame($inviter->id, $invited->referred_by);
        $this->assertTrue($inviter->referrals()->whereKey($invited->id)->exists());
    }

    public function test_registration_without_referral_leaves_referred_by_null(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        $this->post('/kayit', [
            'name' => 'Yalnız Üye',
            'email' => 'yalniz@example.com',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
            'country_code' => 'NL',
            'preferred_currency' => 'EUR',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertNull(User::where('email', 'yalniz@example.com')->first()->referred_by);
    }

    public function test_invalid_referral_code_is_ignored(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        $this->get('/kayit?ref=GECERSIZ')->assertOk();

        $this->post('/kayit', [
            'name' => 'Üye',
            'email' => 'uye@example.com',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
            'country_code' => 'FR',
            'preferred_currency' => 'EUR',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertNull(User::where('email', 'uye@example.com')->first()->referred_by);
    }

    public function test_invite_page_shows_users_referral_link_and_count(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        User::factory()->count(2)->create(['referred_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('panel.invite'))
            ->assertOk()
            ->assertSee($user->referral_code)
            ->assertSee('Davet ettiklerin');
    }

    public function test_invite_page_requires_authentication(): void
    {
        $this->get(route('panel.invite'))->assertRedirect(route('login'));
    }

    public function test_invite_page_backfills_missing_referral_code(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Davet sistemi öncesi kayıt olmuş gibi kodu boşalt
        $user->forceFill(['referral_code' => null])->save();

        $this->actingAs($user)->get(route('panel.invite'))->assertOk();

        $this->assertNotEmpty($user->fresh()->referral_code);
    }
}

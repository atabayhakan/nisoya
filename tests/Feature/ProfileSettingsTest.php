<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_user_can_view_profile_settings(): void
    {
        $this->actingAs(User::factory()->create())->get('/panel/profil')->assertOk();
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/panel/profil', [
            'name' => 'Yeni İsim',
            'username' => 'yeni-kullanici',
            'bio' => 'Merhaba, ben bir öğretmenim.',
            'country_code' => 'NL',
            'city' => 'Amsterdam',
            'preferred_currency' => 'EUR',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Yeni İsim',
            'username' => 'yeni-kullanici',
            'country_code' => 'NL',
        ]);
    }

    public function test_user_can_select_payment_methods(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => $user->username,
            'country_code' => 'KZ',
            'preferred_currency' => 'KZT',
            'payment_methods' => ['kaspi', 'sepa_iban'],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $fresh = $user->fresh();
        $this->assertCount(2, $fresh->payment_methods);
        $this->assertTrue($fresh->payment_methods->contains(PaymentMethod::Kaspi));
        $this->assertTrue($fresh->payment_methods->contains(PaymentMethod::SepaIban));
    }

    public function test_payment_methods_rejects_invalid_value(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => $user->username,
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'payment_methods' => ['bitcoin'],
        ])->assertSessionHasErrors('payment_methods.0');
    }

    public function test_payment_methods_show_as_badges_on_public_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'odeme-test-satici',
            'payment_methods' => [PaymentMethod::Kaspi, PaymentMethod::SepaIban],
        ]);

        $response = $this->get("/uye/{$user->username}");

        $response->assertOk();
        $response->assertSee('Kaspi');
        $response->assertSee('Banka Havalesi (IBAN)');
    }

    public function test_suggested_payment_methods_for_kazakhstan_include_kaspi(): void
    {
        $suggestions = PaymentMethod::suggestedFor('KZ');

        $this->assertContains(PaymentMethod::Kaspi, $suggestions);
    }

    public function test_suggested_payment_methods_for_usa_include_zelle_and_venmo(): void
    {
        $suggestions = PaymentMethod::suggestedFor('US');

        $this->assertContains(PaymentMethod::Zelle, $suggestions);
        $this->assertContains(PaymentMethod::Venmo, $suggestions);
    }

    public function test_username_must_be_unique(): void
    {
        $existing = User::factory()->create(['username' => 'alinmis-ad']);
        $user = User::factory()->create();

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => 'alinmis-ad',
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
        ])->assertSessionHasErrors('username');
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('eski-sifre1')]);

        $this->actingAs($user)->put('/panel/profil/sifre', [
            'current_password' => 'eski-sifre1',
            'password' => 'yeni-sifre1',
            'password_confirmation' => 'yeni-sifre1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('yeni-sifre1', $user->fresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('eski-sifre1')]);

        $this->actingAs($user)->put('/panel/profil/sifre', [
            'current_password' => 'yanlis',
            'password' => 'yeni-sifre1',
            'password_confirmation' => 'yeni-sifre1',
        ])->assertSessionHasErrors('current_password');
    }
}

<?php

namespace Tests\Feature;

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

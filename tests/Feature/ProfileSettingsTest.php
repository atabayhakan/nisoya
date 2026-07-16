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

    public function test_header_shows_logged_in_users_name_linking_to_account_page(): void
    {
        $user = User::factory()->create(['name' => 'Ayşe Yılmaz']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSeeInOrder([route('panel.profile.edit'), 'Ayşe Yılmaz'], false);
    }

    public function test_header_hides_account_link_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee(route('panel.profile.edit'), false);
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

    public function test_user_can_set_skills_as_comma_separated_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => $user->username,
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'skills' => 'İngilizce, Web Tasarım, İngilizce, , Photoshop',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['İngilizce', 'Web Tasarım', 'Photoshop'], $user->fresh()->skills);
    }

    public function test_empty_skills_input_clears_skills(): void
    {
        $user = User::factory()->create(['skills' => ['Eski Yetenek']]);

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => $user->username,
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'skills' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->skills);
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

    /** Sürükleyerek hizalama (Faz M7): profil fotoğrafının odak noktası. */
    public function test_user_can_align_avatar_focal_point(): void
    {
        $user = User::factory()->create(['avatar_path' => 'avatars/foto.jpg', 'avatar_focal_x' => 50, 'avatar_focal_y' => 50]);

        $this->actingAs($user)->patch('/panel/profil/avatar-hizala', [
            'focal_x' => 20,
            'focal_y' => 80,
        ])->assertOk()->assertJson(['status' => 'ok']);

        $user->refresh();
        $this->assertSame(20, $user->avatar_focal_x);
        $this->assertSame(80, $user->avatar_focal_y);
    }

    public function test_avatar_align_rejects_out_of_range_values(): void
    {
        $user = User::factory()->create(['avatar_path' => 'avatars/foto.jpg']);

        $this->actingAs($user)->patch('/panel/profil/avatar-hizala', [
            'focal_x' => 101,
            'focal_y' => -1,
        ])->assertSessionHasErrors(['focal_x', 'focal_y']);
    }

    public function test_guest_cannot_align_avatar(): void
    {
        $this->patch('/panel/profil/avatar-hizala', ['focal_x' => 10, 'focal_y' => 10])
            ->assertRedirect(route('login'));
    }
}

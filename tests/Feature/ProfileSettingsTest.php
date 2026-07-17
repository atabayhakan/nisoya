<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Public diske bilinen boyutlarda gerçek bir test görseli koyar
     * (kaydır+zum hizalama sunucuda GERÇEK dosyayı kırptığı için sahte
     * path yetmez) ve avatarı bu dosya olan bir kullanıcı döndürür.
     */
    private function userWithRealAvatar(int $width = 400, int $height = 800): User
    {
        Storage::fake('public');

        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 40, 120, 80));
        ob_start();
        imagepng($image);
        Storage::disk('public')->put('avatars/large/test-avatar.png', ob_get_clean());

        return User::factory()->create(['avatar_path' => 'avatars/large/test-avatar.png']);
    }

    /** Kaydır+zum hizalama: kırpım karesi sunucuda gerçek KARE dosya üretir. */
    public function test_user_can_crop_avatar(): void
    {
        $user = $this->userWithRealAvatar(400, 800);

        $response = $this->actingAs($user)->patch('/panel/profil/avatar-hizala', [
            'crop_x' => 0,
            'crop_y' => 200,
            'crop_size' => 400,
        ]);

        $response->assertOk()->assertJson(['status' => 'ok'])->assertJsonStructure(['cropped_url']);

        $user->refresh();
        $this->assertSame(0, $user->avatar_crop_x);
        $this->assertSame(200, $user->avatar_crop_y);
        $this->assertSame(400, $user->avatar_crop_size);
        $this->assertNotNull($user->avatar_cropped_path);
        Storage::disk('public')->assertExists($user->avatar_cropped_path);

        // Üretilen dosya gerçekten kare olmalı.
        $size = getimagesizefromstring(Storage::disk('public')->get($user->avatar_cropped_path));
        $this->assertSame($size[0], $size[1]);
    }

    public function test_avatar_crop_replaces_previous_cropped_file(): void
    {
        $user = $this->userWithRealAvatar(400, 800);

        $this->actingAs($user)->patch('/panel/profil/avatar-hizala', ['crop_x' => 0, 'crop_y' => 0, 'crop_size' => 400])->assertOk();
        $firstPath = $user->refresh()->avatar_cropped_path;

        $this->actingAs($user)->patch('/panel/profil/avatar-hizala', ['crop_x' => 0, 'crop_y' => 400, 'crop_size' => 400])->assertOk();

        $this->assertNotSame($firstPath, $user->refresh()->avatar_cropped_path);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($user->avatar_cropped_path);
    }

    public function test_avatar_crop_rejects_rect_outside_image_bounds(): void
    {
        $user = $this->userWithRealAvatar(400, 800);

        // 300 + 200 > 400 (genişlik) — kare görsel sınırlarının dışına taşıyor.
        $this->actingAs($user)->patch('/panel/profil/avatar-hizala', [
            'crop_x' => 300,
            'crop_y' => 0,
            'crop_size' => 200,
        ])->assertStatus(422);

        $this->assertNull($user->refresh()->avatar_cropped_path);
    }

    public function test_avatar_crop_rejects_invalid_values(): void
    {
        $user = $this->userWithRealAvatar();

        $this->actingAs($user)->patch('/panel/profil/avatar-hizala', [
            'crop_x' => -5,
            'crop_y' => 0,
            'crop_size' => 4,
        ])->assertSessionHasErrors(['crop_x', 'crop_size']);
    }

    public function test_avatar_crop_requires_existing_avatar(): void
    {
        $user = User::factory()->create(['avatar_path' => null]);

        $this->actingAs($user)->patch('/panel/profil/avatar-hizala', [
            'crop_x' => 0,
            'crop_y' => 0,
            'crop_size' => 100,
        ])->assertStatus(422);
    }

    public function test_guest_cannot_align_avatar(): void
    {
        $this->patch('/panel/profil/avatar-hizala', ['crop_x' => 0, 'crop_y' => 0, 'crop_size' => 100])
            ->assertRedirect(route('login'));
    }

    /** Yeni avatar yüklemesi otomatik ORTALANMIŞ kare kırpım üretir. */
    public function test_avatar_upload_generates_centered_square_crop(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $image = imagecreatetruecolor(300, 600);
        imagefilledrectangle($image, 0, 0, 300, 600, imagecolorallocate($image, 200, 60, 60));
        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        $file = UploadedFile::fake()->createWithContent('avatar.png', $png);

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => $user->username,
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'avatar' => $file,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        $this->assertNotNull($user->avatar_cropped_path);
        Storage::disk('public')->assertExists($user->avatar_cropped_path);

        // Ortalanmış kare: 300x600 kaynakta size=300, x=0, y=150.
        $this->assertSame(300, $user->avatar_crop_size);
        $this->assertSame(0, $user->avatar_crop_x);
        $this->assertSame(150, $user->avatar_crop_y);

        $size = getimagesizefromstring(Storage::disk('public')->get($user->avatar_cropped_path));
        $this->assertSame($size[0], $size[1]);
    }
}

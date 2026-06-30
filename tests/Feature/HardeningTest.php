<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_honeypot_blocks_bot_registration(): void
    {
        $this->post('/kayit', [
            'name' => 'Bot', 'email' => 'bot@example.com',
            'password' => 'sifre1234', 'password_confirmation' => 'sifre1234',
            'country_code' => 'DE', 'preferred_currency' => 'EUR', 'terms' => '1',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    public function test_honeypot_blocks_bot_listing(): void
    {
        $user = User::factory()->create();
        $category = Category::whereNotNull('parent_id')->first();

        $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'hizmet', 'title' => 'Spam ilan başlığı', 'category_id' => $category->id,
            'description' => 'Yeterince uzun bir açıklama metni buraya yazıldı.',
            'currency' => 'EUR', 'price_unit' => 'gorusulur', 'country_code' => 'DE',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_image_service_stores_optimized_file(): void
    {
        Storage::fake('public');

        $path = app(ImageService::class)->storeOptimized(
            UploadedFile::fake()->image('buyuk.jpg', 2400, 1800),
            'listings',
        );

        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_admin_dashboard_shows_stats(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        Listing::factory()->for(User::factory())->create(['status' => 'aktif']);

        $this->actingAs($admin)->get('/yonetim')
            ->assertOk()
            ->assertSee('Aktif ilanlar');
    }
}

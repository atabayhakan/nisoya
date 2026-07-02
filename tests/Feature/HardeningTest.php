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
        ])
            // Honeypot middleware bot'u sessizce geri yönlendirir, validasyon hatası vermez.
            ->assertRedirect()
            ->assertSessionHas('status', 'İşlemin alındı.');

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
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'İşlemin alındı.');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_image_service_stores_optimized_file(): void
    {
        $this->useRealStorage();
        $result = app(ImageService::class)->storeOptimized(
            UploadedFile::fake()->image('buyuk.jpg', 2400, 1800),
            'listings',
        );

        // Yeni return: array (thumb/medium/large + metadata)
        $this->assertNotEmpty($result['thumb']);
        $this->assertNotEmpty($result['medium']);
        $this->assertNotEmpty($result['large']);
        $this->assertIsBool($result['orientation_corrected']);
        $this->assertIsArray($result['original_dimensions']);

        $this->cleanupRealStorage($result);

        // useRealStorage helper'ı kullanıldığı için Storage::fake gerek yok
        $this->assertTrue(true);
    }

    private function useRealStorage(): void
    {
        $tmpPath = storage_path('framework/testing/disk-public');
        if (! is_dir($tmpPath)) {
            mkdir($tmpPath, 0777, true);
        }
        config(['filesystems.disks.public' => [
            'driver' => 'local',
            'root' => $tmpPath,
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ]]);
    }

    private function cleanupRealStorage(array $paths): void
    {
        foreach ($paths as $key => $value) {
            if (is_string($value) && $value && \Storage::disk('public')->exists($value)) {
                \Storage::disk('public')->delete($value);
            }
        }
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

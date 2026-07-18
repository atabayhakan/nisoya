<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Notifications\ListingFlaggedNotification;
use App\Services\ImageModerationService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function enableModeration(): void
    {
        config([
            'ai.default' => 'openrouter',
            'ai.features.image_moderation' => true,
            'ai.providers.openrouter.api_key' => 'test-key',
            'ai.providers.openrouter.base_url' => 'https://openrouter.ai/api/v1',
        ]);
    }

    private function fakeVerdict(bool $flagged, ?string $category = null): void
    {
        Http::fake(['openrouter.ai/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode([
                'uygunsuz' => $flagged,
                'kategori' => $category,
            ])]]],
        ])]);
    }

    // --- ImageModerationService ---

    public function test_service_fails_open_when_ai_not_configured(): void
    {
        config(['ai.default' => 'openrouter', 'ai.providers.openrouter.api_key' => null]);

        $result = app(ImageModerationService::class)->check(public_path('icons/icon-192.png'));

        $this->assertNull($result);
    }

    public function test_service_returns_flagged_verdict(): void
    {
        $this->enableModeration();
        $this->fakeVerdict(true, 'siddet');

        $result = app(ImageModerationService::class)->check(public_path('icons/icon-192.png'));

        $this->assertSame(['flagged' => true, 'reason' => 'siddet'], $result);
    }

    public function test_service_returns_clean_verdict(): void
    {
        $this->enableModeration();
        $this->fakeVerdict(false);

        $result = app(ImageModerationService::class)->check(public_path('icons/icon-192.png'));

        $this->assertSame(['flagged' => false, 'reason' => null], $result);
    }

    public function test_check_passes_timeout_through_to_provider(): void
    {
        // Sohbet yolu (senkron) kısa timeout geçer; kuyruk yolu (varsayılan) null.
        $captured = (object) ['timeout' => 'unset'];
        $fake = new class($captured) implements AiProvider
        {
            public function __construct(private object $captured) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function name(): string
            {
                return 'fake';
            }

            public function lastError(): ?string
            {
                return null;
            }

            public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                $this->captured->timeout = $timeoutSeconds;

                return ['uygunsuz' => false, 'kategori' => null];
            }
        };
        $this->app->instance(AiProvider::class, $fake);
        config(['ai.features.image_moderation' => true]);

        $service = app(ImageModerationService::class);

        // Sohbet: 10s geçilir.
        $service->check(public_path('icons/icon-192.png'), ImageModerationService::SYNC_TIMEOUT_SECONDS);
        $this->assertSame(10, $captured->timeout);
        $this->assertSame(10, ImageModerationService::SYNC_TIMEOUT_SECONDS);

        // Kuyruk yolu (timeout geçmez): sağlayıcı varsayılanını (30s) kullansın diye null.
        $service->check(public_path('icons/icon-192.png'));
        $this->assertNull($captured->timeout);
    }

    // --- İlan görseli akışı (ProcessListingImage) ---

    public function test_flagged_listing_image_puts_listing_under_review(): void
    {
        Storage::fake('public');
        Notification::fake();
        $this->enableModeration();
        $this->fakeVerdict(true, 'cinsel_icerik');

        $user = User::factory()->create();
        $category = Category::whereNotNull('parent_id')->where('is_active', true)->first();

        $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'hizmet',
            'title' => 'Test hizmeti ilanı',
            'category_id' => $category->id,
            'description' => 'Bu bir test ilanı açıklamasıdır, yeterince uzun.',
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'country_code' => 'DE',
            'images' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect();

        $listing = Listing::first();
        $this->assertSame(ListingStatus::Beklemede, $listing->status);

        $image = ListingImage::first();
        $this->assertTrue($image->is_flagged);
        $this->assertSame('cinsel_icerik', $image->flagged_reason);

        Notification::assertSentTo($user, ListingFlaggedNotification::class);
    }

    public function test_clean_listing_image_leaves_listing_active(): void
    {
        Storage::fake('public');
        $this->enableModeration();
        $this->fakeVerdict(false);

        $user = User::factory()->create();
        $category = Category::whereNotNull('parent_id')->where('is_active', true)->first();

        $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'hizmet',
            'title' => 'Test hizmeti ilanı',
            'category_id' => $category->id,
            'description' => 'Bu bir test ilanı açıklamasıdır, yeterince uzun.',
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'country_code' => 'DE',
            'images' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect();

        $listing = Listing::first();
        $this->assertSame(ListingStatus::Aktif, $listing->status);
        $this->assertFalse(ListingImage::first()->is_flagged);
    }

    public function test_moderation_disabled_leaves_listing_active_without_ai_calls(): void
    {
        Storage::fake('public');
        Http::fake(); // hiç çağrı beklenmiyor
        config(['ai.default' => 'openrouter', 'ai.providers.openrouter.api_key' => null]);

        $user = User::factory()->create();
        $category = Category::whereNotNull('parent_id')->where('is_active', true)->first();

        $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'hizmet',
            'title' => 'Test hizmeti ilanı',
            'category_id' => $category->id,
            'description' => 'Bu bir test ilanı açıklamasıdır, yeterince uzun.',
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'country_code' => 'DE',
            'images' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect();

        $this->assertSame(ListingStatus::Aktif, Listing::first()->status);
        Http::assertNothingSent();
    }

    // --- Sohbet fotoğrafı akışı (MessageController) ---

    protected function conversation(User $a, User $b): Conversation
    {
        return Conversation::create(['user_one_id' => $a->id, 'user_two_id' => $b->id, 'last_message_at' => now()]);
    }

    public function test_flagged_chat_photo_is_rejected(): void
    {
        Storage::fake('public');
        $this->enableModeration();
        $this->fakeVerdict(true, 'silah');

        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);

        $this->actingAs($a)
            ->postJson("/panel/mesajlar/{$conv->id}", ['photo' => UploadedFile::fake()->image('foto.jpg')])
            ->assertStatus(422);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_clean_chat_photo_is_accepted(): void
    {
        Storage::fake('public');
        $this->enableModeration();
        $this->fakeVerdict(false);

        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);

        $this->actingAs($a)
            ->postJson("/panel/mesajlar/{$conv->id}", ['photo' => UploadedFile::fake()->image('foto.jpg')])
            ->assertOk()
            ->assertJson(['type' => 'image']);

        $this->assertDatabaseCount('messages', 1);
    }
}

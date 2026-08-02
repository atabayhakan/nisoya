<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewMessageNotification;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_offline_page_loads(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Çevrimdışısın');
    }

    public function test_manifest_is_valid(): void
    {
        // Manifest artık statik dosya değil, dinamik rota (theme_color marka
        // rengini izlesin diye) — ayrıntılar ManifestTest'te; burada PWA
        // sözleşmesinin çekirdeği doğrulanır.
        $manifest = $this->get('/manifest.webmanifest')->assertOk()->json();

        $this->assertSame('Nisoya', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_pwa_assets_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
        $this->assertFileExists(public_path('icons/icon-maskable.png'));
    }

    public function test_layout_links_manifest_and_service_worker(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('/sw.js', false)
            ->assertSee('theme-color', false);
    }

    // --- Web push aboneliği (Faz M1.3) ---

    public function test_push_subscribe_requires_auth(): void
    {
        $this->post('/panel/push-abonelik', [])->assertRedirect('/giris');
    }

    public function test_user_can_subscribe_and_unsubscribe(): void
    {
        $user = User::factory()->create();
        $subscription = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => ['p256dh' => 'test-p256dh-key', 'auth' => 'test-auth-key'],
            'content_encoding' => 'aes128gcm',
        ];

        $this->actingAs($user)
            ->postJson('/panel/push-abonelik', $subscription)
            ->assertOk()
            ->assertJson(['subscribed' => true]);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $subscription['endpoint']]);

        $this->actingAs($user)
            ->deleteJson('/panel/push-abonelik', ['endpoint' => $subscription['endpoint']])
            ->assertOk()
            ->assertJson(['subscribed' => false]);
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $subscription['endpoint']]);
    }

    public function test_push_subscribe_validates_payload(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/panel/push-abonelik', ['endpoint' => 'gecersiz'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_message_notification_uses_webpush_only_when_vapid_configured(): void
    {
        $notification = new NewMessageNotification('Merhaba', 'Ayşe', 1);
        $user = User::factory()->make();

        config(['webpush.vapid.public_key' => 'pk', 'webpush.vapid.private_key' => 'sk']);
        $this->assertContains(WebPushChannel::class, $notification->via($user));

        config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);
        $this->assertNotContains(WebPushChannel::class, $notification->via($user));
    }
}

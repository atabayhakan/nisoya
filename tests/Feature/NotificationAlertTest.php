<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use App\Notifications\NewReviewNotification;
use App\Notifications\SavedSearchAlert;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    // --- Bildirim merkezi ---

    public function test_review_notifies_reviewee(): void
    {
        Notification::fake();
        $seller = User::factory()->create();
        $reviewer = User::factory()->create();

        $this->actingAs($reviewer)->post("/uye/{$seller->username}/degerlendir", ['rating' => 5]);

        Notification::assertSentTo($seller, NewReviewNotification::class);
    }

    public function test_listing_status_change_notifies_owner(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $listing = Listing::factory()->for($owner)->create(['status' => 'aktif']);

        $listing->update(['status' => 'pasif']);

        Notification::assertSentTo($owner, ListingStatusNotification::class);
    }

    public function test_notifications_page_loads_and_marks_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new NewReviewNotification('Ali Veli', 5, route('profiles.show', $user->username)));

        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)->get('/panel/bildirimler')
            ->assertOk()
            ->assertSee('Ali Veli');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    // --- Kayıtlı aramalar ---

    public function test_user_can_save_search(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/panel/arama-kaydet', [
            'q' => 'İngilizce', 'ulke' => 'DE', 'tip' => 'hizmet',
        ])->assertRedirect(route('panel.saved-searches.index'));

        $this->assertDatabaseHas('saved_searches', ['user_id' => $user->id, 'q' => 'İngilizce', 'ulke' => 'DE']);
    }

    public function test_empty_search_is_not_saved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/panel/arama-kaydet', []);
        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_duplicate_search_not_saved_twice(): void
    {
        $user = User::factory()->create();
        $payload = ['q' => 'tekrar', 'ulke' => 'NL'];
        $this->actingAs($user)->post('/panel/arama-kaydet', $payload);
        $this->actingAs($user)->post('/panel/arama-kaydet', $payload);
        $this->assertSame(1, SavedSearch::where('user_id', $user->id)->count());
    }

    public function test_user_can_delete_own_saved_search_only(): void
    {
        $owner = User::factory()->create();
        $search = SavedSearch::create(['user_id' => $owner->id, 'label' => 'Test', 'ulke' => 'DE']);

        $this->actingAs(User::factory()->create())->delete("/panel/aramalarim/{$search->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/panel/aramalarim/{$search->id}")->assertRedirect();
        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }

    public function test_alert_command_emails_matching_new_listings(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $search = SavedSearch::create([
            'user_id' => $user->id, 'label' => 'Almanya', 'ulke' => 'DE',
            'last_notified_at' => now()->subDay(),
        ]);
        Listing::factory()->for(User::factory())->create(['status' => 'aktif', 'country_code' => 'DE']);

        $this->artisan('alerts:saved-searches')->assertSuccessful();

        Notification::assertSentTo($user, SavedSearchAlert::class);
        $this->assertTrue($search->fresh()->last_notified_at->isToday());
    }
}

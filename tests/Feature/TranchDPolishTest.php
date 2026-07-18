<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\JobSavedSearchAlert;
use App\Notifications\SavedSearchAlert;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tranş D düşük öncelik parlatma/perf fix'leri.
 */
class TranchDPolishTest extends TestCase
{
    use RefreshDatabase;

    private function seedRefs(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_listing_view_counter_dedupes_same_visitor(): void
    {
        $this->seedRefs();
        $owner = User::factory()->create();
        $viewer = User::factory()->create(['email_verified_at' => now()]);
        $listing = Listing::factory()->create([
            'user_id' => $owner->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        // Aynı ziyaretçi iki kez açar → sayaç yalnızca 1 artmalı (dedup, giriş
        // yapmış kullanıcı için user id'ye göre anahtarlanır; misafirlerde oturum
        // id'ye göre — ama test client'ı istekler arası session cookie'sini
        // taşımadığı için burada giriş yapmış ziyaretçi kullanılıyor).
        $this->actingAs($viewer)->get(route('listings.show', [$listing, $listing->slug]))->assertOk();
        $this->actingAs($viewer)->get(route('listings.show', [$listing, $listing->slug]))->assertOk();

        $this->assertSame(1, $listing->fresh()->views_count);
    }

    public function test_listing_owner_does_not_increment_view_counter(): void
    {
        $this->seedRefs();
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $listing = Listing::factory()->create([
            'user_id' => $owner->id,
            'category_id' => Category::first()->id,
            'status' => ListingStatus::Aktif,
        ]);

        $this->actingAs($owner)->get(route('listings.show', [$listing, $listing->slug]))->assertOk();

        $this->assertSame(0, $listing->fresh()->views_count);
    }

    public function test_saved_search_alerts_are_queued(): void
    {
        // ShouldQueue → e-posta senkron gönderilmez, kuyruğa alınır (yavaş SMTP
        // zamanlanmış komutu bloke etmesin).
        $this->assertContains(ShouldQueue::class, class_implements(SavedSearchAlert::class));
        $this->assertContains(ShouldQueue::class, class_implements(JobSavedSearchAlert::class));
    }

    public function test_all_notifications_are_queued(): void
    {
        // Regresyon guard: TÜM bildirimler kuyruğa alınmalı — hepsi mail kanalı
        // ve çoğu istek yolunda (değerlendirme, ilan durumu vb.) gönderiliyor;
        // senkron olan bir kullanıcının isteğini SMTP'de bloke eder.
        foreach (glob(app_path('Notifications').'/*.php') as $file) {
            $class = 'App\\Notifications\\'.basename($file, '.php');
            if (! class_exists($class)) {
                continue;
            }
            $this->assertContains(
                ShouldQueue::class,
                class_implements($class),
                "{$class} ShouldQueue implement etmeli (senkron e-posta isteği bloke eder)."
            );
        }
    }
}

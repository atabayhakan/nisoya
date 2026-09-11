<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Services\NabizService;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Nisoya Nabzı": topluluk büyüme hedefi + şehir elçileri.
 */
class NabizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_goal_progress_is_null_when_target_is_zero(): void
    {
        Settings::setMany(['nabiz.hedef_sayi' => '0']);

        $this->assertNull(app(NabizService::class)->goalProgress());
    }

    public function test_goal_progress_counts_new_members_this_month(): void
    {
        Settings::setMany([
            'nabiz.hedef_sayi' => '10',
            'nabiz.hedef_metrik' => 'yeni_uye',
            'nabiz.hedef_baslik' => 'Bu ay hedefimiz',
        ]);

        User::factory()->count(3)->create(['created_at' => now()]);
        User::factory()->create(['created_at' => now()->subMonths(2)]);

        $progress = app(NabizService::class)->goalProgress();

        $this->assertSame(3, $progress['mevcut']);
        $this->assertSame(10, $progress['hedef']);
        $this->assertSame(30, $progress['yuzde']);
        $this->assertSame('Bu ay hedefimiz', $progress['baslik']);
    }

    public function test_goal_progress_caps_percentage_at_100(): void
    {
        Settings::setMany(['nabiz.hedef_sayi' => '2', 'nabiz.hedef_metrik' => 'yeni_uye']);

        User::factory()->count(5)->create(['created_at' => now()]);

        $progress = app(NabizService::class)->goalProgress();

        $this->assertSame(100, $progress['yuzde']);
    }

    public function test_suggest_next_target_averages_last_three_real_months(): void
    {
        Settings::setMany(['nabiz.hedef_metrik' => 'yeni_uye']);

        // Son 3 tamamlanmış ay: 8, 12, 10 üye — ortalama 10, %20 pay ile 12,
        // 5'e yuvarlanınca 10. Bu ayın (henüz bitmemiş) kayıtları SAYILMAMALI.
        User::factory()->count(8)->create(['created_at' => now()->subMonths(1)->startOfMonth()->addDays(3)]);
        User::factory()->count(12)->create(['created_at' => now()->subMonths(2)->startOfMonth()->addDays(3)]);
        User::factory()->count(10)->create(['created_at' => now()->subMonths(3)->startOfMonth()->addDays(3)]);
        User::factory()->count(99)->create(['created_at' => now()]); // bu ay — hariç tutulmalı

        $this->assertSame(10, app(NabizService::class)->suggestNextTarget());
    }

    public function test_suggest_next_target_has_floor_when_no_history(): void
    {
        // Geçmiş 3 ay tamamen boş (yeni site) — "hedef: 1" gibi gülünç
        // küçük bir sayı yerine anlamlı bir taban dönmeli.
        $this->assertSame(10, app(NabizService::class)->suggestNextTarget('yeni_uye'));
    }

    public function test_city_ambassadors_ranks_top_inviter_per_city(): void
    {
        $ayse = User::factory()->create(['name' => 'Ayşe', 'city' => 'Berlin']);
        $mehmet = User::factory()->create(['name' => 'Mehmet', 'city' => 'berlin']); // aynı şehir, farklı yazım
        $fatma = User::factory()->create(['name' => 'Fatma', 'city' => 'Amsterdam']);

        User::factory()->count(3)->create(['referred_by' => $ayse->id, 'created_at' => now()]);
        User::factory()->count(1)->create(['referred_by' => $mehmet->id, 'created_at' => now()]);
        User::factory()->count(2)->create(['referred_by' => $fatma->id, 'created_at' => now()]);

        $ambassadors = app(NabizService::class)->cityAmbassadors();

        // Berlin'de en çok davet eden Ayşe (3), Mehmet aynı şehirden olduğu için elenir.
        $this->assertCount(2, $ambassadors);
        $this->assertSame('Ayşe', $ambassadors->first()->name);
        $this->assertSame(3, $ambassadors->first()->referral_count);
    }

    public function test_city_ambassadors_ignores_referrals_from_previous_months(): void
    {
        $eski = User::factory()->create(['name' => 'Eski Davetçi', 'city' => 'Viyana']);
        User::factory()->count(5)->create(['referred_by' => $eski->id, 'created_at' => now()->subMonths(2)]);

        $ambassadors = app(NabizService::class)->cityAmbassadors();

        $this->assertTrue($ambassadors->isEmpty());
    }

    public function test_nabiz_page_loads_successfully(): void
    {
        $this->get('/nabiz')->assertOk();
    }

    public function test_nabiz_page_shows_no_goal_message_when_disabled(): void
    {
        Settings::setMany(['nabiz.hedef_sayi' => '0']);

        $this->get('/nabiz')->assertSee('Şu an aktif bir topluluk hedefi yok.');
    }

    public function test_homepage_shows_nabiz_card_when_goal_active(): void
    {
        Settings::setMany([
            'nabiz.hedef_sayi' => '50',
            'nabiz.hedef_metrik' => 'yeni_uye',
            'nabiz.hedef_baslik' => 'Bu ay hedefimiz',
        ]);
        User::factory()->create(['created_at' => now()]);

        $this->get('/')->assertSee('Nisoya Nabzı')->assertSee('Bu ay hedefimiz');
    }

    public function test_homepage_hides_nabiz_card_when_goal_disabled(): void
    {
        Settings::setMany(['nabiz.hedef_sayi' => '0']);

        // "Nisoya Nabzı" footer linkinde her zaman görünür; hedef kartına
        // özgü metni (başlık) kontrol ederek kartın gizlendiğini doğrula.
        $this->get('/')->assertDontSee('Bu ay hedefimiz');
    }

    public function test_country_activity_maps_real_coordinates_and_listing_counts(): void
    {
        $this->seed(CategorySeeder::class);
        $seller = User::factory()->create(['country_code' => 'DE']);
        Listing::factory()->count(2)->for($seller)->create(['status' => 'aktif', 'country_code' => 'DE']);

        $activity = collect(app(NabizService::class)->countryActivity());
        $de = $activity->firstWhere('code', 'DE');

        $this->assertNotNull($de);
        $this->assertSame(2, $de['count']);
        $this->assertGreaterThanOrEqual(0.0, $de['x']);
        $this->assertLessThanOrEqual(1.0, $de['x']);
        $this->assertGreaterThanOrEqual(0.0, $de['y']);
        $this->assertLessThanOrEqual(1.0, $de['y']);
    }

    public function test_country_activity_excludes_inactive_listings(): void
    {
        $this->seed(CategorySeeder::class);
        $seller = User::factory()->create(['country_code' => 'NL']);
        Listing::factory()->for($seller)->create(['status' => 'beklemede', 'country_code' => 'NL']);

        $activity = collect(app(NabizService::class)->countryActivity());

        $this->assertSame(0, $activity->firstWhere('code', 'NL')['count']);
    }
}

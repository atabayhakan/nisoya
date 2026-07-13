<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingUnavailableRange;
use App\Models\ListingVehicleDetail;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\VehicleCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, VehicleCategorySeeder::class]);
    }

    protected function verifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
    }

    protected function vehicleCategoryId(string $slug = 'satilik-arac'): int
    {
        return Category::where('slug', $slug)->firstOrFail()->id;
    }

    protected function validVehicleData(array $overrides = []): array
    {
        return array_merge([
            'type' => 'vasita',
            'title' => '2019 VW Golf 1.6 TDI — kesin dönüş nedeniyle',
            'category_id' => $this->vehicleCategoryId(),
            'description' => 'Bakımları yetkili serviste yapıldı, değişensiz, tek elden. Kesin dönüş nedeniyle acil satılıktır.',
            'price' => '14500',
            'currency' => 'EUR',
            'price_unit' => 'toplam',
            'country_code' => 'DE',
            'city' => 'Köln',
            'brand' => 'Volkswagen',
            'model' => 'Golf 1.6 TDI',
            'year' => '2019',
            'mileage_km' => '85000',
            'fuel' => 'dizel',
            'transmission' => 'manuel',
            'body_type' => 'hatchback',
            'color' => 'Gri',
            'badges' => ['kesin_donus', 'bakim_kayitli'],
        ], $overrides);
    }

    protected function makeVehicle(User $user, array $listingOverrides = [], array $detailOverrides = []): Listing
    {
        $listing = Listing::factory()->create(array_merge([
            'user_id' => $user->id,
            'type' => 'vasita',
            'category_id' => $this->vehicleCategoryId(),
            'price' => 14500,
            'price_unit' => 'toplam',
        ], $listingOverrides));

        ListingVehicleDetail::create(array_merge([
            'listing_id' => $listing->id,
            'brand' => 'Volkswagen',
            'model' => 'Golf',
            'year' => 2019,
            'mileage_km' => 85000,
            'fuel' => 'dizel',
            'transmission' => 'manuel',
        ], $detailOverrides));

        return $listing;
    }

    public function test_vehicle_category_seeder_creates_tree(): void
    {
        $root = Category::where('slug', 'vasita')->first();

        $this->assertNotNull($root);
        $this->assertSame('vasita', $root->type->value);
        $this->assertCount(2, Category::where('parent_id', $root->id)->get());
    }

    public function test_user_can_create_vehicle_listing_with_details(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->post('/panel/ilan', $this->validVehicleData())
            ->assertRedirect(route('panel.listings.index'));

        $listing = Listing::where('user_id', $user->id)->first();

        $this->assertSame('vasita', $listing->type->value);
        $this->assertNotNull($listing->vehicleDetail);
        $this->assertSame('Volkswagen', $listing->vehicleDetail->brand);
        $this->assertSame(2019, $listing->vehicleDetail->year);
        $this->assertSame(85000, $listing->vehicleDetail->mileage_km);
        $this->assertEqualsCanonicalizing(['kesin_donus', 'bakim_kayitli'], $listing->vehicleDetail->badges);
    }

    public function test_invalid_fuel_and_property_badge_are_rejected(): void
    {
        $this->actingAs($this->verifiedUser())
            ->post('/panel/ilan', $this->validVehicleData(['fuel' => 'komur']))
            ->assertSessionHasErrors('fuel');

        // Emlak rozeti vasıta ilanında geçersiz olmalı
        $this->actingAs($this->verifiedUser())
            ->post('/panel/ilan', $this->validVehicleData(['badges' => ['adres_kaydi']]))
            ->assertSessionHasErrors('badges.0');
    }

    public function test_vasita_page_renders_and_filters(): void
    {
        $user = $this->verifiedUser();
        $golf = $this->makeVehicle($user, ['title' => 'Satılık temiz Golf dizel manuel']);
        $tesla = $this->makeVehicle($user, ['title' => 'Satılık Tesla Model 3 uzun menzil'], ['brand' => 'Tesla', 'model' => 'Model 3', 'year' => 2023, 'mileage_km' => 20000, 'fuel' => 'elektrik', 'transmission' => 'otomatik']);

        $this->get('/vasita')
            ->assertOk()
            ->assertSee($golf->title)
            ->assertSee($tesla->title);

        $this->get('/vasita?yakit=elektrik')
            ->assertOk()
            ->assertSee($tesla->title)
            ->assertDontSee($golf->title);

        $this->get('/vasita?marka=Volkswagen')
            ->assertOk()
            ->assertSee($golf->title)
            ->assertDontSee($tesla->title);

        $this->get('/vasita?min_yil=2022&max_km=50000')
            ->assertOk()
            ->assertSee($tesla->title)
            ->assertDontSee($golf->title);
    }

    public function test_rental_date_filter_excludes_busy_vehicle(): void
    {
        $user = $this->verifiedUser();
        $rentalCat = $this->vehicleCategoryId('kiralik-arac');
        $busy = $this->makeVehicle($user, ['title' => 'Kiralık dolu passat aracı', 'category_id' => $rentalCat, 'price_unit' => 'gunluk', 'price' => 45]);
        $free = $this->makeVehicle($user, ['title' => 'Kiralık müsait corolla aracı', 'category_id' => $rentalCat, 'price_unit' => 'gunluk', 'price' => 40]);

        ListingUnavailableRange::create([
            'listing_id' => $busy->id,
            'starts_on' => '2027-08-10',
            'ends_on' => '2027-08-20',
        ]);

        $this->get('/vasita?giris=2027-08-12&cikis=2027-08-14')
            ->assertOk()
            ->assertSee($free->title)
            ->assertDontSee($busy->title);
    }

    public function test_rental_request_prefixes_message_with_daily_total(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->makeVehicle($owner, [
            'category_id' => $this->vehicleCategoryId('kiralik-arac'),
            'price' => 40,
            'price_unit' => 'gunluk',
        ]);
        $renter = $this->verifiedUser();

        $this->actingAs($renter)
            ->post("/ilan/{$listing->id}/mesaj", [
                'body' => 'Havalimanından teslim alabilir miyim?',
                'giris' => '2027-08-12',
                'cikis' => '2027-08-15',
            ])
            ->assertRedirect();

        $body = Message::latest('id')->first()->body;

        $this->assertStringContainsString('Müsaitlik talebi: 12.08.2027 → 15.08.2027 (3 gün)', $body);
        $this->assertStringContainsString('40 × 3 = 120 EUR', $body);
    }

    public function test_show_page_renders_vehicle_panel_and_warning(): void
    {
        $listing = $this->makeVehicle($this->verifiedUser());

        $this->get("/ilan/{$listing->id}/{$listing->slug}")
            ->assertOk()
            ->assertSee('Araç Özellikleri')
            ->assertSee('Volkswagen')
            ->assertSee('85.000 km')
            ->assertSee('Aracı görmeden asla kapora')
            ->assertSee('Müsaitlik');
    }

    public function test_owner_can_manage_vehicle_availability(): void
    {
        $user = $this->verifiedUser();
        $listing = $this->makeVehicle($user, ['category_id' => $this->vehicleCategoryId('kiralik-arac')]);

        $this->actingAs($user)
            ->post("/panel/ilan/{$listing->id}/takvim", [
                'starts_on' => '2027-08-10',
                'ends_on' => '2027-08-20',
            ])
            ->assertRedirect();

        $this->assertFalse($listing->isAvailableBetween('2027-08-12', '2027-08-14'));
    }
}

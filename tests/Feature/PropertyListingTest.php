<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPropertyDetail;
use App\Models\ListingUnavailableRange;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PropertyCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, PropertyCategorySeeder::class]);
    }

    protected function verifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
    }

    protected function propertyCategoryId(string $slug = 'kiralik-konut'): int
    {
        return Category::where('slug', $slug)->firstOrFail()->id;
    }

    protected function validPropertyData(array $overrides = []): array
    {
        return array_merge([
            'type' => 'emlak',
            'title' => 'Kreuzberg\'de eşyalı 2+1 kiralık daire',
            'category_id' => $this->propertyCategoryId(),
            'description' => 'Metroya 5 dakika, yeni tadilatlı, aydınlık ve sıcak bir ev arıyorsan burası tam sana göre.',
            'price' => '1200',
            'currency' => 'EUR',
            'price_unit' => 'aylik',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'rooms' => '2+1',
            'area_m2' => '85',
            'floor' => '3',
            'furnished' => '1',
            'deposit' => '2400',
            'badges' => ['adres_kaydi', 'kefilsiz'],
        ], $overrides);
    }

    protected function makeProperty(User $user, array $listingOverrides = [], array $detailOverrides = []): Listing
    {
        $listing = Listing::factory()->create(array_merge([
            'user_id' => $user->id,
            'type' => 'emlak',
            'category_id' => $this->propertyCategoryId(),
            'price' => 100,
            'price_unit' => 'gecelik',
        ], $listingOverrides));

        ListingPropertyDetail::create(array_merge([
            'listing_id' => $listing->id,
            'rooms' => '2+1',
            'area_m2' => 85,
            'furnished' => true,
            'max_guests' => 4,
        ], $detailOverrides));

        return $listing;
    }

    public function test_property_category_seeder_creates_tree(): void
    {
        $root = Category::where('slug', 'emlak')->first();

        $this->assertNotNull($root);
        $this->assertSame('emlak', $root->type->value);
        $this->assertCount(4, Category::where('parent_id', $root->id)->get());
    }

    public function test_user_can_create_property_listing_with_details(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->post('/panel/ilan', $this->validPropertyData())
            ->assertRedirect(route('panel.listings.index'));

        $listing = Listing::where('user_id', $user->id)->first();

        $this->assertSame('emlak', $listing->type->value);
        $this->assertNotNull($listing->propertyDetail);
        $this->assertSame('2+1', $listing->propertyDetail->rooms);
        $this->assertSame(85, $listing->propertyDetail->area_m2);
        $this->assertTrue($listing->propertyDetail->furnished);
        $this->assertEqualsCanonicalizing(['adres_kaydi', 'kefilsiz'], $listing->propertyDetail->badges);
    }

    public function test_property_detail_is_updated_on_edit(): void
    {
        $user = $this->verifiedUser();
        $listing = $this->makeProperty($user);

        $this->actingAs($user)
            ->put("/panel/ilan/{$listing->id}", $this->validPropertyData([
                'rooms' => '3+1',
                'area_m2' => '110',
                'furnished' => null,
                'badges' => [],
            ]))
            ->assertRedirect(route('panel.listings.index'));

        $detail = $listing->propertyDetail()->first();
        $this->assertSame('3+1', $detail->rooms);
        $this->assertSame(110, $detail->area_m2);
        $this->assertFalse($detail->furnished);
    }

    public function test_invalid_room_option_is_rejected(): void
    {
        $this->actingAs($this->verifiedUser())
            ->post('/panel/ilan', $this->validPropertyData(['rooms' => '9+9']))
            ->assertSessionHasErrors('rooms');
    }

    public function test_emlak_page_renders_and_lists_property(): void
    {
        $listing = $this->makeProperty($this->verifiedUser());

        $this->get('/emlak')
            ->assertOk()
            ->assertSee($listing->title)
            ->assertSee('Emlak İlanı Ver');
    }

    public function test_emlak_filters_by_rooms_and_furnished(): void
    {
        $user = $this->verifiedUser();
        $small = $this->makeProperty($user, ['title' => 'Küçük stüdyo daire merkezde'], ['rooms' => '1+0', 'furnished' => false]);
        $big = $this->makeProperty($user, ['title' => 'Geniş aile evi bahçeli'], ['rooms' => '3+1', 'furnished' => true]);

        $this->get('/emlak?oda=3%2B1')
            ->assertOk()
            ->assertSee($big->title)
            ->assertDontSee($small->title);

        $this->get('/emlak?esyali=1')
            ->assertOk()
            ->assertSee($big->title)
            ->assertDontSee($small->title);
    }

    public function test_emlak_short_term_date_filter_excludes_busy_listing(): void
    {
        $user = $this->verifiedUser();
        $busy = $this->makeProperty($user, ['title' => 'Dolu olan tatil evi deniz kenarı']);
        $free = $this->makeProperty($user, ['title' => 'Müsait olan tatil evi orman içi']);

        ListingUnavailableRange::create([
            'listing_id' => $busy->id,
            'starts_on' => '2027-08-10',
            'ends_on' => '2027-08-20',
        ]);

        $this->get('/emlak?giris=2027-08-15&cikis=2027-08-18')
            ->assertOk()
            ->assertSee($free->title)
            ->assertDontSee($busy->title);

        // Çakışmayan aralıkta ikisi de görünür
        $this->get('/emlak?giris=2027-09-01&cikis=2027-09-05')
            ->assertOk()
            ->assertSee($free->title)
            ->assertSee($busy->title);
    }

    public function test_owner_can_manage_availability_ranges(): void
    {
        $user = $this->verifiedUser();
        $listing = $this->makeProperty($user);

        $this->actingAs($user)
            ->post("/panel/ilan/{$listing->id}/takvim", [
                'starts_on' => '2027-08-10',
                'ends_on' => '2027-08-20',
                'note' => 'aile geliyor',
            ])
            ->assertRedirect();

        $range = $listing->unavailableRanges()->first();
        $this->assertNotNull($range);
        $this->assertFalse($listing->isAvailableBetween('2027-08-15', '2027-08-18'));

        $this->actingAs($user)
            ->delete("/panel/ilan/{$listing->id}/takvim/{$range->id}")
            ->assertRedirect();

        $this->assertTrue($listing->isAvailableBetween('2027-08-15', '2027-08-18'));
    }

    public function test_non_owner_cannot_manage_availability(): void
    {
        $listing = $this->makeProperty($this->verifiedUser());
        $intruder = $this->verifiedUser();

        $this->actingAs($intruder)
            ->post("/panel/ilan/{$listing->id}/takvim", [
                'starts_on' => '2027-08-10',
                'ends_on' => '2027-08-20',
            ])
            ->assertForbidden();
    }

    public function test_availability_request_prefixes_message(): void
    {
        $owner = $this->verifiedUser();
        $listing = $this->makeProperty($owner, ['price' => 50, 'price_unit' => 'gecelik']);
        $guest = $this->verifiedUser();

        $this->actingAs($guest)
            ->post("/ilan/{$listing->id}/mesaj", [
                'body' => 'Merhaba, evi çok beğendik.',
                'giris' => '2027-08-15',
                'cikis' => '2027-08-18',
                'kisi' => 2,
            ])
            ->assertRedirect();

        $body = Message::latest('id')->first()->body;

        $this->assertStringContainsString('Müsaitlik talebi: 15.08.2027 → 18.08.2027 (3 gece)', $body);
        $this->assertStringContainsString('2 kişi', $body);
        $this->assertStringContainsString('50 × 3 = 150 EUR', $body);
        $this->assertStringContainsString('Merhaba, evi çok beğendik.', $body);
    }

    public function test_show_page_renders_property_panel_calendar_and_warning(): void
    {
        $listing = $this->makeProperty($this->verifiedUser());

        $this->get("/ilan/{$listing->id}/{$listing->slug}")
            ->assertOk()
            ->assertSee('Emlak Özellikleri')
            ->assertSee('2+1')
            ->assertSee('85 m²')
            ->assertSee('Müsaitlik')
            ->assertSee('Evi görmeden asla kapora');
    }
}

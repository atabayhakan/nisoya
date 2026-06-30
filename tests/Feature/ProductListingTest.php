<?php

namespace Tests\Feature;

use App\Enums\PriceUnit;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class, ProductCategorySeeder::class]);
    }

    public function test_product_categories_are_seeded(): void
    {
        $this->assertDatabaseHas('categories', ['slug' => 'baklava-tatli', 'type' => 'urun']);
        $this->assertDatabaseHas('categories', ['slug' => 'el-yapimi-yiyecek', 'type' => 'urun', 'parent_id' => null]);
    }

    public function test_price_unit_for_type(): void
    {
        $this->assertContains(PriceUnit::Adet, PriceUnit::forType('urun'));
        $this->assertNotContains(PriceUnit::Saatlik, PriceUnit::forType('urun'));
        $this->assertContains(PriceUnit::Saatlik, PriceUnit::forType('hizmet'));
    }

    public function test_product_create_form_loads(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/panel/ilan/yeni?tip=urun')
            ->assertOk()
            ->assertSee('Yeni Ürün İlanı')
            ->assertSee('Stok adedi');
    }

    public function test_user_can_create_product_listing(): void
    {
        $user = User::factory()->create();
        $category = Category::where('type', 'urun')->whereNotNull('parent_id')->first();

        $response = $this->actingAs($user)->post('/panel/ilan', [
            'type' => 'urun',
            'title' => 'Ev yapımı fıstıklı baklava',
            'category_id' => $category->id,
            'description' => 'Taze, günlük hazırlanan ev yapımı fıstıklı baklava. Kargo ile gönderilir.',
            'price' => '18',
            'currency' => 'EUR',
            'price_unit' => 'adet',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'stock' => '25',
        ]);

        $response->assertRedirect(route('panel.listings.index'));

        $listing = Listing::first();
        $this->assertSame('urun', $listing->type->value);
        $this->assertSame(25, $listing->stock);
        $this->assertFalse($listing->is_remote);
    }

    public function test_browse_type_filter_separates_services_and_products(): void
    {
        $user = User::factory()->create();
        Listing::factory()->for($user)->create(['type' => 'hizmet', 'title' => 'Hizmet ilani XYZ', 'status' => 'aktif']);
        Listing::factory()->for($user)->create(['type' => 'urun', 'title' => 'Urun ilani XYZ', 'status' => 'aktif', 'stock' => 5]);

        $this->get('/ilanlar?tip=urun')
            ->assertOk()
            ->assertSee('Urun ilani XYZ')
            ->assertDontSee('Hizmet ilani XYZ');

        $this->get('/ilanlar?tip=hizmet')
            ->assertSee('Hizmet ilani XYZ')
            ->assertDontSee('Urun ilani XYZ');
    }

    public function test_product_detail_shows_stock(): void
    {
        $listing = Listing::factory()->for(User::factory())->create([
            'type' => 'urun', 'status' => 'aktif', 'stock' => 12, 'is_remote' => false,
        ]);

        $this->get(route('listings.show', [$listing, $listing->slug]))
            ->assertOk()
            ->assertSee('Stokta 12 adet');
    }
}

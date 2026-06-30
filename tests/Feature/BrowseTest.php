<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowseTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        $this->seller = User::factory()->create();
    }

    protected function listing(array $attrs = []): Listing
    {
        return Listing::factory()->for($this->seller)->create(array_merge(['status' => 'aktif', 'is_featured' => false], $attrs));
    }

    public function test_index_shows_only_active_listings(): void
    {
        $this->listing(['title' => 'Aktif ilan başlığı']);
        $this->listing(['title' => 'Bekleyen ilan başlığı', 'status' => 'beklemede']);

        $this->get('/ilanlar')
            ->assertOk()
            ->assertSee('Aktif ilan başlığı')
            ->assertDontSee('Bekleyen ilan başlığı');
    }

    public function test_keyword_search(): void
    {
        $this->listing(['title' => 'İngilizce konuşma dersi']);
        $this->listing(['title' => 'Nakliyat ve taşıma']);

        $this->get('/ilanlar?q=İngilizce')
            ->assertSee('İngilizce konuşma dersi')
            ->assertDontSee('Nakliyat ve taşıma');
    }

    public function test_country_filter(): void
    {
        $this->listing(['title' => 'Berlindeki ilan', 'country_code' => 'DE']);
        $this->listing(['title' => 'Londradaki ilan', 'country_code' => 'GB']);

        $this->get('/ilanlar?ulke=GB')
            ->assertSee('Londradaki ilan')
            ->assertDontSee('Berlindeki ilan');
    }

    public function test_remote_filter(): void
    {
        $this->listing(['title' => 'Online verilen hizmet', 'is_remote' => true]);
        $this->listing(['title' => 'Yerinde verilen hizmet', 'is_remote' => false]);

        $this->get('/ilanlar?uzaktan=1')
            ->assertSee('Online verilen hizmet')
            ->assertDontSee('Yerinde verilen hizmet');
    }

    public function test_category_page_includes_child_categories(): void
    {
        $parent = Category::whereNull('parent_id')->orderBy('sort_order')->first();
        $child = $parent->children()->first();
        $otherParent = Category::whereNull('parent_id')->where('id', '!=', $parent->id)->first();
        $otherChild = $otherParent->children()->first();

        $this->listing(['title' => 'Bu kategoride', 'category_id' => $child->id]);
        $this->listing(['title' => 'Baska kategoride', 'category_id' => $otherChild->id]);

        $this->get(route('listings.category', $parent->slug))
            ->assertOk()
            ->assertSee('Bu kategoride')
            ->assertDontSee('Baska kategoride');
    }

    public function test_price_ascending_sort(): void
    {
        $this->listing(['title' => 'Ucuz hizmet AAA', 'price' => 10]);
        $this->listing(['title' => 'Pahali hizmet ZZZ', 'price' => 500]);

        $this->get('/ilanlar?sirala=fiyat_artan')
            ->assertSeeInOrder(['Ucuz hizmet AAA', 'Pahali hizmet ZZZ']);
    }

    public function test_seller_profile_shows_active_listings_only(): void
    {
        $this->listing(['title' => 'Saticinin aktif ilani']);
        $this->listing(['title' => 'Saticinin gizli ilani', 'status' => 'beklemede']);

        $this->get(route('profiles.show', $this->seller->username))
            ->assertOk()
            ->assertSee($this->seller->name)
            ->assertSee('Saticinin aktif ilani')
            ->assertDontSee('Saticinin gizli ilani');
    }

    public function test_profile_returns_404_for_unknown_user(): void
    {
        $this->get('/uye/boyle-bir-kullanici-yok')->assertNotFound();
    }
}

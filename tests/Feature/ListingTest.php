<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function verifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
    }

    protected function leafCategoryId(): int
    {
        return Category::whereNotNull('parent_id')->first()->id;
    }

    protected function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Online İngilizce konuşma dersi',
            'category_id' => $this->leafCategoryId(),
            'description' => 'Yıllardır İngilizce öğretiyorum, online konuşma pratiği yaptırıyorum.',
            'price' => '25',
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'is_remote' => '1',
        ], $overrides);
    }

    public function test_guest_cannot_open_create_form(): void
    {
        $this->get('/panel/ilan/yeni')->assertRedirect(route('login'));
    }

    public function test_verified_user_can_open_create_form(): void
    {
        $this->actingAs($this->verifiedUser())->get('/panel/ilan/yeni')->assertOk()->assertSee('Yeni İlan Ver');
    }

    public function test_user_can_create_listing_with_image(): void
    {
        Storage::fake('public');
        $user = $this->verifiedUser();

        $response = $this->actingAs($user)->post('/panel/ilan', $this->validData([
            'images' => [UploadedFile::fake()->image('foto.jpg')],
        ]));

        $response->assertRedirect(route('panel.listings.index'));

        $listing = Listing::first();
        $this->assertNotNull($listing);
        $this->assertSame($user->id, $listing->user_id);
        $this->assertSame('aktif', $listing->status->value);
        $this->assertSame('hizmet', $listing->type->value);
        $this->assertNotEmpty($listing->slug);

        $this->assertCount(1, $listing->images);
        $this->assertTrue($listing->images->first()->is_cover);
        Storage::disk('public')->assertExists($listing->images->first()->path);
    }

    public function test_listing_creation_validates_input(): void
    {
        $this->actingAs($this->verifiedUser())
            ->post('/panel/ilan', $this->validData(['title' => '', 'description' => 'kısa']))
            ->assertSessionHasErrors(['title', 'description']);

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_user_can_update_own_listing(): void
    {
        $user = $this->verifiedUser();
        $listing = Listing::factory()->for($user)->create(['title' => 'Eski başlık']);

        $this->actingAs($user)
            ->put('/panel/ilan/'.$listing->id, $this->validData(['title' => 'Yeni başlık']))
            ->assertRedirect(route('panel.listings.index'));

        $this->assertSame('Yeni başlık', $listing->fresh()->title);
    }

    public function test_user_cannot_edit_or_delete_others_listing(): void
    {
        $owner = $this->verifiedUser();
        $other = $this->verifiedUser();
        $listing = Listing::factory()->for($owner)->create();

        $this->actingAs($other)->get('/panel/ilan/'.$listing->id.'/duzenle')->assertForbidden();
        $this->actingAs($other)->put('/panel/ilan/'.$listing->id, $this->validData())->assertForbidden();
        $this->actingAs($other)->delete('/panel/ilan/'.$listing->id)->assertForbidden();
    }

    public function test_user_can_delete_own_listing(): void
    {
        $user = $this->verifiedUser();
        $listing = Listing::factory()->for($user)->create();

        $this->actingAs($user)->delete('/panel/ilan/'.$listing->id)->assertRedirect(route('panel.listings.index'));

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
    }

    public function test_public_can_view_active_listing_and_views_increment(): void
    {
        $listing = Listing::factory()->for($this->verifiedUser())->create([
            'status' => 'aktif',
            'views_count' => 0,
        ]);

        $this->get(route('listings.show', [$listing, $listing->slug]))
            ->assertOk()
            ->assertSee($listing->title);

        $this->assertSame(1, $listing->fresh()->views_count);
    }

    public function test_non_active_listing_is_hidden_from_public_but_visible_to_owner(): void
    {
        $owner = $this->verifiedUser();
        $listing = Listing::factory()->for($owner)->create(['status' => 'beklemede']);

        $this->get(route('listings.show', $listing))->assertNotFound();
        $this->actingAs($owner)->get(route('listings.show', $listing))->assertOk();
    }
}

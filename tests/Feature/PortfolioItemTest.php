<?php

namespace Tests\Feature;

use App\Http\Controllers\PortfolioItemController;
use App\Models\PortfolioItem;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Satıcının profil portfolyosuna (geçmiş iş örnekleri) görsel ekleyip
 * kaldırması. Bkz. PaymentLinkTest — aynı desen.
 */
class PortfolioItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        Storage::fake('public');
    }

    public function test_user_can_add_a_portfolio_item(): void
    {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('mutfak-tadilat.jpg', 1200, 900);

        $this->actingAs($user)->post('/panel/profil/portfolyo', [
            'image' => $image,
            'caption' => 'Mutfak tadilatı, 2026',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $item = PortfolioItem::where('user_id', $user->id)->first();
        $this->assertNotNull($item);
        $this->assertSame('Mutfak tadilatı, 2026', $item->caption);
        $this->assertNotNull($item->path_thumb);
        $this->assertNotNull($item->path_medium);
        $this->assertNotNull($item->path_large);
        Storage::disk('public')->assertExists($item->path_large);
    }

    public function test_portfolio_item_caption_is_optional(): void
    {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('is-ornegi.jpg', 800, 600);

        $this->actingAs($user)->post('/panel/profil/portfolyo', [
            'image' => $image,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('portfolio_items', ['user_id' => $user->id, 'caption' => null]);
    }

    public function test_image_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/panel/profil/portfolyo', [
            'caption' => 'Görsel yok',
        ])->assertSessionHasErrors('image');
    }

    public function test_user_cannot_exceed_max_portfolio_items(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < PortfolioItemController::MAX_ITEMS; $i++) {
            PortfolioItem::create([
                'user_id' => $user->id,
                'path_thumb' => "portfolio/thumb/{$i}.webp",
                'path_medium' => "portfolio/medium/{$i}.webp",
                'path_large' => "portfolio/large/{$i}.webp",
                'sort_order' => $i,
            ]);
        }

        $image = UploadedFile::fake()->image('fazla.jpg', 800, 600);

        $this->actingAs($user)->post('/panel/profil/portfolyo', [
            'image' => $image,
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('portfolio_items', PortfolioItemController::MAX_ITEMS);
    }

    public function test_user_can_remove_own_portfolio_item(): void
    {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('kaldirilacak.jpg', 800, 600);
        $this->actingAs($user)->post('/panel/profil/portfolyo', ['image' => $image]);
        $item = PortfolioItem::where('user_id', $user->id)->first();

        $this->actingAs($user)->delete("/panel/profil/portfolyo/{$item->id}")->assertRedirect();

        $this->assertDatabaseMissing('portfolio_items', ['id' => $item->id]);
    }

    public function test_removing_portfolio_item_deletes_image_files(): void
    {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('silinecek.jpg', 800, 600);
        $this->actingAs($user)->post('/panel/profil/portfolyo', ['image' => $image]);
        $item = PortfolioItem::where('user_id', $user->id)->first();
        $largePath = $item->path_large;

        $this->actingAs($user)->delete("/panel/profil/portfolyo/{$item->id}");

        Storage::disk('public')->assertMissing($largePath);
    }

    public function test_user_cannot_remove_someone_elses_portfolio_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = PortfolioItem::create([
            'user_id' => $owner->id,
            'path_thumb' => 'portfolio/thumb/x.webp',
            'path_medium' => 'portfolio/medium/x.webp',
            'path_large' => 'portfolio/large/x.webp',
        ]);

        $this->actingAs($intruder)->delete("/panel/profil/portfolyo/{$item->id}")->assertForbidden();

        $this->assertDatabaseHas('portfolio_items', ['id' => $item->id]);
    }

    public function test_portfolio_items_show_on_public_profile(): void
    {
        $user = User::factory()->create(['username' => 'portfolyo-test-satici']);
        PortfolioItem::create([
            'user_id' => $user->id,
            'path_thumb' => 'portfolio/thumb/x.webp',
            'path_medium' => 'portfolio/medium/x.webp',
            'path_large' => 'portfolio/large/x.webp',
            'caption' => 'Bahçe düzenleme',
        ]);

        $response = $this->get('/uye/portfolyo-test-satici');

        $response->assertOk();
        $response->assertSee('Portfolyo');
        $response->assertSee('Bahçe düzenleme');
    }

    public function test_skills_show_as_badges_on_public_profile(): void
    {
        $user = User::factory()->create(['username' => 'yetenek-test-satici', 'skills' => ['İngilizce', 'Web Tasarım']]);

        $response = $this->get('/uye/yetenek-test-satici');

        $response->assertOk();
        $response->assertSee('İngilizce');
        $response->assertSee('Web Tasarım');
    }
}

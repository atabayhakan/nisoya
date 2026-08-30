<?php

namespace Tests\Feature\Football;

use App\Models\FootballVenue;
use App\Models\FootballVenueReview;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FootballVenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_user_can_create_a_venue_and_add_review(): void
    {
        $user = User::factory()->create(['status' => 'aktif']);

        $response = $this->actingAs($user)->post(route('football.venues.store'), [
            'name' => 'Kreuzberg Arena Halı Saha',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'address' => 'Gneisenaustr. 45, 10961 Berlin',
            'pitch_type' => 'kapali',
            'surface_type' => 'suni_cim',
            'features' => ['soyunma_odasi', 'dus', 'otopark'],
            'price_info' => 'Saatlik 90€',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('football_venues', [
            'name' => 'Kreuzberg Arena Halı Saha',
            'city' => 'Berlin',
            'pitch_type' => 'kapali',
        ]);

        $venue = FootballVenue::where('name', 'Kreuzberg Arena Halı Saha')->firstOrFail();

        // İkinci bir kullanıcı yorum yazar
        $reviewer = User::factory()->create(['status' => 'aktif']);

        $reviewResponse = $this->actingAs($reviewer)->post(route('football.venues.review', $venue), [
            'rating' => 5,
            'saha_kalitesi' => 5,
            'temizlik' => 4,
            'comment' => 'Harika bir saha, çok memnun kaldık.',
        ]);

        $reviewResponse->assertRedirect();

        $this->assertDatabaseHas('football_venue_reviews', [
            'venue_id' => $venue->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
        ]);

        $this->assertEquals(5.00, $venue->fresh()->rating);
        $this->assertEquals(1, $venue->fresh()->reviews_count);
    }
}

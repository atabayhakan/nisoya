<?php

namespace Tests\Feature\Football;

use App\Models\Country;
use App\Models\FootballVenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FootballVenueMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_venue_distance_calculation_and_map_urls(): void
    {
        $venue = new FootballVenue([
            'name' => 'Kadıköy Halı Saha',
            'city' => 'İstanbul',
            'address' => 'Moda Cad. No: 10',
            'latitude' => 40.9900,
            'longitude' => 29.0250,
        ]);

        $this->assertNotNull($venue->getGoogleMapsUrl());
        $this->assertStringContainsString('40.99,29.025', $venue->getGoogleMapsUrl());

        $this->assertNotNull($venue->getYandexMapsUrl());
        $this->assertStringContainsString('40.99,29.025', $venue->getYandexMapsUrl());

        // ~1 km yakınında bir nokta (40.9850, 29.0250)
        $distance = $venue->distanceFrom(40.9850, 29.0250);
        $this->assertNotNull($distance);
        $this->assertLessThan(2.0, $distance);
        $this->assertGreaterThan(0.1, $distance);
    }

    public function test_ai_recommend_endpoint_returns_smart_suggestions(): void
    {
        $creator = User::factory()->create();
        Country::query()->firstOrCreate(['code' => 'DE'], ['name_tr' => 'Almanya', 'name_en' => 'Germany', 'name_original' => 'Deutschland', 'currency' => 'EUR', 'is_active' => true]);

        FootballVenue::create([
            'created_by_id' => $creator->id,
            'name' => 'Berlin Arena Kapalı',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'address' => 'Kreuzberg 1',
            'pitch_type' => 'kapali',
            'surface_type' => 'suni_cim',
            'features' => ['dus', 'otopark', 'gece_aydinlatmasi'],
            'latitude' => 52.5200,
            'longitude' => 13.4050,
            'rating' => 4.8,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('football.venues.ai-recommend'), [
            'city' => 'Berlin',
            'pitch_type' => 'kapali',
            'surface_type' => 'suni_cim',
            'features' => ['dus', 'otopark'],
            'lat' => 52.5180,
            'lng' => 13.4020,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'count',
            'recommendations' => [
                '*' => [
                    'id',
                    'name',
                    'city',
                    'rating',
                    'distance_text',
                    'ai_comment',
                    'google_maps_url',
                    'url',
                ],
            ],
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(1, $response->json('count'));
    }
}

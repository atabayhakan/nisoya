<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_matches_listing_by_keywords_and_country(): void
    {
        $alert = ListingAlert::create([
            'email' => 'test@example.com',
            'keywords' => 'nakliye',
            'country_code' => 'DE',
            'type' => 'hizmet',
        ]);

        $user = User::factory()->create();

        $matchingListing = Listing::factory()->create([
            'user_id' => $user->id,
            'title' => 'Berlin İçi Nakliye Hizmeti',
            'country_code' => 'DE',
            'type' => 'hizmet',
        ]);

        $nonMatchingListing = Listing::factory()->create([
            'user_id' => $user->id,
            'title' => 'Londra Web Tasarım',
            'country_code' => 'GB',
            'type' => 'hizmet',
        ]);

        $this->assertTrue($alert->matchesListing($matchingListing));
        $this->assertFalse($alert->matchesListing($nonMatchingListing));
    }
}

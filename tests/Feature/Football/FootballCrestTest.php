<?php

namespace Tests\Feature\Football;

use App\Models\Country;
use App\Models\User;
use App\Services\Football\FootballCrestGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FootballCrestTest extends TestCase
{
    use RefreshDatabase;

    public function test_crest_generator_service_generates_valid_svg(): void
    {
        Storage::fake('public');

        $service = new FootballCrestGeneratorService;
        $result = $service->generateAndStore(
            teamName: 'Berlin Hilalspor',
            city: 'Berlin',
            primaryColor: '#dc2626',
            secondaryColor: '#f8fafc',
            symbol: 'hilal',
            style: 'klasik_kalkan'
        );

        $this->assertNotEmpty($result['path']);
        $this->assertNotEmpty($result['url']);
        $this->assertNotEmpty($result['svg']);
        $this->assertEquals('BHI', $result['initials']);
        $this->assertStringContainsString('<svg', $result['svg']);
        $this->assertStringContainsString('BERLIN HILALSPOR', $result['svg']);

        Storage::disk('public')->assertExists($result['path']);
    }

    public function test_authenticated_user_can_request_ai_logo_via_endpoint(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->postJson(route('football.teams.ai-logo'), [
            'name' => 'Kreuzberg Kaplanları',
            'city' => 'Berlin',
            'primary_color' => 'Sarı',
            'secondary_color' => 'Siyah',
            'symbol' => 'aslan',
            'style' => 'klasik_kalkan',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'path',
            'url',
            'svg',
            'initials',
        ]);

        $this->assertTrue($response->json('success'));
        Storage::disk('public')->assertExists($response->json('path'));
    }

    public function test_team_can_be_created_with_ai_generated_logo_path(): void
    {
        Storage::fake('public');
        Country::firstOrCreate(
            ['code' => 'TR'],
            ['name_tr' => 'Türkiye', 'name_en' => 'Turkey', 'name_original' => 'Türkiye', 'currency' => 'TRY', 'is_active' => true]
        );

        $user = User::factory()->create(['email_verified_at' => now()]);

        $service = new FootballCrestGeneratorService;
        $crest = $service->generateAndStore('Boğaziçi FC', 'İstanbul', '#2563eb', '#ffffff', 'kartal');

        $response = $this->actingAs($user)->post(route('football.teams.store'), [
            'name' => 'Boğaziçi FC',
            'city' => 'İstanbul',
            'country_code' => 'TR',
            'level' => 'orta',
            'primary_kit_color' => 'Mavi',
            'secondary_kit_color' => 'Beyaz',
            'ai_logo_path' => $crest['path'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('football_teams', [
            'name' => 'Boğaziçi FC',
            'city' => 'İstanbul',
            'logo_path' => $crest['path'],
        ]);
    }
}

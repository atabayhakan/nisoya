<?php

namespace Tests\Feature\Football;

use App\Enums\FootballLevel;
use App\Enums\FootballMatchStatus;
use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\FootballVenue;
use App\Models\User;
use App\Services\Football\FootballNewsService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FootballNewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_news_service_generates_factual_sports_news_and_whatsapp_text(): void
    {
        $homeUser = User::factory()->create(['name' => 'Ahmet Kaptan']);
        $awayUser = User::factory()->create(['name' => 'Mehmet Kaptan']);

        $homeTeam = FootballTeam::create(['user_id' => $homeUser->id, 'name' => 'Berlin Kartalları', 'city' => 'Berlin', 'country_code' => 'DE', 'level' => FootballLevel::Iyi]);
        $awayTeam = FootballTeam::create(['user_id' => $awayUser->id, 'name' => 'Köln Kaplanları', 'city' => 'Berlin', 'country_code' => 'DE', 'level' => FootballLevel::Iyi]);
        $venue = FootballVenue::create(['name' => 'Arena Soccer Berlin', 'city' => 'Berlin', 'country_code' => 'DE', 'address' => 'Hauptstr. 1']);

        $match = FootballMatch::create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'venue_id' => $venue->id,
            'city' => 'Berlin',
            'country_code' => 'DE',
            'match_date' => now()->subDay(),
            'status' => FootballMatchStatus::Oynandi->value,
            'home_score' => 7,
            'away_score' => 5,
            'result_status' => FootballResultStatus::Dogrulandi->value,
            'mvp_player_id' => $homeUser->id,
        ]);

        $newsService = app(FootballNewsService::class);
        $news = $newsService->generateMatchNews($match);

        $this->assertNotEmpty($news['title']);
        $this->assertNotEmpty($news['summary']);
        $this->assertNotEmpty($news['body']);

        $this->assertStringContainsString('Berlin', $news['title']);
        $this->assertStringContainsString('7', $news['title']);
        $this->assertStringContainsString('5', $news['title']);

        $whatsAppText = $newsService->generateWhatsAppShareText($match);
        $this->assertStringContainsString('Berlin Kartalları 7 — 5 Köln Kaplanları', $whatsAppText);
        $this->assertStringContainsString('Arena Soccer Berlin', $whatsAppText);
    }
}

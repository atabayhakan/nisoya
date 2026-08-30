<?php

namespace Tests\Feature\Football;

use App\Enums\FootballLevel;
use App\Enums\FootballMatchStatus;
use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\User;
use App\Services\Football\FootballLeagueService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FootballLeagueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_league_standings_rank_teams_by_points_and_goal_difference(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $teamA = FootballTeam::create(['user_id' => $user1->id, 'name' => 'Takım A', 'city' => 'Berlin', 'country_code' => 'DE', 'level' => FootballLevel::Orta]);
        $teamB = FootballTeam::create(['user_id' => $user2->id, 'name' => 'Takım B', 'city' => 'Berlin', 'country_code' => 'DE', 'level' => FootballLevel::Orta]);
        $teamC = FootballTeam::create(['user_id' => $user3->id, 'name' => 'Takım C', 'city' => 'Berlin', 'country_code' => 'DE', 'level' => FootballLevel::Orta]);

        // Maç 1: Takım A (5) - Takım B (2) [Doğrulanmış] -> A: 3p (+3 av), B: 0p (-3 av)
        FootballMatch::create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'city' => 'Berlin',
            'country_code' => 'DE',
            'match_date' => now()->subDays(2),
            'status' => FootballMatchStatus::Oynandi->value,
            'home_score' => 5,
            'away_score' => 2,
            'result_status' => FootballResultStatus::Dogrulandi->value,
        ]);

        // Maç 2: Takım C (2) - Takım B (2) [Doğrulanmış] -> C: 1p (0 av), B: 1p (-3 av)
        FootballMatch::create([
            'home_team_id' => $teamC->id,
            'away_team_id' => $teamB->id,
            'city' => 'Berlin',
            'country_code' => 'DE',
            'match_date' => now()->subDays(1),
            'status' => FootballMatchStatus::Oynandi->value,
            'home_score' => 2,
            'away_score' => 2,
            'result_status' => FootballResultStatus::Dogrulandi->value,
        ]);

        $leagueService = app(FootballLeagueService::class);
        $standings = $leagueService->getCityStandings('Berlin');

        $this->assertCount(3, $standings);

        // 1. Sıra: Takım A (3 puan)
        $this->assertEquals($teamA->id, $standings[0]['team']->id);
        $this->assertEquals(3, $standings[0]['points']);
        $this->assertEquals(3, $standings[0]['goal_diff']);

        // 2. Sıra: Takım C (1 puan, 0 av)
        $this->assertEquals($teamC->id, $standings[1]['team']->id);
        $this->assertEquals(1, $standings[1]['points']);
        $this->assertEquals(0, $standings[1]['goal_diff']);

        // 3. Sıra: Takım B (1 puan, -3 av)
        $this->assertEquals($teamB->id, $standings[2]['team']->id);
        $this->assertEquals(1, $standings[2]['points']);
        $this->assertEquals(-3, $standings[2]['goal_diff']);
    }
}

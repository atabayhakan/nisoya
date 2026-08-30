<?php

namespace Tests\Feature\Football;

use App\Enums\FootballLevel;
use App\Enums\FootballMatchStatus;
use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\FootballTeamMember;
use App\Models\User;
use App\Notifications\FootballMatchResultNotification;
use App\Notifications\FootballMatchVerifiedNotification;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FootballScoreVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    /** @return array{homeCaptain: User, awayCaptain: User, homeTeam: FootballTeam, awayTeam: FootballTeam, match: FootballMatch} */
    private function matchScenario(): array
    {
        $homeCaptain = User::factory()->create(['status' => 'aktif']);
        $awayCaptain = User::factory()->create(['status' => 'aktif']);

        $homeTeam = FootballTeam::create([
            'user_id' => $homeCaptain->id,
            'name' => 'Berlin Hilalspor',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'level' => FootballLevel::Iyi,
        ]);

        FootballTeamMember::create([
            'team_id' => $homeTeam->id,
            'user_id' => $homeCaptain->id,
            'role' => 'captain',
            'status' => 'aktif',
        ]);

        $awayTeam = FootballTeam::create([
            'user_id' => $awayCaptain->id,
            'name' => 'Kreuzberg United',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'level' => FootballLevel::Iyi,
        ]);

        FootballTeamMember::create([
            'team_id' => $awayTeam->id,
            'user_id' => $awayCaptain->id,
            'role' => 'captain',
            'status' => 'aktif',
        ]);

        $match = FootballMatch::create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'city' => 'Berlin',
            'country_code' => 'DE',
            'match_date' => now()->subHours(2),
            'status' => FootballMatchStatus::Planlandi->value,
            'result_status' => FootballResultStatus::Beklemede->value,
        ]);

        return compact('homeCaptain', 'awayCaptain', 'homeTeam', 'awayTeam', 'match');
    }

    public function test_home_captain_submits_score_and_away_captain_is_notified(): void
    {
        Notification::fake();
        ['homeCaptain' => $homeCaptain, 'awayCaptain' => $awayCaptain, 'match' => $match] = $this->matchScenario();

        $response = $this->actingAs($homeCaptain)->post(route('football.matches.score-submit', $match), [
            'home_score' => 6,
            'away_score' => 4,
            'mvp_player_id' => $homeCaptain->id,
        ]);

        $response->assertRedirect();

        $match->refresh();
        $this->assertEquals(6, $match->home_score);
        $this->assertEquals(4, $match->away_score);
        $this->assertEquals(FootballResultStatus::Girildi, $match->result_status);
        $this->assertEquals($homeCaptain->id, $match->result_submitted_by_id);
        $this->assertNotEmpty($match->news_title);

        Notification::assertSentTo($awayCaptain, FootballMatchResultNotification::class);
    }

    public function test_submitter_cannot_self_verify_the_score(): void
    {
        ['homeCaptain' => $homeCaptain, 'match' => $match] = $this->matchScenario();

        $match->update([
            'home_score' => 5,
            'away_score' => 3,
            'result_status' => FootballResultStatus::Girildi->value,
            'result_submitted_by_id' => $homeCaptain->id,
        ]);

        // Ev sahibi kaptan kendi girdiği skoru onaylamaya çalışırsa 403 almalı
        $response = $this->actingAs($homeCaptain)->post(route('football.matches.score-verify', $match));
        $response->assertForbidden();

        $this->assertEquals(FootballResultStatus::Girildi, $match->fresh()->result_status);
    }

    public function test_opposing_captain_confirms_score_and_standings_update(): void
    {
        Notification::fake();
        ['homeCaptain' => $homeCaptain, 'awayCaptain' => $awayCaptain, 'homeTeam' => $homeTeam, 'awayTeam' => $awayTeam, 'match' => $match] = $this->matchScenario();

        $match->update([
            'home_score' => 5,
            'away_score' => 3,
            'result_status' => FootballResultStatus::Girildi->value,
            'result_submitted_by_id' => $homeCaptain->id,
        ]);

        $response = $this->actingAs($awayCaptain)->post(route('football.matches.score-verify', $match));
        $response->assertRedirect();

        $match->refresh();
        $this->assertEquals(FootballResultStatus::Dogrulandi, $match->result_status);
        $this->assertEquals(FootballMatchStatus::Oynandi, $match->status);

        // Ev sahibi kazandı: 3 puan, Deplasman kaybetti: 0 puan
        $homeTeam->refresh();
        $awayTeam->refresh();

        $this->assertEquals(3, $homeTeam->points);
        $this->assertEquals(1, $homeTeam->matches_count);
        $this->assertEquals(1, $homeTeam->wins_count);
        $this->assertEquals(5, $homeTeam->goals_for);
        $this->assertEquals(3, $homeTeam->goals_against);

        $this->assertEquals(0, $awayTeam->points);
        $this->assertEquals(1, $awayTeam->matches_count);
        $this->assertEquals(1, $awayTeam->losses_count);

        Notification::assertSentTo($homeCaptain, FootballMatchVerifiedNotification::class);
    }

    public function test_opposing_captain_can_dispute_score(): void
    {
        ['homeCaptain' => $homeCaptain, 'awayCaptain' => $awayCaptain, 'match' => $match] = $this->matchScenario();

        $match->update([
            'home_score' => 5,
            'away_score' => 3,
            'result_status' => FootballResultStatus::Girildi->value,
            'result_submitted_by_id' => $homeCaptain->id,
        ]);

        $response = $this->actingAs($awayCaptain)->post(route('football.matches.score-dispute', $match), [
            'dispute_reason' => 'Gerçek skor 4-4 berabereydi, son gol geçersizdi.',
        ]);

        $response->assertRedirect();

        $match->refresh();
        $this->assertEquals(FootballResultStatus::Itiraz, $match->result_status);
        $this->assertEquals('Gerçek skor 4-4 berabereydi, son gol geçersizdi.', $match->dispute_reason);
    }
}

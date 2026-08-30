<?php

namespace Tests\Feature\Football;

use App\Enums\FootballLevel;
use App\Enums\FootballMemberStatus;
use App\Models\FootballTeam;
use App\Models\FootballTeamMember;
use App\Models\User;
use App\Notifications\FootballTeamInviteNotification;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FootballTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_authenticated_user_can_create_a_team_and_becomes_captain(): void
    {
        $user = User::factory()->create(['status' => 'aktif']);

        $response = $this->actingAs($user)->post(route('football.teams.store'), [
            'name' => 'Berlin Kaplanları',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'level' => 'orta',
            'primary_kit_color' => 'Sarı',
            'secondary_kit_color' => 'Lacivert',
            'description' => 'Berlin merkezli halı saha takımı.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('football_teams', [
            'user_id' => $user->id,
            'name' => 'Berlin Kaplanları',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'level' => 'orta',
        ]);

        $team = FootballTeam::where('name', 'Berlin Kaplanları')->firstOrFail();

        $this->assertDatabaseHas('football_team_members', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'captain',
            'status' => 'aktif',
        ]);
    }

    public function test_captain_can_invite_a_player_to_the_team(): void
    {
        Notification::fake();

        $captain = User::factory()->create(['status' => 'aktif']);
        $player = User::factory()->create(['username' => 'alikaptan', 'status' => 'aktif']);

        $team = FootballTeam::create([
            'user_id' => $captain->id,
            'name' => 'Kreuzberg Spor',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'level' => FootballLevel::Orta,
        ]);

        $response = $this->actingAs($captain)->post(route('football.teams.invite', $team), [
            'username' => 'alikaptan',
            'primary_position' => 'forvet',
            'jersey_number' => 9,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('football_team_members', [
            'team_id' => $team->id,
            'user_id' => $player->id,
            'role' => 'player',
            'status' => FootballMemberStatus::DavetEdildi->value,
            'primary_position' => 'forvet',
            'jersey_number' => 9,
        ]);

        Notification::assertSentTo($player, FootballTeamInviteNotification::class);
    }

    public function test_player_can_accept_invitation(): void
    {
        $captain = User::factory()->create();
        $player = User::factory()->create(['status' => 'aktif']);

        $team = FootballTeam::create([
            'user_id' => $captain->id,
            'name' => 'Köln Hilal',
            'city' => 'Köln',
            'country_code' => 'DE',
            'level' => FootballLevel::Orta,
        ]);

        $member = FootballTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $player->id,
            'role' => 'player',
            'status' => FootballMemberStatus::DavetEdildi->value,
        ]);

        $response = $this->actingAs($player)->post(route('football.teams.respond-invite', $member), [
            'action' => 'accept',
        ]);

        $response->assertRedirect();

        $this->assertEquals(FootballMemberStatus::Aktif, $member->fresh()->status);
    }

    public function test_player_can_leave_team(): void
    {
        $captain = User::factory()->create();
        $player = User::factory()->create(['status' => 'aktif']);

        $team = FootballTeam::create([
            'user_id' => $captain->id,
            'name' => 'Frankfurt Gücü',
            'city' => 'Frankfurt',
            'country_code' => 'DE',
            'level' => FootballLevel::Orta,
        ]);

        FootballTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $player->id,
            'role' => 'player',
            'status' => FootballMemberStatus::Aktif->value,
        ]);

        $response = $this->actingAs($player)->delete(route('football.teams.leave', $team));
        $response->assertRedirect();

        $this->assertDatabaseMissing('football_team_members', [
            'team_id' => $team->id,
            'user_id' => $player->id,
        ]);
    }
}

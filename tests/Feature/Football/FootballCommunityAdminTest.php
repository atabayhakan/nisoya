<?php

namespace Tests\Feature\Football;

use App\Enums\UserRole;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\FootballMatches\Pages\ListFootballMatches;
use App\Filament\Resources\FootballPlayerRequests\Pages\ListFootballPlayerRequests;
use App\Filament\Resources\FootballTeams\Pages\ListFootballTeams;
use App\Filament\Resources\FootballVenues\Pages\ListFootballVenues;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Stories\Pages\ListStories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FootballCommunityAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin);
    }

    public function test_football_matches_list_and_tabs_render(): void
    {
        $comp = Livewire::test(ListFootballMatches::class);
        $comp->assertSuccessful();

        foreach (array_keys($comp->instance()->getTabs()) as $tabKey) {
            $comp->set('activeTab', $tabKey);
            $comp->assertSuccessful();
        }
    }

    public function test_football_teams_list_and_tabs_render(): void
    {
        $comp = Livewire::test(ListFootballTeams::class);
        $comp->assertSuccessful();

        foreach (array_keys($comp->instance()->getTabs()) as $tabKey) {
            $comp->set('activeTab', $tabKey);
            $comp->assertSuccessful();
        }
    }

    public function test_football_venues_list_and_tabs_render(): void
    {
        $comp = Livewire::test(ListFootballVenues::class);
        $comp->assertSuccessful();

        foreach (array_keys($comp->instance()->getTabs()) as $tabKey) {
            $comp->set('activeTab', $tabKey);
            $comp->assertSuccessful();
        }
    }

    public function test_football_player_requests_list_and_tabs_render(): void
    {
        $comp = Livewire::test(ListFootballPlayerRequests::class);
        $comp->assertSuccessful();

        foreach (array_keys($comp->instance()->getTabs()) as $tabKey) {
            $comp->set('activeTab', $tabKey);
            $comp->assertSuccessful();
        }
    }

    public function test_contact_messages_list_and_tabs_render(): void
    {
        $comp = Livewire::test(ListContactMessages::class);
        $comp->assertSuccessful();

        foreach (array_keys($comp->instance()->getTabs()) as $tabKey) {
            $comp->set('activeTab', $tabKey);
            $comp->assertSuccessful();
        }
    }

    public function test_reviews_list_and_tabs_render(): void
    {
        $comp = Livewire::test(ListReviews::class);
        $comp->assertSuccessful();

        foreach (array_keys($comp->instance()->getTabs()) as $tabKey) {
            $comp->set('activeTab', $tabKey);
            $comp->assertSuccessful();
        }
    }

    public function test_stories_list_and_tabs_render(): void
    {
        $comp = Livewire::test(ListStories::class);
        $comp->assertSuccessful();

        foreach (array_keys($comp->instance()->getTabs()) as $tabKey) {
            $comp->set('activeTab', $tabKey);
            $comp->assertSuccessful();
        }
    }
}

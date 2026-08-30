<?php

namespace Tests\Feature\Football;

use App\Enums\FootballRequestType;
use App\Models\FootballPlayerRequest;
use App\Models\User;
use App\Notifications\FootballPlayerAppliedNotification;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FootballPlayerRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_user_can_create_player_wanted_request_and_others_can_apply(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['status' => 'aktif']);
        $applicant = User::factory()->create(['status' => 'aktif']);

        $response = $this->actingAs($creator)->post(route('football.requests.store'), [
            'type' => 'oyuncu_araniyor',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'venue_name' => 'Soccerworld Kreuzberg',
            'needed_count' => 2,
            'level' => 'orta',
            'positions' => ['defans', 'forvet'],
            'description' => 'Akşam maçımıza 2 eksik oyuncu arıyoruz.',
        ]);

        $response->assertRedirect();

        $request = FootballPlayerRequest::where('user_id', $creator->id)->firstOrFail();
        $this->assertEquals(FootballRequestType::OyuncuAraniyor, $request->type);
        $this->assertEquals(2, $request->needed_count);

        // İkinci kullanıcı başvursun
        $applyResponse = $this->actingAs($applicant)->post(route('football.requests.apply', $request), [
            'message' => 'Ben gelebilirim, forvet oynuyorum.',
        ]);

        $applyResponse->assertRedirect();

        $this->assertDatabaseHas('football_player_request_applications', [
            'request_id' => $request->id,
            'user_id' => $applicant->id,
            'message' => 'Ben gelebilirim, forvet oynuyorum.',
        ]);

        Notification::assertSentTo($creator, FootballPlayerAppliedNotification::class);
    }
}

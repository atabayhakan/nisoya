<?php

namespace Tests\Feature;

use App\Enums\StoryStatus;
use App\Models\Story;
use App\Models\User;
use App\Services\NabizService;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Gurbet Günlüğü": Nisoya Nabzı'nın anonim hikaye duvarı.
 */
class StoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        Settings::setMany(['nabiz.hikaye_duvari_aktif' => '1']);
    }

    public function test_authenticated_user_can_submit_a_story(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/nabiz/hikaye', [
            'body' => 'İlk yıl çok zorlandım ama Nisoya sayesinde harika insanlarla tanıştım ve artık kendimi evimde hissediyorum.',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('stories', [
            'user_id' => $user->id,
            'status' => StoryStatus::Beklemede->value,
        ]);
    }

    public function test_guest_cannot_submit_a_story(): void
    {
        $this->post('/nabiz/hikaye', ['body' => str_repeat('a', 30)])->assertRedirect('/giris');
    }

    public function test_story_body_has_minimum_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/nabiz/hikaye', ['body' => 'çok kısa'])
            ->assertSessionHasErrors('body');
    }

    public function test_story_body_has_maximum_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/nabiz/hikaye', ['body' => str_repeat('a', 601)])
            ->assertSessionHasErrors('body');
    }

    public function test_user_cannot_submit_a_new_story_while_one_is_pending(): void
    {
        $user = User::factory()->create();
        Story::create(['user_id' => $user->id, 'body' => str_repeat('a', 30), 'status' => StoryStatus::Beklemede]);

        $this->actingAs($user)->post('/nabiz/hikaye', ['body' => str_repeat('b', 30)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('stories', 1);
    }

    public function test_user_can_resubmit_after_rejection(): void
    {
        $user = User::factory()->create();
        Story::create(['user_id' => $user->id, 'body' => str_repeat('a', 30), 'status' => StoryStatus::Reddedildi]);

        $this->actingAs($user)->post('/nabiz/hikaye', ['body' => str_repeat('b', 30)])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('stories', 2);
    }

    public function test_submission_is_rejected_when_story_wall_disabled(): void
    {
        Settings::setMany(['nabiz.hikaye_duvari_aktif' => '0']);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/nabiz/hikaye', ['body' => str_repeat('a', 30)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('stories', 0);
    }

    public function test_approved_stories_are_shown_anonymously_on_nabiz_page(): void
    {
        $author = User::factory()->create(['name' => 'Çok Gizli İsim']);
        Story::create(['user_id' => $author->id, 'body' => 'Bu benim anonim hikayem, kimse yazarını bilmemeli.', 'status' => StoryStatus::Onaylandi]);

        $response = $this->get('/nabiz');

        $response->assertOk();
        $response->assertSee('Bu benim anonim hikayem, kimse yazarını bilmemeli.');
        $response->assertDontSee('Çok Gizli İsim');
    }

    public function test_pending_and_rejected_stories_are_not_shown_publicly(): void
    {
        Story::create(['user_id' => User::factory()->create()->id, 'body' => 'Beklemedeki hikaye metni burada.', 'status' => StoryStatus::Beklemede]);
        Story::create(['user_id' => User::factory()->create()->id, 'body' => 'Reddedilen hikaye metni burada.', 'status' => StoryStatus::Reddedildi]);

        $response = $this->get('/nabiz');

        $response->assertDontSee('Beklemedeki hikaye metni burada.');
        $response->assertDontSee('Reddedilen hikaye metni burada.');
    }

    public function test_story_wall_section_hidden_when_disabled(): void
    {
        Settings::setMany(['nabiz.hikaye_duvari_aktif' => '0']);

        $response = $this->get('/nabiz');

        $response->assertDontSee('Gurbet Günlüğü');
    }

    public function test_approved_stories_service_returns_only_body_and_date(): void
    {
        $author = User::factory()->create();
        Story::create(['user_id' => $author->id, 'body' => 'Servis testi hikayesi.', 'status' => StoryStatus::Onaylandi]);

        $stories = app(NabizService::class)->approvedStories();

        $this->assertCount(1, $stories);
        $this->assertSame('Servis testi hikayesi.', $stories->first()->body);
        $this->assertNotNull($stories->first()->created_at);
    }

    public function test_approved_stories_cache_does_not_corrupt_on_second_read(): void
    {
        $author = User::factory()->create();
        Story::create(['user_id' => $author->id, 'body' => 'Cache testi hikayesi.', 'status' => StoryStatus::Onaylandi]);

        $service = app(NabizService::class);
        $first = $service->approvedStories();
        $second = $service->approvedStories();

        $this->assertSame('Cache testi hikayesi.', $first->first()->body);
        $this->assertSame('Cache testi hikayesi.', $second->first()->body);
    }

    public function test_account_deletion_removes_users_stories(): void
    {
        $user = User::factory()->create();
        Story::create(['user_id' => $user->id, 'body' => str_repeat('a', 30), 'status' => StoryStatus::Onaylandi]);

        $this->actingAs($user)->delete('/panel/profil', [
            'current_password' => 'password',
            'confirm_text' => 'HESABIMI SİL',
        ]);

        $this->assertDatabaseCount('stories', 0);
    }

    public function test_data_export_includes_own_stories(): void
    {
        $user = User::factory()->create();
        Story::create(['user_id' => $user->id, 'body' => 'Dışa aktarılacak hikaye.', 'status' => StoryStatus::Beklemede]);

        $response = $this->actingAs($user)->get('/panel/profil/verilerim');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Dışa aktarılacak hikaye.', $content);
    }
}

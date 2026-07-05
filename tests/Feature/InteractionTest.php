<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    // --- Favoriler ---

    public function test_user_can_toggle_favorite(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['status' => 'aktif']);

        $this->actingAs($user)->post("/favori/{$listing->id}")->assertRedirect();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user)->post("/favori/{$listing->id}");
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);
    }

    public function test_guest_cannot_favorite(): void
    {
        $listing = Listing::factory()->create(['status' => 'aktif']);
        $this->post("/favori/{$listing->id}")->assertRedirect(route('login'));
    }

    public function test_favorites_page_lists_favorited_listings(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['status' => 'aktif', 'title' => 'Favori ilan başlığı']);
        $user->favorites()->create(['listing_id' => $listing->id]);

        $this->actingAs($user)->get('/panel/favorilerim')->assertOk()->assertSee('Favori ilan başlığı');
    }

    // --- İş ilanı yer imleri ---

    /** İşveren + şirket + aktif iş ilanı üretir. */
    protected function jobListingFixture(): object
    {
        $employer = User::factory()->create();
        $company = Company::create(['user_id' => $employer->id, 'name' => 'Acme GmbH', 'slug' => 'acme-gmbh']);
        $job = $company->jobListings()->create([
            'title' => 'Yer imi ilanı', 'slug' => 'yer-imi-ilani', 'description' => 'Açıklama.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);

        return (object) ['employer' => $employer, 'company' => $company, 'job' => $job];
    }

    public function test_user_can_toggle_job_bookmark(): void
    {
        $user = User::factory()->create();
        $job = $this->jobListingFixture()->job;

        $this->actingAs($user)->post("/is-yer-imi/{$job->id}")->assertRedirect();
        $this->assertDatabaseHas('job_bookmarks', ['user_id' => $user->id, 'job_listing_id' => $job->id]);

        $this->actingAs($user)->post("/is-yer-imi/{$job->id}");
        $this->assertDatabaseMissing('job_bookmarks', ['user_id' => $user->id, 'job_listing_id' => $job->id]);
    }

    public function test_guest_cannot_bookmark_job(): void
    {
        $job = $this->jobListingFixture()->job;

        $this->post("/is-yer-imi/{$job->id}")->assertRedirect(route('login'));
    }

    public function test_job_bookmarks_page_lists_bookmarked_jobs(): void
    {
        $user = User::factory()->create();
        $job = $this->jobListingFixture()->job;
        $user->jobBookmarks()->create(['job_listing_id' => $job->id]);

        $this->actingAs($user)->get('/panel/is-yer-imlerim')->assertOk()->assertSee('Yer imi ilanı');
    }

    // --- Mesajlaşma ---

    public function test_messages_index_renders_when_empty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/panel/mesajlar')
            ->assertOk()
            ->assertSee('Henüz mesajın yok');
    }

    public function test_messages_index_renders_with_conversations(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create(['name' => 'Karşı Taraf']);
        $conversation = Conversation::create([
            'user_one_id' => $me->id, 'user_two_id' => $other->id, 'last_message_at' => now(),
        ]);
        $conversation->messages()->create(['sender_id' => $other->id, 'body' => 'Merhaba!']);

        $this->actingAs($me)
            ->get('/panel/mesajlar')
            ->assertOk()
            ->assertSee('Karşı Taraf')
            ->assertSee('Merhaba!');
    }

    public function test_user_can_message_seller_from_listing(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = Listing::factory()->for($seller)->create(['status' => 'aktif']);

        $this->actingAs($buyer)
            ->post("/ilan/{$listing->id}/mesaj", ['body' => 'Merhaba, müsait misiniz?'])
            ->assertRedirect();

        $this->assertDatabaseHas('conversations', ['listing_id' => $listing->id]);
        $this->assertDatabaseHas('messages', ['sender_id' => $buyer->id, 'body' => 'Merhaba, müsait misiniz?']);
    }

    public function test_user_cannot_message_own_listing(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->for($seller)->create(['status' => 'aktif']);

        $this->actingAs($seller)->post("/ilan/{$listing->id}/mesaj", ['body' => 'test']);

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_only_participants_can_view_thread(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => $buyer->id, 'user_two_id' => $seller->id, 'last_message_at' => now(),
        ]);

        $this->actingAs($stranger)->get("/panel/mesajlar/{$conversation->id}")->assertForbidden();
        $this->actingAs($buyer)->get("/panel/mesajlar/{$conversation->id}")->assertOk();
    }

    public function test_participant_can_reply(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => $buyer->id, 'user_two_id' => $seller->id, 'last_message_at' => now(),
        ]);

        $this->actingAs($seller)
            ->post("/panel/mesajlar/{$conversation->id}", ['body' => 'Evet, müsaitim.'])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'sender_id' => $seller->id, 'body' => 'Evet, müsaitim.']);
    }

    // --- Değerlendirme ---

    public function test_user_can_review_seller(): void
    {
        $seller = User::factory()->create();
        $reviewer = User::factory()->create();
        Conversation::create(['user_one_id' => $reviewer->id, 'user_two_id' => $seller->id, 'last_message_at' => now()]);

        $this->actingAs($reviewer)
            ->post("/uye/{$seller->username}/degerlendir", ['rating' => 5, 'comment' => 'Çok memnun kaldım.'])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', ['reviewee_id' => $seller->id, 'reviewer_id' => $reviewer->id, 'rating' => 5]);
    }

    public function test_review_updates_existing_instead_of_duplicating(): void
    {
        $seller = User::factory()->create();
        $reviewer = User::factory()->create();
        Conversation::create(['user_one_id' => $reviewer->id, 'user_two_id' => $seller->id, 'last_message_at' => now()]);
        Review::create(['reviewee_id' => $seller->id, 'reviewer_id' => $reviewer->id, 'rating' => 5, 'status' => 'yayinda']);

        $this->actingAs($reviewer)->post("/uye/{$seller->username}/degerlendir", ['rating' => 3]);

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['reviewee_id' => $seller->id, 'reviewer_id' => $reviewer->id, 'rating' => 3]);
    }

    public function test_user_cannot_review_self(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post("/uye/{$user->username}/degerlendir", ['rating' => 5])->assertForbidden();
    }

    public function test_user_cannot_review_without_prior_contact(): void
    {
        $seller = User::factory()->create();
        $reviewer = User::factory()->create();

        $this->actingAs($reviewer)
            ->post("/uye/{$seller->username}/degerlendir", ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    // --- Şirket değerlendirmesi ---

    public function test_user_can_review_company_after_applying(): void
    {
        $fixture = $this->jobListingFixture();
        $applicant = User::factory()->create();
        $fixture->job->applications()->create(['user_id' => $applicant->id, 'status' => 'gonderildi']);

        $this->actingAs($applicant)
            ->post("/sirket/{$fixture->company->slug}/degerlendir", ['rating' => 4, 'comment' => 'İyi bir işverendi.'])
            ->assertRedirect();

        $this->assertDatabaseHas('company_reviews', ['company_id' => $fixture->company->id, 'reviewer_id' => $applicant->id, 'rating' => 4]);
    }

    public function test_company_review_updates_existing_instead_of_duplicating(): void
    {
        $fixture = $this->jobListingFixture();
        $applicant = User::factory()->create();
        $fixture->job->applications()->create(['user_id' => $applicant->id, 'status' => 'gonderildi']);
        CompanyReview::create(['company_id' => $fixture->company->id, 'reviewer_id' => $applicant->id, 'rating' => 5, 'status' => 'yayinda']);

        $this->actingAs($applicant)->post("/sirket/{$fixture->company->slug}/degerlendir", ['rating' => 2]);

        $this->assertDatabaseCount('company_reviews', 1);
        $this->assertDatabaseHas('company_reviews', ['company_id' => $fixture->company->id, 'reviewer_id' => $applicant->id, 'rating' => 2]);
    }

    public function test_user_cannot_review_own_company(): void
    {
        $fixture = $this->jobListingFixture();

        $this->actingAs($fixture->employer)
            ->post("/sirket/{$fixture->company->slug}/degerlendir", ['rating' => 5])
            ->assertForbidden();
    }

    public function test_user_cannot_review_company_without_applying(): void
    {
        $fixture = $this->jobListingFixture();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post("/sirket/{$fixture->company->slug}/degerlendir", ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('company_reviews', 0);
    }

    public function test_company_review_form_only_shown_after_applying(): void
    {
        $fixture = $this->jobListingFixture();
        $applicant = User::factory()->create();

        $this->actingAs($applicant)
            ->get("/sirket/{$fixture->company->slug}")
            ->assertOk()
            ->assertDontSee('Deneyimini paylaş')
            ->assertSee('önce bir iş ilanına başvurmuş olman gerekir');

        $fixture->job->applications()->create(['user_id' => $applicant->id, 'status' => 'gonderildi']);

        $this->actingAs($applicant)
            ->get("/sirket/{$fixture->company->slug}")
            ->assertOk()
            ->assertSee('Deneyimini paylaş');
    }

    public function test_review_form_only_shown_after_contact(): void
    {
        $seller = User::factory()->create();
        $strangerVisitor = User::factory()->create();

        $this->actingAs($strangerVisitor)
            ->get("/uye/{$seller->username}")
            ->assertOk()
            ->assertDontSee('Deneyimini paylaş')
            ->assertSee('önce kendisiyle iletişime geçmiş olman gerekir');

        Conversation::create(['user_one_id' => $strangerVisitor->id, 'user_two_id' => $seller->id, 'last_message_at' => now()]);

        $this->actingAs($strangerVisitor)
            ->get("/uye/{$seller->username}")
            ->assertOk()
            ->assertSee('Deneyimini paylaş');
    }

    public function test_profile_membership_date_shows_month_name_once(): void
    {
        $seller = User::factory()->create(['created_at' => '2026-07-03']);

        $response = $this->get("/uye/{$seller->username}");

        $response->assertOk();
        $response->assertSee('Üyelik: Temmuz 2026');
        $response->assertDontSee('TemmuzTemmuz');
    }

    // --- Şikayet ---

    public function test_user_can_report_listing(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['status' => 'aktif']);

        $this->actingAs($user)
            ->post("/ilan/{$listing->id}/sikayet", ['reason' => 'Yanıltıcı / sahte ilan'])
            ->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $user->id,
            'reportable_type' => 'listing',
            'reportable_id' => $listing->id,
            'reason' => 'Yanıltıcı / sahte ilan',
        ]);
    }
}

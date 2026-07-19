<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use App\Notifications\DealNotification;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Anlaşma (Deal) durum makinesi + değerlendirme deal-bağı (K-C, "çekirdek").
 * Nisoya para akışını görmez; bu bir niyet defteridir.
 */
class DealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    /** @return array{seller: User, buyer: User, conversation: Conversation, listing: Listing} */
    private function scenario(): array
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $seller->id, 'status' => 'aktif']);
        $conversation = Conversation::findOrCreateBetween($buyer->id, $seller->id, $listing->id);

        return compact('seller', 'buyer', 'conversation', 'listing');
    }

    public function test_participant_can_propose_a_deal_and_other_is_notified(): void
    {
        Notification::fake();
        ['seller' => $seller, 'buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();

        $this->actingAs($buyer)
            ->post(route('panel.deals.propose', $conversation), ['amount' => 150, 'currency' => 'EUR'])
            ->assertRedirect();

        $this->assertDatabaseHas('deals', [
            'conversation_id' => $conversation->id,
            'proposed_by' => $buyer->id,
            'seller_id' => $seller->id, // ilan sahibi satıcı
            'buyer_id' => $buyer->id,
            'status' => 'teklif',
            'amount' => 150,
            'currency' => 'EUR',
        ]);

        Notification::assertSentTo($seller, DealNotification::class);
    }

    public function test_non_participant_cannot_propose(): void
    {
        ['conversation' => $conversation] = $this->scenario();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('panel.deals.propose', $conversation), ['amount' => 10, 'currency' => 'EUR'])
            ->assertForbidden();
    }

    public function test_cannot_propose_when_an_open_deal_exists(): void
    {
        ['buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();
        $this->actingAs($buyer)->post(route('panel.deals.propose', $conversation), ['currency' => 'EUR']);

        $this->actingAs($buyer)
            ->post(route('panel.deals.propose', $conversation), ['currency' => 'EUR'])
            ->assertSessionHasErrors('deal');

        $this->assertDatabaseCount('deals', 1);
    }

    public function test_other_party_can_accept_but_proposer_cannot(): void
    {
        ['seller' => $seller, 'buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();
        $this->actingAs($buyer)->post(route('panel.deals.propose', $conversation), ['currency' => 'EUR']);
        $deal = Deal::first();

        // Teklifi yapan kabul edemez
        $this->actingAs($buyer)->post(route('panel.deals.accept', $deal))->assertForbidden();
        $this->assertSame('teklif', $deal->fresh()->status->value);

        // Karşı taraf kabul edebilir
        $this->actingAs($seller)->post(route('panel.deals.accept', $deal))->assertRedirect();
        $this->assertSame('kabul', $deal->fresh()->status->value);
        $this->assertNotNull($deal->fresh()->accepted_at);
    }

    public function test_accepted_deal_can_be_completed(): void
    {
        ['seller' => $seller, 'buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();
        $this->actingAs($buyer)->post(route('panel.deals.propose', $conversation), ['currency' => 'EUR']);
        $deal = Deal::first();
        $this->actingAs($seller)->post(route('panel.deals.accept', $deal));

        $this->actingAs($buyer)->post(route('panel.deals.complete', $deal))->assertRedirect();
        $this->assertSame('tamamlandi', $deal->fresh()->status->value);
        $this->assertNotNull($deal->fresh()->completed_at);
    }

    public function test_cannot_complete_a_deal_that_is_not_accepted(): void
    {
        ['buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();
        $this->actingAs($buyer)->post(route('panel.deals.propose', $conversation), ['currency' => 'EUR']);
        $deal = Deal::first();

        $this->actingAs($buyer)->post(route('panel.deals.complete', $deal))->assertForbidden();
        $this->assertSame('teklif', $deal->fresh()->status->value);
    }

    public function test_participant_can_cancel_open_deal(): void
    {
        ['buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();
        $this->actingAs($buyer)->post(route('panel.deals.propose', $conversation), ['currency' => 'EUR']);
        $deal = Deal::first();

        $this->actingAs($buyer)->post(route('panel.deals.cancel', $deal))->assertRedirect();
        $this->assertSame('iptal', $deal->fresh()->status->value);
    }

    public function test_dispute_marks_deal_and_stores_note(): void
    {
        Notification::fake();
        ['seller' => $seller, 'buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();
        $this->actingAs($buyer)->post(route('panel.deals.propose', $conversation), ['currency' => 'EUR']);
        $deal = Deal::first();
        $this->actingAs($seller)->post(route('panel.deals.accept', $deal));

        $this->actingAs($buyer)
            ->post(route('panel.deals.dispute', $deal), ['dispute_note' => 'Ödemeyi aldı ama hizmeti vermedi.'])
            ->assertRedirect();

        $fresh = $deal->fresh();
        $this->assertSame('sorunlu', $fresh->status->value);
        $this->assertSame('Ödemeyi aldı ama hizmeti vermedi.', $fresh->dispute_note);
        Notification::assertSentTo($seller, DealNotification::class);
    }

    public function test_review_is_marked_verified_when_a_completed_deal_exists(): void
    {
        ['seller' => $seller, 'buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();
        $deal = Deal::create([
            'conversation_id' => $conversation->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'proposed_by' => $buyer->id,
            'status' => 'tamamlandi',
            'completed_at' => now(),
        ]);

        // Alıcı, satıcıyı değerlendirir → tamamlanmış anlaşma olduğu için deal_id bağlanır
        $this->actingAs($buyer)
            ->post(route('reviews.store', $seller->username), ['rating' => 5, 'comment' => 'Harika iş.'])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'deal_id' => $deal->id,
        ]);
    }

    public function test_review_without_completed_deal_is_not_verified(): void
    {
        ['seller' => $seller, 'buyer' => $buyer] = $this->scenario();
        // Konuşma var ama tamamlanmış anlaşma yok → deal_id null (rozetsiz)
        $this->actingAs($buyer)
            ->post(route('reviews.store', $seller->username), ['rating' => 4])
            ->assertRedirect();

        $review = Review::first();
        $this->assertNotNull($review);
        $this->assertNull($review->deal_id);
    }

    public function test_trust_tier_counts_completed_deals(): void
    {
        $user = User::factory()->create();
        $this->assertSame(0, $user->trustProfile()['completed_deals']);

        $other = User::factory()->create();
        $conversation = Conversation::findOrCreateBetween($user->id, $other->id, null);
        Deal::create([
            'conversation_id' => $conversation->id,
            'seller_id' => $user->id,
            'buyer_id' => $other->id,
            'proposed_by' => $other->id,
            'status' => 'tamamlandi',
            'completed_at' => now(),
        ]);

        $this->assertSame(1, $user->fresh()->trustProfile()['completed_deals']);
    }

    public function test_deal_panel_renders_on_conversation_page(): void
    {
        ['buyer' => $buyer, 'conversation' => $conversation] = $this->scenario();

        $this->actingAs($buyer)
            ->get(route('panel.messages.show', $conversation))
            ->assertOk()
            ->assertSee('Anlaşma')
            ->assertSee('Anlaşma başlat');
    }

    public function test_admin_can_view_deals_admin_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/yonetim/deals')->assertOk();
    }

    public function test_moderator_cannot_view_deals_admin_page(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($moderator)->get('/yonetim/deals')->assertForbidden();
    }
}

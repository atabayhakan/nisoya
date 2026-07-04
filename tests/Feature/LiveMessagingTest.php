<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function conversation(User $a, User $b): Conversation
    {
        return Conversation::create([
            'user_one_id' => $a->id, 'user_two_id' => $b->id, 'last_message_at' => now(),
        ]);
    }

    public function test_stream_returns_only_messages_after_given_id(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);

        $first = $conv->messages()->create(['sender_id' => $a->id, 'body' => 'İlk mesaj']);
        $second = $conv->messages()->create(['sender_id' => $b->id, 'body' => 'İkinci mesaj']);

        $response = $this->actingAs($a)->getJson("/panel/mesajlar/{$conv->id}/akis?after={$first->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonFragment(['body' => 'İkinci mesaj'])
            ->assertJsonMissing(['body' => 'İlk mesaj']);
    }

    public function test_stream_is_participant_only(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $stranger = User::factory()->create();
        $conv = $this->conversation($a, $b);

        $this->actingAs($stranger)->getJson("/panel/mesajlar/{$conv->id}/akis")->assertForbidden();
    }

    public function test_ajax_store_returns_json_message(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);

        $this->actingAs($a)
            ->postJson("/panel/mesajlar/{$conv->id}", ['body' => 'AJAX mesajı'])
            ->assertOk()
            ->assertJson(['body' => 'AJAX mesajı', 'mine' => true]);

        $this->assertDatabaseHas('messages', ['conversation_id' => $conv->id, 'body' => 'AJAX mesajı']);
    }

    public function test_stream_marks_incoming_messages_read(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);
        $msg = $conv->messages()->create(['sender_id' => $b->id, 'body' => 'Okunacak mesaj']);

        $this->assertNull($msg->read_at);

        $this->actingAs($a)->getJson("/panel/mesajlar/{$conv->id}/akis");

        $this->assertNotNull(Message::find($msg->id)->read_at);
    }

    // --- Mesaj geri alma ---

    public function test_sender_can_recall_own_message(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);
        $msg = $conv->messages()->create(['sender_id' => $a->id, 'body' => 'Yanlış mesaj']);

        $this->actingAs($a)
            ->deleteJson("/panel/mesajlar/{$conv->id}/mesaj/{$msg->id}")
            ->assertOk()
            ->assertJson(['recalled' => true]);

        $this->assertNotNull(Message::find($msg->id)->recalled_at);
        // body moderasyon için DB'de kalır
        $this->assertSame('Yanlış mesaj', Message::find($msg->id)->body);
    }

    public function test_cannot_recall_someone_elses_message(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);
        $msg = $conv->messages()->create(['sender_id' => $b->id, 'body' => 'Karşının mesajı']);

        $this->actingAs($a)
            ->deleteJson("/panel/mesajlar/{$conv->id}/mesaj/{$msg->id}")
            ->assertForbidden();

        $this->assertNull(Message::find($msg->id)->recalled_at);
    }

    public function test_non_participant_cannot_recall(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $stranger = User::factory()->create();
        $conv = $this->conversation($a, $b);
        $msg = $conv->messages()->create(['sender_id' => $a->id, 'body' => 'Mesaj']);

        $this->actingAs($stranger)
            ->deleteJson("/panel/mesajlar/{$conv->id}/mesaj/{$msg->id}")
            ->assertForbidden();
    }

    public function test_cannot_recall_message_from_another_conversation(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);
        $otherConv = $this->conversation($a, $b);
        $msg = $otherConv->messages()->create(['sender_id' => $a->id, 'body' => 'Başka konuşma']);

        $this->actingAs($a)
            ->deleteJson("/panel/mesajlar/{$conv->id}/mesaj/{$msg->id}")
            ->assertNotFound();
    }

    public function test_recalled_message_body_is_hidden_in_stream(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);
        $msg = $conv->messages()->create(['sender_id' => $b->id, 'body' => 'Gizlenecek içerik', 'recalled_at' => now()]);

        $response = $this->actingAs($a)->getJson("/panel/mesajlar/{$conv->id}/akis");

        $response->assertOk()
            ->assertJsonFragment(['id' => $msg->id, 'recalled' => true, 'body' => ''])
            ->assertJsonMissing(['body' => 'Gizlenecek içerik']);
    }

    public function test_stream_reports_recalled_ids_for_existing_messages(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conv = $this->conversation($a, $b);
        $old = $conv->messages()->create(['sender_id' => $b->id, 'body' => 'Eski', 'recalled_at' => now()]);

        // after=$old->id → mesaj listesinde gelmez ama recalled listesinde olmalı
        $this->actingAs($a)
            ->getJson("/panel/mesajlar/{$conv->id}/akis?after={$old->id}")
            ->assertOk()
            ->assertJsonFragment(['recalled' => [$old->id]]);
    }
}

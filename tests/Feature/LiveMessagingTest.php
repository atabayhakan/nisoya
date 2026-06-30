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
}

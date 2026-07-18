<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'phone' => '',
            'category' => 'genel',
            'message' => 'Merhaba, bir sorum olacaktı, yardımcı olabilir misiniz?',
        ], $overrides);
    }

    public function test_contact_page_renders(): void
    {
        $this->get('/iletisim')->assertOk()->assertSee('İletişim')->assertSee('Mesajı Gönder');
    }

    public function test_guest_can_submit_contact_message(): void
    {
        Notification::fake();

        $this->post('/iletisim', $this->validData())->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'category' => 'genel',
            'status' => 'yeni',
            'user_id' => null,
        ]);

        Notification::assertSentOnDemand(NewContactMessageNotification::class);
    }

    public function test_authenticated_users_message_is_linked_to_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/iletisim', $this->validData())->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'ayse@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_contact_form_validates_input(): void
    {
        $this->post('/iletisim', [
            'name' => '',
            'email' => 'gecersiz-eposta',
            'category' => 'olmayan-kategori',
            'message' => 'kısa',
        ])->assertSessionHasErrors(['name', 'email', 'category', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_honeypot_silently_drops_bot_submission(): void
    {
        $this->post('/iletisim', $this->validData(['website' => 'http://spam.example']))
            ->assertRedirect();

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_non_admin_cannot_access_contact_messages_admin_page(): void
    {
        $user = User::factory()->create(['role' => 'uye']);

        $this->actingAs($user, 'web')->get('/yonetim/contact-messages')->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_contact_messages(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        ContactMessage::create($this->validData() + ['status' => 'yeni']);

        $this->actingAs($admin, 'web')->get('/yonetim/contact-messages')
            ->assertOk()
            ->assertSee('Ayşe Yılmaz');
    }
}

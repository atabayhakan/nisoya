<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\EPostaMetinleri;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Support\MailTemplates;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 3 · G6 — düzenlenebilir e-posta metinleri (konu/gövde parçaları panelden).
 */
class MailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    // ---------------------------------------------------------- Çekirdek

    public function test_part_returns_default_without_override(): void
    {
        $this->assertSame(
            'Merhaba Ali,',
            MailTemplates::part('yeni_mesaj', 'greeting', ['{ad}' => 'Ali'])
        );
    }

    public function test_part_returns_override_with_substitution(): void
    {
        Settings::setMany(['mail_template.yeni_mesaj.subject' => 'Selam {gonderen}!']);

        $this->assertSame(
            'Selam Veli!',
            MailTemplates::part('yeni_mesaj', 'subject', ['{gonderen}' => 'Veli'])
        );
    }

    // -------------------------------------------------- Bildirime yansıma

    public function test_new_message_notification_uses_template(): void
    {
        Settings::setMany(['mail_template.yeni_mesaj.subject' => 'Yeni ileti: {gonderen}']);

        $user = User::factory()->make(['name' => 'Ali']);
        $mail = (new NewMessageNotification('Merhaba dünya', 'Veli', 1))->toMail($user);

        $this->assertSame('Yeni ileti: Veli', $mail->subject);
        $this->assertSame('Merhaba Ali,', $mail->greeting); // varsayılan + yer-tutucu
        $this->assertSame('Mesajı görüntüle', $mail->actionText);
    }

    // --------------------------------------------------------- Admin sayfa

    public function test_admin_can_save_templates(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        Livewire::actingAs($admin)
            ->test(EPostaMetinleri::class)
            ->fillForm([
                'yeni_mesaj__subject' => 'Özel konu',
                'kayitli_arama__action' => 'İlanlara bak',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'mail_template.yeni_mesaj.subject', 'value' => 'Özel konu']);
        $this->assertDatabaseHas('site_settings', ['key' => 'mail_template.kayitli_arama.action', 'value' => 'İlanlara bak']);
    }

    public function test_member_redirected_from_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/e-posta-metinleri')
            ->assertRedirect(route('dashboard'));
    }
}

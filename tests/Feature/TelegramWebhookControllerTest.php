<?php

namespace Tests\Feature;

use App\Jobs\TelegramGuncellemesiIsleJob;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Telegram webhook ucu.
 *
 * NE KORUYOR: SesGeriBildirimController'daki gerekçenin aynısı — imza/sır
 * doğrulaması olmadan herkes bu uca rastgele "Telegram mesajı" gönderip
 * Kâhya'yı istismar edebilirdi (sahte AI çağrısı, bütçe tüketimi).
 */
class TelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function ayarla(): void
    {
        Settings::setMany([
            'kahya.telegram.bot_token' => 'sahte-token',
            'kahya.telegram.izinli_grup_id' => '-100',
            'kahya.telegram.webhook_sirri' => 'dogru-sir',
        ]);
    }

    public function test_yapilandirilmamisken_503(): void
    {
        $this->postJson('/webhook/telegram', [])->assertStatus(503);
    }

    public function test_yanlis_sir_403(): void
    {
        $this->ayarla();

        $this->postJson('/webhook/telegram', [], ['X-Telegram-Bot-Api-Secret-Token' => 'yanlis'])
            ->assertStatus(403);
    }

    public function test_sir_yoksa_403(): void
    {
        $this->ayarla();

        $this->postJson('/webhook/telegram', [])->assertStatus(403);
    }

    public function test_dogru_sir_200_ve_is_kuyruklaniyor(): void
    {
        $this->ayarla();
        Queue::fake();

        $govde = ['message' => ['text' => 'merhaba', 'chat' => ['id' => '-100', 'type' => 'group']]];

        $this->postJson('/webhook/telegram', $govde, ['X-Telegram-Bot-Api-Secret-Token' => 'dogru-sir'])
            ->assertStatus(200);

        Queue::assertPushed(TelegramGuncellemesiIsleJob::class, fn ($job) => $job->update === $govde);
    }
}

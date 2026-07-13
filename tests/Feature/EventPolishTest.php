<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventPolishTest extends TestCase
{
    use RefreshDatabase;

    protected function verifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
    }

    protected function makeEvent(User $host, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'user_id' => $host->id,
            'type' => 'dugun',
            'title' => 'Elif & Can Düğünü',
            'starts_at' => '2027-09-05 17:00',
            'venue_name' => 'Bellevue Salon',
            'venue_address' => 'Kurfürstendamm 45, Berlin',
            'description' => "Sizleri aramızda görmek isteriz.\nNikah 17:00.",
            'theme' => '',
            'is_active' => true,
        ], $overrides));
    }

    public function test_ics_download_contains_valid_event(): void
    {
        $event = $this->makeEvent($this->verifiedUser());

        $response = $this->get('/davet/'.$event->token.'/takvim.ics');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $ics = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('DTSTART:20270905T170000', $ics);
        $this->assertStringContainsString('DTEND:20270905T210000', $ics);
        $this->assertStringContainsString('SUMMARY:Elif & Can Düğünü', $ics);
        $this->assertStringContainsString('LOCATION:Bellevue Salon — Kurfürstendamm 45\\, Berlin', $ics);
        $this->assertStringContainsString('DESCRIPTION:Sizleri aramızda görmek isteriz.\\nNikah 17:00.', $ics);
    }

    public function test_invitation_switches_language_with_dil_param(): void
    {
        $event = $this->makeEvent($this->verifiedUser());

        $this->get('/davet/'.$event->token)
            ->assertSee('Katılıyor musun?')
            ->assertSee('Eylül'); // Türkçe ay adı

        $this->get('/davet/'.$event->token.'?dil=en')
            ->assertSee('Will you attend?')
            ->assertSee('September');

        $this->get('/davet/'.$event->token.'?dil=de')
            ->assertSee('Kommst du?')
            ->assertSee('September 2027');

        // Geçersiz dil Türkçeye düşer
        $this->get('/davet/'.$event->token.'?dil=fr')
            ->assertOk()
            ->assertSee('Katılıyor musun?');
    }

    public function test_qr_card_is_host_only_and_renders_svg(): void
    {
        $host = $this->verifiedUser();
        $event = $this->makeEvent($host);

        $this->actingAs($this->verifiedUser())
            ->get("/panel/etkinlik/{$event->id}/qr")
            ->assertForbidden();

        $this->actingAs($host)
            ->get("/panel/etkinlik/{$event->id}/qr")
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('Fotoğraflarını bizimle paylaş');
    }

    public function test_happy_moments_lists_only_public_albums_with_media(): void
    {
        $host = $this->verifiedUser();

        $public = $this->makeEvent($host, ['title' => 'Herkese Açık Kutlama', 'album_is_public' => true]);
        $public->media()->create(['type' => 'image', 'status' => 'yayinda', 'path_thumb' => 't.webp', 'path_medium' => 'm.webp', 'path_large' => 'l.webp', 'size_bytes' => 1]);

        $private = $this->makeEvent($host, ['title' => 'Gizli Aile Düğünü']);
        $private->media()->create(['type' => 'image', 'status' => 'yayinda', 'path_thumb' => 't2.webp', 'path_medium' => 'm2.webp', 'path_large' => 'l2.webp', 'size_bytes' => 1]);

        $publicNoMedia = $this->makeEvent($host, ['title' => 'Açık Ama Boş Etkinlik', 'album_is_public' => true]);

        $this->get('/mutlu-anlar')
            ->assertOk()
            ->assertSee('Herkese Açık Kutlama')
            ->assertDontSee('Gizli Aile Düğünü')
            ->assertDontSee('Açık Ama Boş Etkinlik');
    }

    public function test_host_can_download_album_zip(): void
    {
        Storage::fake('public');
        $host = $this->verifiedUser();
        $event = $this->makeEvent($host);

        Storage::disk('public')->put('event-media/1/large/foto.webp', 'webp-bytes');
        Storage::disk('public')->put('event-media/1/video/klip.mp4', 'mp4-bytes');
        $event->media()->create(['type' => 'image', 'status' => 'yayinda', 'path_large' => 'event-media/1/large/foto.webp', 'size_bytes' => 10]);
        $event->media()->create(['type' => 'video', 'status' => 'yayinda', 'path' => 'event-media/1/video/klip.mp4', 'size_bytes' => 9]);
        // Beklemedeki medya ZIP'e girmez
        $event->media()->create(['type' => 'video', 'status' => 'beklemede', 'path' => 'event-media/1/video/yok.mp4', 'size_bytes' => 1]);

        $this->actingAs($this->verifiedUser())
            ->get("/panel/etkinlik/{$event->id}/album.zip")
            ->assertForbidden();

        $response = $this->actingAs($host)->get("/panel/etkinlik/{$event->id}/album.zip");

        $response->assertOk();
        $this->assertStringContainsString('.zip', $response->headers->get('Content-Disposition'));

        // ZIP içeriğini doğrula
        $zipFile = $response->getFile()->getPathname();
        $zip = new \ZipArchive;
        $zip->open($zipFile);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();
    }

    public function test_public_album_toggle_persists_via_edit(): void
    {
        $host = $this->verifiedUser();
        $event = $this->makeEvent($host);

        $this->actingAs($host)->put("/panel/etkinlik/{$event->id}", [
            'type' => 'dugun',
            'title' => $event->title,
            'starts_at' => '2027-09-05T17:00',
            'is_active' => '1',
            'allow_uploads' => '1',
            'require_approval' => '0',
            'album_is_public' => '1',
        ])->assertRedirect();

        $this->assertTrue($event->fresh()->album_is_public);
    }
}

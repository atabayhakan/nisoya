<?php

namespace Tests\Feature;

use App\Jobs\ProcessEventImage;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventMedia;
use App\Models\User;
use App\Notifications\EventMediaPurgeWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function verifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
    }

    /** Etkinlik dün başladı → akış açık. */
    protected function makeLiveEvent(User $host, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'user_id' => $host->id,
            'type' => 'dugun',
            'title' => 'Test Düğünü',
            'starts_at' => now()->subDay(),
            'theme' => '',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeGuest(Event $event, array $overrides = []): EventGuest
    {
        return $event->guests()->create(array_merge([
            'name' => 'Misafir Ayşe',
            'status' => 'geliyor',
            'party_size' => 1,
        ], $overrides));
    }

    public function test_stream_not_visible_before_event_day(): void
    {
        $event = $this->makeLiveEvent($this->verifiedUser(), ['starts_at' => now()->addWeek()]);

        $this->get('/davet/'.$event->token)
            ->assertOk()
            ->assertDontSee('Anı Akışı');
    }

    public function test_stream_visible_from_event_day(): void
    {
        $event = $this->makeLiveEvent($this->verifiedUser());

        $this->get('/davet/'.$event->token)
            ->assertOk()
            ->assertSee('Anı Akışı')
            ->assertSee('katılımını bildir (LCV)'); // kimliksiz ziyaretçiye yükleme yerine LCV çağrısı
    }

    public function test_guest_with_cookie_can_upload_photo_and_video(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Queue::fake();

        $event = $this->makeLiveEvent($this->verifiedUser());
        $guest = $this->makeGuest($event);

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->post('/davet/'.$event->token.'/medya', [
                'files' => [
                    UploadedFile::fake()->image('dugun.jpg', 1200, 800),
                    UploadedFile::fake()->create('dans.mp4', 2048, 'video/mp4'),
                ],
            ])
            ->assertRedirect(route('davet.show', $event->token))
            ->assertSessionHas('media_status');

        // Video anında kaydedilir; fotoğraf kuyruğa girer
        Queue::assertPushed(ProcessEventImage::class, 1);
        $this->assertSame(1, $event->media()->where('type', 'video')->count());
        $video = $event->media()->where('type', 'video')->first();
        $this->assertSame($guest->id, $video->event_guest_id);
        $this->assertSame('yayinda', $video->status);
    }

    public function test_anonymous_visitor_cannot_upload(): void
    {
        Queue::fake();
        $event = $this->makeLiveEvent($this->verifiedUser());

        $this->post('/davet/'.$event->token.'/medya', [
            'files' => [UploadedFile::fake()->image('x.jpg')],
        ])->assertSessionHasErrors('files');

        Queue::assertNothingPushed();
        $this->assertSame(0, $event->media()->count());
    }

    public function test_blocked_guest_cannot_upload(): void
    {
        $event = $this->makeLiveEvent($this->verifiedUser());
        $guest = $this->makeGuest($event, ['is_blocked' => true]);

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->post('/davet/'.$event->token.'/medya', [
                'files' => [UploadedFile::fake()->image('x.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_approval_mode_marks_uploads_pending_and_hides_from_others(): void
    {
        Storage::fake('public');
        $host = $this->verifiedUser();
        $event = $this->makeLiveEvent($host, ['require_approval' => true]);
        $guest = $this->makeGuest($event);

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->post('/davet/'.$event->token.'/medya', [
                'files' => [UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4')],
            ])
            ->assertRedirect();

        $media = $event->media()->first();
        $this->assertSame('beklemede', $media->status);

        // Yükleyen kendi bekleyenini görür; çerezsiz (anonim) ziyaretçi görmez.
        // withCookie test boyunca kalıcıdır — anonim istek için önce temizle.
        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->get('/davet/'.$event->token)->assertSee('Onay bekliyor');
        $this->defaultCookies = [];
        $this->get('/davet/'.$event->token)->assertDontSee('Onay bekliyor');

        // Ev sahibi onaylar → yayında
        $this->actingAs($host)
            ->post("/panel/etkinlik/{$event->id}/medya/{$media->id}/onayla")
            ->assertRedirect();
        $this->assertSame('yayinda', $media->fresh()->status);
    }

    public function test_video_count_limit_enforced(): void
    {
        $event = $this->makeLiveEvent($this->verifiedUser());
        $guest = $this->makeGuest($event);

        // Limiti dolduran sahte kayıtlar
        for ($i = 0; $i < EventMedia::MAX_VIDEOS_PER_EVENT; $i++) {
            $event->media()->create(['type' => 'video', 'status' => 'yayinda', 'path' => "x/{$i}.mp4", 'size_bytes' => 1]);
        }

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->post('/davet/'.$event->token.'/medya', [
                'files' => [UploadedFile::fake()->create('fazla.mp4', 1024, 'video/mp4')],
            ])
            ->assertSessionHasErrors('files');

        $this->assertSame(EventMedia::MAX_VIDEOS_PER_EVENT, $event->media()->count());
    }

    public function test_guest_can_delete_own_media_but_not_others(): void
    {
        Storage::fake('public');
        $event = $this->makeLiveEvent($this->verifiedUser());
        $guest = $this->makeGuest($event);
        $other = $this->makeGuest($event, ['name' => 'Başka Misafir']);

        $own = $event->media()->create(['event_guest_id' => $guest->id, 'type' => 'video', 'status' => 'yayinda', 'path' => 'a.mp4', 'size_bytes' => 1]);
        $others = $event->media()->create(['event_guest_id' => $other->id, 'type' => 'video', 'status' => 'yayinda', 'path' => 'b.mp4', 'size_bytes' => 1]);

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->delete('/davet/'.$event->token.'/medya/'.$others->id)
            ->assertForbidden();

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->delete('/davet/'.$event->token.'/medya/'.$own->id)
            ->assertRedirect();

        $this->assertNull($own->fresh());
        $this->assertNotNull($others->fresh());
    }

    public function test_host_can_block_guest_which_removes_their_media(): void
    {
        Storage::fake('public');
        $host = $this->verifiedUser();
        $event = $this->makeLiveEvent($host);
        $guest = $this->makeGuest($event);
        $event->media()->create(['event_guest_id' => $guest->id, 'type' => 'video', 'status' => 'yayinda', 'path' => 'a.mp4', 'size_bytes' => 1]);

        $this->actingAs($host)
            ->post("/panel/etkinlik/{$event->id}/misafir/{$guest->id}/engelle")
            ->assertRedirect();

        $this->assertTrue($guest->fresh()->is_blocked);
        $this->assertSame(0, $event->media()->count());
    }

    public function test_purge_command_warns_at_11_months_and_deletes_at_12(): void
    {
        Storage::fake('public');
        Notification::fake();
        $host = $this->verifiedUser();

        $warnable = $this->makeLiveEvent($host, ['starts_at' => now()->subMonths(11)->subDay()]);
        $warnable->media()->create(['type' => 'video', 'status' => 'yayinda', 'path' => 'w.mp4', 'size_bytes' => 1]);

        $purgeable = $this->makeLiveEvent($host, ['title' => 'Eski Düğün', 'starts_at' => now()->subMonths(12)->subDay()]);
        $purgeable->media()->create(['type' => 'video', 'status' => 'yayinda', 'path' => 'p.mp4', 'size_bytes' => 1]);

        $this->artisan('events:purge-media')->assertSuccessful();

        Notification::assertSentTo($host, EventMediaPurgeWarning::class);
        $this->assertNotNull($warnable->fresh()->media_purge_warned_at);
        $this->assertSame(1, $warnable->media()->count());   // 11 ay: sadece uyarı
        $this->assertSame(0, $purgeable->media()->count());  // 12 ay: silindi
        $this->assertNotNull($purgeable->fresh());           // etkinlik + LCV kalır
    }
}

<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventInvitationTest extends TestCase
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
            'title' => 'Ayşe & Mehmet Düğünü',
            'starts_at' => now()->addMonth(),
            'venue_name' => 'Grand Salon',
            'venue_address' => 'Hauptstraße 12, Berlin',
            'description' => 'Bu mutlu günümüzde sizleri de aramızda görmek isteriz.',
            'theme' => '',
            'is_active' => true,
        ], $overrides));
    }

    public function test_host_can_create_event_and_gets_unguessable_token(): void
    {
        $host = $this->verifiedUser();

        $this->actingAs($host)
            ->post('/panel/etkinlik', [
                'type' => 'sunnet',
                'title' => 'Kerem\'in Sünnet Düğünü',
                'starts_at' => now()->addWeeks(3)->format('Y-m-d\TH:i'),
                'venue_name' => 'Bahçe Restoran',
            ])
            ->assertRedirect();

        $event = Event::first();
        $this->assertSame($host->id, $event->user_id);
        $this->assertSame(16, strlen($event->token));
        $this->assertSame('kutlama', $event->theme); // türe göre varsayılan tema
    }

    public function test_guest_cannot_access_event_panel(): void
    {
        $this->get('/panel/etkinlikler')->assertRedirect(route('login'));
    }

    public function test_public_invitation_renders_by_token(): void
    {
        $event = $this->makeEvent($this->verifiedUser());

        $this->get('/davet/'.$event->token)
            ->assertOk()
            ->assertSee('Ayşe & Mehmet Düğünü')
            ->assertSee('Grand Salon')
            ->assertSee('Katılımını bildir')
            ->assertSee('Nisoya'); // büyüme döngüsü altbilgisi
    }

    public function test_wrong_token_and_inactive_event_return_404(): void
    {
        $event = $this->makeEvent($this->verifiedUser(), ['is_active' => false]);

        $this->get('/davet/tamamen-yanlis-token')->assertNotFound();
        $this->get('/davet/'.$event->token)->assertNotFound();
    }

    public function test_visitor_can_rsvp_without_account(): void
    {
        $event = $this->makeEvent($this->verifiedUser());

        $response = $this->post('/davet/'.$event->token.'/lcv', [
            'name' => 'Fatma Yılmaz',
            'status' => 'geliyor',
            'party_size' => 3,
            'note' => 'çocuklu geliyoruz',
        ]);

        $response->assertRedirect(route('davet.show', $event->token));
        $response->assertCookie('davet_misafir_'.$event->id);

        $guest = $event->guests()->first();
        $this->assertSame('Fatma Yılmaz', $guest->name);
        $this->assertSame(3, $guest->party_size);
        $this->assertSame('geliyor', $guest->status->value);
    }

    public function test_returning_guest_updates_own_rsvp_via_cookie(): void
    {
        $event = $this->makeEvent($this->verifiedUser());
        $guest = $event->guests()->create(['name' => 'Fatma', 'status' => 'belki', 'party_size' => 2]);

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->post('/davet/'.$event->token.'/lcv', [
                'name' => 'Fatma Yılmaz',
                'status' => 'geliyor',
                'party_size' => 4,
            ])
            ->assertRedirect();

        $this->assertSame(1, $event->guests()->count()); // mükerrer kayıt yok
        $guest->refresh();
        $this->assertSame('geliyor', $guest->status->value);
        $this->assertSame(4, $guest->party_size);
    }

    public function test_rsvp_validation_rejects_bad_input(): void
    {
        $event = $this->makeEvent($this->verifiedUser());

        $this->post('/davet/'.$event->token.'/lcv', [
            'name' => 'A',
            'status' => 'bilmiyorum',
            'party_size' => 99,
        ])->assertSessionHasErrors(['name', 'status', 'party_size']);
    }

    public function test_host_sees_rsvp_summary_and_guest_list(): void
    {
        $host = $this->verifiedUser();
        $event = $this->makeEvent($host);
        $event->guests()->createMany([
            ['name' => 'Ali', 'status' => 'geliyor', 'party_size' => 2],
            ['name' => 'Veli', 'status' => 'geliyor', 'party_size' => 3],
            ['name' => 'Ayşe', 'status' => 'belki', 'party_size' => 1],
            ['name' => 'Zeynep', 'status' => 'gelmiyor', 'party_size' => 1],
        ]);

        $summary = $event->rsvpSummary();
        $this->assertSame(5, $summary['geliyor']['people']);
        $this->assertSame(2, $summary['geliyor']['entries']);
        $this->assertSame(6, $summary['expected_people']); // 5 geliyor + 1 belki

        // guests() ilişkisi normal listeleme için orderByDesc('id') taşır;
        // groupBy() sorgusuna bu miras kalırsa MySQL'in only_full_group_by
        // modunda SQLSTATE[42000] (1055) hatası verir (SQLite bu kısıtlamayı
        // uygulamadığından çalıştırma bazlı bir test bunu YAKALAYAMAZ — bu
        // yüzden üretilen SQL'i doğrudan denetliyoruz). 2026-07-17 üretim
        // raporu: "davetiye oluştur" sonrası yönlendirilen show sayfası bu
        // yüzden 500 veriyordu.
        $sql = $event->guests()->reorder()
            ->selectRaw('status, count(*) as entries, sum(party_size) as people')
            ->groupBy('status')
            ->toSql();
        $this->assertStringNotContainsString('order by', mb_strtolower($sql));

        $this->actingAs($host)
            ->get('/panel/etkinlik/'.$event->id)
            ->assertOk()
            ->assertSee('Ali')
            ->assertSee('Beklenen kişi')
            ->assertSee($event->token); // davet linki gösteriliyor
    }

    public function test_non_owner_cannot_view_or_edit_event(): void
    {
        $event = $this->makeEvent($this->verifiedUser());
        $intruder = $this->verifiedUser();

        $this->actingAs($intruder)->get('/panel/etkinlik/'.$event->id)->assertForbidden();
        $this->actingAs($intruder)->put('/panel/etkinlik/'.$event->id, [
            'type' => 'dugun', 'title' => 'Hack', 'starts_at' => now()->format('Y-m-d\TH:i'),
        ])->assertForbidden();
        $this->actingAs($intruder)->delete('/panel/etkinlik/'.$event->id)->assertForbidden();
    }

    public function test_invitation_page_is_noindex_and_hides_guest_names(): void
    {
        $event = $this->makeEvent($this->verifiedUser());
        $event->guests()->create(['name' => 'GizliMisafirAdı', 'status' => 'geliyor', 'party_size' => 1]);

        $this->get('/davet/'.$event->token)
            ->assertOk()
            ->assertSee('noindex', false)
            ->assertDontSee('GizliMisafirAdı');
    }
}

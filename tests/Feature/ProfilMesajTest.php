<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Profilden doğrudan mesaj — KIRIK HUNİ onarımı (2026-07-28).
 *
 * Bulgu: mesajlaşma yalnız bir İLAN üzerinden başlatılabiliyordu
 * (POST /ilan/{listing}/mesaj). Aktif ilanı olmayan bir yetenek /adaylar
 * listesinde görünüyor, profiline girilebiliyor ama kendisine ULAŞILAMIYORDU.
 * "Yeteneğini paraya dönüştür" vaadinin karşılığı olan yol kapalıydı.
 *
 * conversations.listing_id zaten nullable'dı; eksik olan giriş noktasıydı.
 */
class ProfilMesajTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function uye(array $ozellikler = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $ozellikler));
    }

    public function test_ilani_olmayan_kisiye_profilinden_mesaj_gonderilebilir(): void
    {
        Notification::fake();

        $gonderen = $this->uye();
        $alici = $this->uye();
        $this->assertSame(0, $alici->listings()->count(), 'Test kurulumu: alıcının ilanı olmamalı');

        $this->actingAs($gonderen)
            ->post(route('messages.startWithUser', $alici->username), ['body' => 'Merhaba, iş için yazıyorum.'])
            ->assertRedirect();

        $konusma = Conversation::query()->whereNull('listing_id')->first();
        $this->assertNotNull($konusma, 'İlansız konuşma oluşmadı');
        $this->assertSame(1, $konusma->messages()->count());
        $this->assertSame('Merhaba, iş için yazıyorum.', $konusma->messages()->first()->body);
    }

    public function test_ikinci_mesaj_yeni_konusma_acmaz(): void
    {
        // findOrCreateBetween'de `where('listing_id', null)` SQL'de hiçbir
        // satırla eşleşmez; whereNull olmadan her mesaj yeni konuşma açardı ve
        // iki kişi arasında onlarca kopuk sohbet birikirdi.
        Notification::fake();

        $gonderen = $this->uye();
        $alici = $this->uye();

        foreach (['İlk mesaj', 'İkinci mesaj'] as $metin) {
            $this->actingAs($gonderen)
                ->post(route('messages.startWithUser', $alici->username), ['body' => $metin]);
        }

        $this->assertSame(1, Conversation::query()->whereNull('listing_id')->count(), 'Her mesaj yeni konuşma açmış');
        $this->assertSame(2, Conversation::query()->whereNull('listing_id')->first()->messages()->count());
    }

    public function test_ters_yonden_yazan_ayni_konusmaya_duser(): void
    {
        Notification::fake();

        $a = $this->uye();
        $b = $this->uye();

        $this->actingAs($a)->post(route('messages.startWithUser', $b->username), ['body' => 'Selam']);
        $this->actingAs($b)->post(route('messages.startWithUser', $a->username), ['body' => 'Merhaba']);

        $this->assertSame(1, Conversation::query()->whereNull('listing_id')->count());
    }

    public function test_kisi_kendine_yazamaz(): void
    {
        $uye = $this->uye();

        $this->actingAs($uye)
            ->post(route('messages.startWithUser', $uye->username), ['body' => 'Kendime'])
            ->assertRedirect();

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_misafir_gonderemez(): void
    {
        $alici = $this->uye();

        $this->post(route('messages.startWithUser', $alici->username), ['body' => 'Merhaba'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_bos_mesaj_reddedilir(): void
    {
        $gonderen = $this->uye();
        $alici = $this->uye();

        $this->actingAs($gonderen)
            ->post(route('messages.startWithUser', $alici->username), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_honeypot_dolduran_bot_konusma_acamaz(): void
    {
        $gonderen = $this->uye();
        $alici = $this->uye();

        $this->actingAs($gonderen)->post(route('messages.startWithUser', $alici->username), [
            'body' => 'Spam',
            'website' => 'http://bot.example',
        ]);

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_profil_sayfasi_mesaj_kutusunu_gosterir(): void
    {
        $gonderen = $this->uye();
        $alici = $this->uye();

        $this->actingAs($gonderen)->get(route('profiles.show', $alici->username))
            ->assertOk()
            ->assertSee('Mesaj gönder')
            ->assertSee('name="website"', false);
    }

    public function test_kendi_profilinde_mesaj_kutusu_cikmaz(): void
    {
        $uye = $this->uye();

        $this->actingAs($uye)->get(route('profiles.show', $uye->username))
            ->assertOk()
            ->assertDontSee('Mesaj gönder');
    }

    public function test_misafire_giris_cagrisi_gosterilir(): void
    {
        $alici = $this->uye();

        $this->get(route('profiles.show', $alici->username))
            ->assertOk()
            ->assertSee('Giriş yap')
            ->assertDontSee('Mesaj gönder');
    }

    public function test_rozetler_baslik_etiketinin_disinda(): void
    {
        // İçerideyken ekran okuyucu başlığı "Hakan Güvenilir Doğrulanmış"
        // diye tek parça okuyordu; başlık kişinin ADIDIR.
        $alici = $this->uye(['is_verified' => true]);

        $icerik = $this->get(route('profiles.show', $alici->username))->assertOk()->getContent();

        preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $icerik, $m);
        $this->assertNotEmpty($m, 'Profilde h1 bulunamadı');
        $this->assertStringNotContainsString('Doğrulanmış', $m[1], 'Rozet hâlâ h1 içinde');
    }
}

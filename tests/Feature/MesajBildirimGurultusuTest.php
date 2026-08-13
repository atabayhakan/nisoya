<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Okunmuş mesaj için bildirim GÖNDERİLMEZ.
 *
 * ---------------------------------------------------------------------------
 * SAHİBİN BİLDİRİMİ (2026-08-13)
 *
 * "Her mesaj yazdığında mail geliyor; sohbet ekranı açıkken mail gelmesine
 * gerek yok."
 *
 * Karşılıklı yazışırken her satır için bir e-posta, gelen kutusunu doldurur ve
 * insanı bildirimleri TOPTAN kapatmaya iter — yani gerçekten gerekli olan
 * bildirimi de kaybettirir.
 *
 * ---------------------------------------------------------------------------
 * "EKRAN AÇIK MI" DEĞİL, "OKUNDU MU"
 *
 * Ekran açıklığını yoklamak kırılgan: sekmesi açık unutulmuş ama başında
 * olmayan kişi bildirim alamazdı. Okundu bilgisi daha sağlam — sekmeyi bir
 * dakika sonra açanı da kapsar.
 *
 * Bedeli: gerçekten uzakta olan kişiye bildirim bir dakika geç gider. Uzaktaki
 * biri için bir dakika hiçbir şey.
 */
class MesajBildirimGurultusuTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: User, 2: Conversation} */
    private function konusma(): array
    {
        $gonderen = User::factory()->create();
        $alici = User::factory()->create();

        $konusma = Conversation::create([
            'user_one_id' => $gonderen->id,
            'user_two_id' => $alici->id,
        ]);

        return [$gonderen, $alici, $konusma];
    }

    private function mesaj(Conversation $k, User $gonderen, ?string $okundu = null): Message
    {
        return $k->messages()->create([
            'sender_id' => $gonderen->id,
            'body' => 'Merhaba, müsait misin?',
            'read_at' => $okundu,
        ]);
    }

    public function test_okunmus_mesaj_icin_hicbir_kanal_calismaz(): void
    {
        /*
         * ASIL KURAL. Yalnız postayı kesmek yetmezdi: okunmuş bir mesaj için
         * zilde bildirim biriktirmek de aynı gürültünün başka biçimi.
         */
        [$gonderen, $alici, $konusma] = $this->konusma();
        $mesaj = $this->mesaj($konusma, $gonderen, okundu: now()->toDateTimeString());

        $bildirim = new NewMessageNotification('Merhaba', $gonderen->name, $konusma->id, $mesaj->id);

        $this->assertSame([], $bildirim->via($alici),
            'Okunmuş mesaj için bildirim gönderiliyor — yazışma sırasında gelen kutusu dolar.');
    }

    public function test_okunmamis_mesaj_bildirim_gonderilir(): void
    {
        // Ters yön: "hiç gönderme" çözümü de yukarıdaki testi geçerdi.
        [$gonderen, $alici, $konusma] = $this->konusma();
        $mesaj = $this->mesaj($konusma, $gonderen);

        $kanallar = (new NewMessageNotification('Merhaba', $gonderen->name, $konusma->id, $mesaj->id))->via($alici);

        $this->assertContains('mail', $kanallar);
        $this->assertContains('database', $kanallar);
    }

    public function test_mesaj_kimligi_yoksa_eski_davranis_surer(): void
    {
        /*
         * Geriye dönük güvenlik: kimliksiz kurulan bir bildirim (ör. kuyrukta
         * bekleyen eski iş) sessizce kaybolmamalı.
         */
        [$gonderen, $alici, $konusma] = $this->konusma();

        $kanallar = (new NewMessageNotification('Merhaba', $gonderen->name, $konusma->id))->via($alici);

        $this->assertContains('mail', $kanallar);
    }

    public function test_gonderim_gecikmeli_kuyruga_giriyor(): void
    {
        /*
         * Gecikme OLMAZSA okundu kontrolü hiçbir işe yaramaz: bildirim mesajla
         * aynı anda gider ve okunmaya fırsat kalmaz. İkisi birlikte anlamlı.
         */
        Notification::fake();

        [$gonderen, $alici, $konusma] = $this->konusma();

        $this->actingAs($gonderen)
            ->post(route('panel.messages.store', $konusma), ['body' => 'Merhaba, müsait misin?'])
            ->assertRedirect();

        Notification::assertSentTo($alici, NewMessageNotification::class,
            function (NewMessageNotification $b) {
                $this->assertNotNull($b->messageId, 'Mesaj kimliği taşınmıyor — okundu kontrolü çalışamaz.');
                $this->assertNotNull($b->delay, 'Bildirim gecikmesiz gidiyor — okunmaya fırsat kalmaz.');

                return true;
            });
    }
}

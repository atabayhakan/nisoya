<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Yeni mesaj sesi.
 *
 * ---------------------------------------------------------------------------
 * ÜÇ KURAL (sahibin isteği üzerine eklendi, 2026-08-13)
 *
 *   1. KENDİ mesajında çalmaz — her yazdığında kendine ses duymak can sıkar.
 *   2. Kapatılabilir ve tercih hatırlanır. Kapatılamayan ses, sesin
 *      kendisinden kötüdür.
 *   3. Tarayıcı izin vermezse SESSİZCE geçilir — sohbet sesten önemli.
 *
 * Ses saf tarayıcı işi olduğu için sunucu testi ancak arayüzün ve kuralın
 * YERİNDE olduğunu ölçebilir; sesin duyulduğunu ölçemez. Yine de en pahalı
 * iki hatayı yakalar: sessize alma düğmesinin kaybolması ve "kendi mesajında
 * çalmasın" kapısının düşmesi.
 */
class SohbetSesiTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Conversation} */
    private function konusma(): array
    {
        $ben = User::factory()->create();
        $karsi = User::factory()->create();

        $konusma = Conversation::create([
            'user_one_id' => $ben->id,
            'user_two_id' => $karsi->id,
        ]);

        return [$ben, $konusma];
    }

    public function test_sessize_alma_dugmesi_var(): void
    {
        [$ben, $konusma] = $this->konusma();

        $this->actingAs($ben)
            ->get(route('panel.messages.show', $konusma))
            ->assertOk()
            ->assertSee('id="ses-toggle"', escape: false)
            // Erişilebilirlik: durum yalnız simgeyle değil, ekran okuyucuya da
            // söylenmeli.
            ->assertSee('aria-pressed', escape: false)
            ->assertSee('Mesaj sesini kapat', escape: false);
    }

    public function test_ses_yalniz_karsi_tarafin_mesajinda_calar(): void
    {
        /*
         * ASIL KURAL. Kapı düşerse kullanıcı kendi yazdığı her mesajda ses
         * duyar — özelliği sevimliden sinir bozucuya çeviren tek şey bu.
         *
         * Ses saf JS olduğu için kaynak üzerinden ölçülüyor; davranışsal bir
         * karşılığı yok. Yine de kapının VARLIĞINI kilitler.
         */
        [$ben, $konusma] = $this->konusma();

        $icerik = (string) $this->actingAs($ben)
            ->get(route('panel.messages.show', $konusma))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('if (! m.mine) sesCal();', $icerik,
            '"Kendi mesajımda çalma" kapısı kaybolmuş.');
    }

    public function test_ses_hatasi_sohbeti_bozmuyor(): void
    {
        // Tarayıcı izni yoksa/AudioContext yoksa sessizce geçilmeli.
        [$ben, $konusma] = $this->konusma();

        $icerik = (string) $this->actingAs($ben)
            ->get(route('panel.messages.show', $konusma))
            ->getContent();

        $this->assertStringContainsString('catch (e) { /* ses çalınamadı', $icerik,
            'Ses hatası yutulmuyor — sohbet sesten önemli.');
    }
}

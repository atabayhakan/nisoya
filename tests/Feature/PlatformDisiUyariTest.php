<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Support\PlatformDisiIsaret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Platform-dışı çekme uyarısı (açık işler envanteri).
 *
 * Sözleşme: "WhatsApp'tan devam edelim" tarzı mesajlar ENGELLENMEZ ama
 * alıcıya, ilgili mesajın altında yumuşak bir dikkat notu düşülür. Uyarının
 * muhatabı ALICIDIR — kendi mesajının altında uyarı çıkmaz. Kalıplar
 * muhafazakârdır: fiyat, tarih-saat gibi gündelik rakamlar tetiklememeli
 * (yanlış alarm yorgunluğu uyarıyı görünmez yapar).
 */
class PlatformDisiUyariTest extends TestCase
{
    use RefreshDatabase;

    private const UYARI = 'platform dışına taşıma teklifi';

    // ------------------------------------------------------------- Tespit

    public function test_platform_disi_kaliplar_yakalanir(): void
    {
        $pozitifler = [
            'whatsapptan devam edelim mi',
            'Watsap var mı sende',
            'wa.me/905321234567',
            'telegramdan yazsana',
            't.me/kullanici',
            'instagramdan dm at',
            'wp den konuşalım',
            'numaram şu, oradan ara',
            '0532 123 45 67',
            '+49 171 234 5678',
            'IBAN: TR33 0006 1005 1978 6457 8413 26',
        ];

        foreach ($pozitifler as $metin) {
            $this->assertTrue(PlatformDisiIsaret::tespit($metin), "Yakalanmalıydı: {$metin}");
        }
    }

    public function test_gundelik_rakamlar_ve_metin_tetiklemez(): void
    {
        $negatifler = [
            'Merhaba, ilan hâlâ geçerli mi?',
            'Fiyat 1.500.000 TL olur mu',
            '01.02.2026 15:30 uygun mu sana',
            'Yarın saat 10:30 gibi buluşalım',
            '3+1 daire, 125 m2, 2. kat',
            'Sipariş 5 gün içinde hazır',
            '',
            null,
        ];

        foreach ($negatifler as $metin) {
            $this->assertFalse(PlatformDisiIsaret::tespit($metin), 'Tetiklememeliydi: '.var_export($metin, true));
        }
    }

    // ------------------------------------------------------------- Arayüz

    /** @return array{User, User, Conversation} */
    private function konusma(): array
    {
        $ben = User::factory()->create(['email_verified_at' => now()]);
        $karsi = User::factory()->create(['email_verified_at' => now()]);
        $konusma = Conversation::findOrCreateBetween($ben->id, $karsi->id, null);

        return [$ben, $karsi, $konusma];
    }

    /**
     * Uyarı metni JS aynası (dikkatNode) yüzünden her sayfanın <script>
     * bloğunda BİR kez zaten geçer — bu yüzden "görünür/görünmez" sayımla
     * ayrılır: yalnız JS kopyası = 1, sunucu ayrıca render ettiyse ≥ 2.
     */
    private function uyariSayisi(User $ben, Conversation $konusma): int
    {
        $icerik = $this->actingAs($ben)
            ->get(route('panel.messages.show', $konusma))
            ->assertOk()
            ->getContent();

        return substr_count($icerik, self::UYARI);
    }

    public function test_karsi_tarafin_cekme_mesajinda_uyari_gorunur(): void
    {
        [$ben, $karsi, $konusma] = $this->konusma();

        $konusma->messages()->create(['sender_id' => $karsi->id, 'body' => 'whatsapptan devam edelim, numaram 0532 123 45 67']);

        $this->assertSame(2, $this->uyariSayisi($ben, $konusma), 'Uyarı mesajın altında render edilmeliydi.');
    }

    public function test_kendi_mesajinda_ve_masum_mesajda_uyari_yok(): void
    {
        [$ben, $karsi, $konusma] = $this->konusma();

        // Çekme teklifini BEN yazdım (uyarının muhatabı ben değilim)…
        $konusma->messages()->create(['sender_id' => $ben->id, 'body' => 'istersen whatsapptan konuşalım']);
        // …karşı taraf masum bir mesaj yazdı.
        $konusma->messages()->create(['sender_id' => $karsi->id, 'body' => 'Ürün yarın hazır olur.']);

        $this->assertSame(1, $this->uyariSayisi($ben, $konusma), 'Yalnız JS kopyası kalmalıydı — sunucu uyarı render etmemeli.');
    }

    public function test_akis_ucu_dikkat_bayragini_dogru_isaretler(): void
    {
        [$ben, $karsi, $konusma] = $this->konusma();

        $konusma->messages()->create(['sender_id' => $karsi->id, 'body' => 'telegramdan yazsana']);
        $konusma->messages()->create(['sender_id' => $karsi->id, 'body' => 'Yarın uygun mu?']);
        $konusma->messages()->create(['sender_id' => $ben->id, 'body' => 'wp var mı']);

        $mesajlar = $this->actingAs($ben)
            ->getJson(route('panel.messages.stream', $konusma).'?after=0')
            ->assertOk()
            ->json('messages');

        $bayraklar = collect($mesajlar)->map(fn ($m) => [$m['body'], $m['dikkat']])->all();

        $this->assertContains(['telegramdan yazsana', true], $bayraklar);
        $this->assertContains(['Yarın uygun mu?', false], $bayraklar);
        // Kendi mesajım kalıp içerse de bayrak taşımaz.
        $this->assertContains(['wp var mı', false], $bayraklar);
    }
}

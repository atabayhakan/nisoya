<?php

namespace Tests\Feature;

use App\Enums\TrustTier;
use App\Models\Conversation;
use App\Models\Deal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Değerlendirme kapısı (açık işler envanteri, 2026-08-02).
 *
 * Eski durum iki uçtan istismar edilebiliyordu: (1) kapı "aramızda bir
 * konuşma kaydı var"dı — tek yönlü tek mesaj yetiyordu; (2) rozet hesabı
 * değerlendirenin niteliğine bakmıyordu — beş taze hesap + beş "merhaba" +
 * beş yıldız = "Güvenilir satıcı". Güven sitenin ana vaadi olduğu için bu
 * testler kapının iki kanadını da mühürlüyor:
 *
 *   Kapı: iki tarafın da yazdığı konuşma YA DA tamamlanmış anlaşma.
 *   Rozet: yalnız NİTELİKLİ değerlendirmeler sayılır (doğrulanmış e-posta +
 *   [anlaşma-bağlı YA DA ≥7 günlük hesap]); görünen sayı/ortalama değişmez.
 */
class DegerlendirmeKapisiTest extends TestCase
{
    use RefreshDatabase;

    private function konusma(User $a, User $b): Conversation
    {
        return Conversation::create(['user_one_id' => $a->id, 'user_two_id' => $b->id, 'last_message_at' => now()]);
    }

    private function degerlendir(User $reviewer, User $seller, int $puan = 5): TestResponse
    {
        return $this->actingAs($reviewer)
            ->post("/uye/{$seller->username}/degerlendir", ['rating' => $puan]);
    }

    // ---------------------------------------------------------------- Kapı

    public function test_tek_yonlu_mesajla_degerlendirilemez(): void
    {
        [$seller, $reviewer] = [User::factory()->create(), User::factory()->create()];

        // Yalnız değerlendiren yazmış; karşı taraf hiç cevap vermemiş.
        $this->konusma($reviewer, $seller)
            ->messages()->create(['sender_id' => $reviewer->id, 'body' => 'Merhaba!']);

        $this->degerlendir($reviewer, $seller)->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_mesajsiz_konusma_kaydi_da_yetmez(): void
    {
        [$seller, $reviewer] = [User::factory()->create(), User::factory()->create()];
        $this->konusma($reviewer, $seller);

        $this->degerlendir($reviewer, $seller)->assertForbidden();
    }

    public function test_karsilikli_konusma_kapiyi_acar(): void
    {
        [$seller, $reviewer] = [User::factory()->create(), User::factory()->create()];
        $konusma = $this->konusma($reviewer, $seller);
        $konusma->messages()->create(['sender_id' => $reviewer->id, 'body' => 'İlan güncel mi?']);
        $konusma->messages()->create(['sender_id' => $seller->id, 'body' => 'Evet.']);

        $this->degerlendir($reviewer, $seller)->assertRedirect();
        $this->assertDatabaseHas('reviews', ['reviewer_id' => $reviewer->id, 'reviewee_id' => $seller->id]);
    }

    public function test_tamamlanmis_anlasma_konusma_olmadan_kapiyi_acar(): void
    {
        [$seller, $reviewer] = [User::factory()->create(), User::factory()->create()];

        // Konuşmada tek mesaj bile yok — ama anlaşma iki tarafın onayından
        // geçmiş gerçek bir etkileşim izi; kapıyı tek başına açar.
        Deal::create([
            'conversation_id' => $this->konusma($reviewer, $seller)->id,
            'seller_id' => $seller->id,
            'buyer_id' => $reviewer->id,
            'proposed_by' => $reviewer->id,
            'status' => 'tamamlandi',
            'completed_at' => now(),
        ]);

        $this->degerlendir($reviewer, $seller)->assertRedirect();

        // Anlaşma değerlendirmeye bağlanır → "Doğrulanmış işlem" (K-C).
        $this->assertNotNull(
            $seller->reviewsReceived()->firstOrFail()->deal_id,
            'Anlaşma üzerinden gelen değerlendirme anlaşmaya bağlanmalı.'
        );
    }

    public function test_profil_formu_kapiyla_ayni_kurali_uygular(): void
    {
        [$seller, $reviewer] = [User::factory()->create(), User::factory()->create()];
        $konusma = $this->konusma($reviewer, $seller);
        $konusma->messages()->create(['sender_id' => $reviewer->id, 'body' => 'Merhaba!']);

        // Tek yönlü konuşma: form görünmez (görünüp 403 yemek "bozuk" hissettirir).
        $this->actingAs($reviewer)->get("/uye/{$seller->username}")
            ->assertOk()
            ->assertDontSee('Bu üyeyi değerlendir');

        // Karşı taraf cevap yazınca form belirir.
        $konusma->messages()->create(['sender_id' => $seller->id, 'body' => 'Buyrun?']);

        $this->actingAs($reviewer)->get("/uye/{$seller->username}")
            ->assertOk()
            ->assertSee('Bu üyeyi değerlendir');
    }

    // --------------------------------------------------------------- Rozet

    /** @param  array<int, User>  $reviewers */
    private function karsilikliYorumlar(User $seller, array $reviewers): void
    {
        foreach ($reviewers as $reviewer) {
            $konusma = $this->konusma($reviewer, $seller);
            $konusma->messages()->create(['sender_id' => $reviewer->id, 'body' => 'İlgileniyorum.']);
            $konusma->messages()->create(['sender_id' => $seller->id, 'body' => 'Konuşalım.']);

            $this->degerlendir($reviewer, $seller)->assertRedirect();
        }
    }

    public function test_bes_taze_hesap_guvenilir_rozeti_basamaz(): void
    {
        $seller = User::factory()->create(['created_at' => now()->subDays(90)]);
        $reviewers = User::factory()->count(5)->create()->all(); // bugün açılmış hesaplar

        $this->karsilikliYorumlar($seller, $reviewers);

        $profil = $seller->fresh()->trustProfile();

        // Görünen sayı/ortalama değişmez — sayfada gizlenen yorum yok…
        $this->assertSame(5, $profil['review_count']);
        // …ama rozetin kanıt eşiği taze hesapları saymaz.
        $this->assertSame(0, $profil['qualified_reviews']);
        $this->assertNotSame(TrustTier::Guvenilir, $profil['tier'], 'Beş taze hesapla rozet basılamamalı.');
    }

    public function test_yerlesik_hesaplarin_yorumlari_rozeti_acar(): void
    {
        $seller = User::factory()->create(['created_at' => now()->subDays(90)]);
        $reviewers = User::factory()->count(5)->create(['created_at' => now()->subDays(30)])->all();

        $this->karsilikliYorumlar($seller, $reviewers);

        $profil = $seller->fresh()->trustProfile();

        $this->assertSame(5, $profil['qualified_reviews']);
        $this->assertSame(TrustTier::Guvenilir, $profil['tier'], 'Yerleşik hesapların gerçek yorumları rozeti açmalı.');
    }

    public function test_anlasma_bagli_yorum_hesap_yasi_sartini_atlar(): void
    {
        $seller = User::factory()->create(['created_at' => now()->subDays(90)]);
        $reviewer = User::factory()->create(); // taze hesap

        Deal::create([
            'conversation_id' => $this->konusma($reviewer, $seller)->id,
            'seller_id' => $seller->id,
            'buyer_id' => $reviewer->id,
            'proposed_by' => $reviewer->id,
            'status' => 'tamamlandi',
            'completed_at' => now(),
        ]);

        $this->degerlendir($reviewer, $seller)->assertRedirect();

        // Taze hesap ama anlaşma-bağlı: iki tarafın onayından geçmiş gerçek
        // işlem, hesap yaşından daha güçlü bir kanıt — nitelikli sayılır.
        $this->assertSame(1, $seller->fresh()->trustProfile()['qualified_reviews']);
    }

    public function test_dogrulanmamis_epostali_yorum_nitelikli_sayilmaz(): void
    {
        // HTTP kapısı doğrulamasız hesabı zaten içeri almaz (route 'verified');
        // burada rozet hesabının KENDİ savunması sınanır — ör. e-postası
        // sonradan doğrulamasız kalmış bir hesabın eski yorumu.
        $seller = User::factory()->create(['created_at' => now()->subDays(90)]);
        $reviewer = User::factory()->create(['created_at' => now()->subDays(30), 'email_verified_at' => null]);

        Review::create(['reviewee_id' => $seller->id, 'reviewer_id' => $reviewer->id, 'rating' => 5, 'status' => 'yayinda']);

        $profil = $seller->fresh()->trustProfile();

        $this->assertSame(1, $profil['review_count']);
        $this->assertSame(0, $profil['qualified_reviews']);
    }
}

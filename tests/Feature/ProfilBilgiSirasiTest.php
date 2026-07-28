<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Conversation;
use App\Models\PaymentLink;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profil sayfasında bilgi sırası ve değerlendirme sayfalaması (2026-07-29).
 *
 * BULUNAN HATA — SIRA: tıklanabilir PayPal linki, IBAN ve QR profil başlığının
 * en üstünde duruyordu; bunları uyaran güvenlik kartı ise 66 satır AŞAĞIDAYDI.
 * Ziyaretçi "PayPal'da Mal ve Hizmetler seç" uyarısını hiç görmeden satıcının
 * ödeme sayfasına çıkabiliyordu — ön ödeme dolandırıcılığının istediği tam akış.
 * K-A/K-D güven yatırımı tek bir sıralama hatasıyla atlatılıyordu.
 *
 * Doğru sıra projenin KENDİ deseniydi (`listings/show`, `vitrin/listings/show`:
 * mesaj → ödeme → uyarı); profil tek istisnaydı. Mevcut `TransactionSafetyTest`
 * yalnız kartın VAR OLDUĞUNU doğruluyordu, YERİNİ değil — bu yüzden hata
 * testlerden geçiyordu. Bu dosya sırayı mühürler.
 *
 * BULUNAN HATA — SAYFALAMA: değerlendirmeler sınırsız `->get()` ile çekiliyordu.
 * Düz `paginate()` eklemek iki sessiz bozulma üretirdi ve ikisi de burada
 * test ediliyor: puan ortalamasının yalnız görünen sayfadan hesaplanması, ve
 * kullanıcının kendi yorumu 2. sayfadayken formun "Gönder" moduna düşmesi.
 */
class ProfilBilgiSirasiTest extends TestCase
{
    use RefreshDatabase;

    private function satici(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        PaymentLink::create([
            'user_id' => $user->id,
            'method' => PaymentMethod::PayPal,
            'detail' => 'https://paypal.me/ornek',
        ]);

        return $user;
    }

    /** Review'in factory'si yok; kolon adı `reviewee_id` (bkz. InteractionTest). */
    private function degerlendir(User $hedef, int $puan, ?User $yazan = null, ?string $tarih = null, ?string $yorum = null): Review
    {
        $review = Review::create([
            'reviewee_id' => $hedef->id,
            'reviewer_id' => ($yazan ?? User::factory()->create(['email_verified_at' => now()]))->id,
            'rating' => $puan,
            'comment' => $yorum,
            'status' => 'yayinda',
        ]);

        // created_at MASS-ASSIGN EDİLEMEZ (fillable'da yok) — create() içine
        // yazmak sessizce yok sayılır ve tüm kayıtlar aynı zaman damgasını alır.
        // O zaman `latest()` sıralaması keyfîleşir ve "en eski yorum 2. sayfada"
        // öncülü hiç kurulmamış olur; test yanlış sebeple geçer/düşer.
        if ($tarih !== null) {
            $review->forceFill(['created_at' => now()->sub($tarih)])->saveQuietly();
        }

        return $review;
    }

    /**
     * ASIL BEKÇİ: uyarı, uyardığı linklerden SONRA gelmeli.
     */
    public function test_guvenlik_uyarisi_odeme_linklerinden_sonra_gelir(): void
    {
        $satici = $this->satici();

        $this->get(route('profiles.show', $satici->username))
            ->assertSuccessful()
            ->assertSeeInOrder([
                'Kabul ettiği ödeme yöntemleri',
                'Mal ve Hizmetler',
            ], false);
    }

    /**
     * Mesajlaşma çağrısı ödeme kanallarından ÖNCE gelmeli: platform içi
     * iletişim birincil eylemdir, ödeme kanalı ikincil bilgidir.
     */
    public function test_mesaj_cagrisi_odeme_kanallarindan_once_gelir(): void
    {
        $satici = $this->satici();
        $ziyaretci = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($ziyaretci)
            ->get(route('profiles.show', $satici->username))
            ->assertSuccessful()
            ->assertSeeInOrder([
                'kişisine yaz',
                'Kabul ettiği ödeme yöntemleri',
                'Mal ve Hizmetler',
            ], false);
    }

    public function test_puan_ortalamasi_gorunen_sayfadan_degil_tumunden_hesaplanir(): void
    {
        $satici = User::factory()->create(['email_verified_at' => now()]);

        // 1. sayfada 10 tane 5 yıldız, 2. sayfada 5 tane 1 yıldız.
        // Ortalama TÜMÜNDEN: (10*5 + 5*1) / 15 = 3.7
        for ($i = 0; $i < 10; $i++) {
            $this->degerlendir($satici, 5);
        }
        for ($i = 0; $i < 5; $i++) {
            $this->degerlendir($satici, 1);
        }

        $this->get(route('profiles.show', $satici->username))
            ->assertSuccessful()
            ->assertSee('3.7', false)
            ->assertSee('15 değerlendirme', false);
    }

    /**
     * Kendi yorumu 2. sayfada olan kullanıcı yine "güncelle" modunu görmeli.
     * Aksi hâlde ikinci bir kayıt denenir.
     */
    public function test_kendi_yorumu_ikinci_sayfadayken_de_bulunur(): void
    {
        $satici = User::factory()->create(['email_verified_at' => now()]);
        $ziyaretci = User::factory()->create(['email_verified_at' => now()]);

        Conversation::findOrCreateBetween($ziyaretci->id, $satici->id, null);

        // Ziyaretçinin yorumu EN ESKİ olsun ki `latest()` sıralamasında
        // son sayfaya düşsün.
        $benim = $this->degerlendir($satici, 4, $ziyaretci, '1 year');

        for ($i = 0; $i < 12; $i++) {
            $this->degerlendir($satici, 5);
        }

        $yanit = $this->actingAs($ziyaretci)
            ->get(route('profiles.show', $satici->username))
            ->assertSuccessful();

        $yanit->assertSee('Değerlendirmeni güncelle', false);
        $yanit->assertDontSee('Bu üyeyi değerlendir', false);

        $this->assertSame(4, $benim->fresh()->rating);
    }

    /**
     * Yorum sayfası ilan listesini kaydırmamalı: iki paginator ayrı ad
     * kullanmalı.
     *
     * DAVRANIŞSAL test — "sayfada `?page=2` dizesi geçmesin" gibi bir olumsuz
     * iddia kırılgandı: koca bir HTML'de alakasız bir bağlantı yüzünden
     * kırılabiliyordu. Burada gerçek gereksinim sınanıyor: `?yorum=2`
     * değerlendirmeleri ilerletir ama İLANLARI ilerletmez.
     */
    public function test_yorum_sayfasi_ilan_listesini_kaydirmaz(): void
    {
        $satici = User::factory()->create(['email_verified_at' => now()]);

        // 15 değerlendirme (10'ar sayfalanıyor) → 2 sayfa.
        // En eskiyi işaretle: `latest()` sıralamasında 2. sayfaya düşer.
        $this->degerlendir($satici, 1, null, '1 year', 'EN-ESKI-YORUM');
        for ($i = 0; $i < 14; $i++) {
            $this->degerlendir($satici, 5);
        }

        $birinci = $this->get(route('profiles.show', $satici->username))->assertSuccessful();
        $ikinci = $this->get(route('profiles.show', $satici->username).'?yorum=2')->assertSuccessful();

        // `yorum` parametresi GERÇEKTEN değerlendirmeleri ilerletiyor:
        // en eski yorum yalnız 2. sayfada görünmeli.
        $birinci->assertDontSee('EN-ESKI-YORUM', false);
        $ikinci->assertSee('EN-ESKI-YORUM', false);

        // İlan bölümü etkilenmiyor: ilanlar kendi `page` parametresini kullanır.
        $ikinci->assertSee('aktif ilan', false);

        // Puan özeti SAYFADAN BAĞIMSIZ — asıl tuzak buydu: ortalama yalnız
        // görünen sayfadan hesaplansaydı iki sayfada farklı çıkardı.
        // (14×5 + 1×1) / 15 = 4.7
        $birinci->assertSee('4.7', false);
        $ikinci->assertSee('4.7', false);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ReportCategory;
use App\Enums\TrustTier;
use App\Models\Listing;
use App\Models\PaymentLink;
use App\Models\Review;
use App\Models\User;
use App\Services\FraudBlocklist;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * İşlem-güvenliği omurgası (K-A ödeme uyarısı, K-B itibar sinyalleri,
 * K-D dolandırıcılık bildirimi + parmak izi kara listesi). Nisoya ödemeye
 * aracılık etmez; bu katman dolandırıcılığı caydırır, kaldırılabilir ve
 * tekrarlanamaz kılar.
 */
class TransactionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    // ---------- K-B: TrustTier (hesaplanmış itibar) ----------

    public function test_fresh_user_is_yeni_tier(): void
    {
        $user = User::factory()->create();

        $this->assertSame(TrustTier::Yeni, $user->trustTier());
        $this->assertTrue($user->isNewSeller());
    }

    public function test_verified_complete_established_user_is_uye_tier(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'avatar_path' => 'avatars/x.jpg',
            'bio' => 'Merhaba, hizmet veriyorum.',
            'country_code' => 'DE',
            'created_at' => now()->subDays(45),
        ]);

        $this->assertSame(TrustTier::Uye, $user->trustTier());
        $this->assertFalse($user->isNewSeller());
    }

    public function test_highly_reviewed_established_user_is_guvenilir_tier(): void
    {
        $seller = User::factory()->create([
            'email_verified_at' => now(),
            'avatar_path' => 'avatars/x.jpg',
            'bio' => 'Uzun süredir buradayım.',
            'country_code' => 'DE',
            'created_at' => now()->subDays(90),
        ]);

        foreach (range(1, 5) as $i) {
            Review::create([
                'reviewee_id' => $seller->id,
                // Değerlendirme kapısı (2026-08-02): rozet yalnız NİTELİKLİ
                // yorumları sayar — değerlendiren hesabı ≥7 günlük olmalı.
                'reviewer_id' => User::factory()->create(['created_at' => now()->subDays(30)])->id,
                'listing_id' => null,
                'rating' => 5,
                'status' => 'yayinda',
            ]);
        }

        $this->assertSame(TrustTier::Guvenilir, $seller->trustTier());
    }

    public function test_hidden_reviews_do_not_count_toward_tier(): void
    {
        $seller = User::factory()->create([
            'email_verified_at' => now(),
            'avatar_path' => 'avatars/x.jpg',
            'bio' => 'Test.',
            'country_code' => 'DE',
            'created_at' => now()->subDays(90),
        ]);

        foreach (range(1, 5) as $i) {
            Review::create([
                'reviewee_id' => $seller->id,
                'reviewer_id' => User::factory()->create()->id,
                'listing_id' => null,
                'rating' => 5,
                'status' => 'gizli',
            ]);
        }

        // Gizli değerlendirmeler sayılmaz → Güvenilir olamaz (ama yaş+profil ile Üye).
        $this->assertSame(TrustTier::Uye, $seller->trustTier());
    }

    public function test_trust_badge_shown_on_public_profile(): void
    {
        User::factory()->create(['username' => 'guven-test']);

        $this->get('/uye/guven-test')
            ->assertOk()
            ->assertSee('Yeni üye');
    }

    // ---------- K-A: Ödeme anı güvenlik kartı ----------

    public function test_payment_safety_card_shown_on_profile_when_payment_link_present(): void
    {
        $user = User::factory()->create(['username' => 'odeme-guvenlik']);
        PaymentLink::create(['user_id' => $user->id, 'method' => 'paypal', 'detail' => 'https://paypal.me/x']);

        $this->get('/uye/odeme-guvenlik')
            ->assertOk()
            ->assertSee('Güvenli ödeme ipuçları')
            ->assertSee('Mal ve Hizmetler');
    }

    public function test_no_payment_safety_card_on_profile_without_payment_link(): void
    {
        User::factory()->create(['username' => 'linksiz']);

        $this->get('/uye/linksiz')
            ->assertOk()
            ->assertDontSee('Güvenli ödeme ipuçları');
    }

    public function test_new_seller_gets_stronger_prepayment_warning(): void
    {
        $user = User::factory()->create(['username' => 'yeni-satici']);
        PaymentLink::create(['user_id' => $user->id, 'method' => 'paypal', 'detail' => 'https://paypal.me/x']);

        $this->get('/uye/yeni-satici')
            ->assertOk()
            ->assertSee('ekstra dikkatli');
    }

    public function test_established_seller_does_not_get_new_seller_banner(): void
    {
        $user = User::factory()->create([
            'username' => 'koklu-satici',
            'email_verified_at' => now(),
            'avatar_path' => 'avatars/x.jpg',
            'bio' => 'Köklü satıcı.',
            'country_code' => 'DE',
            'created_at' => now()->subDays(120),
        ]);
        PaymentLink::create(['user_id' => $user->id, 'method' => 'paypal', 'detail' => 'https://paypal.me/x']);

        $this->get('/uye/koklu-satici')
            ->assertOk()
            ->assertSee('Güvenli ödeme ipuçları')
            ->assertDontSee('ekstra dikkatli');
    }

    public function test_payment_safety_card_shown_on_listing_detail(): void
    {
        $seller = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $seller->id, 'status' => 'aktif']);

        $this->get("/ilan/{$listing->id}/{$listing->slug}")
            ->assertOk()
            ->assertSee('Mal ve Hizmetler');
    }

    // ---------- K-D: Dolandırıcılık bildirimi + kara liste ----------

    public function test_user_can_report_another_user_for_fraud(): void
    {
        $reporter = User::factory()->create();
        $suspect = User::factory()->create(['username' => 'supheli']);

        $this->actingAs($reporter)
            ->post('/uye/supheli/dolandiricilik-bildir', ['note' => 'Ödeme aldı, kayboldu. 12.07 tarihinde IBAN gönderdim.'])
            ->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => User::class,
            'reportable_id' => $suspect->id,
            'category' => ReportCategory::Dolandiricilik->value,
        ]);
    }

    public function test_user_cannot_report_self_for_fraud(): void
    {
        $user = User::factory()->create(['username' => 'kendim']);

        $this->actingAs($user)
            ->post('/uye/kendim/dolandiricilik-bildir', ['note' => 'Bu bir denemedir, uzun metin.'])
            ->assertForbidden();
    }

    public function test_fraud_report_requires_meaningful_note(): void
    {
        $reporter = User::factory()->create();
        User::factory()->create(['username' => 'supheli2']);

        $this->actingAs($reporter)
            ->post('/uye/supheli2/dolandiricilik-bildir', ['note' => 'kısa'])
            ->assertSessionHasErrors('note');
    }

    public function test_fingerprint_user_blocklists_email_and_payment_channels(): void
    {
        $fraudster = User::factory()->create(['email' => 'dolandirici@ornek.com']);
        PaymentLink::create(['user_id' => $fraudster->id, 'method' => 'sepa_iban', 'detail' => 'DE89 3704 0044 0532 0130 00']);
        PaymentLink::create(['user_id' => $fraudster->id, 'method' => 'paypal', 'detail' => 'https://paypal.me/Dolandirici']);

        $blocklist = app(FraudBlocklist::class);
        $count = $blocklist->fingerprintUser($fraudster->load('paymentLinks'), null, 'test');

        $this->assertSame(3, $count);
        $this->assertTrue($blocklist->isBlocked(FraudBlocklist::TYPE_EMAIL, 'dolandirici@ornek.com'));
        $this->assertTrue($blocklist->isBlocked(FraudBlocklist::TYPE_IBAN, 'DE89 3704 0044 0532 0130 00'));
        $this->assertTrue($blocklist->isBlocked(FraudBlocklist::TYPE_HANDLE, 'https://paypal.me/Dolandirici'));
    }

    public function test_iban_matching_ignores_spaces_and_case(): void
    {
        $blocklist = app(FraudBlocklist::class);
        $blocklist->block(FraudBlocklist::TYPE_IBAN, 'DE89 3704 0044 0532 0130 00');

        $this->assertTrue($blocklist->isBlocked(FraudBlocklist::TYPE_IBAN, 'de8937040044053201 3000'));
    }

    public function test_email_matching_is_case_insensitive(): void
    {
        $blocklist = app(FraudBlocklist::class);
        $blocklist->block(FraudBlocklist::TYPE_EMAIL, 'Sahtekar@Ornek.COM');

        $this->assertTrue($blocklist->isBlocked(FraudBlocklist::TYPE_EMAIL, 'sahtekar@ornek.com'));
    }

    public function test_blocklisting_is_idempotent(): void
    {
        $blocklist = app(FraudBlocklist::class);
        $blocklist->block(FraudBlocklist::TYPE_EMAIL, 'x@y.com');
        $blocklist->block(FraudBlocklist::TYPE_EMAIL, 'x@y.com');

        $this->assertDatabaseCount('fraud_blocklist', 1);
    }

    public function test_blocklisted_email_cannot_register(): void
    {
        app(FraudBlocklist::class)->block(FraudBlocklist::TYPE_EMAIL, 'banli@ornek.com');

        $this->post('/kayit', [
            'name' => 'Ban Test',
            'email' => 'banli@ornek.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'terms' => 'on',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'banli@ornek.com']);
    }

    public function test_blocklisted_iban_cannot_be_added_as_payment_link(): void
    {
        $user = User::factory()->create();
        app(FraudBlocklist::class)->block(FraudBlocklist::TYPE_IBAN, 'DE89 3704 0044 0532 0130 00');

        $this->actingAs($user)
            ->post('/panel/profil/odeme-linki', [
                'method' => 'sepa_iban',
                'detail' => 'DE89370400440532013000',
            ])
            ->assertSessionHasErrors('detail');

        $this->assertDatabaseCount('payment_links', 0);
    }
}

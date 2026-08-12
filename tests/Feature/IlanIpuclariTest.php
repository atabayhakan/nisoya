<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Notifications\IlanIpuclariNotification;
use App\Services\Kahya\IlanEksikleri;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Kâhya'nın satıcıya "ilanında şunlar eksik" önerisi.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR — HEPSİ SPAM OLMAMAK ÜZERİNE
 *
 * Satıcıya kendi ilanı hakkında bildirim göndermek, insanları siteden
 * uzaklaştırmanın en hızlı yolu. Dört kapı da bunun için:
 *   1. Yalnız AKTİF ilan (taslak henüz satıcının derdi değil)
 *   2. En az 3 GÜNLÜK ilan (yeni ilana ertesi gün akıl vermek dırdırdır)
 *   3. İLAN BAŞINA BİR KEZ — tekrarı yok, hatırlatması yok
 *   4. Tur başına üst sınır (geçmiş birikimi herkese aynı anda patlamasın)
 *
 * Ayrıca: fiyatın boş olması EKSİK SAYILMAZ. "Görüşülür" geçerli bir cevap;
 * satıcının bilinçli tercihini hata gibi göstermek, öneriyi ters çevirir.
 */
class IlanIpuclariTest extends TestCase
{
    use RefreshDatabase;

    private function ilan(array $ustuneYaz = []): Listing
    {
        $kategori = Category::query()->whereNotNull('parent_id')->where('type', 'hizmet')->firstOrFail();

        $ilan = Listing::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'type' => 'hizmet',
            'status' => ListingStatus::Aktif,
            'is_demo' => false,
            'title' => 'Ev temizliği',
            'description' => 'Kısa.',
            'city' => '',
            // Dili tanımsız ülke: çeviri önerisi bu testlere karışmasın.
            'country_code' => 'KZ',
        ], $ustuneYaz));

        // Fabrika `created_at`'i şimdiye kuruyor; 3 gün kuralını aşmak için
        // geriye çekiyoruz (aksi hâlde her ilan filtreye takılır).
        $ilan->forceFill(['created_at' => now()->subDays(10)])->save();

        return $ilan->fresh();
    }

    private function gorselEkle(Listing $ilan): void
    {
        ListingImage::create([
            'listing_id' => $ilan->id,
            'path_thumb' => 'a.webp',
            'path_medium' => 'b.webp',
            'path_large' => 'c.webp',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);
        config([
            'ai.features.text_moderation' => false,
            'ai.features.service_image' => false,
            'ai.features.listing_translation' => false,
        ]);
    }

    public function test_eksikler_bulunuyor(): void
    {
        $ilan = $this->ilan();

        $anahtarlar = collect(app(IlanEksikleri::class)->tara($ilan))->pluck('anahtar')->all();

        $this->assertContains('gorsel', $anahtarlar);
        $this->assertContains('aciklama', $anahtarlar);
        $this->assertContains('sehir', $anahtarlar);
    }

    public function test_bos_fiyat_eksik_sayilmiyor(): void
    {
        /*
         * "Görüşülür" sitede geçerli bir cevap ve pek çok hizmette doğrusu da
         * o. Satıcının bilinçli tercihini eksik diye bildirmek, öneriyi
         * ters çevirir.
         */
        $ilan = $this->ilan([
            'price' => null,
            'description' => str_repeat('Detaylı bir açıklama metni. ', 6),
            'city' => 'Berlin',
        ]);
        $this->gorselEkle($ilan);

        $this->assertSame([], app(IlanEksikleri::class)->tara($ilan->fresh()));
    }

    public function test_bildirim_gonderiliyor(): void
    {
        Notification::fake();

        $ilan = $this->ilan();

        $this->artisan('kahya:ilan-ipuclari')->assertSuccessful();

        Notification::assertSentTo($ilan->user, IlanIpuclariNotification::class);
        $this->assertNotNull($ilan->fresh()->tips_notified_at);
    }

    public function test_ayni_ilana_ikinci_kez_gonderilmiyor(): void
    {
        /*
         * EN ÖNEMLİ KURAL. Görmezden gelinen bir öneriyi tekrar etmek, ilanın
         * düzelmesini değil bildirimlerin kapatılmasını sağlar.
         */
        Notification::fake();

        $this->ilan();

        $this->artisan('kahya:ilan-ipuclari')->assertSuccessful();
        $this->artisan('kahya:ilan-ipuclari')->assertSuccessful();

        Notification::assertCount(1);
    }

    public function test_yeni_ilana_gonderilmiyor(): void
    {
        // İnsanlar ilanı zamanla tamamlıyor; ertesi gün akıl vermek dırdırdır.
        Notification::fake();

        $ilan = $this->ilan();
        $ilan->forceFill(['created_at' => now()->subDay()])->save();

        $this->artisan('kahya:ilan-ipuclari')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($ilan->fresh()->tips_notified_at);
    }

    public function test_taslak_ve_demo_ilana_gonderilmiyor(): void
    {
        Notification::fake();

        $this->ilan(['status' => ListingStatus::Taslak]);
        $this->ilan(['is_demo' => true]);

        $this->artisan('kahya:ilan-ipuclari')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_eksiksiz_ilan_damgalaniyor_ama_bildirim_gitmiyor(): void
    {
        /*
         * Damga "bakıldı" demek, "gönderildi" demek değil. Basılmasaydı bu
         * ilan her gün yeniden taranır ve sorgu sonsuza dek aynı kayıtları
         * döndürürdü.
         */
        Notification::fake();

        $ilan = $this->ilan([
            'description' => str_repeat('Detaylı bir açıklama metni. ', 6),
            'city' => 'Berlin',
        ]);
        $this->gorselEkle($ilan);

        $this->artisan('kahya:ilan-ipuclari')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNotNull($ilan->fresh()->tips_notified_at);
    }

    public function test_tur_basina_ust_sinir_uygulaniyor(): void
    {
        Notification::fake();

        $this->ilan();
        $this->ilan();
        $this->ilan();

        $this->artisan('kahya:ilan-ipuclari', ['--limit' => 2])->assertSuccessful();

        Notification::assertCount(2);
    }

    public function test_kuru_kosu_hicbir_sey_degistirmiyor(): void
    {
        Notification::fake();

        $ilan = $this->ilan();

        $this->artisan('kahya:ilan-ipuclari', ['--dry' => true])->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertNull($ilan->fresh()->tips_notified_at,
            'Kuru koşu damga basmış — ilan bir daha hiç bildirim alamaz.');
    }

    public function test_ceviri_onerisi_yalniz_dili_tanimli_ulkede(): void
    {
        /*
         * İKİ YÖNLÜ ÖLÇÜM. Yalnız olumsuz tarafı sınasaydım, öneriyi hiç
         * üretmeyen bir kod da testi geçerdi — özelliğin çalıştığını değil
         * sustuğunu kanıtlamış olurdum.
         */
        config(['ai.features.listing_translation' => true]);
        $this->yapilandirilmisAi();

        // Dili tanımsız ülke (KZ): öneri boş vaat olurdu, ÇIKMAMALI.
        $tanimsiz = $this->ilan(['country_code' => 'KZ']);
        $this->assertNotContains('ceviri',
            collect(app(IlanEksikleri::class)->tara($tanimsiz))->pluck('anahtar')->all());

        // Dili tanımlı ülke (DE): öneri ÇIKMALI ve dilin adını söylemeli.
        $tanimli = $this->ilan([
            'country_code' => 'DE',
            'description' => str_repeat('Detaylı bir açıklama metni. ', 6),
        ]);
        $eksikler = collect(app(IlanEksikleri::class)->tara($tanimli->fresh()));

        $this->assertContains('ceviri', $eksikler->pluck('anahtar')->all());
        $this->assertStringContainsString('Almanca',
            (string) $eksikler->firstWhere('anahtar', 'ceviri')['metin']);
    }

    /** Çeviri/görsel önerileri AI yapılandırılmışsa devreye giriyor. */
    private function yapilandirilmisAi(): void
    {
        $sahte = new class implements AiProvider
        {
            public function isConfigured(): bool
            {
                return true;
            }

            public function name(): string
            {
                return 'sahte';
            }

            public function lastError(): ?string
            {
                return null;
            }

            public function analyzeImage(string $b, string $m, string $pr, ?array $s = null, ?int $t = null): ?array
            {
                return null;
            }

            public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                return null;
            }
        };

        $this->app->instance(AiProvider::class, $sahte);
    }
}

<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Jobs\IlanMetniniDenetle;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingFlaggedNotification;
use App\Services\DolandiricilikTespiti;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * İlan metninde dolandırıcılık deseni tespiti — görsel moderasyonun ikizi.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * 1. YANLIŞ ALARM VERMEMEK. Nisoya parayı hiç görmüyor; bu yüzden başka
 *    pazaryerlerinde kırmızı bayrak olan şeyler (WhatsApp'tan yazın, IBAN
 *    paylaşmak, kapora istemek) burada TAMAMEN NORMAL. İstem bunu açıkça
 *    söylüyor ve burada mühürlü — aksi hâlde dürüst satıcıların çoğu
 *    işaretlenirdi.
 * 2. AĞIR/HAFİF AYRIMI. Yalnız ağır kategoride yayındaki ilan düşürülüyor.
 *    "Kapora alınır" kiralamada sıradan bir ifade; hafif bir yanlış alarmda
 *    ev sahibinin ilanını kapatmak korumaktan çok zarar verirdi.
 * 3. HER YOLDAN GEÇEN METİN. Kanca modelde: panel formu, yönetim paneli,
 *    hızlı-ilan akışı… controller'a konsaydı bir yol unutulurdu. Görsel
 *    moderasyonunda tam olarak bu olmuştu (taslak/pasif üzerinden atlatma).
 * 4. FAIL-OPEN. AI kapalı/kırıksa hiçbir şey engellenmez ve "denetlendi"
 *    damgası da BASILMAZ — denetlenmemiş ilan denetlenmiş görünmemeli.
 */
class DolandiricilikTespitiTest extends TestCase
{
    use RefreshDatabase;

    private function sahteAi(?array $donen, ?string &$prompt = null): void
    {
        $sahte = new class($donen, $prompt) implements AiProvider
        {
            public function __construct(private ?array $donen, private ?string &$p) {}

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
                $this->p = $prompt;

                return $this->donen;
            }
        };

        $this->app->instance(AiProvider::class, $sahte);
    }

    private function ilan(array $ustuneYaz = []): Listing
    {
        $kategori = Category::query()->whereNotNull('parent_id')->where('type', 'hizmet')->firstOrFail();

        return Listing::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'type' => 'hizmet',
            'status' => ListingStatus::Aktif,
            'title' => 'Ev temizliği hizmeti',
            'description' => 'Haftalık ev temizliği yapıyorum, referanslarım var. WhatsApp\'tan yazabilirsiniz.',
        ], $ustuneYaz));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);
        config(['ai.features.text_moderation' => true]);
    }

    public function test_temiz_metin_isaretlenmiyor_ama_denetlendigi_yaziliyor(): void
    {
        $this->sahteAi(['supheli' => false, 'kategori' => null]);

        $ilan = $this->ilan();
        (new IlanMetniniDenetle($ilan->id))->handle(app(DolandiricilikTespiti::class));

        $taze = $ilan->fresh();

        $this->assertNull($taze->fraud_reason);
        $this->assertNotNull($taze->fraud_checked_at, '"Denetlendi ve temiz" ile "hiç denetlenmedi" ayırt edilemiyor.');
        $this->assertSame(ListingStatus::Aktif, $taze->status);
    }

    public function test_agir_kategori_ilani_beklemeye_aliyor_ve_haber_veriyor(): void
    {
        Notification::fake();
        $this->sahteAi(['supheli' => true, 'kategori' => 'kimlik_sifre_isteme']);

        $ilan = $this->ilan();
        (new IlanMetniniDenetle($ilan->id))->handle(app(DolandiricilikTespiti::class));

        $taze = $ilan->fresh();

        $this->assertSame('kimlik_sifre_isteme', $taze->fraud_reason);
        $this->assertSame(ListingStatus::Beklemede, $taze->status);
        Notification::assertSentTo($ilan->user, ListingFlaggedNotification::class);
    }

    public function test_hafif_kategori_ilani_yayindan_dusurmuyor(): void
    {
        /*
         * ASIL DENGE. "Kapora alınır" Türkiye ve Avrupa kiralama pratiğinde
         * sıradan bir ifade. Hafif bir yanlış alarmda dürüst bir ev sahibinin
         * ilanını kapatmak, korumaktan çok zarar verirdi — hem de arz zaten
         * yokken.
         */
        Notification::fake();
        $this->sahteAi(['supheli' => true, 'kategori' => 'gormeden_kapora']);

        $ilan = $this->ilan();
        (new IlanMetniniDenetle($ilan->id))->handle(app(DolandiricilikTespiti::class));

        $taze = $ilan->fresh();

        $this->assertSame('gormeden_kapora', $taze->fraud_reason, 'İşaret konmamış — panelde hiç görünmez.');
        $this->assertSame(ListingStatus::Aktif, $taze->status, 'Hafif kategoride ilan yayından düşürülmüş.');
        Notification::assertNothingSent();
    }

    public function test_ai_kirikken_denetlendi_damgasi_basilmiyor(): void
    {
        // Fail-open: engellemiyoruz. Ama "denetlendi" de demiyoruz —
        // denetlenmemiş ilan denetlenmiş görünürse gerçek boşluk gizlenir.
        $this->sahteAi(null);

        $ilan = $this->ilan();
        (new IlanMetniniDenetle($ilan->id))->handle(app(DolandiricilikTespiti::class));

        $taze = $ilan->fresh();

        $this->assertNull($taze->fraud_reason);
        $this->assertNull($taze->fraud_checked_at);
        $this->assertSame(ListingStatus::Aktif, $taze->status);
    }

    public function test_kategorisiz_supheli_yanit_isaret_koymuyor(): void
    {
        // Sebebi olmayan bir işaret, paneldeki insana hiçbir şey söylemez.
        $this->sahteAi(['supheli' => true, 'kategori' => null]);

        $ilan = $this->ilan();
        (new IlanMetniniDenetle($ilan->id))->handle(app(DolandiricilikTespiti::class));

        $this->assertNull($ilan->fresh()->fraud_reason);
    }

    public function test_istem_normal_davranisi_supheli_saymiyor(): void
    {
        /*
         * EN ÖNEMLİ BEKÇİ. Nisoya parayı hiç görmüyor: iletişim paylaşmak ve
         * IBAN vermek bu platformda satıcının TEK yolu. İstem bunu söylemezse
         * model onları dolandırıcılık sanar ve dürüst satıcıların çoğu
         * işaretlenir.
         */
        $this->sahteAi(null, $prompt);

        app(DolandiricilikTespiti::class)->kontrolEt($this->ilan());

        $this->assertStringContainsString('Parayı HİÇ görmez', $prompt);
        $this->assertStringContainsString('TAMAMEN NORMALDİR', $prompt);
        $this->assertStringContainsString('IBAN', $prompt);
        $this->assertStringContainsString('Kapora/depozito İSTEMEK', $prompt);
        $this->assertStringContainsString('EMİN DEĞİLSEN şüpheli=false', $prompt);
        $this->assertStringContainsString('Ev temizliği hizmeti', $prompt, 'İlanın kendi metni isteme girmemiş.');
    }

    public function test_agir_liste_kisa_kaliyor(): void
    {
        /*
         * Bu listeye eklenen her kategori, yanlış alarmda bir satıcının
         * ilanının yayından kalkması demek. Liste büyürse bilerek büyümeli —
         * bu test o kararı görünür kılar.
         */
        $this->assertSame(
            ['kimlik_sifre_isteme', 'sahte_site_guvencesi'],
            DolandiricilikTespiti::AGIR,
        );
    }

    public function test_metin_degisince_denetim_kuyruga_giriyor(): void
    {
        Queue::fake();
        $this->sahteAi(['supheli' => false, 'kategori' => null]);

        $ilan = $this->ilan();
        Queue::assertPushed(IlanMetniniDenetle::class, 1);

        // Açıklama değişti → yeniden denetlenmeli.
        $ilan->update(['description' => 'Artık yalnız ofis temizliği yapıyorum, ev işi almıyorum.']);
        Queue::assertPushed(IlanMetniniDenetle::class, 2);

        /*
         * Alakasız alan değişti → yeni denetim YOK.
         *
         * BU SATIR GERÇEK BİR HATA YAKALADI: kanca önce tek bir `saved` +
         * `wasRecentlyCreated` ile yazılmıştı. O bayrak model ÖRNEĞİNİN ömrü
         * boyunca true kaldığı için, ilan kaydedildikten sonraki her ek kayıt
         * (kapak düzeltme, detay eşitleme…) denetimi yeniden kuyruğa atıyordu
         * — bir ilan için üç dört AI çağrısı.
         */
        $ilan->update(['views_count' => 5]);
        Queue::assertPushed(IlanMetniniDenetle::class, 2);
    }

    public function test_taslak_ve_pasif_ilan_da_denetleniyor(): void
    {
        /*
         * Görsel moderasyonunda taslak/pasif üzerinden atlatma GERÇEKTEN
         * yaşandı. Bugün taslak olan yarın yayına alınıyor; denetimi duruma
         * bağlamak, kapıyı açık bırakmaktır.
         */
        Queue::fake();
        $this->sahteAi(['supheli' => false, 'kategori' => null]);

        $this->ilan(['status' => ListingStatus::Taslak]);
        $this->ilan(['status' => ListingStatus::Pasif]);

        Queue::assertPushed(IlanMetniniDenetle::class, 2);
    }

    public function test_demo_ilan_denetlenmiyor(): void
    {
        // Örnek veri gerçek arz değil; kuyruğa ve jetona değmez.
        Queue::fake();
        $this->sahteAi(['supheli' => false, 'kategori' => null]);

        $this->ilan(['is_demo' => true]);

        Queue::assertNothingPushed();
    }

    public function test_isaret_yonetim_panelinde_gorunuyor(): void
    {
        /*
         * HAFİF kategoride ilan yayında kalıyor; işaretin TEK karşılığı bu
         * ekran. Sütun basılmazsa hafif tespit hiçbir işe yaramaz — kimsenin
         * görmediği bir işaret, tespit sayılmaz.
         */
        $this->sahteAi(['supheli' => true, 'kategori' => 'gormeden_kapora']);

        $ilan = $this->ilan();
        (new IlanMetniniDenetle($ilan->id))->handle(app(DolandiricilikTespiti::class));

        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get('/yonetim/listings')
            ->assertOk()
            ->assertSee('Görmeden/tanışmadan kapora baskısı', escape: false);
    }

    public function test_ozellik_kapaliyken_kuyruga_is_atilmiyor(): void
    {
        Queue::fake();
        config(['ai.features.text_moderation' => false]);
        $this->sahteAi(['supheli' => true, 'kategori' => 'kimlik_sifre_isteme']);

        $this->ilan();

        Queue::assertNothingPushed();
    }
}

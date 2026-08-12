<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Models\OutreachTarget;
use App\Services\Growth\DetectionResult;
use App\Services\Growth\ErisimMesajiYazari;
use App\Support\Growth\RegionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * İşletmeye tanışma postası taslağı.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * Elle erişim listesi bugün TEK arz kanalı (AWS üretim erişimi reddedildikten
 * sonra soğuk e-posta otomasyonu bırakıldı). Bu özellik o kanalı hızlandırır
 * ama üç şeyden ödün veremez:
 *
 *   1. GÖNDERMEZ. Kâhya taslağı yazar; gönderme kararı ve eylemi sahibindir.
 *   2. HUKUKİ KAPI GEÇERLİ. Yalnız gönderime açık bölgedeki Türk işletmeler.
 *      Bu kapı gerçek bir olaydan sonra kondu (TR'deki 19 işletme
 *      "gönderilebilir" görünüyordu); taslak ekranı onu delemez.
 *   3. UYDURMA YOK. Model bu işletme hakkında yalnız veritabanındaki alanları
 *      bilir. Yeterli bilgi yoksa kişisel cümle BOŞ kalır — uydurulmuş bir
 *      cümle, hiç cümle olmamasından kötüdür.
 */
class ErisimMesajiTaslagiTest extends TestCase
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

    private function aday(array $ustuneYaz = []): OutreachTarget
    {
        return OutreachTarget::create(array_merge([
            'name' => 'Anadolu Restaurant',
            // `source` + `external_id` NOT NULL: havuz tekilleştirmeyi bu
            // ikilinin üzerinden yapıyor (bkz. DiscoveryRunner upsert).
            'source' => 'test',
            'external_id' => 'test-'.uniqid(),
            'country' => 'US',
            'city' => 'New Jersey',
            'sector' => 'restoran',
            'contact_email' => 'info@ornek.test',
            'detection_band' => DetectionResult::BAND_TURKISH,
            'marketing_status' => RegionPolicy::ALLOWED,
        ], $ustuneYaz));
    }

    public function test_gonderime_kapali_aday_icin_taslak_uretilmez(): void
    {
        /*
         * HUKUKİ KAPI. Bu kapı gerçek bir olaydan sonra kondu: TR'deki 19
         * işletme "gönderilebilir" görünüyordu ve 35 kayıt kapatıldı. Taslak
         * ekranı o kapıyı delemez — aksi hâlde sahip taslağı görür, gönderir.
         */
        config(['ai.features.outreach_draft' => true]);
        $this->sahteAi(['kisisel_cumle' => 'Uydurma cümle']);

        $kapali = $this->aday(['country' => 'TR', 'marketing_status' => 'blocked']);

        $yazar = app(ErisimMesajiYazari::class);

        $this->assertFalse($yazar->uygunMu($kapali));
        $this->assertNull($yazar->taslak($kapali)['kisisel_cumle'],
            'Gönderime kapalı adaya kişisel cümle üretilmiş — hukuki kapı delinmiş.');
    }

    public function test_turk_olmayan_aday_icin_taslak_uretilmez(): void
    {
        config(['ai.features.outreach_draft' => true]);
        $this->sahteAi(['kisisel_cumle' => 'Cümle']);

        $aday = $this->aday(['detection_band' => 'not_turkish']);

        $this->assertFalse(app(ErisimMesajiYazari::class)->uygunMu($aday));
    }

    public function test_kisisel_cumle_mesaja_yerlesiyor(): void
    {
        config(['ai.features.outreach_draft' => true]);
        $this->sahteAi(['kisisel_cumle' => 'New Jersey\'de uzun süredir açık olduğunuzu gördüm.']);

        $taslak = app(ErisimMesajiYazari::class)->taslak($this->aday());

        $this->assertStringContainsString('New Jersey\'de uzun süredir', $taslak['mesaj']);
        $this->assertStringNotContainsString('[buraya', $taslak['mesaj']);
    }

    public function test_cumle_uretilemezse_koseli_parantez_kaliyor(): void
    {
        /*
         * Model "elimde yeterli bilgi yok" diyebilmeli ve dediğinde mesaj
         * yine kurulmalı — sahip parantezi kendisi doldurur. Sessizce
         * uydurulmuş bir cümleden çok daha iyisi bu.
         */
        config(['ai.features.outreach_draft' => true]);
        $this->sahteAi(['kisisel_cumle' => null]);

        $taslak = app(ErisimMesajiYazari::class)->taslak($this->aday());

        $this->assertNull($taslak['kisisel_cumle']);
        $this->assertStringContainsString('[buraya', $taslak['mesaj'],
            'Cümle yokken bile mesaj kurulmalı ve doldurulacak yer belli olmalı.');
    }

    public function test_degismez_vaatler_her_hâlukârda_mesajda(): void
    {
        /*
         * ASIL BEKÇİ. Bu cümleler platformun temel vaadi ve modele
         * yazdırılmıyor — kodda sabit. Modelden gelseydi her seferinde biraz
         * farklı, bazen fazla iddialı çıkardı.
         */
        config(['ai.features.outreach_draft' => true]);
        $this->sahteAi(['kisisel_cumle' => null]);

        $mesaj = app(ErisimMesajiYazari::class)->taslak($this->aday())['mesaj'];

        $this->assertStringContainsString('Komisyon yok', $mesaj);
        $this->assertStringContainsString('ödemeye aracılık etmiyoruz', $mesaj);
        $this->assertStringContainsString('nisoya.com', $mesaj);
    }

    public function test_prompt_uydurmayi_ve_vaat_vermeyi_yasakliyor(): void
    {
        config(['ai.features.outreach_draft' => true]);
        $prompt = null;
        $this->sahteAi(['kisisel_cumle' => null], $prompt);

        app(ErisimMesajiYazari::class)->taslak($this->aday());

        $this->assertStringContainsString('YALNIZ AŞAĞIDA VERİLEN BİLGİYİ KULLAN', $prompt);
        $this->assertStringContainsString('null DÖNDÜR', $prompt);
        $this->assertStringContainsString('VAAT VERME', $prompt);
        $this->assertStringContainsString('Anadolu Restaurant', $prompt, 'Adayın bilgileri prompt\'a girmemiş.');
    }

    public function test_modal_gorunumu_derleniyor(): void
    {
        /*
         * Blade hatası aksiyona TIKLANANA kadar ortaya çıkmaz — yani sahip
         * mesajı yazmak istediği anda patlar. Görünümü burada bir kez
         * derliyoruz ki o an gelmesin.
         */
        config(['ai.features.outreach_draft' => true]);
        $this->sahteAi(['kisisel_cumle' => 'New Jersey\'de açık olduğunuzu gördüm.']);

        $aday = $this->aday();
        $html = view('filament.outreach.mesaj-taslagi', [
            'aday' => $aday,
            'taslak' => app(ErisimMesajiYazari::class)->taslak($aday),
        ])->render();

        $this->assertStringContainsString('Anadolu Restaurant', $html);
        $this->assertStringContainsString('Komisyon yok', $html);
        $this->assertStringContainsString('Nisoya bu mesajı göndermez', $html,
            'Göndermediğimiz ekranda yazmıyor — sahip yanlış beklentiye girer.');
    }

    public function test_epostasi_olmayan_aday_uyarilir(): void
    {
        // Mesaj hazır ama gönderilecek adres yoksa bunu önceden söylemek gerekir.
        config(['ai.features.outreach_draft' => true]);
        $this->sahteAi(['kisisel_cumle' => null]);

        $aday = $this->aday(['contact_email' => null]);
        $html = view('filament.outreach.mesaj-taslagi', [
            'aday' => $aday,
            'taslak' => app(ErisimMesajiYazari::class)->taslak($aday),
        ])->render();

        $this->assertStringContainsString('e-posta adresi yok', $html);
    }

    public function test_ozellik_kapaliyken_mesaj_yine_kuruluyor(): void
    {
        // AI kapalı olsa da sahip taslağı görebilmeli; yalnız kişisel cümle yok.
        config(['ai.features.outreach_draft' => false]);
        $this->sahteAi(['kisisel_cumle' => 'Olmamalı']);

        $taslak = app(ErisimMesajiYazari::class)->taslak($this->aday());

        $this->assertNull($taslak['kisisel_cumle']);
        $this->assertStringContainsString('Komisyon yok', $taslak['mesaj']);
    }
}

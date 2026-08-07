<?php

namespace Tests\Feature;

use App\Services\Kahya\Dis\EngelListesi;
use App\Services\Kahya\Dis\SnsDogrulayici;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * SES bounce/şikâyet → kalıcı engel listesi (2026-08-07).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Engel listesi tablosu F4'te kuruldu ama onu DOLDURAN hiçbir şey yoktu.
 * AWS üretim erişimi talebinde "her ret/şikâyet adresi kalıcı engel listesine
 * girer" yazıyordu; kodda karşılığı yalnız elle yazılabilecek satırlardı.
 *
 * ---------------------------------------------------------------------------
 * ASIL SINANAN: ÜÇ KAPI
 *
 * Uç herkese açık ve yaptığı iş "bu adrese bir daha yazma". Doğrulanmazsa
 * isteyen, hedef kitlemizi tek tek susturabilir — ve bu SESSİZ bir sabotaj
 * olurdu: hiçbir hata görünmez, postalar sadece gitmemeye başlar.
 *
 * Kapılar: (1) konu yapılandırılmış mı, (2) imza Amazon'un mu, (3) mesaj
 * BİZİM konumuzdan mı. Üçü de ayrı ayrı mühürlü — biri düşerse test kırılır.
 */
class KahyaSesGeriBildirimTest extends TestCase
{
    use RefreshDatabase;

    private const KONU = 'arn:aws:sns:eu-central-1:151955775808:nisoya-ses';

    private const SERTIFIKA_URL = 'https://sns.eu-central-1.amazonaws.com/SimpleNotificationService-abc.pem';

    /** @var \OpenSSLAsymmetricKey */
    private $ozelAnahtar;

    private string $acikAnahtarPem;

    protected function setUp(): void
    {
        parent::setUp();

        // Sertifika önbelleğe alınıyor; testler arasında sızmasın.
        Cache::flush();
        Settings::setMany(['kahya.ses_konu_arn' => self::KONU]);

        /*
         * Anahtar ÜRETİLMİYOR, dosyadan okunuyor.
         *
         * `openssl_pkey_new()` bir openssl.cnf ister ve taşınabilir PHP
         * kurulumlarında (bu projenin geliştirme ortamı) o dosya
         * bulunamayınca `false` döner — test makineye bağlı hâle gelirdi.
         * Var olan bir anahtarı okumak, imzalamak ve doğrulamak yapılandırma
         * dosyası istemez. Fixture yalnız testler için üretilmiş, hiçbir yerde
         * kullanılmayan atılabilir bir anahtardır.
         */
        $this->ozelAnahtar = openssl_pkey_get_private(
            (string) file_get_contents(__DIR__.'/../Fixtures/sns-test-anahtar.pem'),
        );
        $detay = openssl_pkey_get_details($this->ozelAnahtar);
        $this->acikAnahtarPem = $detay['key'];

        // Gerçekte X.509 sertifika döner; `openssl_pkey_get_public` ikisini de
        // kabul eder ve doğrulama yolu aynıdır — testte açık anahtar PEM'i
        // yeterli (sertifika üretmek openssl.cnf bağımlılığı getirirdi).
        Http::fake([self::SERTIFIKA_URL => Http::response($this->acikAnahtarPem, 200)]);
    }

    /**
     * Amazon'un imzaladığı gibi imzalar.
     *
     * @param  array<string, mixed>  $mesaj
     * @return array<string, mixed>
     */
    private function imzala(array $mesaj): array
    {
        $mesaj['SignatureVersion'] = '2';
        $mesaj['SigningCertURL'] = self::SERTIFIKA_URL;

        openssl_sign(SnsDogrulayici::imzaMetni($mesaj), $imza, $this->ozelAnahtar, OPENSSL_ALGO_SHA256);
        $mesaj['Signature'] = base64_encode($imza);

        return $mesaj;
    }

    /** @param array<string, mixed> $icerik */
    private function bildirim(array $icerik): array
    {
        return $this->imzala([
            'Type' => 'Notification',
            'MessageId' => 'm-1',
            'TopicArn' => self::KONU,
            'Message' => json_encode($icerik),
            'Timestamp' => '2026-08-07T10:00:00.000Z',
        ]);
    }

    private function gonder(array $mesaj): TestResponse
    {
        return $this->call('POST', '/webhook/ses-geri-bildirim', [], [], [], [], json_encode($mesaj));
    }

    private function engelliMi(string $eposta): bool
    {
        return app(EngelListesi::class)->engelliMi($eposta);
    }

    // === Mutlu yol ==================================================

    public function test_sikayet_adresi_kalici_engellenir(): void
    {
        $this->gonder($this->bildirim([
            'notificationType' => 'Complaint',
            'complaint' => ['complainedRecipients' => [['emailAddress' => 'kizgin@dernek.test']]],
        ]))->assertOk();

        $this->assertTrue($this->engelliMi('kizgin@dernek.test'));
    }

    public function test_kalici_bounce_engellenir(): void
    {
        $this->gonder($this->bildirim([
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Permanent',
                'bouncedRecipients' => [['emailAddress' => 'yok@dernek.test']],
            ],
        ]))->assertOk();

        $this->assertTrue($this->engelliMi('yok@dernek.test'));
    }

    public function test_gecici_bounce_engellenmez(): void
    {
        /*
         * Dolu posta kutusu ya da bakımdaki sunucu adresin geçersiz olduğunu
         * göstermez. Geçici bounce'u kalıcı engellemek, birkaç saatliğine
         * kutusu dolu olan gerçek bir muhatabı TEMELLİ kaybetmek olurdu.
         */
        $this->gonder($this->bildirim([
            'notificationType' => 'Bounce',
            'bounce' => [
                'bounceType' => 'Transient',
                'bouncedRecipients' => [['emailAddress' => 'dolu@dernek.test']],
            ],
        ]))->assertOk();

        $this->assertFalse($this->engelliMi('dolu@dernek.test'));
    }

    public function test_event_publishing_bicimi_de_anlasilir(): void
    {
        // Klasik bildirim `notificationType`, Event Publishing `eventType`
        // kullanır; aynı olay, iki ad.
        $this->gonder($this->bildirim([
            'eventType' => 'Complaint',
            'complaint' => ['complainedRecipients' => [['emailAddress' => 'olay@dernek.test']]],
        ]))->assertOk();

        $this->assertTrue($this->engelliMi('olay@dernek.test'));
    }

    // === Kapı 1: konu yapılandırılmamış =============================

    public function test_konu_arn_bos_ise_uc_calismaz(): void
    {
        Settings::setMany(['kahya.ses_konu_arn' => '']);

        $this->gonder($this->bildirim([
            'notificationType' => 'Complaint',
            'complaint' => ['complainedRecipients' => [['emailAddress' => 'x@dernek.test']]],
        ]))->assertStatus(503);

        $this->assertFalse($this->engelliMi('x@dernek.test'));
    }

    // === Kapı 2: imza ===============================================

    public function test_sahte_imza_reddedilir(): void
    {
        $mesaj = $this->bildirim([
            'notificationType' => 'Complaint',
            'complaint' => ['complainedRecipients' => [['emailAddress' => 'kurban@dernek.test']]],
        ]);
        $mesaj['Signature'] = base64_encode('uydurma');

        $this->gonder($mesaj)->assertStatus(403);

        $this->assertFalse($this->engelliMi('kurban@dernek.test'));
    }

    public function test_imzadan_sonra_govde_degistirilirse_reddedilir(): void
    {
        // Asıl saldırı bu: geçerli bir mesajı yakalayıp içindeki adresi
        // değiştirmek. İmza gövdeyi kapsadığı için tutmaz.
        $mesaj = $this->bildirim([
            'notificationType' => 'Complaint',
            'complaint' => ['complainedRecipients' => [['emailAddress' => 'gercek@dernek.test']]],
        ]);
        $mesaj['Message'] = json_encode([
            'notificationType' => 'Complaint',
            'complaint' => ['complainedRecipients' => [['emailAddress' => 'kurban@dernek.test']]],
        ]);

        $this->gonder($mesaj)->assertStatus(403);

        $this->assertFalse($this->engelliMi('kurban@dernek.test'));
    }

    // === Kapı 3: konu eşleşmesi =====================================

    public function test_baska_bir_sns_konusundan_gelen_mesaj_reddedilir(): void
    {
        /*
         * İmza doğrulaması TEK BAŞINA yetmez: saldırgan kendi AWS hesabında
         * bir SNS konusu açıp geçerli imzalı mesaj gönderebilir. İmza
         * "Amazon'dan geldi" der; "bizim için geldi" demez.
         */
        $mesaj = $this->imzala([
            'Type' => 'Notification',
            'MessageId' => 'm-2',
            'TopicArn' => 'arn:aws:sns:eu-central-1:999999999999:saldirgan',
            'Message' => json_encode([
                'notificationType' => 'Complaint',
                'complaint' => ['complainedRecipients' => [['emailAddress' => 'kurban@dernek.test']]],
            ]),
            'Timestamp' => '2026-08-07T10:00:00.000Z',
        ]);

        $this->gonder($mesaj)->assertStatus(403);

        $this->assertFalse($this->engelliMi('kurban@dernek.test'));
    }

    // === Abonelik onayı =============================================

    public function test_abonelik_onayi_amazon_adresine_gider(): void
    {
        $onayUrl = 'https://sns.eu-central-1.amazonaws.com/?Action=ConfirmSubscription&Token=abc';
        Http::fake([
            self::SERTIFIKA_URL => Http::response($this->acikAnahtarPem, 200),
            'sns.eu-central-1.amazonaws.com/*' => Http::response('<ok/>', 200),
        ]);

        $this->gonder($this->imzala([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => 'm-3',
            'TopicArn' => self::KONU,
            'Message' => 'Onayla',
            'SubscribeURL' => $onayUrl,
            'Token' => 'abc',
            'Timestamp' => '2026-08-07T10:00:00.000Z',
        ]))->assertOk();

        Http::assertSent(fn ($istek) => $istek->url() === $onayUrl);
    }

    public function test_onay_adresi_amazon_disindaysa_acilmaz(): void
    {
        /*
         * SSRF kapısı: onay adresi mesajın İÇİNDEN geliyor, yani dışarıdan.
         * İmza ve konu kapılarını geçmiş olsa bile host doğrulanmazsa uç,
         * sunucuya iç ağa istek attırmanın aracına dönerdi.
         */
        $kotuUrl = 'https://sns.eu-central-1.amazonaws.com.saldirgan.example/?Action=ConfirmSubscription';

        $this->gonder($this->imzala([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => 'm-4',
            'TopicArn' => self::KONU,
            'Message' => 'Onayla',
            'SubscribeURL' => $kotuUrl,
            'Token' => 'abc',
            'Timestamp' => '2026-08-07T10:00:00.000Z',
        ]))->assertStatus(403);

        Http::assertNotSent(fn ($istek) => str_contains($istek->url(), 'saldirgan.example'));
    }

    // === Doğrulayıcının saf parçaları ===============================

    public function test_sertifika_url_kaliplari(): void
    {
        $this->assertTrue(SnsDogrulayici::sertifikaUrlGecerli(self::SERTIFIKA_URL));

        foreach ([
            'http://sns.eu-central-1.amazonaws.com/a.pem',            // https değil
            'https://sns.eu-central-1.amazonaws.com.kotu.example/a.pem', // alt alan tuzağı
            'https://evil-amazonaws.com/a.pem',                       // "biten" tuzağı
            'https://sns.eu-central-1.amazonaws.com/a.txt',           // .pem değil
        ] as $kotu) {
            $this->assertFalse(SnsDogrulayici::sertifikaUrlGecerli($kotu), $kotu.' kabul edilmemeli');
        }
    }

    public function test_imza_metni_subject_alanini_ancak_vars_a_ekler(): void
    {
        // Amazon `Subject`'i yalnız varsa imzalar. Yok sayılıp boş yazılırsa
        // imza tutmaz ve geçerli bildirimler sessizce reddedilir.
        $temel = ['Type' => 'Notification', 'MessageId' => '1', 'TopicArn' => 'a', 'Message' => 'm', 'Timestamp' => 't'];

        $this->assertStringNotContainsString('Subject', SnsDogrulayici::imzaMetni($temel));
        $this->assertStringContainsString("Subject\nselam\n", SnsDogrulayici::imzaMetni($temel + ['Subject' => 'selam']));
    }

    public function test_bilinmeyen_mesaj_turu_imzalanamaz(): void
    {
        $this->assertNull(SnsDogrulayici::imzaMetni(['Type' => 'BilinmeyenTur']));
    }
}

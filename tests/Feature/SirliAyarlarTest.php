<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sırlı ayarlar veritabanında ŞİFRELİ durur (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * NEDEN ÖNEMLİ
 *
 * `site_settings.value` düz metin bir TEXT kolonu ve SMTP parolası ile YZ
 * sağlayıcı anahtarları orada duruyordu. Panelin yedekleme özelliği her
 * ZIP'e TAM VERİTABANI DÖKÜMÜ koyuyor — yani indirilen her yedek sırları
 * açık taşıyordu. Yedeği bir buluta koymak ya da e-postayla göndermek,
 * farkında olmadan sırları dağıtmak demekti.
 *
 * Proje 2FA sırlarını zaten şifreliyordu ("DB sızıntısı TOTP secret'ını ifşa
 * etmesin"); ilke kuruluydu, ayarlara uygulanmamıştı.
 */
class SirliAyarlarTest extends TestCase
{
    use RefreshDatabase;

    private function hamDeger(string $key): ?string
    {
        return DB::table('site_settings')->where('key', $key)->value('value');
    }

    /**
     * Migration'ı DOĞRUDAN çalıştırır.
     *
     * `artisan migrate --path` işe yaramaz: RefreshDatabase tüm migration'ları
     * zaten koşturmuş olduğu için Laravel bunu "uygulanmış" sayıp atlar ve
     * test hiçbir şey denemeden geçerdi (ilk yazışta tam olarak bu oldu).
     */
    private function migrationuCalistir(): void
    {
        $migration = require database_path('migrations/2026_08_05_190000_sirli_ayarlari_sifrele.php');
        $migration->up();
    }

    public function test_smtp_parolasi_veritabaninda_acik_durmaz(): void
    {
        Settings::setMany(['mail.password' => 'cok-gizli-parola']);

        $ham = $this->hamDeger('mail.password');

        $this->assertNotNull($ham);
        $this->assertNotSame('cok-gizli-parola', $ham, 'SMTP parolası düz metin yazılmış.');
        $this->assertStringNotContainsString('cok-gizli-parola', $ham);

        // Okurken şeffaf biçimde çözülür — çağıran taraf hiçbir şey bilmez.
        $this->assertSame('cok-gizli-parola', Settings::get('mail.password'));
    }

    public function test_diger_ayarlar_sifrelenmez(): void
    {
        // Liste dar tutuluyor: her ayarı şifrelemek arama/filtrelemeyi bozar
        // ve hata ayıklamayı zorlaştırır.
        Settings::setMany(['genel.site_adi' => 'Nisoya']);

        $this->assertSame('Nisoya', $this->hamDeger('genel.site_adi'));
    }

    public function test_tum_sirli_anahtarlar_kapsanir(): void
    {
        foreach (Settings::SIRLI_ANAHTARLAR as $anahtar) {
            Settings::setMany([$anahtar => "sir-{$anahtar}"]);

            $this->assertNotSame("sir-{$anahtar}", $this->hamDeger($anahtar), "{$anahtar} şifrelenmemiş.");
            $this->assertSame("sir-{$anahtar}", Settings::get($anahtar));
        }
    }

    public function test_iki_kez_kaydetmek_kat_kat_sifrelemez(): void
    {
        // Aksi hâlde aynı ayarı iki kez kaydetmek değeri sararak bozardı.
        Settings::setMany(['mail.password' => 'p1']);
        $birinci = $this->hamDeger('mail.password');

        Settings::setMany(['mail.password' => $birinci]);

        $this->assertSame('p1', Settings::get('mail.password'));
    }

    public function test_cozulemeyen_deger_siteyi_dusurmez(): void
    {
        /*
         * En kritik dayanıklılık testi: `mail.password` HER İSTEKTE okunuyor
         * (AppServiceProvider::mergeMailConfig). Çözülemeyen bir değer istisna
         * fırlatsaydı TÜM SİTE 500 verirdi — Kurtarma Kiti'ndeki hatanın çok
         * daha pahalı hâli.
         *
         * null dönmek "parola yok" demektir: e-posta durur, site ayakta kalır,
         * panelden yeniden girilebilir.
         */
        SiteSetting::query()->create([
            'key' => 'mail.password', 'value' => 'bu-cozulemez', 'group' => 'mail',
        ]);
        SiteSetting::query()->create([
            'key' => 'mail.host', 'value' => 'smtp.ornek.com', 'group' => 'mail',
        ]);
        Settings::forget();

        $this->assertNull(Settings::get('mail.password'));

        // Ve site gerçekten ayakta.
        $this->get('/')->assertOk();
    }

    public function test_migration_mevcut_duz_metni_devralir(): void
    {
        // Canlıda zaten yazılı sırlar var; migration onları devralmalı.
        SiteSetting::query()->create([
            'key' => 'mail.password', 'value' => 'eski-duz-parola', 'group' => 'mail',
        ]);
        Settings::forget();

        $this->migrationuCalistir();

        $this->assertNotSame('eski-duz-parola', $this->hamDeger('mail.password'));
        $this->assertSame('eski-duz-parola', Settings::get('mail.password'));
    }

    public function test_migration_tekrar_kosarsa_bozmaz(): void
    {
        // İdempotentlik: ikinci koşu değeri kat kat sarmamalı.
        Settings::setMany(['mail.password' => 'zaten-sifreli']);

        $this->migrationuCalistir();
        $this->migrationuCalistir();

        $this->assertSame('zaten-sifreli', Settings::get('mail.password'));
    }

    public function test_mail_ayarlari_ekrani_parolayi_html_e_basmaz(): void
    {
        /*
         * Alan `->password()` olduğu için ekranda noktalar görünüyordu ama
         * DEĞER HTML'e basılıyordu: "kaynağı görüntüle" ya da geliştirici
         * araçlarıyla okunabiliyordu. Gereksizdi de — save() boş bırakılan
         * parolayı zaten "değiştirme" olarak yorumluyor.
         */
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Settings::setMany(['mail.password' => 'html-e-sizmamali']);

        $icerik = $this->actingAs($admin)->get('/yonetim/mail-ayarlari')->assertOk()->getContent();

        $this->assertStringNotContainsString('html-e-sizmamali', $icerik);
        $this->assertStringContainsString('(kayıtlı)', $icerik);
    }

    public function test_sifreli_deger_onbellege_acik_dusmez(): void
    {
        // Önbellek HAM (şifreli) değeri tutar; çözme yalnız okuma anında
        // yapılır. Aksi hâlde dosya tabanlı önbellek sırrı yine açık yazardı.
        Settings::setMany(['mail.password' => 'onbellek-sinavi']);

        $hepsi = Settings::all();

        $this->assertNotSame('onbellek-sinavi', $hepsi['mail.password']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Kahya;
use App\Filament\Pages\KahyaAyarlari;
use App\Models\KahyaCalismasi;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kâhya paneli (Faz D).
 *
 * İki sorunun ekranı: "sitede ne durumda?" ve "Kâhya çalışıyor mu?".
 * İkincisi sanılandan önemli — rapor zamanlanmış bir komuttur ve zamanlanmış
 * komutlar sessizce ölür. Gelen kutusunda rapor olmaması ile "her şey yolunda"
 * ancak bu sayfadaki bant sayesinde ayrışır.
 */
class KahyaPaneliTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    private function moderator(): User
    {
        return User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);
    }

    public function test_admin_kahya_sayfasini_acabilir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Kahya::class)
            ->assertSuccessful();
    }

    /**
     * Rapor log imzaları ve boş ayar anahtarları içeriyor — moderatörün işi
     * değil. Hem menüde hem doğrudan erişimde kapalı olmalı.
     */
    public function test_moderator_kahya_sayfasina_erisemez(): void
    {
        $this->actingAs($this->moderator());

        $this->assertFalse(Kahya::canAccess());
        $this->assertFalse(KahyaAyarlari::canAccess());
    }

    public function test_uye_kahya_sayfasina_erisemez(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));

        $this->assertFalse(Kahya::canAccess());
    }

    /**
     * ASIL BEKÇİ: hiç rapor üretilmemişken sayfa bunu AÇIKÇA söylemeli.
     *
     * `null` ile `0` arasındaki fark: 0 "az önce koştu", null "hiç koşmadı"
     * demektir ve ikincisi bir arızadır — zamanlayıcı çalışmıyor olabilir.
     */
    public function test_hic_rapor_yokken_uyari_gosterilir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Kahya::class)
            ->assertSee('Kâhya henüz hiç çalışmadı');
    }

    public function test_eski_rapor_uyari_gosterir(): void
    {
        $kayit = KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true]);
        $kayit->forceFill(['created_at' => now()->subHours(50)])->saveQuietly();

        Livewire::actingAs($this->admin())
            ->test(Kahya::class)
            ->assertSee('beklenenden eski');
    }

    public function test_taze_rapor_uyari_gostermez(): void
    {
        KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true]);

        Livewire::actingAs($this->admin())
            ->test(Kahya::class)
            ->assertDontSee('beklenenden eski')
            ->assertDontSee('henüz hiç çalışmadı');
    }

    public function test_envanter_uyarisi_sayfada_gorunur(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Kahya::class)
            ->assertSee('Pazaryeri boş');
    }

    /**
     * SMTP'nin gerçekten çalıştığını doğrulamanın tek pratik yolu.
     * "Rapor gelmiyor" şikâyetinin ilk nedeni yapılandırma; bu düğme
     * onu bir saniyede ayırt eder.
     */
    public function test_ornek_rapor_gonder_dugmesi_calisir(): void
    {
        Notification::fake();
        Settings::setMany(['kahya.alici' => 'yonetici@ornek.com']);

        Livewire::actingAs($this->admin())
            ->test(Kahya::class)
            ->call('ornekRaporGonder')
            ->assertSuccessful();

        $this->assertSame(1, KahyaCalismasi::query()->gunlukRapor()->count());
    }

    // ------------------------------------------------------------- Ayarlar

    public function test_admin_ayarlari_kaydedebilir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(KahyaAyarlari::class)
            ->fillForm(['alici' => 'rapor@ornek.com', 'rapor_saati' => '09:00'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('rapor@ornek.com', Settings::get('kahya.alici'));
        $this->assertSame('09:00', Settings::get('kahya.rapor_saati'));
    }

    public function test_gecersiz_eposta_kaydedilmez(): void
    {
        Livewire::actingAs($this->admin())
            ->test(KahyaAyarlari::class)
            ->fillForm(['alici' => 'bu-eposta-degil', 'rapor_saati' => '07:30'])
            ->call('save')
            ->assertHasFormErrors(['alici']);
    }

    /**
     * Panelden ayarlanan saat config'e runtime'da binmeli — config:cache
     * ya da SSH gerekmeden bir sonraki gün geçerli olsun.
     */
    public function test_rapor_saati_config_e_biner(): void
    {
        Settings::setMany(['kahya.rapor_saati' => '09:00']);

        $this->mergeKahyaConfigCagir();

        $this->assertSame('09:00', config('kahya.rapor_saati'));
    }

    /**
     * Geçersiz saat config'e BİNMEMELİ: dailyAt() bozuk değerde sessizce
     * çalışmaz ve zamanlayıcı hatası da sessiz bir hatadır.
     */
    public function test_gecersiz_saat_config_e_binmez(): void
    {
        Settings::setMany(['kahya.rapor_saati' => 'saat-degil']);

        $this->mergeKahyaConfigCagir();

        $this->assertSame('07:30', config('kahya.rapor_saati'), 'Bozuk değer varsayılanı ezmemeli.');
    }

    /**
     * Provider metodunu doğrudan çağır — MailSettingsTest ile aynı desen.
     * refreshApplication() İŞE YARAMAZ: yeni uygulama örneği yeni bir
     * bağlantı açar ve RefreshDatabase'in işlemindeki ayarları göremez.
     */
    private function mergeKahyaConfigCagir(): void
    {
        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'mergeKahyaConfig');
        $method->setAccessible(true);
        $method->invoke($provider);
    }
}

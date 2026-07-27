<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TasarimAyarlari;
use App\Models\User;
use App\Support\Settings;
use App\Support\Tema;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tema motoru (Vitrin — Faz P0) sözleşme testleri.
 *
 * P0'da vitrin view ağacı HENÜZ YOK: bu testler altyapının (bayrak, fallback,
 * tek-kapı tasarim_modu bastırması, admin önizleme, bekçi) klasiğe sıfır etkiyle
 * çalıştığını mühürler. P1/P2'de OVERRIDE_LISTESI'ne dosya eklendikçe bekçi
 * testi genişler.
 */
class TemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * vitrin/ ağacına konulmasına İZİN VERİLEN override dosyalarının beyaz
     * listesi (repo köküne göre resources/views/vitrin altındaki yol).
     * Yeni bir override eklemek = bu listeye bilinçli bir satır eklemek.
     * Liste ile dizin içeriği birebir eşleşmezse CI kırmızı (sessiz
     * sürüklenmeye karşı bekçi — mimari plan, Ö2 aşısı).
     */
    private const OVERRIDE_LISTESI = [
        'components/layouts/app.blade.php',
        'components/layouts/guest.blade.php',
        'home.blade.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_default_theme_is_klasik(): void
    {
        $this->assertSame('klasik', setting('gorunum.tema', 'klasik'));
        $this->assertFalse(Tema::vitrinMi());

        $this->get('/')->assertOk()->assertDontSee('data-tema="vitrin"', false);
    }

    public function test_vitrin_renders_overrides_and_falls_back_elsewhere(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->assertTrue(Tema::vitrinMi());

        // Override'ı olan sayfa vitrin iskeletiyle (data-tema işareti + palet
        // remap'i + Plus Jakarta) render edilir...
        $this->get('/')->assertOk()
            ->assertSee('data-tema="vitrin"', false)
            ->assertSee('--color-emerald-600: #3E63F0', false)
            ->assertSee('Plus Jakarta Sans', false);

        // ...override'ı OLMAYAN sayfa klasik gövdeyle ama vitrin kabuğu
        // olmadan (kendi klasik iskeletiyle değil — layout override'ı
        // sayesinde vitrin iskeletiyle) hatasız açılır (fallback garantisi).
        $this->get('/ilanlar')->assertOk()->assertSee('data-tema="vitrin"', false);
    }

    public function test_vitrin_guest_pages_use_vitrin_palette(): void
    {
        // Giriş/kayıt sayfaları klasik guest iskeletini kullanıyordu ve o iskelet
        // hiçbir tema bileşeni basmadığı için vitrin'de emerald sınıfları
        // Tailwind'in varsayılan YEŞİLİne çözülüyordu (mavi sitenin ortasında
        // yeşil auth sayfası). Override bunu kapatır.
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->get('/giris')->assertOk()
            ->assertSee('data-tema="vitrin"', false)
            ->assertSee('--color-emerald-600: #3E63F0', false)
            ->assertDontSee('#059669', false);
    }

    public function test_vitrin_home_preserves_content_contracts(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        // Hero metinleri (Slogan Set 3) vitrin hero'sunda da settings'ten akar;
        // klasiğin brand/tasarim theme'leri vitrin'de basılmaz.
        $this->get('/')->assertOk()
            ->assertSee('Nakliyeci mi, hoca mı?', false)
            ->assertSee('Hepsi burada, Türkçe.', false)
            ->assertDontSee('nisoya-tasarim-theme', false);
    }

    public function test_invalid_theme_value_falls_back_to_klasik(): void
    {
        Settings::setMany(['gorunum.tema' => 'h4x']);

        $this->assertSame('klasik', Tema::aktif());
        $this->get('/')->assertOk();
    }

    public function test_vitrin_suppresses_obsidian_dark_lock_but_preserves_preference(): void
    {
        // Kalıntı obsidian tercihi vitrin'de hükümsüz (koyu-kilit sızmaz)...
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'gorunum.tasarim_modu' => 'obsidian',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('const FORCE_DARK = false', false)
            ->assertSee('Karanlık/aydınlık tema değiştir', false);

        // ...klasiğe dönünce tercih aynen geri gelir.
        Settings::setMany(['gorunum.tema' => 'klasik']);

        $this->get('/')->assertOk()
            ->assertSee('const FORCE_DARK = true', false)
            ->assertDontSee('Karanlık/aydınlık tema değiştir', false);
    }

    public function test_vitrin_suppresses_yeni_serif_and_pulse_map_leak(): void
    {
        // Kalıntı 'yeni' preset'i vitrin'de serif başlık / Nabız Haritası
        // sızdırmamalı (risk denetimi #7: fallback klasik gövdelerde
        // serif+terracotta karışık kimlik). TAM preset yazılır (secPreset
        // 'yeni' tasarim_modu ile BİRLİKTE font/renk de yazar) — yalnız
        // tasarim_modu ile test etmek tasarim-theme'in font/renk sızıntısını
        // gözden kaçırıyordu (P0 inceleme bulgusu #4).
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'gorunum.tasarim_modu' => 'yeni',
            'gorunum.font_family' => 'serif',
            'gorunum.primary_color' => '#0f5c42',
        ]);

        $this->get('/')->assertOk()
            ->assertDontSee('font-serif italic', false)
            ->assertDontSee("Nisoya'nın Nabzı", false)
            ->assertDontSee("--font-sans: 'Instrument Serif'", false)
            ->assertDontSee('#0f5c42', false);
    }

    public function test_middleware_finder_yollarini_idempotent_esitler(): void
    {
        // Finder 'view' singleton'ında yaşar — aynı test/worker ömründeki
        // ardışık isteklerde yol birikmemeli, klasiğe dönüşte çıkarılmalı
        // (P0 inceleme bulgusu #1: birikim + $views cache zehirlenmesi
        // klasiğe dönüşü sessizce etkisiz bırakıyordu).
        $yol = resource_path('views/vitrin');
        $hedef = realpath($yol) ?: $yol;

        Settings::setMany(['gorunum.tema' => 'vitrin']);
        $this->get('/')->assertOk();
        $this->get('/')->assertOk();

        $finder = $this->app['view']->getFinder();
        $sayi = count(array_filter($finder->getPaths(), fn ($p) => $p === $hedef));
        $this->assertSame(1, $sayi, 'vitrin yolu finder\'da tam BİR kez olmalı (birikim yok)');

        Settings::setMany(['gorunum.tema' => 'klasik']);
        $this->get('/')->assertOk();

        $this->assertNotContains(
            $hedef,
            $this->app['view']->getFinder()->getPaths(),
            'klasiğe dönüşte vitrin yolu finder\'dan çıkarılmalı'
        );
    }

    public function test_preview_query_is_ignored_for_guests_and_members(): void
    {
        $this->get('/?tema_onizleme=vitrin')->assertOk()->assertSessionMissing('tema_onizleme');

        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);
        $this->actingAs($member)->get('/?tema_onizleme=vitrin')->assertOk()->assertSessionMissing('tema_onizleme');
    }

    public function test_admin_preview_persists_in_session_and_can_be_closed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/?tema_onizleme=vitrin')
            ->assertOk()
            ->assertSessionHas('tema_onizleme', 'vitrin');

        // Gezinmede korunur (DB bayrağı hâlâ klasik).
        $this->actingAs($admin)->get('/')->assertOk()->assertSessionHas('tema_onizleme', 'vitrin');
        $this->assertSame('klasik', setting('gorunum.tema', 'klasik'));

        $this->actingAs($admin)->get('/?tema_onizleme=kapat')
            ->assertOk()
            ->assertSessionMissing('tema_onizleme');
    }

    public function test_admin_preview_rejects_invalid_value(): void
    {
        $this->actingAs($this->admin())->get('/?tema_onizleme=h4x')
            ->assertOk()
            ->assertSessionMissing('tema_onizleme');
    }

    public function test_livewire_sec_tema_switches_and_validates(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secTema', 'vitrin')
            ->assertSet('aktifTema', 'vitrin');

        $this->assertDatabaseHas('site_settings', ['key' => 'gorunum.tema', 'value' => 'vitrin']);

        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secTema', 'gecersiz')
            ->assertSet('aktifTema', 'vitrin'); // geçersiz değer yok sayılır

        $this->assertSame('vitrin', setting('gorunum.tema'));
    }

    /**
     * BEKÇİ (çift yönlü): resources/views/vitrin altındaki dosya kümesi
     * OVERRIDE_LISTESI beyaz listesine eşit olmalı VE her override'ın klasik
     * ağaçta bir adaşı bulunmalı. Örtük view çözümlemesinde "sessiz
     * sürüklenme"ye (klasik dosya değişir, vitrin adaşı unutulur / yanlış
     * adla dosya eklenir) karşı tek yapısal fren budur.
     */
    public function test_bekci_vitrin_agaci_beyaz_listeyle_birebir_ayni(): void
    {
        $vitrinKok = resource_path('views/vitrin');

        $mevcut = File::isDirectory($vitrinKok)
            ? collect(File::allFiles($vitrinKok))
                ->map(fn ($f) => str_replace('\\', '/', $f->getRelativePathname()))
                ->reject(fn (string $yol) => $yol === 'README.md')
                ->sort()->values()->all()
            : [];

        $beklenen = collect(self::OVERRIDE_LISTESI)->sort()->values()->all();

        $this->assertSame(
            $beklenen,
            $mevcut,
            'resources/views/vitrin içeriği TemaTest::OVERRIDE_LISTESI ile eşleşmiyor. '
            .'Override eklemek/silmek bilinçli bir karardır: listeyi ve dosyayı birlikte güncelleyin.'
        );

        foreach ($mevcut as $yol) {
            $this->assertFileExists(
                resource_path('views/'.$yol),
                "vitrin/{$yol} override'ının klasik ağaçta adaşı yok — yanlış dosya adı "
                .'(bkz. vitrin/README.md: handoff dosya şeması geçersiz, aynı-ad kuralı geçerli).'
            );
        }

        // Dizin var olduğu an README zorunlu ve ilk satırı aynı-ad kuralını
        // duyurmalı — handoff dokümanındaki (çelişen) dosya şemasını izleyecek
        // bir uygulayıcıya karşı ilk savunma hattı (P0 inceleme bulgusu #6).
        if (File::isDirectory($vitrinKok)) {
            $readme = $vitrinKok.DIRECTORY_SEPARATOR.'README.md';
            $this->assertFileExists($readme, 'vitrin/ dizini README.md olmadan var olamaz.');

            $ilkSatir = trim(strtok((string) file_get_contents($readme), "\n"));
            $this->assertStringContainsString(
                'aynı-ad',
                $ilkSatir,
                'vitrin/README.md ilk satırı aynı-ad kuralını duyurmalı (handoff şeması geçersiz).'
            );
        }
    }

    /**
     * Ekstraksiyon mührü: consent zinciri + SEO head sözleşmesi her iki
     * temada da (P0'da vitrin=klasik fallback) aynen mevcut olmalı.
     */
    public function test_paylasilan_sozlesme_bilesenleri_her_iki_temada_var(): void
    {
        foreach (['klasik', 'vitrin'] as $tema) {
            Settings::setMany(['gorunum.tema' => $tema]);

            $html = $this->get('/')->assertOk()->getContent();

            $this->assertStringContainsString('window.nisoyaActivateConsent', $html, "consent fonksiyonu yok (tema={$tema})");
            $this->assertStringContainsString('rel="canonical"', $html, "canonical yok (tema={$tema})");
            $this->assertStringContainsString('og:site_name', $html, "og meta yok (tema={$tema})");
            $this->assertStringContainsString('"@type": "WebSite"', $html, "JSON-LD yok (tema={$tema})");
            $this->assertStringContainsString('serviceWorker', $html, "sw kaydı yok (tema={$tema})");
        }
    }

    public function test_hero_copy_set3_is_live(): void
    {
        // Slogan Set 3 migration'ı: yeni metinler ana sayfada.
        $this->get('/')->assertOk()
            ->assertSee('Nakliyeci mi, hoca mı?', false)
            ->assertSee('Hepsi burada, Türkçe.', false)
            ->assertDontSee('Yeteneğini paraya dönüştür', false);
    }
}

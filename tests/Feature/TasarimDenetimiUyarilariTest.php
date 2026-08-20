<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\NavigationLink;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\ComponentAttributeBag;
use Tests\TestCase;

/**
 * Tasarım sistemi denetiminin (2026-08-20) kalan 10 uyarı maddesini mühürler
 * (kıdem rozeti öncelik listesinde zaten ayrı PR'da kapatılmıştı).
 */
class TasarimDenetimiUyarilariTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    // ---------------------------------------------------------------- x-chip

    public function test_chip_tonlari_birbirinden_farkli_siniflar_uretir(): void
    {
        $teal = view('components.chip', ['tone' => 'teal'])->with('slot', 'Online')->render();
        $amber = view('components.chip', ['tone' => 'amber'])->with('slot', 'x')->render();
        $neutral = view('components.chip', ['tone' => 'neutral'])->with('slot', 'x')->render();

        $this->assertStringContainsString('#0f9d76', $teal);
        $this->assertStringContainsString('#b9741a', $amber);
        $this->assertStringContainsString('bg-stone-100', $neutral);
    }

    public function test_chip_ekstra_oznitelikler_gecer(): void
    {
        $html = view('components.chip', [
            'tone' => 'teal',
            'attributes' => new ComponentAttributeBag(['title' => 'Doğrulanmış üye']),
        ])->with('slot', 'Doğrulandı')->render();

        $this->assertStringContainsString('title="Doğrulanmış üye"', $html);
    }

    // ----------------------------------------------------- Masaüstü gezinme

    public function test_aktif_sayfanin_menu_baglantisi_isaretlenir(): void
    {
        // Gerçek olay (2026-08-21, canlıda ölçüldü): admin panelinden
        // kaydedilen $navLink->url GÖRECELİ yoldur ("/nasil-calisir") —
        // url()/route() ile üretilen TAM adres DEĞİL. İlk sürüm bunu
        // request()->url() (tam adres) ile birebir karşılaştırıyordu ve asla
        // eşleşmiyordu; fixture'da yanlışlıkla url() kullanılınca bu hata
        // testte gizli kalmıştı. Burada bilerek GERÇEK biçim kullanılıyor.
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        NavigationLink::query()->create([
            'label' => 'İş İlanları', 'url' => '/is-ilanlari', 'sort_order' => 1, 'is_active' => true,
        ]);
        NavigationLink::query()->create([
            'label' => 'Nasıl Çalışır', 'url' => '/nasil-calisir', 'sort_order' => 2, 'is_active' => true,
        ]);

        $html = $this->get('/nasil-calisir')->assertOk()->getContent();

        // Aynı URL canonical/og:url etiketlerinde de geçtiği için önce
        // MASAÜSTÜ MENÜ kapsayıcısını (kendine özgü sınıfıyla) bulup arama
        // alanını ona daraltıyoruz — aksi hâlde <head>'deki canonical
        // etiketiyle yanlış eşleşir.
        $menuBaslangici = strpos($html, '<nav class="hidden items-center gap-6');
        $this->assertNotFalse($menuBaslangici, 'Masaüstü menü kapsayıcısı bulunamadı.');
        $menu = substr($html, $menuBaslangici, 2000);

        // Her bağlantının KENDİ etiketi — href'inden başlayıp yalnız ileri
        // doğru kısa bir pencere (komşu bağlantıya taşmadan) — ayrı ayrı
        // incelenir. "Nasıl Çalışır" işaretli, "İş İlanları" işaretsiz olmalı.
        $aktifKonum = strpos($menu, 'href="/nasil-calisir"');
        $this->assertNotFalse($aktifKonum, 'Aktif bağlantı menüde bulunamadı.');
        $aktifEtiket = substr($menu, $aktifKonum, 300);
        $this->assertStringContainsString('aria-current="page"', $aktifEtiket);
        $this->assertStringContainsString('after:w-full', $aktifEtiket);

        $pasifKonum = strpos($menu, 'href="/is-ilanlari"');
        $this->assertNotFalse($pasifKonum, 'Pasif bağlantı menüde bulunamadı.');
        $pasifEtiket = substr($menu, $pasifKonum, 300);
        $this->assertStringNotContainsString('aria-current', $pasifEtiket);
    }

    public function test_harici_baglanti_hicbir_zaman_aktif_isaretlenmez(): void
    {
        // Aynı yol segmentini taşıyan ama BAŞKA bir siteye giden bağlantı
        // yanlışlıkla aktif işaretlenmemeli — yalnız yol değil, host da
        // (varsa) eşleşmeli.
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        NavigationLink::query()->create([
            'label' => 'Dış Bağlantı', 'url' => 'https://baska-site.test/nasil-calisir', 'sort_order' => 1, 'is_active' => true, 'opens_new_tab' => true,
        ]);

        $this->get('/nasil-calisir')->assertOk()->assertDontSee('aria-current="page"', false);
    }

    public function test_aktif_olmayan_sayfada_hicbir_baglanti_isaretlenmez(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        NavigationLink::query()->create([
            'label' => 'Nasıl Çalışır', 'url' => url('/nasil-calisir'), 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertDontSee('aria-current="page"', false);
    }

    // -------------------------------------------------------------- Avatar

    public function test_mesajlar_listesinde_avatar_bilesen_kullanir(): void
    {
        $ben = User::factory()->create(['email_verified_at' => now()]);
        $digeri = User::factory()->create(['email_verified_at' => now(), 'name' => 'Karşı Taraf']);

        Conversation::query()->create([
            'user_one_id' => $ben->id,
            'user_two_id' => $digeri->id,
            'last_message_at' => now(),
        ]);

        $html = $this->actingAs($ben)->get(route('panel.messages.index'))->assertOk()->getContent();

        // x-avatar bileşeni baş harfi aynı mantıkla üretir; burada asıl ölçülen
        // şey hand-rolled kopyanın SİLİNMİŞ olması (regresyon: iki farklı
        // avatar tarifi olmamalı).
        $this->assertStringContainsString('K', $html);
    }

    // ----------------------------------------------------------- Boş durum

    public function test_emlak_bos_durumu_paylasilan_bilesenle_basar(): void
    {
        $this->get(route('properties.index'))->assertOk()
            ->assertSee('Henüz ilan yok')
            ->assertSee('border-dashed', false);
    }

    public function test_vasita_bos_durumu_paylasilan_bilesenle_basar(): void
    {
        $this->get(route('vehicles.index'))->assertOk()
            ->assertSee('Henüz ilan yok')
            ->assertSee('border-dashed', false);
    }

    public function test_mutlu_anlar_bos_durumu_yeni_kamera_illustrasyonuyla_basar(): void
    {
        $this->get(route('happy-moments'))->assertOk()
            ->assertSee('Henüz herkese açık albüm yok')
            ->assertSee('border-dashed', false);
    }

    // --------------------------------------------------------------- Başlık

    public function test_sirket_profili_baslik_boyutu_ilan_detayiyla_ayni_katmanda(): void
    {
        $sahip = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::create(['user_id' => $sahip->id, 'name' => 'Acme', 'slug' => 'acme']);

        $this->get(route('companies.show', $company))->assertOk()
            ->assertSee('text-2xl font-bold', false);
    }

    // ------------------------------------------------------- Sondaki köşe

    public function test_ilan_detayinda_olcek_disi_koseler_kalmadi(): void
    {
        $satici = User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
        $kategori = Category::query()->whereNotNull('parent_id')->firstOrFail();
        $listing = Listing::factory()->for($satici)->create(['category_id' => $kategori->id, 'status' => 'aktif']);

        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $html = $this->get(route('listings.show', [$listing, $listing->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString('rounded-[9px]', $html);
        $this->assertStringNotContainsString('rounded-[13px]', $html);
        $this->assertStringNotContainsString('rounded-[18px]', $html);
    }

    // -------------------------------------------------------- Ekmek kırıntısı

    public function test_ilan_detayi_ekmek_kirintisi_erisilebilir_kaliba_uyar(): void
    {
        $satici = User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
        $kategori = Category::query()->whereNotNull('parent_id')->firstOrFail();
        $listing = Listing::factory()->for($satici)->create(['category_id' => $kategori->id, 'status' => 'aktif']);

        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->get(route('listings.show', [$listing, $listing->slug]))->assertOk()
            ->assertSee('aria-label="breadcrumb"', false);
    }

    // ------------------------------------------------- Form doğrulama rengi

    public function test_hesap_silme_formunda_hata_metni_koyu_mod_varyanti_tasir(): void
    {
        $uye = User::factory()->create(['email_verified_at' => now(), 'role' => UserRole::Uye]);

        $html = $this->actingAs($uye)->get(route('panel.profile.edit'))->assertOk()->getContent();

        $this->assertStringNotContainsString('text-red-600">', $html, 'Koyu mod varyantsız kırmızı hata metni kalmamalı.');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\HeroYoneticisi;
use App\Models\HomeHighlight;
use App\Models\User;
use App\Support\Hero;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hero Yöneticisi (Vitrin — Faz P3): panelden yönetilen hero.
 *
 * Ayrı bir hero_settings tablosu yerine `hero.*` site_settings anahtarları
 * kullanılır (gerekçe: App\Support\Hero docblock'u) — bu testler hem panel
 * kaydını hem ön yüz karşılığını mühürler.
 */
class HeroYoneticisiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_can_open_page_and_member_cannot(): void
    {
        $this->actingAs($this->admin())->get('/yonetim/hero-yoneticisi')->assertOk();

        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);
        $this->actingAs($member)->get('/yonetim/hero-yoneticisi')->assertRedirect(route('dashboard'));
    }

    public function test_defaults_fall_back_to_classic_hero_copy(): void
    {
        // Panel hiç açılmadan: hero alanları boş → klasik home.hero_* değerleri.
        $this->assertSame('bento', Hero::duzen());
        $this->assertSame(setting('home.hero_satir1'), Hero::baslik());
        $this->assertSame(setting('home.hero_vurgu'), Hero::vurgu());
        $this->assertSame(setting('home.hero_aciklama'), Hero::altBaslik());
        $this->assertSame(array_keys(Hero::BLOKLAR), Hero::aktifBloklar());
    }

    public function test_admin_can_save_hero_content_and_frontend_reflects_it(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        Livewire::actingAs($this->admin())
            ->test(HeroYoneticisi::class)
            ->fillForm([
                'duzen' => 'sahne',
                'rozet' => 'Test rozeti',
                'baslik' => 'Panelden gelen başlık',
                'vurgu' => 'Vurgulu satır',
                'alt_baslik' => 'Panelden gelen alt başlık',
                'cta1_etiket' => 'Hemen başla',
                'cta1_url' => '/kayit',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('sahne', setting('hero.duzen'));

        $this->get('/')->assertOk()
            ->assertSee('Panelden gelen başlık', false)
            ->assertSee('Vurgulu satır', false)
            ->assertSee('Panelden gelen alt başlık', false)
            ->assertSee('Hemen başla', false);
    }

    public function test_secondary_cta_is_not_rendered_when_disabled(): void
    {
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'hero.cta2_aktif' => '0',
            'hero.cta2_etiket' => 'Gizli buton',
            'hero.cta2_url' => '/gizli',
        ]);

        $this->assertNull(Hero::cta(2));
        $this->get('/')->assertOk()->assertDontSee('Gizli buton', false);

        // Açılınca görünür (etiket + URL dolu olmalı)
        Settings::setMany(['hero.cta2_aktif' => '1']);
        $this->get('/')->assertOk()->assertSee('Gizli buton', false);
    }

    public function test_closed_block_is_not_rendered_at_all(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        // Varsayılan: arama bloğu açık → arama formu var
        $this->get('/')->assertOk()->assertSee('Kim lazım?', false);

        // Arama bloğu kapatılınca DOM'a HİÇ basılmamalı (CSS ile gizleme değil)
        Settings::setMany(['hero.bloklar' => json_encode([
            ['anahtar' => 'arama', 'acik' => false],
            ['anahtar' => 'populer_etiketler', 'acik' => true],
            ['anahtar' => 'canli_sayaclar', 'acik' => true],
        ])]);

        $this->assertFalse(Hero::blokAcikMi('arama'));
        $this->get('/')->assertOk()->assertDontSee('Kim lazım?', false);
    }

    public function test_block_order_is_respected(): void
    {
        Settings::setMany(['hero.bloklar' => json_encode([
            ['anahtar' => 'canli_sayaclar', 'acik' => true],
            ['anahtar' => 'arama', 'acik' => true],
            ['anahtar' => 'populer_etiketler', 'acik' => false],
        ])]);

        $this->assertSame(['canli_sayaclar', 'arama'], Hero::aktifBloklar());
    }

    public function test_invalid_values_fall_back_safely(): void
    {
        Settings::setMany([
            'hero.duzen' => 'h4x',
            'hero.arkaplan_tipi' => 'h4x',
            'hero.overlay' => '999',
            'hero.odak' => 'h4x',
            'hero.bloklar' => 'bozuk-json',
        ]);

        $this->assertSame('bento', Hero::duzen());
        $this->assertSame('yok', Hero::arkaplanTipi());
        $this->assertSame(100, Hero::overlay());
        $this->assertSame('center', Hero::odakCss());
        $this->assertSame(array_keys(Hero::BLOKLAR), Hero::aktifBloklar());
    }

    public function test_background_image_renders_with_overlay_and_focal_point(): void
    {
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'hero.arkaplan_tipi' => 'gorsel',
            'hero.gorsel_masaustu' => 'hero/kapak.webp',
            'hero.overlay' => '40',
            'hero.odak' => 'sag-merkez',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('hero/kapak.webp', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('object-position: right center', false)
            ->assertSee('opacity: 0.4', false);
    }

    public function test_vitrin_home_renders_admin_managed_highlight_cards(): void
    {
        // P3-b: vurgu kartları (home_highlights) artık vitrin ana sayfasında da
        // görünür — aynı model, aynı HomeSections kapısı, backend'e dokunulmadan.
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        HomeHighlight::create([
            'slot' => HomeHighlight::SLOT_BIG,
            'title' => 'Büyük kart başlığı',
            'text' => 'Büyük kart metni',
            'icon' => 'heart',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        HomeHighlight::create([
            'slot' => HomeHighlight::SLOT_SMALL,
            'title' => 'Küçük kart başlığı',
            'text' => 'Küçük kart metni',
            'icon' => 'gift',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Büyük kart başlığı', false)
            ->assertSee('Küçük kart başlığı', false);

        // Bölüm panelden kapatılınca hiç basılmamalı (HomeSections sözleşmesi)
        Settings::setMany(['home.section.deger_onerileri' => '0']);

        $this->get('/')->assertOk()
            ->assertDontSee('Büyük kart başlığı', false)
            ->assertDontSee('Küçük kart başlığı', false);
    }

    public function test_kampanya_yalniz_tarih_araliginda_gecerlidir(): void
    {
        // Kampanya kararı HER RENDER'DA verilir (cache'e yalnız ham tarihler
        // girer) — bu yüzden başlangıç/bitişte cache temizlemeye ya da
        // zamanlanmış işe gerek yoktur. Test bunu üç pencerede doğrular.
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'hero.baslik' => 'Normal başlık',
            'hero.kampanya_aktif' => '1',
            'hero.kampanya_baslik' => 'Bayram başlığı',
            'hero.kampanya_baslangic' => now()->addDay()->toDateTimeString(),
            'hero.kampanya_bitis' => now()->addDays(3)->toDateTimeString(),
        ]);

        // 1) Henüz başlamadı → normal metin
        $this->assertFalse(Hero::kampanyaAktifMi());
        $this->get('/')->assertOk()->assertSee('Normal başlık', false)->assertDontSee('Bayram başlığı', false);

        // 2) Pencere içinde → kampanya metni
        Settings::setMany([
            'hero.kampanya_baslangic' => now()->subHour()->toDateTimeString(),
            'hero.kampanya_bitis' => now()->addHour()->toDateTimeString(),
        ]);
        $this->assertTrue(Hero::kampanyaAktifMi());
        $this->get('/')->assertOk()->assertSee('Bayram başlığı', false)->assertDontSee('Normal başlık', false);

        // 3) Süresi doldu → kendiliğinden normale döner
        Settings::setMany([
            'hero.kampanya_baslangic' => now()->subDays(3)->toDateTimeString(),
            'hero.kampanya_bitis' => now()->subDay()->toDateTimeString(),
        ]);
        $this->assertFalse(Hero::kampanyaAktifMi());
        $this->get('/')->assertOk()->assertSee('Normal başlık', false)->assertDontSee('Bayram başlığı', false);
    }

    public function test_kampanya_kapaliyken_tarihler_dolu_olsa_bile_calismaz(): void
    {
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'hero.baslik' => 'Normal başlık',
            'hero.kampanya_aktif' => '0',
            'hero.kampanya_baslik' => 'Bayram başlığı',
            'hero.kampanya_baslangic' => now()->subHour()->toDateTimeString(),
            'hero.kampanya_bitis' => now()->addHour()->toDateTimeString(),
        ]);

        $this->assertFalse(Hero::kampanyaAktifMi());
        $this->get('/')->assertOk()->assertSee('Normal başlık', false);
    }

    public function test_bozuk_kampanya_tarihi_kampanyayi_acmaz(): void
    {
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'hero.kampanya_aktif' => '1',
            'hero.kampanya_baslik' => 'Bayram başlığı',
            'hero.kampanya_baslangic' => 'bozuk-tarih',
        ]);

        $this->assertFalse(Hero::kampanyaAktifMi());
        $this->get('/')->assertOk()->assertDontSee('Bayram başlığı', false);
    }

    public function test_bos_birakilan_kampanya_alani_normal_degerini_korur(): void
    {
        Settings::setMany([
            'gorunum.tema' => 'vitrin',
            'hero.baslik' => 'Normal başlık',
            'hero.alt_baslik' => 'Normal alt başlık',
            'hero.kampanya_aktif' => '1',
            'hero.kampanya_baslik' => 'Bayram başlığı',
            // kampanya_alt_baslik bilinçli olarak boş
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Bayram başlığı', false)
            ->assertSee('Normal alt başlık', false);
    }

    public function test_hero_settings_are_inert_while_klasik_theme_is_active(): void
    {
        // Vitrin'e özgü: klasik tema aktifken hero.* ayarları ön yüzü etkilemez.
        Settings::setMany([
            'gorunum.tema' => 'klasik',
            'hero.baslik' => 'Vitrin başlığı',
        ]);

        $this->get('/')->assertOk()->assertDontSee('Vitrin başlığı', false);
    }
}

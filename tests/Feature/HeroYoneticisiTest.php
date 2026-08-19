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
use Filament\Notifications\Livewire\Notifications as BildirimBileseni;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
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

    /** Filament'in `Notification::assertNotified()` içinde yaptığının aynısı — burada süreyi de okumak için. */
    private function bildirimGetir(string $baslik): Notification
    {
        $bilesen = new BildirimBileseni;
        $bilesen->mount();

        $bulunan = $bilesen->notifications->first(fn ($n) => $n->getTitle() === $baslik);
        $this->assertNotNull($bulunan, "Bildirim bulunamadı: {$baslik}");

        return $bulunan;
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

    /**
     * Gerçek olay (2026-08-20, sahip bildirdi): "bildirim geliyor gitmiyor"
     * — panelde her kayıtta (düz metin değişikliğinde bile) kalıcı bir
     * bildirim birikiyordu, elle kapatmak gerekiyordu.
     *
     * Kök neden: `->persistent()` koşulsuzdu. Medya boru hattından ekstra
     * satır (kontrast uyarısı gibi) gelmediği sürece kalıcılığın hiçbir
     * anlamı yok — sıradan bir "kaydedildi" onayı kendiliğinden kapanmalı.
     */
    public function test_medya_uyarisi_yokken_bildirim_kendiliginden_kapanir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(HeroYoneticisi::class)
            ->fillForm(['baslik' => 'Düz metin değişikliği'])
            ->call('save')
            ->assertHasNoFormErrors();

        $bildirim = $this->bildirimGetir('Hero ayarları kaydedildi');
        $this->assertNotSame('persistent', $bildirim->getDuration(), 'Medya bilgisi yokken bildirim kalıcı olmamalı.');
    }

    public function test_medya_isleme_bilgisi_varken_bildirim_kalicidir(): void
    {
        Storage::fake('public');

        $img = (new ImageManager(new Driver))->createImage(2400, 1200)->fill('#8a8a8a');
        $hamYol = 'hero/'.uniqid().'.jpg';
        Storage::disk('public')->put($hamYol, (string) $img->encode(new JpegEncoder(quality: 92)));

        Settings::setMany(['hero.arkaplan_tipi' => 'gorsel', 'hero.gorsel_masaustu' => $hamYol]);

        Livewire::actingAs($this->admin())
            ->test(HeroYoneticisi::class)
            ->fillForm(['arkaplan_tipi' => 'gorsel'])
            ->call('save')
            ->assertHasNoFormErrors();

        $bildirim = $this->bildirimGetir('Hero ayarları kaydedildi');
        $this->assertSame('persistent', $bildirim->getDuration(), 'Medya işleme satırları (kontrast ölçümü vb.) varken bildirim kalıcı olmalı.');
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

    /**
     * SÖZLEŞME DEĞİŞTİ (2026-08-06) — eski hâli:
     * `test_hero_settings_are_inert_while_klasik_theme_is_active`, yani
     * "klasik tema aktifken hero.* ayarları ön yüzü etkilemez".
     *
     * O sözleşme kasıtlıydı ama ÜRÜN OLARAK yanlıştı ve sahibin şikâyetiyle
     * ortaya çıktı: "Hero Yöneticisi'nde değişiklik yapıyorum, ana sayfada
     * değişmiyor." Ekran kaydediyordu, hiçbir yere bağlanmıyordu — sayfanın
     * tepesindeki uyarı bandı da kaydırılınca görülmüyordu.
     *
     * Yeni sözleşme: METİN alanları iki temada da işler (klasik hero artık
     * App\Support\Hero üzerinden okuyor). Düzen, bloklar, arka plan, kampanya
     * ve butonlar Vitrin'e özgü kalır — klasik hero'da karşılıkları yok.
     *
     * Geriye dönük uyum korunuyor: `hero.*` boşken Hero zaten `home.*`
     * metinlerine düşüyor, yani yöneticiye hiç dokunmamış bir site
     * eskisiyle birebir aynı görünür (bkz. HeroYoneticisiEtkiTest).
     */
    public function test_hero_metinleri_klasik_temada_da_isler(): void
    {
        Settings::setMany([
            'gorunum.tema' => 'klasik',
            'hero.baslik' => 'Vitrin başlığı',
        ]);

        $this->get('/')->assertOk()->assertSee('Vitrin başlığı', false);
    }

    public function test_kampanya_metni_klasik_temada_da_isler(): void
    {
        /*
         * BU TESTİ ÖNCE TERS YAZDIM ve düştü — kod değil iddiam yanlıştı.
         *
         * Kampanya, Hero içinde AYNI DÖRT METNİN tarih aralıklı hâlidir
         * (kampanyaliDeger: kampanya > panel değeri > klasik metin); Vitrin'e
         * özgü görsel bir yanı yok. Metin katmanını klasik temaya açıp
         * kampanyayı dışarıda bırakmak, "bayram başlığı neden çıkmıyor" diye
         * geri gelecek yapay bir istisna olurdu.
         */
        Settings::setMany([
            'gorunum.tema' => 'klasik',
            'hero.baslik' => 'Normal başlık',
            'hero.kampanya_aktif' => '1',
            'hero.kampanya_baslik' => 'Bayram kampanyası',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Bayram kampanyası', false)
            ->assertDontSee('Normal başlık', false);
    }

    public function test_vitrine_ozgu_butonlar_klasik_temada_etkisiz(): void
    {
        /*
         * Sözleşmenin DEĞİŞMEYEN yarısı: klasik hero'da karşılığı OLMAYAN
         * şeyler. Butonların klasik tasarımda yeri yok — orada hero'nun
         * altında arama kutusu var ve birincil eylem odur. Butonların
         * sızması "metin alanlarını açtık" kararını sessizce bir tema
         * karışımına çevirirdi.
         *
         * -----------------------------------------------------------------
         * ARKA PLAN GÖRSELİ BU TESTTEN ÇIKARILDI (2026-08-08)
         *
         * Bu test eskiden `hero.gorsel_masaustu`nun da klasikte SIZMAMASINI
         * mühürlüyordu; yani "klasik hero'nun arka plan görseli yoktur" bir
         * karardı, kaza değil.
         *
         * Karar SAHİBİN İSTEĞİYLE değişti: panel görsel yükleme, kırpma,
         * karartma ve 9 noktalı odak sunuyor ama hangi temada çalıştığını
         * hiçbir yerde söylemiyordu — sahip kontrolü kullanıp sonucu canlıda
         * göremiyordu. İki dürüst çıkış vardı: paneli susturmak ya da kabloyu
         * bağlamak. Sahip ikincisini seçti.
         *
         * Medya artık klasikte de çalışıyor ve pozitif yönde mühürlü:
         * {@see HeroYoneticisiEtkiTest} "MEDYA KABLOSU" bloğu — görsel, mobil
         * görsel, video, karartma, odak ve geriye dönük uyum (medya yokken
         * çıktı birebir eski) İKİ TEMADA BİRDEN sınanıyor.
         *
         * Butonlar bilerek dışarıda kaldı: istenen "medya kablosu"ydu,
         * butonlar ayrı bir tasarım kararı.
         */
        Settings::setMany([
            'gorunum.tema' => 'klasik',
            'hero.cta1_etiket' => 'VITRIN-BUTONU',
            'hero.cta1_url' => '/ilanlar',
        ]);

        $this->get('/')->assertOk()
            ->assertDontSee('VITRIN-BUTONU', false);
    }
}

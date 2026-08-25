<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Page;
use App\Models\RehberGeriBildirimi;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use App\Services\Kahya\BekleyenIsler;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RehberAlmanyaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ülke-Adaptif Rehber F1 (tasarım: docs/plans/2026-08-01-ulke-adaptif-rehber-tasarimi.md).
 *
 * Modül aç/kapa katmanının genel testleri ModulesTest'te (KEYS döngüsü yeni
 * 'rehber' anahtarını kendiliğinden kapsar); burada rehberin KENDİ sözleşmeleri
 * sınanır: taslak-önce yayın kapısı (K7), URL şeması ve catch-all komşuluğu,
 * geri bildirim akışı, sitemap boş-içerik kuralı, Kâhya bayatlık kuyruğu.
 */
class RehberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function temsilcilik(array $overrides = []): Temsilcilik
    {
        return Temsilcilik::query()->create(array_merge([
            'country_code' => 'DE',
            'ad' => 'Köln Başkonsolosluğu',
            'slug' => 'koeln',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK,
            'sehir' => 'Köln',
            'is_active' => true,
        ], $overrides));
    }

    private function islemTuru(array $overrides = []): IslemTuru
    {
        return IslemTuru::query()->create(array_merge([
            'ad' => 'Vekaletname',
            'slug' => 'vekaletname',
            'is_active' => true,
        ], $overrides));
    }

    private function yayindaIslem(Temsilcilik $temsilcilik, IslemTuru $tur, array $overrides = []): TemsilcilikIslemi
    {
        return TemsilcilikIslemi::query()->create(array_merge([
            'temsilcilik_id' => $temsilcilik->id,
            'islem_turu_id' => $tur->id,
            'evraklar' => [['ad' => 'T.C. kimlik kartı'], ['ad' => 'Randevu', 'not' => 'konsolosluk.gov.tr']],
            'sure_metni' => 'aynı gün',
            'ucret_metni' => '50 €',
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_YAYIN,
            'dogrulanma_tarihi' => now(),
        ], $overrides));
    }

    // ------------------------------------------------------------ Sayfalar

    public function test_ulke_sayfasi_temsilcilikleri_listeler(): void
    {
        $this->temsilcilik();

        $this->get('/de')
            ->assertOk()
            ->assertSee('Almanya')
            ->assertSee('Köln Başkonsolosluğu');
    }

    public function test_pasif_veya_bilinmeyen_ulke_404(): void
    {
        Country::query()->where('code', 'DE')->update(['is_active' => false]);

        $this->get('/de')->assertNotFound();
        $this->get('/zz')->assertNotFound();
    }

    public function test_temsilcilik_sayfasi_yalniz_yayindaki_islemleri_gosterir(): void
    {
        $t = $this->temsilcilik();
        $yayinda = $this->yayindaIslem($t, $this->islemTuru());
        $this->yayindaIslem($t, $this->islemTuru(['ad' => 'Pasaport', 'slug' => 'pasaport']), [
            'status' => TemsilcilikIslemi::STATUS_TASLAK,
        ]);

        $this->get('/de/koeln')
            ->assertOk()
            ->assertSee('Vekaletname')
            ->assertDontSee('Pasaport');
    }

    public function test_temsilcilik_sayfasi_ziyaret_oncesi_ipuclarini_her_zaman_gosterir(): void
    {
        $this->get(route('rehber.temsilcilik', ['de', $this->temsilcilik()->slug]))
            ->assertOk()
            ->assertSee('Ziyaret öncesi')
            ->assertSee('yeminli tercüme');
    }

    public function test_temsilcilik_sayfasi_koordinat_varsa_harita_dugmesi_gosterir(): void
    {
        $t = $this->temsilcilik(['latitude' => 50.8816558, 'longitude' => 6.8923562]);

        $this->get(route('rehber.temsilcilik', ['de', $t->slug]))
            ->assertOk()
            ->assertSee('Google Haritalar');
    }

    public function test_temsilcilik_sayfasi_koordinat_yoksa_harita_dugmesi_gostermez(): void
    {
        $this->get(route('rehber.temsilcilik', ['de', $this->temsilcilik()->slug]))
            ->assertOk()
            ->assertDontSee("'ta aç", escape: false);
    }

    public function test_islem_sayfasi_evrak_listesi_ve_resmi_kaynak_gosterir(): void
    {
        $this->yayindaIslem($this->temsilcilik(), $this->islemTuru());

        $this->get('/de/koeln/vekaletname')
            ->assertOk()
            ->assertSee('T.C. kimlik kartı')
            ->assertSee('https://www.konsolosluk.gov.tr')
            ->assertSee('bilgilendirme amaçlıdır', escape: false)
            ->assertSee('Son doğrulama');
    }

    /** K7'nin kalbi: taslak içerik SİTEDE YOKTUR — 404, "yakında" bile değil. */
    public function test_taslak_islem_sayfasi_404(): void
    {
        $this->yayindaIslem($this->temsilcilik(), $this->islemTuru(), [
            'status' => TemsilcilikIslemi::STATUS_TASLAK,
        ]);

        $this->get('/de/koeln/vekaletname')->assertNotFound();
    }

    public function test_pasif_temsilcilik_404(): void
    {
        $t = $this->temsilcilik(['is_active' => false]);
        $this->yayindaIslem($t, $this->islemTuru());

        $this->get('/de/koeln')->assertNotFound();
        $this->get('/de/koeln/vekaletname')->assertNotFound();
    }

    // ------------------------------------------------- Ülke değiştirici (F3)

    public function test_ulke_sayfasinda_baska_hazir_ulkeye_gecis_baglantisi_var(): void
    {
        $tur = $this->islemTuru();
        $this->yayindaIslem($this->temsilcilik(), $tur);
        $this->yayindaIslem($this->temsilcilik([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
        ]), $tur);

        $this->get('/de')
            ->assertOk()
            ->assertSee('Başka ülke:')
            ->assertSee('Kırgızistan')
            ->assertSee(route('rehber.ulke', 'kg'));
    }

    public function test_tek_hazir_ulke_varken_degistirici_gorunmez(): void
    {
        // Geçilecek başka bir ülke yokken "Başka ülke:" etiketi de görünmemeli
        // — boş bir seçenek satırı yalancı seçenek sunardı.
        $this->yayindaIslem($this->temsilcilik(), $this->islemTuru());

        $this->get('/de')->assertOk()->assertDontSee('Başka ülke:');
    }

    public function test_bos_ulke_sayfasi_hazir_ulkeleri_sayfa_icinde_onerir(): void
    {
        // US aktif ama içeriksiz. Eski davranış yalnız "ana sayfadan
        // bulabilirsin" derdi (geri yönlendirme); yenisi hazır ülkeyi
        // (Almanya) doğrudan sayfanın İÇİNDE önerir.
        $this->yayindaIslem($this->temsilcilik(), $this->islemTuru());

        $this->get('/us')
            ->assertOk()
            ->assertSee('Bu ülkenin rehberi henüz hazırlanıyor.')
            ->assertSee('Almanya')
            ->assertSee(route('rehber.ulke', 'de'))
            ->assertDontSee('ana sayfadan bulabilirsin');
    }

    public function test_temsilcilik_ve_islem_sayfalarinda_da_degistirici_gorunur(): void
    {
        $tur = $this->islemTuru();
        $this->yayindaIslem($this->temsilcilik(), $tur);
        $this->yayindaIslem($this->temsilcilik([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
        ]), $tur);

        $this->get('/de/koeln')->assertOk()->assertSee('Kırgızistan');
        $this->get('/de/koeln/vekaletname')->assertOk()->assertSee('Kırgızistan');
    }

    // ------------------------------------------- Catch-all / CMS komşuluğu

    public function test_cms_sayfalari_rehber_rotalarindan_etkilenmez(): void
    {
        // İçerik değil ROTA sınanıyor — boş blok yeter.
        Page::query()->create([
            'title' => 'Hakkımızda', 'slug' => 'hakkimizda', 'status' => 'yayin', 'blocks' => [],
        ]);

        // 3+ harfli slug catch-all'a düşmeye devam eder.
        $this->get('/hakkimizda')->assertOk();
    }

    public function test_modul_kapaliyken_iki_harfli_yol_404(): void
    {
        $this->temsilcilik();
        Settings::setMany(['modul.rehber' => '0']);

        $this->get('/de')->assertNotFound();
    }

    // ------------------------------------------------------ Geri bildirim

    public function test_geri_bildirim_kaydedilir(): void
    {
        $islem = $this->yayindaIslem($this->temsilcilik(), $this->islemTuru());

        $this->from('/de/koeln/vekaletname')
            ->post(route('rehber.geribildirim', $islem), ['tur' => 'guncel_degil', 'metin' => 'Harç 60 € oldu'])
            ->assertRedirect('/de/koeln/vekaletname')
            ->assertSessionHas('rehber_tesekkur');

        $this->assertDatabaseHas('rehber_geri_bildirimleri', [
            'temsilcilik_islemi_id' => $islem->id,
            'tur' => 'guncel_degil',
            'metin' => 'Harç 60 € oldu',
            'incelendi' => false,
        ]);

        // Ham IP saklanmaz — yalnız hash (kötüye kullanım izi).
        $kayit = RehberGeriBildirimi::query()->firstOrFail();
        $this->assertNotNull($kayit->ip_hash);
        $this->assertStringNotContainsString('127.0.0.1', (string) $kayit->ip_hash);
    }

    public function test_honeypot_dolu_geri_bildirim_sessizce_dusurulur(): void
    {
        $islem = $this->yayindaIslem($this->temsilcilik(), $this->islemTuru());

        $this->post(route('rehber.geribildirim', $islem), [
            'tur' => 'hata',
            'website' => 'http://spam.example',
        ])->assertRedirect();

        $this->assertSame(0, RehberGeriBildirimi::query()->count());
    }

    public function test_taslak_isleme_geri_bildirim_gonderilemez(): void
    {
        $islem = $this->yayindaIslem($this->temsilcilik(), $this->islemTuru(), [
            'status' => TemsilcilikIslemi::STATUS_TASLAK,
        ]);

        $this->post(route('rehber.geribildirim', $islem), ['tur' => 'hata'])->assertNotFound();
        $this->assertSame(0, RehberGeriBildirimi::query()->count());
    }

    // ------------------------------------------------------------ Sitemap

    public function test_sitemap_yalniz_icerikli_rehber_sayfalarini_icerir(): void
    {
        $t = $this->temsilcilik();
        $this->yayindaIslem($t, $this->islemTuru());
        // İçeriksiz ikinci temsilcilik — boş sayfa arama motoruna önerilmez.
        $this->temsilcilik(['ad' => 'Berlin Büyükelçiliği', 'slug' => 'berlin-buyukelciligi', 'sehir' => 'Berlin']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/de'), escape: false)
            ->assertSee(url('/de/koeln/vekaletname'), escape: false)
            ->assertDontSee(url('/de/berlin-buyukelciligi'), escape: false);
    }

    public function test_sitemap_modul_kapaliyken_rehber_icermez(): void
    {
        // Cache anahtarı modül durumunu içerir — kapatınca taze üretilir, flush gerekmez.
        $this->yayindaIslem($this->temsilcilik(), $this->islemTuru());
        Settings::setMany(['modul.rehber' => '0']);

        $this->get('/sitemap.xml')->assertOk()->assertDontSee(url('/de/koeln'), escape: false);
    }

    // ------------------------------------------------------------- Kâhya

    public function test_bayat_rehber_kahya_kuyruguna_duser(): void
    {
        $t = $this->temsilcilik();
        $this->yayindaIslem($t, $this->islemTuru(), [
            'dogrulanma_tarihi' => now()->subDays(TemsilcilikIslemi::BAYATLIK_GUN + 1),
        ]);
        // Taze doğrulanmış ikinci kayıt kuyruğa girmez.
        $this->yayindaIslem($t, $this->islemTuru(['ad' => 'Pasaport', 'slug' => 'pasaport']));

        $kuyruklar = collect(app(BekleyenIsler::class)->topla());
        $bayat = $kuyruklar->firstWhere('anahtar', 'rehber_bayat');

        $this->assertNotNull($bayat);
        $this->assertSame(1, $bayat['adet']);
    }

    public function test_incelenmemis_geri_bildirim_kahya_kuyruguna_duser(): void
    {
        $islem = $this->yayindaIslem($this->temsilcilik(), $this->islemTuru());
        RehberGeriBildirimi::query()->create([
            'temsilcilik_islemi_id' => $islem->id, 'tur' => 'hata', 'incelendi' => false,
        ]);

        $kuyruk = collect(app(BekleyenIsler::class)->topla())->firstWhere('anahtar', 'rehber_geri_bildirim');

        $this->assertNotNull($kuyruk);
        $this->assertSame(1, $kuyruk['adet']);
    }

    // ------------------------------------------------------------- Seeder

    public function test_almanya_seederi_iskelet_kurar_ve_tekrar_kosuda_ezmez(): void
    {
        $this->seed(RehberAlmanyaSeeder::class);

        $this->assertSame(14, Temsilcilik::query()->where('country_code', 'DE')->count());
        $this->assertSame(15, IslemTuru::query()->count());
        $this->assertSame(14 * 15, TemsilcilikIslemi::query()->count());
        // K7: her şey TASLAK doğar — hiçbir içerik doğrulanmadan yayında olamaz.
        $this->assertSame(0, TemsilcilikIslemi::query()->yayinda()->count());

        // Sahip bir kaydı düzenleyip yayına aldı; seeder tekrar koşunca EZMEZ.
        $islem = TemsilcilikIslemi::query()->firstOrFail();
        $islem->update(['status' => TemsilcilikIslemi::STATUS_YAYIN, 'ucret_metni' => '61 €']);

        $this->seed(RehberAlmanyaSeeder::class);

        $islem->refresh();
        $this->assertSame(TemsilcilikIslemi::STATUS_YAYIN, $islem->status);
        $this->assertSame('61 €', $islem->ucret_metni);
        $this->assertSame(14 * 15, TemsilcilikIslemi::query()->count());
    }

    // ---------------------------------------------------- K5 slug rezervi

    public function test_iki_harfli_cms_slugu_kural_tarafindan_reddedilir(): void
    {
        // PageForm'daki not_regex kuralının davranışsal karşılığı: /de gibi
        // 2 harfli bir slug rehber rotasının gölgesinde kalırdı.
        $validator = validator(
            ['slug' => 'de'],
            ['slug' => ['required', 'not_regex:/^[a-z]{2}$/']],
        );

        $this->assertTrue($validator->fails());
    }

    // ------------------------------------------------------------- Yetki

    public function test_admin_panel_kaynaklari_erisilebilir(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get('/yonetim/temsilcilikler')->assertOk();
        $this->actingAs($admin)->get('/yonetim/islem-turleri')->assertOk();
        $this->actingAs($admin)->get('/yonetim/temsilcilik-islemleri')->assertOk();
        $this->actingAs($admin)->get('/yonetim/rehber-geri-bildirimleri')->assertOk();
    }

    public function test_moderator_rehber_kaynaklarina_erisemez(): void
    {
        $mod = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($mod)->get('/yonetim/temsilcilikler')->assertForbidden();
    }
}

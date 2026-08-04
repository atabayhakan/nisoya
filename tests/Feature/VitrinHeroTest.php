<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Vitrin hero — 3 saniye kurgusu ve DÜRÜSTLÜK sözleşmesi (2026-07-28).
 *
 * Bu turun asıl bulgusu görsel değildi: hero'da "Topluluk her gün büyüyor /
 * Son 7 gün · tüm kategoriler" başlıklı, "canlı" rozetli bir kart vardı ve
 * çubukları elle yazılmış sabit sayılardan geliyordu ([46,63,39,72,88,55,67]).
 * Güven üzerine kurulu bir pazaryerinin ilk ekranında uydurma grafik olamaz.
 *
 * İkinci bulgu: hero çipleri `?q=` ile başlık/açıklama araması yapıyordu,
 * kategoriye bakmıyordu — "taşınma" çipi "Nakliyat & Taşınma" kategorisindeki
 * ilanları getirmiyor, ilk tıklamada BOŞ SAYFA dönüyordu.
 *
 * Testler ikisinin de geri gelmesini engeller.
 */
class VitrinHeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        Settings::setMany(['gorunum.tema' => 'vitrin']);
    }

    // ------------------------------------------------ Sahte veri bekçisi

    public function test_hero_sablonunda_elle_yazilmis_sayi_dizisi_bulunmaz(): void
    {
        $kaynak = File::get(resource_path('views/components/vitrin/hero.blade.php'));

        // 3+ elemanlı sayı dizisi = neredeyse kesinlikle uydurma grafik verisi.
        $this->assertDoesNotMatchRegularExpression(
            '/=\s*\[\s*\d+\s*,\s*\d+\s*,\s*\d+/',
            $kaynak,
            'Hero şablonuna elle yazılmış sayı dizisi girmiş. İlk ekranda gösterilen '.
            'her sayı gerçek sorgudan gelmeli; gelemiyorsa o blok hiç basılmamalı.'
        );
    }

    public function test_hic_aktif_ilan_yokken_hero_sayi_ya_da_canli_iddiasi_uretmez(): void
    {
        // Veri yoksa dürüst davranış: bloğu hiç basmamak.
        $yanit = $this->get('/')->assertOk();

        $yanit->assertDontSee('Şu an nerede ilan var');
        $yanit->assertDontSee('Canlı akış');
        $yanit->assertDontSee('Topluluk her gün büyüyor');
    }

    public function test_eski_sahte_grafik_basligi_hicbir_yerde_kalmadi(): void
    {
        Listing::factory()->create(['status' => 'aktif']);

        $this->get('/')->assertOk()->assertDontSee('Topluluk her gün büyüyor');
    }

    // ------------------------------------------------ Gerçek veri kartı

    public function test_ulke_karti_yalniz_ilani_olan_ulkeleri_gosterir(): void
    {
        // countryActivity() ilanı olmayan ülkeleri de count=0 ile döndürür;
        // filtrelenmezse ekranda "0 ilan" yazan satırlar çıkardı.
        Listing::factory()->count(2)->create(['status' => 'aktif', 'country_code' => 'DE']);

        // Sayfanın başka bölümleri de (Ülkeler listesi) ?ulke= bağlantısı
        // üretir; iddia HERO KARTINA daraltılır.
        $kart = $this->heroBolgesi('Şu an nerede ilan var');

        $this->assertStringContainsString('ilanlar?ulke=DE', $kart);

        // Sıfır ilanlı bir ülkenin satırı basılmamalı.
        preg_match_all('/ilanlar\?ulke=([A-Z]{2})/', $kart, $m);
        $this->assertSame(['DE'], array_values(array_unique($m[1])));
    }

    /** Sayfadan bir hero bloğunu kesip alır — iddialar sayfanın tamamına değil o bloğa yapılsın. */
    private function heroBolgesi(string $isaret, int $uzunluk = 2500): string
    {
        $icerik = $this->get('/')->assertOk()->getContent();
        $bas = mb_strpos($icerik, $isaret);
        $this->assertNotFalse($bas, "Hero bloğu bulunamadı: {$isaret}");

        return mb_substr($icerik, $bas, $uzunluk);
    }

    // ------------------------------------------------ Çipler

    public function test_cipler_kategori_rotasina_gider_ve_bos_sonuc_donduremez(): void
    {
        $kategori = Category::query()->where('is_active', true)->first();
        $this->assertNotNull($kategori, 'Test kurulumu: aktif kategori yok.');
        Listing::factory()->create(['status' => 'aktif', 'category_id' => $kategori->id]);

        // İddia çip satırına daraltılır: sayfanın başka yerleri (kategori
        // bölümü, footer) ?q= kullanabilir, bizi ilgilendiren hero çipleri.
        $cipler = $this->heroBolgesi('Popüler:', 1800);

        // Eski sözleşme (?q=) hero çiplerine geri gelmemeli.
        $this->assertStringNotContainsString('ilanlar?q=', $cipler);
        $this->assertStringContainsString('ilanlar?kategori='.$kategori->slug, $cipler);

        // Ve o bağlantı gerçekten sonuç döndürmeli.
        $this->get('/ilanlar?kategori='.$kategori->slug)->assertOk();
    }

    public function test_ilani_olmayan_kategori_cip_olarak_basilmaz(): void
    {
        $dolu = Category::query()->where('is_active', true)->first();
        Listing::factory()->create(['status' => 'aktif', 'category_id' => $dolu->id]);

        $bos = Category::query()->where('is_active', true)->where('id', '!=', $dolu->id)->first();
        $this->assertNotNull($bos);

        $this->get('/')->assertOk()->assertDontSee('ilanlar?kategori='.$bos->slug, false);
    }

    // ------------------------------------------------ Dürüst akış etiketi

    public function test_akis_etiketi_taze_icerik_yokken_canli_demez(): void
    {
        $ilan = Listing::factory()->create(['status' => 'aktif']);
        $ilan->forceFill(['created_at' => now()->subDays(6)])->save();

        $yanit = $this->get('/')->assertOk();

        $yanit->assertDontSee('Canlı akış');
        $yanit->assertSee('Son eklenenler');
    }

    public function test_taze_icerik_varken_canli_der(): void
    {
        Listing::factory()->create(['status' => 'aktif']);

        $this->get('/')->assertOk()->assertSee('Canlı akış');
    }

    // ------------------------------------------------ Slogan

    public function test_yeni_slogan_yayinda(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tarif etmeye çalışma.', false)
            ->assertSee('Türkçe anlat, iş bitsin.', false);
    }

    public function test_slogan_migrationi_sahibin_kendi_metnini_ezmez(): void
    {
        // Migration korumalı: yalnız BUGÜNKÜ değer eski varsayılana birebir
        // eşitse günceller. Sahip kendi metnini yazdıysa dokunmamalı.
        Settings::setMany(['home.hero_satir1' => 'Bizim kendi başlığımız']);

        $this->artisan('migrate:refresh', ['--path' => 'database/migrations/2026_07_28_140000_hero_sloganini_guncelle.php'])
            ->assertSuccessful();

        $this->assertSame('Bizim kendi başlığımız', Settings::get('home.hero_satir1'));
    }

    // ------------------------------------------------ Kanıt satırı

    public function test_kanit_satiri_katalog_degil_gercek_hareket_gosterir(): void
    {
        Listing::factory()->count(3)->create(['status' => 'aktif', 'city' => 'Berlin']);

        $yanit = $this->get('/')->assertOk();

        $yanit->assertSee('aktif ilan');
        $yanit->assertDontSee('kategori</span>', false);
    }

    public function test_sehir_sayimi_buyuk_kucuk_harf_farkini_tek_sehir_sayar(): void
    {
        // listings.city serbest metin; ham distinct 'Berlin'/'berlin'/'Berlin '
        // üç şehir sayardı.
        //
        // 12 kayıt ŞART, 3 değil: kanıt şeridi sayıları ancak envanter bir ilan
        // listesi sayfasını doldurunca basıyor (bkz. HomeController::heroSerit).
        // Bu testin derdi şeridin görünürlük kuralı değil, göründüğünde şehri
        // doğru sayması — o yüzden eşiği geçip asıl iddiaya bakıyoruz.
        foreach (range(1, 12) as $i) {
            Listing::factory()->create([
                'status' => 'aktif',
                'is_demo' => false,
                'city' => ['Berlin', 'berlin', 'BERLIN'][$i % 3],
            ]);
        }

        $yanit = $this->get('/')->assertOk();
        $yanit->assertSee('şehir');

        // İddia SINIF ADLARINDAN BAĞIMSIZ olmalı: bu test şehir sayımının
        // doğruluğunu mühürlüyor, hangi Tailwind sınıfının kullanıldığını
        // değil. Sınıfa bağlı hâli, tipografi ölçeği değişince kırılıyordu.
        preg_match('/>\s*(\d+)\s*<[^>]*>\s*<[^>]*>\s*şehir/su', $yanit->getContent(), $m);
        $this->assertNotEmpty($m, 'Kanıt satırındaki şehir sayısı okunamadı.');
        $this->assertSame('1', $m[1], 'Berlin/berlin/BERLIN tek şehir sayılmalı');
    }
}

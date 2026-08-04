<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Onaylanmış içeriğin JSON'dan aktarımı.
 *
 * En kritik bekçi `aktarma_yayinlamaz`: aktarma ile yayınlama BİLEREK ayrı.
 * Tek komut olsaydı yayın kapısı anlamsızlaşırdı — aktaran taraf yayınlayan
 * taraf olamaz. Aktarma taslak yazar, `rehber:yayinla` kendi ölçütleriyle
 * yayınlar.
 *
 * İkinci kritik bekçi `onay_tarihi_yoksa_aktarilmaz`: `dogrulanma_tarihi`
 * ziyaretçiye "Son doğrulama" olarak gösteriliyor ve 90 günde bayatlıyor.
 * Bu tarih uydurulamaz; dosyada yoksa aktarım tamamen durur.
 */
class RehberIcerikAktarTest extends TestCase
{
    use RefreshDatabase;

    private string $dosya;

    protected function setUp(): void
    {
        parent::setUp();

        Country::firstOrCreate(['code' => 'US'], ['name_tr' => 'Amerika Birleşik Devletleri', 'emoji' => '🇺🇸', 'is_active' => true]);

        Temsilcilik::create([
            'country_code' => 'US', 'ad' => 'New York Başkonsolosluğu', 'slug' => 'new-york',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'New York', 'is_active' => true, 'sort_order' => 1,
        ]);

        IslemTuru::firstOrCreate(['slug' => 'vekaletname'], ['ad' => 'Vekaletname', 'is_active' => true, 'sort_order' => 0]);

        $this->dosya = 'database/data/test-icerik.json';
    }

    protected function tearDown(): void
    {
        @unlink(base_path($this->dosya));

        parent::tearDown();
    }

    private function dosyaYaz(array $veri): void
    {
        $yol = base_path($this->dosya);
        @mkdir(dirname($yol), 0777, true);
        file_put_contents($yol, json_encode($veri, JSON_UNESCAPED_UNICODE));
    }

    private function gecerliVeri(array $ust = [], array $satir = []): array
    {
        return array_merge([
            'onay_tarihi' => '2026-08-04',
            'icerikler' => [array_merge([
                'temsilcilik' => 'new-york',
                'islem' => 'vekaletname',
                'kaynak_url' => 'https://newyork-bk.mfa.gov.tr/Mission/ShowInfoNote/418263',
                'evraklar' => ['Kimlik kartı', 'İki adet fotoğraf'],
                'sure_metni' => '',
                'notlar' => '',
            ], $satir)],
        ], $ust);
    }

    public function test_abd_icin_kayit_olusturur(): void
    {
        // RehberAlmanyaSeeder yalnız Almanya'yı tohumlamıştı; ABD'de hiç kayıt
        // yok. Aktarma güncellemekle yetinemez, oluşturmak zorunda.
        $this->assertSame(0, TemsilcilikIslemi::count());

        $this->dosyaYaz($this->gecerliVeri());
        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertSuccessful();

        $kayit = TemsilcilikIslemi::first();
        $this->assertNotNull($kayit);
        $this->assertSame(['Kimlik kartı', 'İki adet fotoğraf'], $kayit->evraklar);
        $this->assertSame('2026-08-04', $kayit->dogrulanma_tarihi?->toDateString());
    }

    public function test_aktarma_yayinlamaz(): void
    {
        $this->dosyaYaz($this->gecerliVeri());
        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertSuccessful();

        // Kapı ayrı komutun işi; aktarma tek başına içeriği yayına sokamaz.
        $this->assertSame(TemsilcilikIslemi::STATUS_TASLAK, TemsilcilikIslemi::first()->status);
    }

    public function test_onay_tarihi_yoksa_aktarilmaz(): void
    {
        $veri = $this->gecerliVeri();
        unset($veri['onay_tarihi']);
        $this->dosyaYaz($veri);

        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertFailed();
        $this->assertSame(0, TemsilcilikIslemi::count());
    }

    public function test_evrak_listesi_bos_satir_atlanir(): void
    {
        $this->dosyaYaz($this->gecerliVeri(satir: ['evraklar' => []]));

        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertSuccessful();

        $this->assertSame(0, TemsilcilikIslemi::count());
    }

    public function test_rapor_secenegi_yazmaz(): void
    {
        $this->dosyaYaz($this->gecerliVeri());

        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya, '--rapor' => true])->assertSuccessful();

        $this->assertSame(0, TemsilcilikIslemi::count());
    }

    public function test_bilinmeyen_temsilcilik_atlanir(): void
    {
        $this->dosyaYaz($this->gecerliVeri(satir: ['temsilcilik' => 'olmayan-yer']));

        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertSuccessful();

        $this->assertSame(0, TemsilcilikIslemi::count());
    }
}

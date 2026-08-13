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

        /*
         * BEKLENTİ 2026-08-13'TE DEĞİŞTİ — eskisi hatayı sabitliyordu.
         *
         * JSON düz metin dizisi veriyor; bu test de düz metin bekliyordu ve
         * yeşildi. Oysa görünüm ve yönetim panelindeki Repeater ['ad','not']
         * şekli okuyor. Sonuç: /de/berlin/pasaport sayfasında "Gerekli
         * evraklar" SEKİZ BOŞ MADDE olarak basılıyordu — sayfa 200 dönüyordu,
         * yani hiçbir izleme göremezdi.
         *
         * Artık şekil modelde tek noktada düzenleniyor
         * (TemsilcilikIslemi::evraklariDuzenle); bu test onu doğruluyor.
         */
        $this->assertSame([
            ['ad' => 'Kimlik kartı', 'not' => null],
            ['ad' => 'İki adet fotoğraf', 'not' => null],
        ], $kayit->evraklar);
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

    public function test_uzun_sure_metni_notlara_tasinir(): void
    {
        // CANLIDA ÖĞRENİLDİ: ajanlar `sure_metni`ne paragraf yazdı. Kolon
        // string(200) ve arayüzde ROZET olarak basılıyor ("⏱ Süre: X").
        // MySQL 200'ü aşanı reddetti ve aktarım çöktü; SQLite dayatmadığı için
        // yerelde fark edilmemişti.
        $uzun = str_repeat('Bu sayfada net bir işlem süresi verilmiyor. ', 12);
        $this->assertGreaterThan(200, mb_strlen($uzun));

        $this->dosyaYaz($this->gecerliVeri(satir: ['sure_metni' => $uzun, 'notlar' => 'Randevu şart.']));

        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertSuccessful();

        $kayit = TemsilcilikIslemi::first();
        $this->assertNull($kayit->sure_metni, 'Uzun süre metni rozete yazılmamalı.');
        $this->assertStringContainsString('net bir işlem süresi', (string) $kayit->notlar);
        $this->assertStringContainsString('Randevu şart.', (string) $kayit->notlar, 'Mevcut notlar kaybolmamalı.');
    }

    public function test_kisa_sure_metni_korunur(): void
    {
        $this->dosyaYaz($this->gecerliVeri(satir: ['sure_metni' => '2-4 hafta']));

        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertSuccessful();

        $this->assertSame('2-4 hafta', TemsilcilikIslemi::first()->sure_metni);
    }

    public function test_bir_satir_patlarsa_hicbiri_yazilmaz(): void
    {
        // CANLIDA ÖĞRENİLDİ: aktarım satır satır yazıyordu ve üçüncü kayıtta
        // çöktü. İlk iki kayıt TASLAĞA çekilmiş ama yayınlanmamıştı — o ana
        // kadar YAYINDA olan sayfa 404 döndü. Yarım aktarım, hiç aktarımdan
        // kötüdür; artık tek işlem (transaction) içinde.
        IslemTuru::firstOrCreate(['slug' => 'pasaport'], ['ad' => 'Pasaport', 'is_active' => true, 'sort_order' => 1]);

        $veri = $this->gecerliVeri();
        $veri['icerikler'][] = [
            'temsilcilik' => 'new-york',
            'islem' => 'pasaport',
            // `resmi_kaynak_url` kolonu NOT NULL (K7: kaynak zorunlu). null
            // vermek kontrollü bir veritabanı hatası üretir — SQLite NOT NULL'ı
            // DAYATIR (uzunluğu dayatmadığı hâlde), yani bu hata yerelde de
            // gerçekleşir. Sınanan şey hatanın kendisi değil, geri sarma.
            'kaynak_url' => null,
            'evraklar' => ['x'],
            'sure_metni' => '',
            'notlar' => '',
        ];
        $this->dosyaYaz($veri);

        try {
            $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->run();
        } catch (\Throwable) {
            // Hatanın kendisi konu değil; konu geriye ne kaldığı.
        }

        $this->assertSame(0, TemsilcilikIslemi::count(), 'Patlayan aktarım hiçbir kayıt bırakmamalı.');
    }

    public function test_bilinmeyen_temsilcilik_atlanir(): void
    {
        $this->dosyaYaz($this->gecerliVeri(satir: ['temsilcilik' => 'olmayan-yer']));

        $this->artisan('rehber:icerik-aktar', ['dosya' => $this->dosya])->assertSuccessful();

        $this->assertSame(0, TemsilcilikIslemi::count());
    }
}

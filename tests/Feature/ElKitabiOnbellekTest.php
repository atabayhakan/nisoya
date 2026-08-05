<?php

namespace Tests\Feature;

use App\Services\Rehber\ElKitabiRehberi;
use App\Services\Rehber\RehberSayfasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * El Kitabı önbellek yolu — CANLIDA 500 VEREN YOL (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * HATA
 *
 *   TypeError: ElKitabiRehberi::tumSayfalar(): Return value must be of type
 *   Illuminate\Support\Collection, __PHP_Incomplete_Class returned
 *
 * Önbellekte SERİLEŞTİRİLMİŞ RehberSayfasi nesneleri duruyordu. Serileştirme
 * sınıfın o anki şekline bağlıdır; sınıf değişince eski satır çözülemez ve PHP
 * `__PHP_Incomplete_Class` döndürür. Yani önbellek, koddaki her değişikliğe
 * karşı kırılgan bir bağ kuruyordu.
 *
 * ---------------------------------------------------------------------------
 * NEDEN 35 TEST GEÇERKEN CANLI KIRILDI — asıl ders
 *
 * `tumSayfalar()` yerelde önbelleği ATLIYOR (`app()->isLocal()`), ki bu doğru
 * bir tercih: markdown'ı düzenleyip anında görmek gerekir. Ama sonuç şuydu:
 * ÖNBELLEK YOLU TESTLERDE HİÇ ÇALIŞMIYORDU. Kırılan yol, test edilmeyen yoldu.
 *
 * Bu dosya o dalı doğrudan zorlar: ortam "production" yapılır ki gerçek
 * yazma/okuma turu koşsun.
 */
class ElKitabiOnbellekTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // KRİTİK: local'de önbellek dalı atlanıyor. Bu testin varlık sebebi
        // o dalı koşturmak, dolayısıyla ortamı değiştirmek zorunlu.
        $this->app->detectEnvironment(fn () => 'production');
        Cache::flush();
    }

    public function test_onbellek_turu_ayni_tipi_dondurur(): void
    {
        $rehber = app(ElKitabiRehberi::class);

        // 1. çağrı: hesaplar ve önbelleğe yazar.
        $ilk = $rehber->tumSayfalar();
        $this->assertInstanceOf(Collection::class, $ilk);
        $this->assertGreaterThan(0, $ilk->count(), 'docs/rehber okunamadı.');

        // 2. çağrı: ÖNBELLEKTEN okur — canlıda tam burada patlıyordu.
        $ikinci = $rehber->tumSayfalar();
        $this->assertInstanceOf(Collection::class, $ikinci);
        $this->assertInstanceOf(RehberSayfasi::class, $ikinci->first());
        $this->assertSame($ilk->count(), $ikinci->count());
        $this->assertSame($ilk->first()->slug, $ikinci->first()->slug);
        $this->assertSame($ilk->first()->baslik, $ikinci->first()->baslik);
    }

    public function test_onbellege_nesne_degil_duz_dizi_yazilir(): void
    {
        // Asıl düzeltme bu: nesne serileştirmek sınıf değişince kırılır.
        app(ElKitabiRehberi::class)->tumSayfalar();

        $ham = Cache::get('rehber.sayfalar');

        $this->assertIsArray($ham, 'Önbelleğe dizi yazılmalı.');
        $this->assertIsArray($ham[0] ?? null, 'Satırlar da düz dizi olmalı.');
        $this->assertArrayHasKey('slug', $ham[0]);
    }

    public function test_bozuk_onbellek_sayfayi_dusurmez(): void
    {
        /*
         * Canlıdaki hatanın tam senaryosu: önbellekte beklenmedik bir şey var.
         * Artık sessizce yeniden hesaplanır — El Kitabı'nın açılmaması,
         * önbelleğin ıskalanmasından çok daha pahalı.
         */
        Cache::put('rehber.sayfalar', 'bu bir dizi degil', 600);

        $sayfalar = app(ElKitabiRehberi::class)->tumSayfalar();

        $this->assertInstanceOf(Collection::class, $sayfalar);
        $this->assertGreaterThan(0, $sayfalar->count());
    }

    public function test_eski_surumden_kalan_eksik_alan_patlatmaz(): void
    {
        // Alan eklenirse eski satırlar varsayılanla doldurulmalı, kırılmamalı.
        Cache::put('rehber.sayfalar', [['slug' => 'eski', 'baslik' => 'Eski sayfa']], 600);

        $sayfalar = app(ElKitabiRehberi::class)->tumSayfalar();

        $this->assertInstanceOf(RehberSayfasi::class, $sayfalar->first());
        $this->assertSame('eski', $sayfalar->first()->slug);
        $this->assertSame([], $sayfalar->first()->etiketler);
    }
}

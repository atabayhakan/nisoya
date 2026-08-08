<?php

namespace Tests\Feature;

use App\Models\OutreachTarget;
use App\Support\Growth\RegionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `growth:ulke-duzelt` komutunun davranışı. (2026-08-09)
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Komutun iki ayrı işi var ve bunlar bir kez TEK KOŞULA bağlanmıştı:
 *   1. ülkesi yanlış kayıtları düzelt
 *   2. ülkesi kanıtlanamayan kayıtların gönderim kapısını kapat
 *
 * "Değişecek kayıt yok → erken çık" satırı (1) için doğruydu ama (2)'yi de
 * atlıyordu: hiçbir ülkenin düzelmediği bir koşuda komut, gönderime açık
 * kanıtsız kayıtları KAPATMADAN çıkıyordu — üstelik ekrana "KAPATILIYOR"
 * yazdıktan sonra. Üretimde ilk koşuda fark edilmedi çünkü o an kanıtsız-açık
 * kayıt kalmamıştı; yani hata SESSİZDİ ve ancak ileride patlayacaktı.
 */
class GrowthUlkeDuzeltTest extends TestCase
{
    use RefreshDatabase;

    private function hedef(array $ozellikler = []): OutreachTarget
    {
        return OutreachTarget::query()->create(array_merge([
            'name' => 'Test İşletme',
            'country' => 'US',
            'city' => 'NJ 07110',
            'source' => 'test',
            'external_id' => 'test-'.uniqid(),
            'detection_band' => 'turkish',
            'marketing_status' => RegionPolicy::ALLOWED,
        ], $ozellikler));
    }

    public function test_kuru_kosu_hicbir_seyi_degistirmez(): void
    {
        $yanlis = $this->hedef(['country' => 'KZ', 'city' => '34876 Kartal/İstanbul']);

        $this->artisan('growth:ulke-duzelt')->assertSuccessful();

        $yanlis->refresh();
        $this->assertSame('KZ', $yanlis->country);
        $this->assertSame(RegionPolicy::ALLOWED, $yanlis->marketing_status);
    }

    public function test_uygula_yanlis_ulkeyi_duzeltir_ve_kapiyi_kapatir(): void
    {
        $yanlis = $this->hedef(['country' => 'KZ', 'city' => '34876 Kartal/İstanbul']);

        $this->artisan('growth:ulke-duzelt --uygula')->assertSuccessful();

        $yanlis->refresh();
        $this->assertSame('TR', $yanlis->country);
        $this->assertSame(RegionPolicy::BLOCKED, $yanlis->marketing_status);
    }

    public function test_kapanan_kaydin_iletisim_adresi_silinir(): void
    {
        // Engelli bölgede iletişim TOPLANMAMALIYDI; kayıt yanlışlıkla
        // "allowed" göründüğü için toplandı. Ülkeyi düzeltip adresi bırakmak
        // ihlali kalıcı yapardı.
        $yanlis = $this->hedef([
            'country' => 'UZ',
            'city' => '34413 Kağıthane/İstanbul',
            'contact_email' => 'info@ornek.com.tr',
        ]);

        $this->artisan('growth:ulke-duzelt --uygula')->assertSuccessful();

        $this->assertNull($yanlis->refresh()->contact_email);
    }

    public function test_ulkesi_dogru_olan_kaydin_adresi_silinmez(): void
    {
        // Silme YALNIZ kapanan yönde olmalı; zararsız düzelen kaydın verisi durur.
        $dogru = $this->hedef(['contact_email' => 'info@ornek.com']);

        $this->artisan('growth:ulke-duzelt --uygula')->assertSuccessful();

        $this->assertSame('info@ornek.com', $dogru->refresh()->contact_email);
    }

    /**
     * BU TESTİN VARLIK SEBEBİ OLAN KUSUR.
     *
     * Ülkesi değişecek HİÇBİR kayıt yokken de kanıtsız-açık kayıtlar
     * kapatılmalı. Kusurlu sürümde komut buraya hiç gelmeden çıkıyordu.
     */
    public function test_ulke_degisikligi_yokken_bile_kanitsiz_kayit_kapatilir(): void
    {
        // Ülkesi tespit edilebilen ve DOĞRU olan bir kayıt → değişecek liste boş kalır.
        $this->hedef(['country' => 'US', 'city' => 'NJ 07110']);

        // Ülkesi hiçbir kalıba uymayan ama gönderime AÇIK kayıt.
        $kanitsiz = $this->hedef(['country' => 'KZ', 'city' => 'Bilinmeyen Yer 12']);

        $this->artisan('growth:ulke-duzelt --uygula')->assertSuccessful();

        $kanitsiz->refresh();
        $this->assertSame(RegionPolicy::BLOCKED, $kanitsiz->marketing_status, 'Kanıtsız kayıt kapatılmadı.');
        // Ülke KASITLI olarak değişmez — uydurma ülke yazmayız.
        $this->assertSame('KZ', $kanitsiz->country);
    }

    public function test_kuru_kosu_kanitsiz_kaydi_da_kapatmaz(): void
    {
        $kanitsiz = $this->hedef(['country' => 'KZ', 'city' => 'Bilinmeyen Yer 12']);

        $this->artisan('growth:ulke-duzelt')->assertSuccessful();

        $this->assertSame(RegionPolicy::ALLOWED, $kanitsiz->refresh()->marketing_status);
    }

    public function test_ikinci_kosu_hicbir_sey_degistirmez(): void
    {
        // Idempotentlik: aynı komutu iki kez çalıştırmak zarar vermemeli.
        $this->hedef(['country' => 'KZ', 'city' => '34876 Kartal/İstanbul']);
        $this->hedef(['country' => 'KZ', 'city' => 'Bilinmeyen Yer 12']);

        $this->artisan('growth:ulke-duzelt --uygula')->assertSuccessful();

        $once = OutreachTarget::query()->orderBy('id')->get(['country', 'marketing_status'])->toArray();

        $this->artisan('growth:ulke-duzelt --uygula')->assertSuccessful();

        $sonra = OutreachTarget::query()->orderBy('id')->get(['country', 'marketing_status'])->toArray();
        $this->assertSame($once, $sonra);
    }
}

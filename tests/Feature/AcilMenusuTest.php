<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Support\AcilNumaralar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Acil yardım menüsü.
 *
 * ---------------------------------------------------------------------------
 * BU GÜVENLİK VERİSİ
 *
 * Buradaki numaralar gerçekten acil durumdaki birinin arayacağı numaralar.
 * En önemli test `test_aktif_her_ulkenin_acil_numarasi_var`: yeni bir ülke
 * açıldığında acil numarası UNUTULAMAZ, çünkü test kırmızıya döner. Aksi
 * hâlde o ülkedeki kullanıcı, acil durumda numara yerine boş bir kutu görür
 * ve bunu kimse fark etmez.
 */
class AcilMenusuTest extends TestCase
{
    use RefreshDatabase;

    private function acilKategorisi(): void
    {
        // Modal yalnız acil kategori VARSA basılır (bileşenin ön koşulu).
        // "Acil" kategoriler, slug'ı `acil-yardim` olan ana kategorinin
        // çocuklarıdır (bkz. Category::EMERGENCY_SLUG).
        $ana = Category::query()->create([
            'name' => 'Acil Yardım', 'slug' => Category::EMERGENCY_SLUG,
            'type' => 'hizmet', 'is_active' => true, 'sort_order' => 0,
        ]);

        Category::query()->create([
            'parent_id' => $ana->id, 'name' => 'Nöbetçi doktor', 'slug' => 'nobetci-doktor',
            'type' => 'hizmet', 'is_active' => true, 'sort_order' => 1,
        ]);

        Cache::forget(Category::EMERGENCY_CACHE_KEY);
    }

    public function test_aktif_her_ulkenin_acil_numarasi_var(): void
    {
        /*
         * ASIL BEKÇİ. Ülke listesi büyüdükçe (Körfez, Türk dünyası…) acil
         * numarası eklemeyi unutmak çok kolay; bu test onu imkânsız kılar.
         */
        $harita = AcilNumaralar::harita();
        $eksik = [];

        foreach (Country::query()->where('is_active', true)->pluck('code') as $kod) {
            if (! isset($harita[$kod]) || blank($harita[$kod]['genel'] ?? null)) {
                $eksik[] = $kod;
            }
        }

        $this->assertSame([], $eksik,
            'Bu ülkelerin acil numarası yok: '.implode(', ', $eksik).
            ' — config/acil.php dosyasına DOĞRULANMIŞ numara ekle.');
    }

    public function test_her_kayitta_dogrulama_tarihi_var(): void
    {
        // Eskiyen veriyi fark edebilmek için tarih zorunlu.
        foreach (config('acil.ulkeler') as $kod => $kayit) {
            $this->assertArrayHasKey('dogrulandi', $kayit, "{$kod}: dogrulandi tarihi yok");
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $kayit['dogrulandi'], "{$kod}: tarih biçimi bozuk");
        }
    }

    public function test_dogrulama_tarihi_istemciye_sizmaz(): void
    {
        // Bakım alanı; arayüze taşınırsa gereksiz veri ve gürültü olur.
        $this->assertStringNotContainsString('dogrulandi', json_encode(AcilNumaralar::harita()));
    }

    public function test_modal_uc_katmani_da_basar(): void
    {
        $this->acilKategorisi();

        $this->get('/')
            ->assertOk()
            ->assertSee('Acil servis')                       // katman 1
            ->assertSee('Konsolosluk')                       // katman 2
            ->assertSee('Türkçe konuşan yardım')             // katman 3
            ->assertSee('Konumumu kopyala');
    }

    public function test_konsolosluk_cagri_merkezi_aranabilir(): void
    {
        // Yurt dışındaki Türk için en değerli tek numara; tıklanınca aramalı.
        $this->acilKategorisi();

        $this->get('/')
            ->assertOk()
            ->assertSee('tel:+903122922929', false)
            ->assertSee('+90 312 292 29 29');
    }

    public function test_numara_haritasi_ve_rehberli_ulkeler_sayfaya_gecer(): void
    {
        // Alpine ülke değiştikçe numarayı bunlardan okur.
        $this->acilKategorisi();
        Country::query()->where('code', 'DE')->update(['is_active' => true]);

        $html = $this->get('/')->assertOk()->getContent();

        // `@js()` çıktısı `JSON.parse('{"DE":…}')` biçimindedir:
        // tırnaklar `&quot;` DEĞİL `"` olarak kaçırılır. (İlk yazışta
        // `&quot;` bekledim, test kırmızı verdi — gerçek çıktıya bakıldı.)
        $this->assertStringContainsString('\u0022DE\u0022', $html, 'Numara haritası sayfaya basılmamış');
        $this->assertStringContainsString('\u0022112\u0022', $html, 'Almanya numarası haritada yok');
        $this->assertStringContainsString('rehberliUlkeler', $html);
    }

    public function test_platform_sinirini_soyleyen_uyari_duruyor(): void
    {
        /*
         * Nisoya acil servis DEĞİL. Bu satır kaldırılırsa arayüz, olmadığı
         * bir şey gibi davranmaya başlar — kaldırılmasını engelliyoruz.
         */
        $this->acilKategorisi();

        $this->get('/')
            ->assertOk()
            ->assertSee('acil servis değildir');
    }
}

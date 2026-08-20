<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyGalleryImage;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * SEO denetim raporunun (2026-08-20) Kritik + Uyarı maddelerini mühürler.
 * Her test, denetimin kendi canlı doğrulamasıyla bulduğu somut bir hatayı
 * yeniden üretip düzeltmeyi kanıtlar — bkz. rapor: /code/artifact/2df0d9d8-…
 */
class SeoDenetimDuzeltmeleriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function ilanOlustur(array $overrides = []): Listing
    {
        $kategori = Category::query()->whereNotNull('parent_id')->firstOrFail();

        return Listing::factory()->create(array_merge([
            'category_id' => $kategori->id,
            'status' => 'aktif',
        ], $overrides));
    }

    // -------------------------------------------------- Kritik: JSON-LD null

    /**
     * Gerçek olay (canlıda ölçüldü): fiyatsız/ülkesiz bir "hizmet" ilanı
     * JSON-LD'de literal "offers": null / "areaServed": null basıyordu —
     * geçersiz şema, zengin sonuç uygunluğunu riske atıyordu.
     */
    public function test_hizmet_ilaninda_bos_alanlar_json_ld_disinda_kalir(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        $ilan = $this->ilanOlustur(['type' => 'hizmet', 'price' => null, 'country_code' => null]);

        $html = $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString('"offers": null', $html);
        $this->assertStringNotContainsString('"areaServed": null', $html);
        $this->assertStringContainsString('"@type": "Service"', $html);
    }

    public function test_emlak_ilaninda_fiyatsizsa_offers_json_ld_disinda_kalir(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        $ilan = $this->ilanOlustur(['type' => 'emlak', 'price' => null]);

        $html = $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString('"offers": null', $html);
        $this->assertStringContainsString('"@type": "RealEstateListing"', $html);
    }

    public function test_vasita_ilaninda_markasiz_ve_fiyatsizsa_null_sizmaz(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        $ilan = $this->ilanOlustur(['type' => 'vasita', 'price' => null]);

        $html = $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString('"offers": null', $html);
        $this->assertStringNotContainsString('"brand": null', $html);
    }

    /** Aynı hata klasik temada da vardı — aynı düzeltme orada da doğrulanır. */
    public function test_klasik_temada_da_null_sizmaz(): void
    {
        Settings::setMany(['gorunum.tema' => 'klasik']);
        $ilan = $this->ilanOlustur(['type' => 'hizmet', 'price' => null, 'country_code' => null]);

        $html = $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString('"offers": null', $html);
        $this->assertStringNotContainsString('"areaServed": null', $html);
    }

    // ------------------------------------------------ Uyarı: açıklama sürüklenmesi

    /**
     * Gerçek olay (canlıda ölçüldü): meta açıklama `seo.default_description`
     * okurken site geneli JSON-LD açıklaması `footer.aciklama` okuyordu —
     * paneldeki SEO açıklamasını güncellemek JSON-LD'yi sessizce eskide
     * bırakıyordu. İkisi artık AYNI ayardan besleniyor.
     */
    public function test_meta_aciklama_ve_json_ld_aciklamasi_ayni_ayardan_gelir(): void
    {
        Settings::setMany([
            'seo.default_description' => 'SEO-PANELİNDEN-AÇIKLAMA',
            'footer.aciklama' => 'FOOTER-METNİ-FARKLI',
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // footer.aciklama GÖRÜNÜR footer metni için hâlâ kullanılıyor — sayfada
        // bulunması beklenir. Ölçülen şey: JSON-LD onu ARTIK tekrar etmiyor.
        $this->assertStringContainsString('name="description" content="SEO-PANELİNDEN-AÇIKLAMA"', $html);
        $this->assertStringContainsString('"description": "SEO-PANELİNDEN-AÇIKLAMA"', $html);
        $this->assertStringContainsString('FOOTER-METNİ-FARKLI', $html, 'footer.aciklama görünür footer metninde kalmalı.');

        $jsonLdBaslangici = strpos($html, '"@type": "WebSite"');
        $jsonLdBitisi = strpos($html, '</script>', $jsonLdBaslangici);
        $websiteSemasi = substr($html, $jsonLdBaslangici, $jsonLdBitisi - $jsonLdBaslangici);

        $this->assertStringNotContainsString('FOOTER-METNİ-FARKLI', $websiteSemasi, 'WebSite şeması artık footer.aciklama okumamalı.');
    }

    // ---------------------------------------------- Uyarı: misafir canonical

    /**
     * Gerçek olay: giriş/kayıt sayfaları paylaşılan head-meta bileşenini hiç
     * kullanmıyordu — canonical etiketi bu sayfalarda yoktu.
     */
    public function test_giris_sayfasinda_canonical_etiketi_var(): void
    {
        $html = $this->get('/giris')->assertOk()->getContent();

        $this->assertStringContainsString('rel="canonical" href="'.url('/giris').'"', $html);
        $this->assertStringContainsString('<title>Giriş Yap — Nisoya</title>', $html);
    }

    public function test_vitrin_temasinda_da_giris_sayfasinda_canonical_var(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->get('/kayit')->assertOk()
            ->assertSee('rel="canonical" href="'.url('/kayit').'"', false);
    }

    // --------------------------------------------- Uyarı: sabit yol → route()

    /**
     * Gerçek olay: aynı layout dosyası aynı hedefler (giriş/kayıt/ilanlar/
     * nasıl-çalışır/çerez-tercihleri/ilan-ver) için hem route() hem sabit
     * yol kullanıyordu — route bir gün değişirse sabit yollar sessizce
     * kırılırdı. Kaynak artık tutarlı şekilde route() kullanıyor.
     */
    public function test_vitrin_layoutunda_sabit_yol_kalmadi(): void
    {
        $kaynak = file_get_contents(resource_path('views/vitrin/components/layouts/app.blade.php'));

        foreach (["url('/giris')", "url('/kayit')", "url('/ilanlar')", "url('/nasil-calisir')", "url('/panel/ilan/yeni')", '"/cerez-tercihleri"'] as $eski) {
            $this->assertStringNotContainsString($eski, $kaynak, "Sabit yol geri sızmış: {$eski}");
        }
    }

    public function test_footer_baglantilari_dogru_hedeflere_gider(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $html = $this->get('/')->assertOk()->getContent();

        foreach ([
            route('listings.index') => 'Tüm İlanlar',
            route('pages.how') => 'Nasıl Çalışır?',
            route('login') => 'Giriş Yap',
            route('register') => 'Kayıt Ol',
            route('pages.cookie-preferences') => 'Çerez Tercihleri',
        ] as $beklenenHref => $etiket) {
            $this->assertStringContainsString('href="'.$beklenenHref.'"', $html, "Bağlantı eksik/yanlış: {$etiket}");
        }
    }

    // --------------------------------------------- Uyarı: galeri alt metni

    /**
     * Gerçek olay: galeri alt metni yalnız opsiyonel başlık alanından
     * geliyordu — başlık boşsa gerçek bir fotoğraf boş alt metinle
     * yayınlanıyordu.
     */
    public function test_galeri_gorseli_basliksizsa_alt_metni_sirket_adina_duser(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Berlin Temizlik Ltd.', 'slug' => 'berlin-temizlik']);
        CompanyGalleryImage::query()->create([
            'company_id' => $company->id,
            'path_thumb' => 'sirket-galeri/ornek-thumb.jpg',
            'path_medium' => 'sirket-galeri/ornek-medium.jpg',
            'path_large' => 'sirket-galeri/ornek-large.jpg',
            'caption' => null,
            'sort_order' => 0,
        ]);

        $html = $this->get('/sirket/berlin-temizlik')->assertOk()->getContent();

        $this->assertStringContainsString('alt="Berlin Temizlik Ltd. galeri fotoğrafı"', $html);
        $this->assertStringNotContainsString('alt=""', $html);
    }

    // ---------------------------------------------- Uyarı: sondaki eğik çizgi

    /**
     * Laravel'in KENDİ test yardımcısı (`prepareUrlForRequest`) `get()`
     * çağrısında baştaki/sondaki eğik çizgiyi siliyor — yani `$this->get('/ilanlar/')`
     * middleware'e hiç ulaşmadan sessizce `/ilanlar` isteği yapar ve bu testi
     * anlamsız kılar. Ham bir `Request` ile TAM pipeline'dan (tüm middleware
     * dahil) geçirmek gerekiyor.
     */
    private function hamIstekYap(string $path): Response
    {
        return $this->app->handle(Request::create($path, 'GET'));
    }

    /**
     * Gerçek olay (canlıda ölçüldü): `/ilanlar/` ve `/ilanlar` ikisi de 200
     * dönüyordu, aralarında yönlendirme yoktu — tarama bütçesi ikisine
     * birden harcanıyordu.
     */
    public function test_sondaki_egik_cizgi_301_ile_kanonik_forma_yonlendirilir(): void
    {
        $yanit = $this->hamIstekYap('/ilanlar/');

        $this->assertSame(301, $yanit->getStatusCode());
        $this->assertSame(url('/ilanlar'), $yanit->headers->get('Location'));
    }

    public function test_sondaki_egik_cizgi_yonlendirmesi_sorgu_dizesini_korur(): void
    {
        $yanit = $this->hamIstekYap('/ilanlar/?sehir=Berlin');

        $this->assertSame(301, $yanit->getStatusCode());
        $this->assertSame(url('/ilanlar').'?sehir=Berlin', $yanit->headers->get('Location'));
    }

    public function test_kok_adres_yonlendirilmez(): void
    {
        $this->assertSame(200, $this->hamIstekYap('/')->getStatusCode());
    }

    public function test_sondaki_egik_cizgisiz_adres_normal_calisir(): void
    {
        $this->assertSame(200, $this->hamIstekYap('/ilanlar')->getStatusCode());
    }
}

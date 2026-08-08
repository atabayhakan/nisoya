<?php

namespace Tests\Feature;

use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Hero Yöneticisi ana sayfaya GERÇEKTEN etki ediyor mu? (2026-08-06)
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Sahip "Hero Yöneticisi'nde değişiklik yapıyorum, ana sayfada değişmiyor"
 * dedi ve bire bir haklıydı. Sebep iki AYRI ANAHTAR UZAYIydı:
 *
 *   Hero Yöneticisi YAZAR : hero.rozet, hero.baslik, hero.vurgu, hero.alt_baslik
 *   Klasik ana sayfa OKUR : home.hero_badge, home.hero_satir1, home.hero_vurgu…
 *
 * Arada `App\Support\Hero` vardı ve o ikisini zaten köprülüyordu
 * (`hero.baslik ?: home.hero_satir1`) — ama Hero'yu YALNIZCA Vitrin hero'su
 * kullanıyordu. Klasik ana sayfa ayarları doğrudan okuyordu, yani yönetici
 * ekranı kaydediyor ama hiçbir yere bağlanmıyordu: ekran vardı, kablo yoktu.
 *
 * Bu dosya kabloyu mühürler. Sessiz kopması mümkün bir bağ — kopunca hata
 * vermez, sadece "değişmiyor".
 */
class HeroYoneticisiEtkiTest extends TestCase
{
    use RefreshDatabase;

    public static function temalar(): array
    {
        return ['klasik' => ['klasik'], 'vitrin' => ['vitrin']];
    }

    private function temayiSec(string $tema): void
    {
        Settings::setMany(['gorunum.tema' => $tema]);
        Cache::flush();
        $this->assertSame($tema === 'vitrin', Tema::vitrinMi());
    }

    #[DataProvider('temalar')]
    public function test_hero_yoneticisinin_metinleri_ana_sayfada_gorunur(string $tema): void
    {
        $this->temayiSec($tema);

        Settings::setMany([
            'hero.rozet' => 'ROZET-YONETICIDEN',
            'hero.baslik' => 'BASLIK-YONETICIDEN',
            'hero.vurgu' => 'VURGU-YONETICIDEN',
            'hero.alt_baslik' => 'ALTBASLIK-YONETICIDEN',
        ]);
        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('ROZET-YONETICIDEN')
            ->assertSee('BASLIK-YONETICIDEN')
            ->assertSee('VURGU-YONETICIDEN')
            ->assertSee('ALTBASLIK-YONETICIDEN');
    }

    #[DataProvider('temalar')]
    public function test_yonetici_bos_birakilinca_klasik_metinlere_duser(string $tema): void
    {
        /*
         * GERİYE DÖNÜK UYUM — bu testin varlık sebebi düzeltmenin kimseyi
         * kırmamasını kanıtlamak. Hero Yöneticisi'ni hiç açmamış bir sitede
         * `hero.*` boştur; sayfa eskisi gibi İçerik (Metinler) sayfasındaki
         * `home.*` metinlerini basmalı.
         */
        $this->temayiSec($tema);

        Settings::setMany([
            'hero.rozet' => '',
            'hero.baslik' => '',
            'hero.vurgu' => '',
            'hero.alt_baslik' => '',
            'home.hero_badge' => 'ROZET-ICERIKTEN',
            'home.hero_satir1' => 'BASLIK-ICERIKTEN',
            'home.hero_vurgu' => 'VURGU-ICERIKTEN',
            'home.hero_aciklama' => 'ALTBASLIK-ICERIKTEN',
        ]);
        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('ROZET-ICERIKTEN')
            ->assertSee('BASLIK-ICERIKTEN')
            ->assertSee('VURGU-ICERIKTEN')
            ->assertSee('ALTBASLIK-ICERIKTEN');
    }

    #[DataProvider('temalar')]
    public function test_yonetici_icerigi_ezer(string $tema): void
    {
        // İkisi de doluysa yönetici kazanır — aksi hâlde "değişmiyor"
        // şikâyeti başka bir kılıkta geri gelirdi.
        $this->temayiSec($tema);

        Settings::setMany([
            'hero.baslik' => 'YONETICI-KAZANIR',
            'home.hero_satir1' => 'ICERIK-KAYBEDER',
        ]);
        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('YONETICI-KAZANIR')
            ->assertDontSee('ICERIK-KAYBEDER');
    }

    // =================================================================
    // MEDYA KABLOSU (2026-08-08)
    //
    // Yukarıdaki testler METİN anahtarlarını mühürledi. Ama aynı ekranın
    // MEDYA yarısı bağlanmadan kaldı: sahip panelden arka plan görseli
    // yükleyip kırpıyor, karartma ayarlıyor, odak seçiyor — klasik temada
    // hiçbiri işe yaramıyordu. `App\Support\Hero` 16 metot sunarken klasik
    // hero 4'ünü okuyordu.
    //
    // Yani aynı hatanın yarısı düzeltilmiş, yarısı bırakılmıştı. Bu blok
    // öbür yarıyı mühürler ve İKİ TEMADA BİRDEN koşar — medya sözleşmesi
    // temaya göre ayrışırsa panelin ne yapacağı temaya bağlı olurdu.
    // =================================================================

    /**
     * Sayfanın YALNIZCA hero bölümü.
     *
     * Belgenin başından kesmek yanlış sonuç verir: `<head>` ve site başlığı da
     * `text-stone-900` gibi sınıflar taşıyor ve "hero'da koyu metin var" diye
     * okunur. İlk `<section` ile onu kapatan `</section>` arası kesilir.
     */
    private function heroBolumu(string $govde): string
    {
        $bas = (int) strpos($govde, '<section');
        $son = (int) strpos($govde, '</section>', $bas);

        return substr($govde, $bas, $son - $bas);
    }

    /** @param array<string, string> $ek */
    private function medyayiAyarla(array $ek = []): void
    {
        Settings::setMany(array_merge([
            'hero.arkaplan_tipi' => 'gorsel',
            'hero.gorsel_masaustu' => 'https://ornek.test/hero-masaustu.jpg',
        ], $ek));
        Cache::flush();
    }

    #[DataProvider('temalar')]
    public function test_arka_plan_gorseli_ana_sayfada_basilir(string $tema): void
    {
        $this->temayiSec($tema);
        $this->medyayiAyarla();

        $this->get('/')
            ->assertOk()
            ->assertSee('https://ornek.test/hero-masaustu.jpg', false);
    }

    #[DataProvider('temalar')]
    public function test_odak_noktasi_object_position_olarak_cikar(string $tema): void
    {
        // 9 noktalı odak seçici panelde yıllardır duruyordu; klasikte hiçbir
        // şeye dönüşmüyordu. Kırpma kararının tek görünür izi bu CSS.
        $this->temayiSec($tema);
        $this->medyayiAyarla(['hero.odak' => 'sag-alt']);

        $this->get('/')
            ->assertOk()
            ->assertSee('object-position: right bottom', false);
    }

    #[DataProvider('temalar')]
    public function test_karartma_yuzdesi_opacity_olarak_cikar(string $tema): void
    {
        $this->temayiSec($tema);
        $this->medyayiAyarla(['hero.overlay' => '40']);

        $this->get('/')
            ->assertOk()
            ->assertSee('opacity: 0.4', false);
    }

    #[DataProvider('temalar')]
    public function test_mobil_gorsel_ayri_source_olarak_basilir(string $tema): void
    {
        // Mobil görsel masaüstünden FARKLIYSA ayrı `<source>` gerekir; aynıysa
        // gereksiz bir istek olurdu.
        $this->temayiSec($tema);
        $this->medyayiAyarla(['hero.gorsel_mobil' => 'https://ornek.test/hero-mobil.jpg']);

        $this->get('/')
            ->assertOk()
            ->assertSee('media="(max-width: 639px)"', false)
            ->assertSee('https://ornek.test/hero-mobil.jpg', false);
    }

    #[DataProvider('temalar')]
    public function test_video_tipi_secilince_video_etiketi_basilir(string $tema): void
    {
        $this->temayiSec($tema);
        Settings::setMany([
            'hero.arkaplan_tipi' => 'video',
            'hero.video_url' => 'https://ornek.test/hero.mp4',
        ]);
        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('https://ornek.test/hero.mp4', false);
    }

    #[DataProvider('temalar')]
    public function test_medya_yokken_hicbir_arka_plan_medyasi_basilmaz(string $tema): void
    {
        /*
         * GERİYE DÖNÜK UYUM — bu blokun en önemli testi.
         *
         * Site canlı ve `hero.arkaplan_tipi` varsayılanı 'yok'. Kablo
         * bağlanırken sayfanın görünümü değişmemeliydi; değişseydi kimsenin
         * istemediği bir tasarım değişikliği sessizce yayına girerdi.
         */
        $this->temayiSec($tema);
        Settings::setMany(['hero.arkaplan_tipi' => 'yok']);
        Cache::flush();

        $hero = $this->heroBolumu((string) $this->get('/')->assertOk()->getContent());

        $this->assertStringNotContainsString('<video', $hero);
        $this->assertStringNotContainsString('object-position:', $hero);
    }

    public function test_klasik_hero_metni_medya_uzerinde_acik_renge_doner(): void
    {
        /*
         * Karartılmış bir görselin üstünde `text-stone-900` okunmaz. Klasik
         * hero'nun tek düzeni var ve metin her zaman ortada — yani medya
         * varsa metin HER ZAMAN onun üstünde.
         *
         * Vitrin'de bu koşul farklı (`sahne && medya`) çünkü orada metin
         * "bento" düzeninde medyanın yanında durur; o yüzden bu test yalnız
         * klasik için koşuyor.
         */
        $this->temayiSec('klasik');
        $this->medyayiAyarla();

        $hero = $this->heroBolumu((string) $this->get('/')->assertOk()->getContent());

        /*
         * İDDİA DAR TUTULUYOR — çıplak `text-stone-900` aramak yanlış alarm
         * verir: arama formunun düğmesinde `dark:text-stone-900` geçiyor ve
         * alt-dize olarak eşleşir. Ölçülen şey hero'nun KENDİ metin sınıfları:
         * açık-tema çiftleri gitmiş, koyu-zemin karşılıkları gelmiş olmalı.
         */
        foreach (['bg-white/15 text-white', 'text-emerald-300', 'text-white/80', 'text-white/70'] as $beklenen) {
            $this->assertStringContainsString($beklenen, $hero, "Medya varken hero metni açık renge dönmeli: {$beklenen}");
        }

        foreach (['bg-emerald-100 text-emerald-700', 'text-stone-600 dark:text-stone-300'] as $olmamali) {
            $this->assertStringNotContainsString($olmamali, $hero, "Açık-tema metin sınıfı medya üstünde kalmamalı: {$olmamali}");
        }
    }
}

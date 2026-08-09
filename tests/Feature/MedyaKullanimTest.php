<?php

namespace Tests\Feature;

use App\Models\HomeHighlight;
use App\Services\Medya\MedyaDeposu;
use App\Services\Medya\MedyaKullanim;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * "Bu dosya nerede kullanılıyor?" (2026-08-09, D adımı)
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § D
 *
 * TASARIMDAN SAPMA — BİLİNÇLİ: tasarım bir BAĞ TABLOSU öngörüyordu ve kendi
 * kusurunu da yazıyordu (yalnız yeni sistemden geçenleri görür). Ters tarama
 * o kör noktayı baştan yok ediyor: referansın gerçekten yaşadığı yerlerde
 * arıyor, migration ve geriye dönük doldurma gerekmiyor.
 *
 * Yine de "güvenle silinebilir" DEMEZ — Blade'e elle yazılmış bir yolu göremez.
 * Bu testler o sınırı da mühürlüyor.
 */
class MedyaKullanimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function gorsel(): UploadedFile
    {
        $img = (new ImageManager(new Driver))->createImage(1400, 900)->fill('#556677');
        $g = tempnam(sys_get_temp_dir(), 'kul').'.jpg';
        file_put_contents($g, (string) $img->encode(new JpegEncoder(quality: 88)));

        return new UploadedFile($g, 'k.jpg', 'image/jpeg', null, true);
    }

    public function test_hero_gorseli_olarak_kullanilan_dosya_bulunur(): void
    {
        $r = app(MedyaDeposu::class)->al($this->gorsel(), 'hero_masaustu');
        Settings::setMany(['hero.gorsel_masaustu' => $r->yol]);
        Cache::flush();

        $yerler = app(MedyaKullanim::class)->nerede($r->yol);

        $this->assertContains('Hero — masaüstü görseli', $yerler);
        $this->assertTrue(app(MedyaKullanim::class)->kullaniliyorMu($r->yol));
    }

    public function test_og_gorseli_olarak_kullanilan_dosya_bulunur(): void
    {
        $r = app(MedyaDeposu::class)->al($this->gorsel(), 'seo_og');
        Settings::setMany(['seo.og_image' => $r->yol]);
        Cache::flush();

        $this->assertContains('SEO — paylaşım görseli', app(MedyaKullanim::class)->nerede($r->yol));
    }

    public function test_vurgu_kartinda_kullanilan_dosya_bulunur(): void
    {
        if (! Schema::hasTable('home_highlights')) {
            $this->markTestSkipped('home_highlights tablosu yok.');
        }

        $r = app(MedyaDeposu::class)->al($this->gorsel(), 'vurgu_buyuk');

        // MODEL üzerinden — ham DB::insert timestamp/cast'leri atlıyor ve
        // satır sessizce eksik yazılabiliyor (ilk yazımda tam bu oldu).
        HomeHighlight::query()->create([
            'slot' => 'big',
            'title' => 'Test kartı',
            'sort_order' => 1,
            'is_active' => true,
            'media' => [['type' => 'image', 'path' => $r->yol]],
        ]);

        $yerler = app(MedyaKullanim::class)->nerede($r->yol);

        $this->assertNotEmpty($yerler, 'Vurgu kartındaki kullanım bulunamadı.');
        $this->assertStringContainsString('Vurgu kartı', $yerler[0]);
    }

    public function test_kullanilmayan_dosya_bos_liste_dondurur(): void
    {
        $r = app(MedyaDeposu::class)->al($this->gorsel(), 'sayfa_icerik');

        $this->assertSame([], app(MedyaKullanim::class)->nerede($r->yol));
        $this->assertFalse(app(MedyaKullanim::class)->kullaniliyorMu($r->yol));
    }

    public function test_bos_yol_hicbir_seyle_eslesmez(): void
    {
        // Boş dize `str_contains` ile HER metinde bulunur — kontrol edilmezse
        // kütüphanedeki her dosya "her yerde kullanılıyor" görünürdü.
        Settings::setMany(['hero.gorsel_masaustu' => 'medya/hero_masaustu/x.webp']);
        Cache::flush();

        $this->assertSame([], app(MedyaKullanim::class)->nerede(''));
        $this->assertSame([], app(MedyaKullanim::class)->nerede('/'));
    }

    public function test_turev_olan_ve_olmayan_dosya_ayirt_edilir(): void
    {
        // Türevin ana kopyası saklı — silinse bile yeniden üretilebilir.
        $r = app(MedyaDeposu::class)->al($this->gorsel(), 'sayfa_icerik');

        $this->assertTrue(app(MedyaKullanim::class)->turevMi($r->yol));
        $this->assertFalse(app(MedyaKullanim::class)->turevMi('hero/elle-yuklenmis.jpg'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\Medya\HeroMedyaBaglayici;
use App\Support\Hero;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * Hero yüklemesi boru hattından geçiyor mu? (2026-08-09)
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § B adımı
 *
 * ÖNCESİ: 4469×2979 / 402 KB'lik dosya olduğu gibi servis ediliyordu; mobil
 * için ayrı dosya elle yükleniyordu; karartma elle tahmin ediliyordu.
 *
 * Bu dosya üçünü de mühürler. Sessizce kopabilir bir bağ — koptuğunda hata
 * vermez, yalnız ham dosya servis edilmeye geri döner.
 */
class HeroMedyaBaglayiciTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    /** Filament'in yaptığı gibi: ham dosyayı public diske koy, yolunu ayara yaz. */
    private function hamYukle(string $ayar, int $en, int $boy, string $renk = '#8a8a8a'): string
    {
        $img = (new ImageManager(new Driver))->createImage($en, $boy)->fill($renk);
        $yol = 'hero/'.uniqid().'.jpg';
        Storage::disk('public')->put($yol, (string) $img->encode(new JpegEncoder(quality: 92)));

        Settings::setMany([$ayar => $yol, 'hero.arkaplan_tipi' => 'gorsel']);
        Cache::flush();

        return $yol;
    }

    public function test_ham_yukleme_slot_boyutuna_indirgenir(): void
    {
        $hamYol = $this->hamYukle('hero.gorsel_masaustu', 4469, 2979);

        app(HeroMedyaBaglayici::class)->isle();
        Cache::flush();

        $yeni = Settings::get('hero.gorsel_masaustu');

        $this->assertStringStartsWith('medya/hero_masaustu/', $yeni, 'Ayar türevi göstermeli.');
        $this->assertStringEndsWith('.webp', $yeni);

        // HAM DOSYA SİLİNİR: hiçbir yerde gösterilmeyen 402 KB'lik dosya
        // indirilebilir kalmamalı.
        Storage::disk('public')->assertMissing($hamYol);
    }

    public function test_mobil_ayni_gorselden_otomatik_turetilir(): void
    {
        // Bugün bunun için ayrı dosya yükleniyordu.
        $this->hamYukle('hero.gorsel_masaustu', 3000, 1500);

        app(HeroMedyaBaglayici::class)->isle();
        Cache::flush();

        $mobil = Settings::get('hero.gorsel_mobil');

        $this->assertStringStartsWith('medya/hero_mobil/', (string) $mobil);
        $this->assertSame(1, MediaAsset::query()->count(), 'Mobil AYNI ana kopyadan türemeli.');
    }

    public function test_ayri_mobil_yuklenirse_o_kazanir(): void
    {
        $this->hamYukle('hero.gorsel_masaustu', 3000, 1500);
        $this->hamYukle('hero.gorsel_mobil', 1080, 1620, '#222222');

        app(HeroMedyaBaglayici::class)->isle();
        Cache::flush();

        // İki AYRI ana kopya olmalı: mobil türetilmedi, yüklenen kullanıldı.
        $this->assertSame(2, MediaAsset::query()->count());
        $this->assertStringStartsWith('medya/hero_mobil/', (string) Settings::get('hero.gorsel_mobil'));
    }

    public function test_karartma_olculerek_yazilir_elle_girilmez(): void
    {
        // Sahibin girdiği saçma bir değer ölçümle EZİLMELİ — karartma artık
        // bir ayar değil, ölçümün sonucu.
        Settings::setMany(['hero.overlay' => '3']);
        $this->hamYukle('hero.gorsel_masaustu', 2400, 1200, '#f2f2f2');   // çok parlak

        app(HeroMedyaBaglayici::class)->isle();
        Cache::flush();

        $this->assertGreaterThan(3, (int) Settings::get('hero.overlay'));
        $this->assertTrue(Hero::metinPaneli(), 'Parlak görselde okunabilirlik paneli açılmalı.');
    }

    public function test_koyu_gorselde_panel_acilmaz(): void
    {
        $this->hamYukle('hero.gorsel_masaustu', 2400, 1200, '#0f0f12');

        app(HeroMedyaBaglayici::class)->isle();
        Cache::flush();

        $this->assertFalse(Hero::metinPaneli());
        $this->assertLessThan(20, (int) Settings::get('hero.overlay'));
    }

    public function test_ikinci_kayit_dosyayi_yeniden_islemez(): void
    {
        // Kaydet düğmesine ikinci kez basmak yeni türev üretmemeli.
        $this->hamYukle('hero.gorsel_masaustu', 2400, 1200);

        app(HeroMedyaBaglayici::class)->isle();
        Cache::flush();
        $ilk = Settings::get('hero.gorsel_masaustu');

        app(HeroMedyaBaglayici::class)->isle();
        Cache::flush();

        $this->assertSame($ilk, Settings::get('hero.gorsel_masaustu'));
        $this->assertSame(1, MediaAsset::query()->count());
    }

    public function test_arkaplan_gorsel_degilse_hicbir_sey_yapilmaz(): void
    {
        Settings::setMany(['hero.arkaplan_tipi' => 'yok', 'hero.gorsel_masaustu' => 'hero/ham.jpg']);
        Cache::flush();

        $this->assertSame([], app(HeroMedyaBaglayici::class)->isle());
        $this->assertSame(0, MediaAsset::query()->count());
    }
}

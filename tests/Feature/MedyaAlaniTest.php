<?php

namespace Tests\Feature;

use App\Filament\Support\MedyaAlani;
use App\Models\MediaAsset;
use App\Models\MediaRendition;
use App\Services\Medya\MedyaDeposu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * Paneldeki TÜM yükleme noktaları boru hattından geçiyor mu? (2026-08-09, C adımı)
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § C
 *
 * ÖNCESİ: beş ayrı yükleme noktası vardı ve hepsi ham dosyayı public diske
 * yazıyordu. Boyut/ağırlık yalnız yardım metninde yazılıydı — kod okumuyordu.
 * Hero'da bedeli ölçüldü: 4469×2979 / 402 KB dosya itirazsız geçti.
 */
class MedyaAlaniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function gorsel(int $en, int $boy): UploadedFile
    {
        $img = (new ImageManager(new Driver))->createImage($en, $boy)->fill('#4477aa');
        $gecici = tempnam(sys_get_temp_dir(), 'alan').'.jpg';
        file_put_contents($gecici, (string) $img->encode(new JpegEncoder(quality: 90)));

        return new UploadedFile($gecici, 'test.jpg', 'image/jpeg', null, true);
    }

    /** Alan tanımlı mı ve slotu doğru mu — kablo koptuğunda sessizce eski davranışa dönerdi. */
    public function test_panel_alanlari_boru_hattina_bagli(): void
    {
        foreach ([
            ['og_image', 'seo_og'],
            ['path', 'vurgu_buyuk'],
            ['image', 'sayfa_icerik'],
            ['logo', 'marka_logo'],
        ] as [$ad, $slot]) {
            $alan = MedyaAlani::make($ad, $slot);

            $this->assertSame($ad, $alan->getName());
            $this->assertIsArray(config("media_slots.{$slot}"), "Slot tanımsız: {$slot}");
        }
    }

    public function test_slot_boyutu_her_yukleme_noktasinda_uygulanir(): void
    {
        $depo = app(MedyaDeposu::class);

        // OG: 1200×630 kapla
        $og = $depo->al($this->gorsel(3000, 3000), 'seo_og');
        $this->assertSame(1200, $og->en);
        $this->assertSame(630, $og->boy);

        // Vurgu kartı: 1200×800 kapla
        $vurgu = $depo->al($this->gorsel(4000, 1000), 'vurgu_buyuk');
        $this->assertSame(1200, $vurgu->en);
        $this->assertSame(800, $vurgu->boy);

        // Sayfa içeriği: sığdır — KIRPILMAZ, oran korunur
        $sayfa = $depo->al($this->gorsel(3200, 1000), 'sayfa_icerik');
        $this->assertSame(1600, $sayfa->en);
        $this->assertSame(500, $sayfa->boy, 'Sığdır kipi oranı korumalı (3200x1000 → 1600x500).');
    }

    public function test_hepsi_webp_ve_hedef_agirligin_altinda(): void
    {
        foreach (['seo_og', 'vurgu_buyuk', 'vurgu_kucuk', 'sayfa_icerik'] as $slot) {
            $r = app(MedyaDeposu::class)->al($this->gorsel(2600, 1800), $slot);

            $this->assertSame('webp', $r->bicim, "{$slot} WebP olmalı.");
            $this->assertTrue($r->hedefiTutuyorMu(), "{$slot} ağırlık hedefini aşıyor: {$r->bayt}");
        }
    }

    public function test_ana_kopya_hicbir_slotta_public_diske_yazilmaz(): void
    {
        // Tasarımın temel kuralı, her yükleme noktası için ayrı ayrı geçerli.
        foreach (['seo_og', 'marka_logo', 'sayfa_icerik'] as $slot) {
            $r = app(MedyaDeposu::class)->al($this->gorsel(1400, 900), $slot);

            Storage::disk('public')->assertMissing($r->asset->yol);
            Storage::disk('local')->assertExists($r->asset->yol);
            Storage::disk('public')->assertExists($r->yol);
        }
    }

    public function test_ayni_gorsel_farkli_slotlarda_tek_ana_kopya(): void
    {
        // Aynı logoyu hem markaya hem OG'ye koyabilirsin; dosya iki kez saklanmaz.
        $depo = app(MedyaDeposu::class);
        $a = $depo->al($this->gorsel(1500, 1500), 'marka_logo');
        $b = $depo->al($this->gorsel(1500, 1500), 'seo_og');

        $this->assertSame($a->media_asset_id, $b->media_asset_id);
        $this->assertSame(1, MediaAsset::query()->count());
        $this->assertSame(2, MediaRendition::query()->count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\Medya\MedyaDeposu;
use App\Services\Medya\MedyaTuretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * Medya boru hattı — A adımı. (2026-08-09)
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Sahip hero'ya 4469×2979 / 402 KB bir görsel yükledi; slot 1265×603 ve hiçbir
 * şey itiraz etmedi. Sebep: "2400×1200 önerilir" bir YARDIM METNİYDİ, kod onu
 * okumuyordu. Bu dosya, boyutun artık koda yazıldığını mühürler.
 *
 * Tasarımda "sessizce bozulabilecek" diye işaretlenenler burada test edilir:
 * slot uygulanması, mobilin doğru orandan türetilmesi, hash tekilleştirmesi,
 * ana kopyanın herkese açık diske YAZILMAMASI, büyütme yapılmaması.
 */
class MedyaBoruHattiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    /** Belirtilen boyutta gerçek bir JPEG üretir (sahte dosya değil — görsel işleniyor). */
    private function gorsel(int $en, int $boy, string $ad = 'test.jpg'): UploadedFile
    {
        // createImage() — bu sürümde tuval üreten metot bu (bkz. DemoGorselUretici:180).
        $img = (new ImageManager(new Driver))->createImage($en, $boy)->fill('#3366aa');
        $gecici = tempnam(sys_get_temp_dir(), 'medya').'.jpg';
        // encode(new JpegEncoder) — depodaki desen (bkz. DemoGorselUretici:170).
        file_put_contents($gecici, (string) $img->encode(new JpegEncoder(quality: 85)));

        return new UploadedFile($gecici, $ad, 'image/jpeg', null, true);
    }

    public function test_slot_boyutu_gercekten_uygulanir(): void
    {
        // Bugünkü hatanın bire bir hâli: slottan çok büyük, yanlış oranlı görsel.
        $rendition = app(MedyaDeposu::class)->al($this->gorsel(4469, 2979), 'hero_masaustu');

        $this->assertSame(2400, $rendition->en);
        $this->assertSame(1200, $rendition->boy);
        $this->assertSame('webp', $rendition->bicim);
    }

    public function test_agirlik_hedefi_tutturulur(): void
    {
        $rendition = app(MedyaDeposu::class)->al($this->gorsel(4000, 2000), 'hero_masaustu');

        $this->assertTrue($rendition->hedefiTutuyorMu(), 'Ağırlık hedefi tutmadı: '.$rendition->bayt);
        $this->assertLessThanOrEqual(250 * 1024, $rendition->bayt);
    }

    public function test_mobil_ayni_ana_kopyadan_dikey_turetilir(): void
    {
        // Tek yükleme + akıllı kırpma kararının kanıtı: YATAY bir ana kopyadan
        // DİKEY mobil kare çıkar. Bugün bunun için ayrı dosya yükleniyordu.
        $depo = app(MedyaDeposu::class);
        $masaustu = $depo->al($this->gorsel(3000, 1500), 'hero_masaustu');

        $mobil = app(MedyaTuretici::class)->turet($masaustu->asset, 'hero_mobil');

        /*
         * ORAN her zaman doğru, BOYUT kaynağa bağlı.
         *
         * 3000×1500 yatay bir ana kopya, 1080×1620 dikey kutuyu BÜYÜTMEDEN
         * dolduramaz (yeterince uzun değil). Doğru davranış: hedef ORANI koru,
         * sığan en büyük kareyi al. İlk yazımda bunun yerine 1080×1500
         * üretiliyordu — yani slot 2:3 isterken türev 1:1.39 oluyordu ve mobil
         * hero yanlış oranda basılırdı.
         */
        $this->assertEqualsWithDelta(1080 / 1620, $mobil->en / $mobil->boy, 0.01, 'Mobil türev slotun oranını taşımalı.');
        $this->assertGreaterThan($mobil->en, $mobil->boy, 'Mobil türev dikey olmalı.');
        $this->assertLessThanOrEqual(1080, $mobil->en, 'Büyütme yapılmamalı.');
    }

    public function test_ana_kopya_herkese_acik_diske_yazilmaz(): void
    {
        /*
         * TASARIMIN TEMEL KURALI. Ana kopya public diskte dursaydı adresi
         * tahmin edilebilir olurdu; hiçbir yerde gösterilmeyen 4469×2979'luk
         * ham dosya yine de indirilebilirdi.
         */
        $rendition = app(MedyaDeposu::class)->al($this->gorsel(1200, 800), 'vurgu_buyuk');
        $asset = $rendition->asset;

        Storage::disk('local')->assertExists($asset->yol);
        Storage::disk('public')->assertMissing($asset->yol);

        // Türev ise public'te OLMALI — yüzeylerin gösterdiği şey odur.
        Storage::disk('public')->assertExists($rendition->yol);
    }

    public function test_ayni_dosya_iki_kez_yuklenirse_tek_ana_kopya_olur(): void
    {
        $depo = app(MedyaDeposu::class);

        $bir = $depo->al($this->gorsel(1200, 800, 'a.jpg'), 'vurgu_buyuk');
        $iki = $depo->al($this->gorsel(1200, 800, 'b.jpg'), 'vurgu_kucuk');

        $this->assertSame($bir->media_asset_id, $iki->media_asset_id);
        $this->assertSame(1, MediaAsset::query()->count());
    }

    public function test_kucuk_gorsel_buyutulmez(): void
    {
        // scaleDown deseni: şişirmek bulanıklığı gizlemez, yalnız dosyayı büyütür.
        $rendition = app(MedyaDeposu::class)->al($this->gorsel(600, 300), 'hero_masaustu');

        $this->assertLessThanOrEqual(600, $rendition->en);
        $this->assertTrue($rendition->asset->slotIcinKucukMu('hero_masaustu'));
    }

    public function test_sigdir_kipi_kirpmaz(): void
    {
        // Logo kırpılırsa marka bozulur; oran korunmalı.
        $rendition = app(MedyaDeposu::class)->al($this->gorsel(1200, 400), 'marka_logo');

        $this->assertSame(600, $rendition->en);
        $this->assertSame(200, $rendition->boy, 'Sığdır kipi oranı korumalı (1200x400 → 600x200).');
    }

    public function test_odak_degisince_turevler_yeniden_uretilir(): void
    {
        /*
         * "Ekran var, kablo yok" hatasının önlenmesi: odak yalnız kayıtta
         * değişip türev eski kalsaydı, panelde sürüklenen nokta hiçbir şey
         * yapmıyormuş gibi görünürdü.
         */
        $depo = app(MedyaDeposu::class);
        $rendition = $depo->al($this->gorsel(3000, 1000), 'hero_masaustu');
        $eskiYol = $rendition->yol;

        $depo->odagiGuncelle($rendition->asset, 90, 10);

        $yeni = $rendition->fresh();
        $this->assertNotSame($eskiYol, $yeni->yol, 'Türev yeniden üretilmedi.');
        Storage::disk('public')->assertMissing($eskiYol);   // eski dosya çöp bırakılmaz
        $this->assertSame(90, $yeni->asset->odak_x);
    }

    public function test_tanimsiz_slot_hata_verir(): void
    {
        // Sessizce varsayılan boyut uydurmak, yanlış boyutu fark edilmez yapardı.
        $this->expectExceptionMessage('Tanımsız slot');

        $asset = app(MedyaDeposu::class)->anaKopyaOlustur($this->gorsel(800, 600));
        app(MedyaTuretici::class)->turet($asset, 'olmayan.slot');
    }

    public function test_yeniden_turet_komutu_varsayilan_kuru_kosudur(): void
    {
        $rendition = app(MedyaDeposu::class)->al($this->gorsel(3000, 1500), 'hero_masaustu');
        $eskiYol = $rendition->yol;

        $this->artisan('media:yeniden-turet')->assertSuccessful();

        $this->assertSame($eskiYol, $rendition->fresh()->yol, 'Kuru koşu dosya üretmemeli.');

        $this->artisan('media:yeniden-turet --uygula')->assertSuccessful();

        $this->assertNotSame($eskiYol, $rendition->fresh()->yol);
    }
}

<?php

namespace Tests\Feature;

use App\Services\Medya\HeroKontrast;
use App\Services\Medya\MedyaDeposu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * Karartma artık bir AYAR değil, ÖLÇÜMÜN SONUCU. (2026-08-09)
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § 4
 *
 * Elle %48 → %69 → %60 gidip gelindi ve mobil yine eşiğin altında kaldı:
 * masaüstü ve mobil AYRI ölçülmemişti, mobil görsel daha parlaktı.
 * Bu dosya kuralı mühürler — sessizce bozulabilecek cinsten.
 */
class HeroKontrastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function duzRenk(string $renk): UploadedFile
    {
        $img = (new ImageManager(new Driver))->createImage(2400, 1200)->fill($renk);
        $gecici = tempnam(sys_get_temp_dir(), 'hero').'.jpg';
        file_put_contents($gecici, (string) $img->encode(new JpegEncoder(quality: 90)));

        return new UploadedFile($gecici, 'hero.jpg', 'image/jpeg', null, true);
    }

    public function test_koyu_gorsel_az_karartma_ister(): void
    {
        $rendition = app(MedyaDeposu::class)->al($this->duzRenk('#101014'), 'hero_masaustu');

        $sonuc = app(HeroKontrast::class)->olc($rendition);

        $this->assertTrue($sonuc['gecti']);
        $this->assertFalse($sonuc['panel_gerekli']);
        $this->assertLessThan(20, $sonuc['karartma'], 'Koyu görselde karartma düşük olmalı.');
        $this->assertGreaterThanOrEqual(HeroKontrast::ESIK_NORMAL, $sonuc['kontrast']);
    }

    public function test_parlak_gorselde_ust_sinir_asilmaz_panel_acilir(): void
    {
        // Bembeyaz zemin: hiçbir makul karartma beyaz metni okunur yapmaz.
        // Doğru davranış görseli öldürmek değil, metnin arkasını koyultmak.
        $rendition = app(MedyaDeposu::class)->al($this->duzRenk('#ffffff'), 'hero_masaustu');

        $sonuc = app(HeroKontrast::class)->olc($rendition);

        $this->assertSame(HeroKontrast::AZAMI_KARARTMA, $sonuc['karartma'], 'Üst sınır aşılmamalı.');
        $this->assertTrue($sonuc['panel_gerekli']);
    }

    public function test_orta_tonlu_gorsel_esigi_gecen_en_dusuk_karartmayi_secer(): void
    {
        // #6a6a6a DEĞİL: o ton karartmasız da eşiği geçiyor (ölçüldü: 5.41),
        // yani "en düşüğü seçiyor mu" sorusunu hiç sınamıyordu. #a0a0a0 gerçekten
        // karartma gerektiriyor (karartmasız 2.55).
        $rendition = app(MedyaDeposu::class)->al($this->duzRenk('#a0a0a0'), 'hero_masaustu');

        $sonuc = app(HeroKontrast::class)->olc($rendition);

        $this->assertGreaterThan(0, $sonuc['karartma'], 'Bu ton karartmasız eşiği geçmemeli.');
        $this->assertLessThan(HeroKontrast::AZAMI_KARARTMA, $sonuc['karartma'], 'Üst sınıra dayanmamalı.');
        $this->assertGreaterThanOrEqual(HeroKontrast::ESIK_NORMAL, $sonuc['kontrast']);
    }

    public function test_masaustu_ve_mobil_ayri_olculur(): void
    {
        /*
         * BU TESTİN VARLIK SEBEBİ: 2026-08-09'da %60 karartmada masaüstü
         * geçiyor (4.72/5.21/4.17) ama mobil kalıyordu (3.71/3.71/3.84) —
         * çünkü tek bir sayı ikisine birden uygulanıyordu.
         */
        $depo = app(MedyaDeposu::class);
        $kontrast = app(HeroKontrast::class);

        $koyu = $depo->al($this->duzRenk('#101014'), 'hero_masaustu');
        $parlak = $depo->al($this->duzRenk('#f0f0f0'), 'hero_mobil');

        $this->assertNotSame(
            $kontrast->olc($koyu)['karartma'],
            $kontrast->olc($parlak)['karartma'],
            'Farklı parlaklıktaki iki görsel aynı karartmayı almamalı.',
        );
    }

    public function test_gorsel_okunamazsa_guvenli_tarafa_duser(): void
    {
        // Bilinmeyeni "sorun yok" saymak, tam da kaçınılan hata.
        $rendition = app(MedyaDeposu::class)->al($this->duzRenk('#333333'), 'hero_masaustu');
        Storage::disk('public')->delete($rendition->yol);

        $sonuc = app(HeroKontrast::class)->olc($rendition->fresh());

        $this->assertSame(HeroKontrast::AZAMI_KARARTMA, $sonuc['karartma']);
        $this->assertTrue($sonuc['panel_gerekli']);
        $this->assertFalse($sonuc['gecti']);
    }
}

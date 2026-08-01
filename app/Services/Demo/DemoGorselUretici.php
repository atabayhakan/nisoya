<?php

namespace App\Services\Demo;

use App\Services\ImageService;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use RuntimeException;

/**
 * Demo görsellerini SIFIRDAN üretir. Dışarıdan hiçbir şey indirmez.
 *
 * ---------------------------------------------------------------------------
 * NEDEN İNDİRMİYORUZ
 *
 * Bir görsel servisinden çekmek üç sorun getirir: üretim sunucusunda ağ
 * bağımlılığı, kaynağı belirsiz görsellerin lisansı, ve en önemlisi —
 * gerçekçi bir fotoğraf demo verisini GERÇEK gibi gösterir. Demo görsel
 * sahte olduğunu belli etmelidir.
 *
 * ---------------------------------------------------------------------------
 * TTF'YE GEÇİŞ (2026-08-01) — eski bitmap-font kararının revizyonu
 *
 * İlk sürüm GD'nin gömülü bitmap fontunu kullanıyordu (depoya font eklememek
 * için). Bedeli canlıda ölçüldü: yalnız ASCII bastığı için ana sayfa
 * "BEBEK BAKICILIGI" / "RESM EVRAK TERCUMESI" gibi Türkçe'siz, kesik
 * metinlerle doldu ve "dürüst yer tutucu" niyeti "bozuk site" izlenimine
 * dönüştü. Depoya açık lisanslı DejaVu Sans Bold eklendi
 * (resources/fonts/, lisans: DEJAVU-LICENSE.txt — Bitstream Vera türevi,
 * yeniden dağıtım serbest). FreeType üretim PHP'sinde de mevcut.
 *
 * "Sahte olduğu belli olsun" ilkesi DURUYOR: görsel hâlâ düz renk + grafik
 * desen (fotoğraf değil) ve köşesinde kalıcı "ÖRNEK GÖRSEL" rozeti taşıyor.
 * Değişen şey dürüstlüğün özensizlik gibi görünmesi.
 *
 * ---------------------------------------------------------------------------
 * INTERVENTION v4 API TUZAKLARI (yaygın dokümantasyondan farklı, ölçüldü)
 *
 *   · `createImage()` — `create()` DEĞİL. Tuval tamamen şeffaf başlar.
 *   · `drawRectangle(callable)` — TEK argüman; konum factory içinde `at()`.
 *   · `place()` metodu YOK; bindirme için `insert()`.
 *   · `align('center', 'middle')` istisna fırlatır; dikey değer `'center'`.
 */
class DemoGorselUretici
{
    /** Demo görsellerinin paleti — kasıtlı olarak düz ve "grafik", fotoğrafik değil. */
    private const PALET = [
        ['#0f766e', '#99f6e4'],
        ['#4338ca', '#c7d2fe'],
        ['#b45309', '#fed7aa'],
        ['#9d174d', '#fbcfe8'],
        ['#166534', '#bbf7d0'],
        ['#1e40af', '#bfdbfe'],
    ];

    public function __construct(private readonly ImageService $gorseller) {}

    /**
     * Bir demo görseli üretip ImageService'ten geçirir.
     *
     * ImageService'ten GEÇMESİ önemli: gerçek yükleme yolunun ürettiği
     * varyantların (thumb/medium/large webp) aynısı üretilsin ki demo veri
     * gerçek veriyle aynı şekilde davransın — kırık varyant, eksik boyut ya
     * da farklı klasör düzeni sürprizi olmasın.
     *
     * @param  string  $dizin  ImageService'e verilecek klasör ('listings', 'avatars')
     * @return array<string, mixed> ImageService::processImage() çıktısı
     */
    public function uret(string $etiket, string $dizin, int $tohum = 0, int $genislik = 1200, int $yukseklik = 800): array
    {
        $gecici = tempnam(sys_get_temp_dir(), 'nisoya-demo-');

        if ($gecici === false) {
            throw new RuntimeException('Geçici dosya oluşturulamadı.');
        }

        try {
            file_put_contents($gecici, $this->tuval($etiket, $tohum, $genislik, $yukseklik));

            return $this->gorseller->storeOptimizedFromPath($gecici, $dizin);
        } finally {
            @unlink($gecici);
        }
    }

    /** Ham PNG baytları — testler bunu ImageService'siz de doğrulayabilsin diye ayrı. */
    public function tuval(string $etiket, int $tohum = 0, int $genislik = 1200, int $yukseklik = 800): string
    {
        [$zemin, $vurgu] = self::PALET[$tohum % count(self::PALET)];
        $font = $this->fontYolu();

        $yonetici = new ImageManager(new Driver);
        $resim = $yonetici->createImage($genislik, $yukseklik);
        $resim->fill($zemin);

        // Köşedeki daire ve alt şerit: düz zeminden ayırt edilebilir bir
        // desen, ama hiçbir şekilde fotoğrafa benzemeyen bir desen.
        $resim->drawCircle(function (CircleFactory $daire) use ($genislik, $yukseklik, $vurgu): void {
            $daire->at((int) ($genislik * 0.86), (int) ($yukseklik * 0.18));
            $daire->radius((int) ($yukseklik * 0.30));
            $daire->background($vurgu.'55'); // yarı saydam — zeminle kaynaşsın
        });

        $resim->drawRectangle(function (RectangleFactory $dikdortgen) use ($genislik, $yukseklik, $vurgu): void {
            $dikdortgen->at(0, $yukseklik - (int) ($yukseklik * 0.035));
            $dikdortgen->size($genislik, (int) ($yukseklik * 0.035));
            $dikdortgen->background($vurgu);
        });

        // Başlık — Türkçe karakterlerle, ortalanmış, gerekirse iki satır.
        $boyut = (int) round($yukseklik * 0.085);
        $satirlar = $this->satirlara($etiket, $boyut, (int) ($genislik * 0.82));
        $satirYuksekligi = (int) ($boyut * 1.35);
        $baslangicY = (int) ($yukseklik * 0.52 - (count($satirlar) - 1) * $satirYuksekligi / 2);

        foreach ($satirlar as $i => $satir) {
            $resim->text($satir, (int) ($genislik / 2), $baslangicY + $i * $satirYuksekligi,
                function (FontFactory $f) use ($font, $boyut): void {
                    $f->filename($font);
                    $f->size($boyut);
                    $f->color('#ffffff');
                    $f->align('center', 'center');
                });
        }

        // "ÖRNEK GÖRSEL" rozeti — sol üstte, her varyantta kalıcı damga.
        $rozetBoyut = max(14, (int) round($yukseklik * 0.038));
        $rozetMetin = 'ÖRNEK GÖRSEL';
        $rozetGenislik = (int) ($rozetBoyut * 0.62 * mb_strlen($rozetMetin)) + $rozetBoyut * 2;
        $rozetYukseklik = (int) ($rozetBoyut * 2.1);

        $resim->drawRectangle(function (RectangleFactory $r) use ($rozetGenislik, $rozetYukseklik, $yukseklik): void {
            $kenar = (int) ($yukseklik * 0.05);
            $r->at($kenar, $kenar);
            $r->size($rozetGenislik, $rozetYukseklik);
            $r->background('#00000066');
        });

        $resim->text($rozetMetin, (int) ($yukseklik * 0.05) + (int) ($rozetGenislik / 2), (int) ($yukseklik * 0.05) + (int) ($rozetYukseklik / 2),
            function (FontFactory $f) use ($font, $rozetBoyut): void {
                $f->filename($font);
                $f->size($rozetBoyut);
                $f->color('#ffffff');
                $f->align('center', 'center');
            });

        return (string) $resim->encode(new PngEncoder);
    }

    /**
     * Metni tahmini karakter genişliğiyle en fazla iki satıra böler.
     *
     * FreeType gerçek ölçüm de verebilirdi (imagettfbbox) ama tahmin bu iş
     * için yeterli: başlıklar kısa, tuval geniş ve taşma durumunda ikinci
     * satır kırpılmıyor — yalnızca alta iniyor.
     *
     * @return list<string>
     */
    private function satirlara(string $metin, int $boyut, int $azamiGenislik): array
    {
        $metin = trim($metin) === '' ? 'ÖRNEK' : trim($metin);
        // DejaVu Sans Bold ortalama glif genişliği ~0.62 em (ölçüldü, kaba).
        $sigar = max(4, (int) floor($azamiGenislik / ($boyut * 0.62)));

        if (mb_strlen($metin) <= $sigar) {
            return [$metin];
        }

        $kelimeler = explode(' ', $metin);
        $satirlar = [''];

        foreach ($kelimeler as $kelime) {
            $aday = trim($satirlar[count($satirlar) - 1].' '.$kelime);

            if (mb_strlen($aday) <= $sigar || $satirlar[count($satirlar) - 1] === '') {
                $satirlar[count($satirlar) - 1] = $aday;

                continue;
            }

            $satirlar[] = $kelime;
        }

        // İki satırdan fazlası görsel karmaşa: üçüncü ve sonrası ikinciye
        // sığmıyorsa üç nokta ile kısaltılır.
        if (count($satirlar) > 2) {
            $satirlar = [$satirlar[0], mb_substr(implode(' ', array_slice($satirlar, 1)), 0, $sigar - 1).'…'];
        }

        return $satirlar;
    }

    private function fontYolu(): string
    {
        $yol = resource_path('fonts/DejaVuSans-Bold.ttf');

        if (! is_file($yol)) {
            throw new RuntimeException('Demo görsel fontu bulunamadı: '.$yol);
        }

        return $yol;
    }
}

<?php

namespace App\Services\Demo;

use App\Services\ImageService;
use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
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
 * NEDEN FONT DOSYASI YOK — ve bunun bedeli
 *
 * GD ile metin yazmanın iki yolu var: TTF (FreeType) ya da gömülü bitmap
 * font. TTF yolu Türkçe karakterleri düzgün basar ama depoya bir font ikilisi
 * eklemeyi gerektirir; bu depoda hiç TTF yok ve `public/build` altındaki
 * woff'lar kullanılamıyor (FreeType woff2'yi okuyamıyor; woff'lar ise
 * fontsource alt kümesi olduğu için ş/ğ/ı ya da ü/ç yerine tofu kutusu
 * basıyor — ölçüldü).
 *
 * Gömülü bitmap font seçildi: yeni bağımlılık yok, üretim Linux'unda birebir
 * aynı çalışır. BEDELİ: yalnız ASCII (`imagestring` bayt tabanlıdır, UTF-8
 * Türkçe mojibake olur) ve boyut 1–5 ile sınırlı. Bu yüzden metin küçük bir
 * katmana yazılıp büyütülüyor — çıkan blok/piksel görünüm bir kayıp değil,
 * "bu gerçek bir fotoğraf değil" sinyalinin ta kendisi.
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

        $yonetici = new ImageManager(new Driver);
        $resim = $yonetici->createImage($genislik, $yukseklik);
        $resim->fill($zemin);

        // Köşedeki daire ve şerit: düz zeminden ayırt edilebilir bir desen,
        // ama hiçbir şekilde fotoğrafa benzemeyen bir desen.
        $resim->drawCircle(function (CircleFactory $daire) use ($genislik, $yukseklik, $vurgu): void {
            $daire->at((int) ($genislik * 0.82), (int) ($yukseklik * 0.26));
            $daire->radius((int) ($yukseklik * 0.22));
            $daire->background($vurgu);
        });

        $resim->drawRectangle(function (RectangleFactory $dikdortgen) use ($genislik, $yukseklik, $vurgu): void {
            $dikdortgen->at(0, (int) ($yukseklik * 0.62));
            $dikdortgen->size($genislik, (int) ($yukseklik * 0.06));
            $dikdortgen->background($vurgu);
        });

        // İmza sırası: (görsel, x, y, hizalama) — v4'te hizalama DÖRDÜNCÜ
        // parametre; x/y hizalanmış konuma göre kaydırmadır.
        $resim->insert($this->metinKatmani('ORNEK GORSEL'), 0, (int) (-$yukseklik * 0.10), Alignment::CENTER);
        $resim->insert($this->metinKatmani($this->asciyeCevir($etiket), 2), 0, (int) ($yukseklik * 0.04), Alignment::CENTER);

        return (string) $resim->encode(new PngEncoder);
    }

    /**
     * Metni küçük bir şeffaf katmana yazıp büyütür.
     *
     * GD'nin gömülü fontu ölçeklenemez (boyut 1–5, 5'te karakter 9×16 px);
     * 1200 px genişliğinde bir tuvalde okunmaz kalırdı. Küçük yazıp büyütmek
     * klasik çözüm ve piksel estetiği burada tam olarak istenen şey.
     */
    private function metinKatmani(string $metin, int $olcek = 4): ImageInterface
    {
        $metin = $metin === '' ? 'DEMO' : $metin;

        // Gömülü font boyut 5: karakter genişliği 9 px, yükseklik 16 px.
        $genislik = max(1, strlen($metin) * 9);
        $yukseklik = 16;

        $yonetici = new ImageManager(new Driver);
        $katman = $yonetici->createImage($genislik, $yukseklik);

        $katman->text($metin, 0, 0, function (FontFactory $font): void {
            // filename() ÇAĞRILMIYOR — gömülü bitmap font bu yüzden devreye girer.
            $font->size(5);
            $font->color('#ffffff');
            $font->align('left', 'top');
        });

        return $katman->scale(width: $genislik * $olcek);
    }

    /**
     * Türkçe harfleri ASCII karşılığına indirger.
     *
     * `imagestring` bayt tabanlıdır: "ÖRNEK" doğrudan yazılırsa mojibake olur.
     * Sessizce bozuk metin basmaktansa harfleri bilinçli olarak indirgemek
     * daha dürüst.
     */
    private function asciyeCevir(string $metin): string
    {
        $metin = strtr($metin, [
            'ç' => 'c', 'Ç' => 'C', 'ğ' => 'g', 'Ğ' => 'G', 'ı' => 'i', 'İ' => 'I',
            'ö' => 'o', 'Ö' => 'O', 'ş' => 's', 'Ş' => 'S', 'ü' => 'u', 'Ü' => 'U',
        ]);

        $metin = (string) preg_replace('/[^\x20-\x7E]/', '', $metin);

        return mb_strtoupper(trim(mb_substr($metin, 0, 34)));
    }
}

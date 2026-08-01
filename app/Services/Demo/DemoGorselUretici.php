<?php

namespace App\Services\Demo;

use App\Services\Ai\FotografUretici;
use App\Services\ImageService;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use RuntimeException;

/**
 * Demo görsellerini üretir: varsayılan grafik tuval; istenirse AI fotoğraf.
 *
 * ---------------------------------------------------------------------------
 * AĞ POLİTİKASI (2026-08-01 revizyonu)
 *
 * İlk kural "dışarıdan hiçbir şey indirmez"di — ağ bağımlılığı, lisans
 * belirsizliği ve "gerçekçi fotoğraf demo veriyi gerçek gibi gösterir"
 * gerekçeleriyle. Sahibin kararıyla üçüncü gerekçe koşullu gevşedi: AI ile
 * üretilen fotoğrafa TÜM GÖRSELE yayılan yarı saydam ÖRNEK filigranı
 * basılırsa (kırpmayla kaybolmaz) güzellik ile dürüstlük birlikte olur.
 * İlk iki gerekçe hâlâ tasarımda: bu sınıfın KENDİSİ ağ çağrısı yapmaz
 * (AI çağrısı App\Services\Ai\FotografUretici'de yaşar) ve AI kapalı/
 * anahtarsız/kırıksa üretim SESSİZCE grafik tuvale düşer — demo makinesi
 * hiçbir koşulda ağ yüzünden kırılmaz. Lisans sorunu AI üretiminde yok.
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

    public function __construct(
        private readonly ImageService $gorseller,
        private readonly FotografUretici $fotograf,
    ) {}

    /**
     * Bir demo görseli üretip ImageService'ten geçirir.
     *
     * ImageService'ten GEÇMESİ önemli: gerçek yükleme yolunun ürettiği
     * varyantların (thumb/medium/large webp) aynısı üretilsin ki demo veri
     * gerçek veriyle aynı şekilde davransın — kırık varyant, eksik boyut ya
     * da farklı klasör düzeni sürprizi olmasın.
     *
     * AI YOLU ($ai=true, yalnız ilan görselleri): FotografUretici'den gerçekçi
     * bir fotoğraf istenir ve ÜZERİNE tüm görsele yayılan yarı saydam "ÖRNEK"
     * filigranı + köşe rozeti basılır — güzellik ile "sahte olduğu belli olsun"
     * ilkesi birlikte. AI kapalı/anahtarsız/kırıksa SESSİZCE grafik tuvale
     * düşülür; demo üretimi hiçbir koşulda ağ yüzünden kırılmaz. Avatarlar
     * BİLEREK AI almaz: sahte kişilere gerçekçi yüz üretmek, rozetle bile
     * yanıltıcılığın yanlış tarafına geçer.
     *
     * @param  string  $dizin  ImageService'e verilecek klasör ('listings', 'avatars')
     * @return array<string, mixed> ImageService::processImage() çıktısı
     */
    public function uret(string $etiket, string $dizin, int $tohum = 0, int $genislik = 1200, int $yukseklik = 800, bool $ai = false): array
    {
        $gecici = tempnam(sys_get_temp_dir(), 'nisoya-demo-');

        if ($gecici === false) {
            throw new RuntimeException('Geçici dosya oluşturulamadı.');
        }

        try {
            $png = null;

            if ($ai && $dizin === 'listings') {
                $foto = $this->fotograf->uret($this->fotoIstemi($etiket));

                if ($foto !== null) {
                    $png = $this->filigranla($foto, $genislik, $yukseklik);
                }
            }

            file_put_contents($gecici, $png ?? $this->tuval($etiket, $tohum, $genislik, $yukseklik));

            return $this->gorseller->storeOptimizedFromPath($gecici, $dizin);
        } finally {
            @unlink($gecici);
        }
    }

    /**
     * AI fotoğraf istemi. Kısıtlar bilinçli: insan yüzü yok (sahte ilana
     * gerçek yüz koymak yanıltıcılığın öbür tarafı + moderasyon riski),
     * görselin içinde metin yok (filigranı BİZ basıyoruz, model değil).
     */
    private function fotoIstemi(string $etiket): string
    {
        return 'Bir hizmet ilanı için profesyonel, gerçekçi stok fotoğraf üret: "'.$etiket.'". '
            .'Doğal ışık, temiz kompozisyon, yatay kadraj. İnsan yüzü OLMASIN '
            .'(gerekirse yalnız eller/uzaktan siluet), görselin içinde yazı, logo veya filigran OLMASIN.';
    }

    /**
     * AI fotoğrafını 1200×800'e oturtup dürüstlük katmanını basar:
     * tüm görsele çapraz yayılan yarı saydam "ÖRNEK" tekrarı + sol üstte
     * standart "ÖRNEK GÖRSEL" rozeti. Köşe rozeti kırpılabilir; çapraz
     * filigran kırpmayla da kaybolmaz — kalıcı damga odur.
     */
    private function filigranla(string $fotoBaytlari, int $genislik, int $yukseklik): string
    {
        $font = $this->fontYolu();

        $yonetici = new ImageManager(new Driver);
        $resim = $yonetici->decodeBinary($fotoBaytlari)->cover($genislik, $yukseklik);

        $boyut = (int) round($yukseklik * 0.11);

        // 3 sıra çapraz "ÖRNEK" — iki geçiş: önce yarı saydam koyu gölge,
        // üstüne yarı saydam beyaz. (Stroke efekti KULLANILAMAZ: Intervention
        // "text color must be fully opaque when using stroke" der — ölçüldü;
        // gölge geçişi aynı işi görür ve açık/koyu her fotoğrafta okunur.)
        // Satırlar yatayda ŞAŞIRTMALI: açılı metin merkez çapasından sağa
        // tırmandığı için hepsi aynı x'te olursa sağda kümelenir ve sol
        // yarı filigransız (kırpılabilir) kalır — ölçüldü.
        foreach ([[-1, 0.30], [0, 0.55], [1, 0.35]] as [$sira, $xOran]) {
            $x = (int) ($genislik * $xOran);
            $y = (int) ($yukseklik / 2 + $sira * $yukseklik * 0.33);

            foreach ([['#00000040', 3], ['#ffffff59', 0]] as [$renk, $kaydir]) {
                $resim->text('Ö R N E K', $x + $kaydir, $y + $kaydir,
                    function (FontFactory $f) use ($font, $boyut, $renk): void {
                        $f->filename($font);
                        $f->size($boyut);
                        $f->color($renk);
                        $f->align('center', 'center');
                        $f->angle(18);
                    });
            }
        }

        $this->rozetBas($resim, $genislik, $yukseklik);

        return (string) $resim->encode(new PngEncoder);
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

        $this->rozetBas($resim, $genislik, $yukseklik);

        return (string) $resim->encode(new PngEncoder);
    }

    /** Sol üstteki "ÖRNEK GÖRSEL" rozeti — grafik tuvalde de AI fotoğrafta da aynı damga. */
    private function rozetBas(ImageInterface $resim, int $genislik, int $yukseklik): void
    {
        $font = $this->fontYolu();
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

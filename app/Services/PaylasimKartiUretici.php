<?php

namespace App\Services;

use App\Enums\PriceUnit;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Exception;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use RuntimeException;

/**
 * İlan paylaşım kartı — WhatsApp durumu için 1080×1920 dikey PNG.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * partials/share-buttons zaten WhatsApp'a gidiyordu ama DÜZ METİN linki
 * gönderiyordu ve `og:image` ilanın kendi yatay fotoğrafıydı. İkisi de
 * WhatsApp *durumunda* kullanılamaz: durum dikey görsel ister, metin linki
 * orada çirkin ve tıklanmaz görünür.
 *
 * Bu kartın büyüme mantığı, tanıtım otomasyonundan farklı: paylaşan kişi
 * ilan sahibinin KENDİSİ ve yayılım kendi tanıdık ağına gidiyor. Yani bot
 * gibi görünen bir dağıtım değil — projenin "kendi insanından" tonuyla
 * çelişmiyor. Üstelik ARZ tarafını çoğaltıyor: ilan verene yayma aracı
 * verirsin, o da çevresinden ilan verenleri getirir.
 *
 * ---------------------------------------------------------------------------
 * DÜRÜSTLÜK KATMANI
 *
 * Demo ilanların kapak görselleri zaten ÖRNEK filigranı taşıyor
 * (bkz. Demo\DemoGorselUretici) ve kart kapağı olduğu gibi bindirdiği için
 * filigran karta da geçiyor. Ama görselsiz demo ilanda geçecek bir filigran
 * yok — o yüzden `is_demo` ise kart, kapaktan bağımsız olarak AYRICA
 * damgalanır. Sahte bir ilanın gerçek bir WhatsApp ağına damgasız düşmesi,
 * bu projenin baştan beri kaçındığı tam olarak o şey.
 *
 * ---------------------------------------------------------------------------
 * QR NEDEN VAR (ve neden tek başına yetmez)
 *
 * WhatsApp durumunu izleyen kişi aynı telefondadır — kendi ekranındaki QR'ı
 * tarayamaz. QR yine de duruyor çünkü kart Instagram hikâyesine de düşebilir,
 * masaüstünden (WhatsApp Web) izlenebilir, hatta basılabilir. Ama ASIL
 * çağrı QR değil, kartta okunaklı boyutta basılan `nisoya.com` adresidir —
 * QR'a bel bağlayan bir tasarım tek cihazlı okurda ölü kalırdı.
 *
 * ---------------------------------------------------------------------------
 * INTERVENTION v4 TUZAKLARI (DemoGorselUretici'de ölçülüp belgelenmişti,
 * burada da aynen geçerli — yaygın dokümantasyondan farklı)
 *
 *   · `createImage()` — `create()` DEĞİL. Tuval tamamen şeffaf başlar.
 *   · `drawRectangle(callable)` — TEK argüman; konum factory içinde `at()`.
 *   · `place()` metodu YOK; bindirme için `insert()`.
 *   · `align('center', 'middle')` istisna fırlatır; dikey değer `'center'`.
 */
class PaylasimKartiUretici
{
    /**
     * Çizim sürümü — kartın GÖRÜNÜMÜ her değiştiğinde ARTIRILMALI.
     *
     * Neden var: önbellek anahtarı ilanın içeriğini (başlık, fiyat, konum...)
     * özetliyordu ama çizim mantığını değil. Sonuç canlıda ölçüldü: demo
     * damgası kapaktan panele taşındı, deploy indi, ama daha önce üretilmiş
     * kartlar diskte durduğu için ESKİ görünüm servis edilmeye devam etti —
     * değişiklik hiçbir zaman kendiliğinden ulaşmayacaktı.
     *
     * Sürümü anahtara katmak bunu kalıcı olarak çözüyor: sabiti artıran her
     * değişiklik tüm kartların dosya adını değiştirir, yani ilk istekte
     * yeniden üretilirler. Elle önbellek temizliği gerekmez.
     *
     * v2: demo damgası kapaktan panele taşındı ("ÖRNEK İLAN" rozeti).
     */
    private const SURUM = 2;

    /** Kartların public diskteki klasörü (süpürme komutu da buradan okur). */
    public const KLASOR = 'paylasim-kartlari';

    public const GENISLIK = 1080;

    public const YUKSEKLIK = 1920;

    /** Kapak görselinin kapladığı üst kare; kalanı metin paneli. */
    private const KAPAK_YUKSEKLIK = 1080;

    private const KENAR = 72;

    /**
     * Kartın diskte hazır olduğunu garantiler ve public diske göreli yolunu
     * döndürür. Tek giriş noktası bu — üretim/önbellek kararı burada yaşasın
     * ki çağıran taraf (controller) aynı `exists → put` mantığını
     * kopyalamak zorunda kalmasın.
     */
    public function hazirla(Listing $listing): string
    {
        $yol = $this->yol($listing);
        $disk = Storage::disk('public');

        if (! $disk->exists($yol)) {
            $disk->put($yol, $this->uret($listing));
            $this->eskileriSil($listing, $yol);
        }

        return $yol;
    }

    /**
     * Bu ilanın ARTIK GEÇERSİZ kartlarını siler (yeni kart yazıldıktan sonra).
     *
     * Dosya adı `{id}-{imza}.png` olduğu için ilan başına birikme kaçınılmaz:
     * başlık/fiyat değişimi ya da SURUM artışı yeni bir ad üretir, eskisi
     * diskte kalır. Kart başına ~800KB olduğundan bu sessizce büyür.
     *
     * Temizlik ÜRETİMDEN SONRA yapılıyor, öncesinde değil: sıra tersine
     * dönerse eski kart silinip yeni üretim hata verdiğinde ilan kartsız
     * kalırdı. Bu sırayla en kötü ihtimalle bir tur fazladan dosya durur.
     *
     * Bu yalnız kartı yeniden istenen ilanları temizler; hiç istenmeyenler
     * için haftalık süpürme var (paylasim-kartlari:temizle).
     */
    private function eskileriSil(Listing $listing, string $guncelYol): void
    {
        $disk = Storage::disk('public');

        $eskiler = array_filter(
            $disk->files(self::KLASOR),
            fn (string $dosya): bool => $dosya !== $guncelYol
                && self::dosyaAdindanIlanId($dosya) === $listing->id
        );

        if ($eskiler !== []) {
            $disk->delete($eskiler);
        }
    }

    /**
     * Dosya adı bizim ürettiğimiz kart desenine (`{id}-{hex}.png`) uyuyorsa
     * ilan id'sini, uymuyorsa null döner.
     *
     * Tek kaynak olması ŞART: anında temizlik ile haftalık süpürme farklı
     * kurallar kullanırsa aynı dosya birine göre çöp, diğerine göre korunacak
     * olur. İlk sürümde tam bu oldu — anında temizlik `{id}-` ile başlayan her
     * şeyi siliyordu, süpürme ise yalnız hex imzalıları; klasöre elle konan bir
     * dosya birinden kurtulup diğerine yakalanıyordu.
     */
    public static function dosyaAdindanIlanId(string $dosyaYolu): ?int
    {
        if (! preg_match('/^(\d+)-[0-9a-f]+\.png$/', basename($dosyaYolu), $eslesme)) {
            return null;
        }

        return (int) $eslesme[1];
    }

    /**
     * Önbellek anahtarı: kartta GÖRÜNEN her şeyin özeti.
     *
     * İlk sürüm `updated_at` kullanıyordu ve bu iki yönden de yanlıştı:
     *
     *  · Fazla tazeleme — ilan her görüntülendiğinde `views_count` artıyor
     *    (ListingController::show), Eloquent'in `increment()`'i zaman damgasına
     *    dokunuyor, yani kart neredeyse her sayfa görüntülemesinde yeniden
     *    üretilirdi. 1080×1920 PNG + QR çizimi için bu ciddi bir bedel.
     *  · Eksik tazeleme — `updated_at` saniye çözünürlüğünde; aynı saniye
     *    içindeki iki düzenleme aynı imzayı verir ve kart eski kalırdı.
     *    (Testte bu yakalandı.)
     *
     * İçerik özeti ikisini de çözer: kartta görünmeyen bir alan değiştiğinde
     * yeniden üretim olmaz, görünen bir alan değiştiğinde zaman damgasından
     * bağımsız olarak olur.
     */
    public function yol(Listing $listing): string
    {
        $imza = md5(implode('|', [
            self::SURUM,
            $listing->title,
            $listing->price,
            $listing->currency,
            $listing->price_unit?->value,
            $listing->city,
            $listing->country_code,
            $this->kategoriAdi($listing),
            $listing->is_demo ? '1' : '0',
            $this->kapakGorseli($listing)?->id,
            brandColorHex(),
        ]));

        return self::KLASOR.'/'.$listing->id.'-'.substr($imza, 0, 10).'.png';
    }

    /** Ham PNG baytları — testler diski kullanmadan da doğrulayabilsin diye ayrı. */
    public function uret(Listing $listing): string
    {
        $marka = brandColorHex();
        $font = $this->fontYolu();

        $yonetici = new ImageManager(new Driver);
        $kart = $yonetici->createImage(self::GENISLIK, self::YUKSEKLIK);
        $kart->fill($marka);

        $this->kapakBas($kart, $listing, $font);

        // Demo damgası KOŞULSUZ ve KAPAĞA DEĞİL PANELE basılıyor.
        //
        // İki tur aldı. Önce "kapak zaten damgalı, atlayalım" denendi: çürüdü,
        // çünkü kapağı 1200×800'den 1080×1080'e `cover()` ile kırpıyoruz ve
        // DemoGorselUretici'nin sol üst rozeti kırpmada kesiliyor
        // ("ÖRNEK GÖRSEL" → "ÖRSEL"); grafik tuval görsellerinde kırpmaya
        // dayanıklı çapraz filigran YOK (o yalnız AI fotoğraflarda). Sonra
        // çapraz filigran koşulsuz basıldı: canlıda AI fotoğraflı ilanda iki
        // filigran üst üste binip "ÖRRNNEEKK" gibi okundu — damga değil,
        // bozuk render izlenimi verdi.
        //
        // Panel rozeti ikisini de çözüyor: panel hiçbir zaman kırpılmıyor
        // (kartın alt üçte biri, her ilanda çiziliyor), kapağın kendi
        // filigranıyla ÇAKIŞMIYOR ve "ÖRNEK İLAN" ifadesi çapraz bir
        // filigrandan daha açık.
        // (bool) şart: kolon NOT NULL ama değeri VERİTABANI varsayılanı
        // dolduruyor, yani alanı set etmeden kaydedilen bir model örneğinde
        // özellik tazelenene kadar bellekte null kalır. Çıplak geçmek
        // TypeError veriyordu (testte yakalandı).
        $this->panelBas($kart, $listing, $font, (bool) $listing->is_demo);

        return (string) $kart->encode(new PngEncoder);
    }

    /**
     * Üst kare: ilanın kapak görseli. Görsel yoksa/okunamıyorsa marka renkli
     * düz alan kalır — kart kırılmaz, yalnız sade görünür.
     *
     * Görseller diskte webp; GD webp okuyabiliyor ama bozuk/eksik dosya
     * ihtimaline karşı çözme denemesi sarmalanmış durumda: paylaşım kartı
     * hiçbir koşulda ilan sayfasını 500'e düşürmemeli.
     */
    private function kapakBas(ImageInterface $kart, Listing $listing, string $font): void
    {
        $gorsel = $this->kapakGorseli($listing);
        $disk = Storage::disk('public');

        foreach (['path_large', 'path_medium', 'path_thumb'] as $kolon) {
            $yol = $gorsel?->{$kolon};

            if (! $yol || ! $disk->exists($yol)) {
                continue;
            }

            try {
                // `decode()` — `read()` DEĞİL. Bu Intervention sürümünde
                // ImageManager'da read() yok; ImageService de decode() kullanıyor.
                $kapak = (new ImageManager(new Driver))
                    ->decode($disk->path($yol))
                    ->cover(self::GENISLIK, self::KAPAK_YUKSEKLIK);
            } catch (Exception) {
                continue; // Bozuk/okunamayan varyant: sonrakini dene.
            }

            // insert() imzası: ($image, int $x, int $y, $alignment, ...) —
            // konum DİZESİ ikinci argüman DEĞİL. Bu çağrı bilerek try dışında:
            // buradaki bir TypeError programlama hatasıdır ve yutulmamalı.
            $kart->insert($kapak, 0, 0);

            /*
             * TEMSİLÎ ETİKETİ KARTA DA BASILIR.
             *
             * Bu kart WhatsApp durumuna gidiyor — siteden kopup tek başına
             * dolaşan tek yüzey burası. Etiket yalnız sitedeki HTML'de
             * kalsaydı, üretilmiş görsel kartta gerçek fotoğraf gibi
             * görünürdü ve tam kaçınmak istediğimiz durum bu.
             */
            if ($gorsel?->is_representative) {
                $this->temsiliRozeti($kart, $font);
            }

            return;
        }

        // Görselsiz ilan: kapak alanının ortasına kategori adını yaz ki
        // kart bomboş bir renk bloğu olarak çıkmasın.
        // Görselsiz ilanda kategori adı, damgayla çakışmasın diye kapak
        // alanının üst üçte birine yazılıyor (damga tam ortada duruyor).
        $etiket = $this->kategoriAdi($listing) ?? 'Nisoya ilanı';

        $kart->text($etiket, (int) (self::GENISLIK / 2), (int) (self::KAPAK_YUKSEKLIK * 0.28),
            function (FontFactory $f) use ($font): void {
                $f->filename($font);
                $f->size(64);
                $f->color('#ffffff66');
                $f->align('center', 'center');
            });
    }

    /** Alt panel: (demo rozeti,) başlık, fiyat, konum, adres ve QR. */
    private function panelBas(ImageInterface $kart, Listing $listing, string $font, bool $demo): void
    {
        $y = self::KAPAK_YUKSEKLIK + 96;

        if ($demo) {
            $this->ornekRozeti($kart, $font, self::KAPAK_YUKSEKLIK + 44);
            $y += 68; // Rozet başlığı aşağı iter; demo olmayan kartta boşluk kalmaz.
        }

        // Başlık — en fazla 3 satır, sığmayan kısım üç noktayla kesilir.
        $satirlar = $this->satirlara($listing->title, 58, self::GENISLIK - self::KENAR * 2, 3);

        foreach ($satirlar as $satir) {
            $kart->text($satir, self::KENAR, $y,
                function (FontFactory $f) use ($font): void {
                    $f->filename($font);
                    $f->size(58);
                    $f->color('#ffffff');
                    $f->align('left', 'top');
                });

            $y += 76;
        }

        $y += 28;

        $kart->text($this->fiyatMetni($listing), self::KENAR, $y,
            function (FontFactory $f) use ($font): void {
                $f->filename($font);
                $f->size(78);
                $f->color('#ffffff');
                $f->align('left', 'top');
            });

        $y += 108;

        $konum = $this->konumMetni($listing);

        if ($konum !== '') {
            $kart->text($konum, self::KENAR, $y,
                function (FontFactory $f) use ($font): void {
                    $f->filename($font);
                    $f->size(42);
                    $f->color('#ffffffbb');
                    $f->align('left', 'top');
                });
        }

        // Alt şerit: solda adres (asıl çağrı), sağda QR.
        $qrKenar = 200;
        $qrX = self::GENISLIK - self::KENAR - $qrKenar;
        $qrY = self::YUKSEKLIK - self::KENAR - $qrKenar;

        $this->qrBas($kart, route('listings.show', [$listing, $listing->slug]), $qrX, $qrY, $qrKenar);

        $kart->text('nisoya.com', self::KENAR, $qrY + (int) ($qrKenar / 2) - 26,
            function (FontFactory $f) use ($font): void {
                $f->filename($font);
                $f->size(56);
                $f->color('#ffffff');
                $f->align('left', 'top');
            });

        $kart->text('Ücretsiz ilan · Türkçe', self::KENAR, $qrY + (int) ($qrKenar / 2) + 34,
            function (FontFactory $f) use ($font): void {
                $f->filename($font);
                $f->size(34);
                $f->color('#ffffff99');
                $f->align('left', 'top');
            });
    }

    /**
     * QR'ı matristen kendimiz çiziyoruz.
     *
     * bacon/bacon-qr-code'un hazır arka uçları bize uymuyor: SvgImageBackEnd
     * SVG üretir (GD okuyamaz), ImagickImageBackEnd ext-imagick ister — canlı
     * sunucuda imagick YOK (yalnız gd kurulu, ölçüldü). Encoder'dan ByteMatrix
     * alıp modülleri dikdörtgen olarak basmak hem bağımsız hem de tek satırlık
     * bir iş; QR spesifikasyonu açısından da doğru, çünkü çizdiğimiz şey
     * kütüphanenin ürettiği matrisin ta kendisi.
     */
    private function qrBas(ImageInterface $kart, string $adres, int $x, int $y, int $kenar): void
    {
        // Beyaz altlık: QR'ın sessiz bölgesi (quiet zone) olmadan okunmaz.
        $kart->drawRectangle(function (RectangleFactory $r) use ($x, $y, $kenar): void {
            $r->at($x, $y);
            $r->size($kenar, $kenar);
            $r->background('#ffffff');
        });

        try {
            $matris = Encoder::encode($adres, ErrorCorrectionLevel::M())->getMatrix();
        } catch (Exception) {
            return; // QR üretilemezse beyaz kutu kalır; kart yine geçerli.
        }

        $modul = (int) floor(($kenar - 24) / max(1, $matris->getWidth()));

        if ($modul < 1) {
            return;
        }

        // Matris kutuya tam ortalansın (bölmeden artan payı iki yana dağıt).
        $kaydirX = $x + (int) (($kenar - $modul * $matris->getWidth()) / 2);
        $kaydirY = $y + (int) (($kenar - $modul * $matris->getHeight()) / 2);

        for ($sutun = 0; $sutun < $matris->getWidth(); $sutun++) {
            for ($satir = 0; $satir < $matris->getHeight(); $satir++) {
                if ($matris->get($sutun, $satir) !== 1) {
                    continue;
                }

                $kart->drawRectangle(function (RectangleFactory $r) use ($kaydirX, $kaydirY, $sutun, $satir, $modul): void {
                    $r->at($kaydirX + $sutun * $modul, $kaydirY + $satir * $modul);
                    $r->size($modul, $modul);
                    $r->background('#000000');
                });
            }
        }
    }

    /**
     * "TEMSİLÎ GÖRSEL" bandı — kapağın SOL ALT köşesine (ölçülen kutu:
     * x 72..413, y 948..1008).
     *
     * Üst köşeler zaman/öne-çıkan rozetlerine, panel ise "ÖRNEK İLAN"a
     * ayrılmış; kapağın alt kenarı boş ve göz görseli bırakırken oradan
     * geçiyor.
     */
    private function temsiliRozeti(ImageInterface $kart, string $font): void
    {
        $metin = 'TEMSİLÎ GÖRSEL';
        $boyut = 32;
        $genislik = (int) ($boyut * 0.62 * mb_strlen($metin)) + $boyut * 2;
        $yukseklik = (int) ($boyut * 1.9);
        $y = self::KAPAK_YUKSEKLIK - $yukseklik - self::KENAR;

        $kart->drawRectangle(function (RectangleFactory $r) use ($genislik, $yukseklik, $y): void {
            $r->at(self::KENAR, $y);
            $r->size($genislik, $yukseklik);
            $r->background('#000000a6');
        });

        $kart->text($metin, self::KENAR + (int) ($genislik / 2), $y + (int) ($yukseklik / 2),
            function (FontFactory $f) use ($font, $boyut): void {
                $f->filename($font);
                $f->size($boyut);
                $f->color('#ffffff');
                $f->align('center', 'center');
            });
    }

    /**
     * Panelin sol üstüne "ÖRNEK İLAN" rozeti.
     *
     * Panelde durması bilinçli: kapak kırpılabilir ve zaten kendi filigranını
     * taşıyor olabilir, panel ise her kartta tam olarak çiziliyor.
     */
    private function ornekRozeti(ImageInterface $kart, string $font, int $y): void
    {
        $metin = 'ÖRNEK İLAN';
        $boyut = 34;
        $genislik = (int) ($boyut * 0.62 * mb_strlen($metin)) + $boyut * 2;
        $yukseklik = (int) ($boyut * 1.9);

        $kart->drawRectangle(function (RectangleFactory $r) use ($genislik, $yukseklik, $y): void {
            $r->at(self::KENAR, $y);
            $r->size($genislik, $yukseklik);
            $r->background('#00000059');
        });

        $kart->text($metin, self::KENAR + (int) ($genislik / 2), $y + (int) ($yukseklik / 2),
            function (FontFactory $f) use ($font, $boyut): void {
                $f->filename($font);
                $f->size($boyut);
                $f->color('#ffffff');
                $f->align('center', 'center');
            });
    }

    private function fiyatMetni(Listing $listing): string
    {
        if ($listing->price === null || $listing->price_unit === PriceUnit::Gorusulur) {
            return 'Fiyat görüşülür';
        }

        return number_format((float) $listing->price, 0, ',', '.')
            .' '.$listing->currency
            .($listing->price_unit?->suffix() ?? '');
    }

    /**
     * Kapak adayı: önce `is_cover` işaretli görsel, yoksa sıradaki ilk görsel.
     *
     * İlişkiye `getRelationValue()` ile erişip `instanceof` ile daraltıyoruz.
     * Sebep tip güvenliği: statik analiz Eloquent ilişkilerini NON-NULL sayıyor
     * ve `?->` kullanımını gereksiz sanıyor, oysa ilanın kapak görseli de
     * kategorisi de gerçekten yok olabilir (`category_id` nullOnDelete).
     * `instanceof` ikisini birden çözüyor: analiz de doğru sonucu görüyor,
     * çalışma zamanı da null'a karşı korunuyor.
     */
    private function kapakGorseli(Listing $listing): ?ListingImage
    {
        $kapak = $listing->getRelationValue('coverImage');

        if ($kapak instanceof ListingImage) {
            return $kapak;
        }

        $ilk = $listing->images->first();

        return $ilk instanceof ListingImage ? $ilk : null;
    }

    private function kategoriAdi(Listing $listing): ?string
    {
        $kategori = $listing->getRelationValue('category');

        return $kategori instanceof Category ? $kategori->name : null;
    }

    private function konumMetni(Listing $listing): string
    {
        // Kolon adı `name_tr` — `name` diye bir alan YOK ve sessizce null döner
        // (ilk sürümde kartta ülke hiç görünmüyordu, gözle yakalandı).
        //
        // Ülke emojisi BİLEREK yok: bayrak emojileri regional-indicator çiftidir
        // ve gömdüğümüz DejaVu Sans Bold onları basamaz — sitedeki gibi bayrak
        // koymaya kalksak kartta iki boş kutu çıkardı.
        $parcalar = array_filter([
            $listing->city,
            $listing->country?->name_tr,
        ]);

        return implode(' · ', $parcalar);
    }

    /**
     * Metni tahmini glif genişliğiyle satırlara böler.
     *
     * DemoGorselUretici'deki yaklaşımın aynısı ve aynı gerekçeyle tahmini:
     * FreeType gerçek ölçüm verebilirdi (imagettfbbox) ama başlıklar kısa,
     * panel geniş ve taşma kırpılmıyor. Tek fark satır üst sınırının
     * parametre olması — burada 3 satır sığıyor, demo tuvalinde 2.
     *
     * @return list<string>
     */
    private function satirlara(string $metin, int $boyut, int $azamiGenislik, int $azamiSatir): array
    {
        $metin = trim($metin) === '' ? 'Nisoya ilanı' : trim($metin);
        // DejaVu Sans Bold ortalama glif genişliği ~0.62 em (ölçüldü, kaba).
        $sigar = max(4, (int) floor($azamiGenislik / ($boyut * 0.62)));

        if (mb_strlen($metin) <= $sigar) {
            return [$metin];
        }

        $satirlar = [''];

        foreach (explode(' ', $metin) as $kelime) {
            $aday = trim($satirlar[count($satirlar) - 1].' '.$kelime);

            if (mb_strlen($aday) <= $sigar || $satirlar[count($satirlar) - 1] === '') {
                $satirlar[count($satirlar) - 1] = $aday;

                continue;
            }

            $satirlar[] = $kelime;
        }

        if (count($satirlar) > $azamiSatir) {
            $son = mb_substr(implode(' ', array_slice($satirlar, $azamiSatir - 1)), 0, $sigar - 1).'…';
            $satirlar = array_slice($satirlar, 0, $azamiSatir - 1);
            $satirlar[] = $son;
        }

        return $satirlar;
    }

    private function fontYolu(): string
    {
        $yol = resource_path('fonts/DejaVuSans-Bold.ttf');

        if (! is_file($yol)) {
            throw new RuntimeException('Paylaşım kartı fontu bulunamadı: '.$yol);
        }

        return $yol;
    }
}

<?php

namespace App\Services\Medya;

use App\Models\MediaAsset;
use App\Models\MediaRendition;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Ana kopyadan slot türevi üretir.
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR SERVİS (ImageService'e eklenmedi)
 *
 * `ImageService` GENİŞLİK tabanlı: `scaleDown(width: N)` ile thumb/medium/large
 * üretir, en-boy oranına karışmaz. Slot ise bir KUTU ister (2400×1200) ve
 * taşanın nereden kırpılacağına karar vermek gerekir. İkisi farklı iş.
 *
 * ImageService'e dokunulmadı çünkü ilan görselleri onun üzerinden çalışıyor ve
 * çalışan bir boru hattını yeni bir gereksinim için genişletmek, ikisini birden
 * riske atardı. Ondan DEVRALINAN üç desen var ve üçü de bilinçli:
 *   1. EXIF yön düzeltmesi (telefon fotoğrafı yan yatmasın),
 *   2. WebP + `strip: true` (metadata sızmasın),
 *   3. `Storage::put()` DÖNÜŞÜNÜN KONTROLÜ — public disk `throw => false` ile
 *      yapılandırılı; disk dolduğunda put() sessizce false döner ve DB hiç
 *      yazılmamış bir dosyayı gösterir (2026-07-29'da canlıda tam bu yaşandı).
 */
class MedyaTuretici
{
    /**
     * Ağırlık hedefi tutmazsa denenecek kalite kademeleri.
     *
     * Sırayla düşülür; en düşükte de tutmazsa dosya YİNE DE yazılır ve çağıran
     * tarafa "hedef tutmadı" bilgisi döner. Sessizce reddetmek de sessizce
     * devasa dosya bırakmak da yanlış olurdu — panel uyarır, sahip karar verir.
     */
    private const KALITE_KADEMELERI = [80, 70, 60];

    /** Bellek koruması: bundan büyük görsel GD'de açılmaz. */
    private const AZAMI_PIKSEL = 50_000_000;

    public function __construct(private readonly ImageService $imageService) {}

    /**
     * Bir slot için türev üretir (varsa üzerine yazar) ve kaydı döndürür.
     *
     * @throws RuntimeException slot tanımsızsa ya da dosya okunamazsa
     */
    public function turet(MediaAsset $asset, string $slot): MediaRendition
    {
        $spec = config("media_slots.{$slot}");

        if (! is_array($spec)) {
            throw new RuntimeException("Tanımsız slot: {$slot}");
        }

        $kaynak = Storage::disk('local')->path($asset->yol);

        if (! is_file($kaynak)) {
            throw new RuntimeException("Ana kopya bulunamadı: {$asset->yol}");
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->decode($kaynak);

        if ($image->width() * $image->height() > self::AZAMI_PIKSEL) {
            throw new RuntimeException('Görsel çok büyük (50 megapiksel üstü).');
        }

        // Telefon fotoğrafı yan yatmasın — ImageService ile aynı düzeltme.
        $this->imageService->applyExifOrientation($image);

        $hedefEn = (int) ($spec['en'] ?? 0);
        $hedefBoy = (int) ($spec['boy'] ?? 0);
        $kip = $spec['kip'] ?? 'sigdir';

        if ($kip === 'kapla' && $hedefEn > 0 && $hedefBoy > 0) {
            // ?? 50 — yeni sistemden ÖNCE oluşmuş kayıtlarda alan boş olabilir.
            $this->kapla($image, $hedefEn, $hedefBoy, (int) ($asset->odak_x ?? 50), (int) ($asset->odak_y ?? 50));
        } elseif ($hedefEn > 0) {
            // SIĞDIR — kırpma yok. Büyütme de yok: `scaleDown` küçük görseli
            // şişirmez (bulanıklığı gizlemez, yalnız dosyayı büyütür).
            $image->scaleDown(width: $hedefEn, height: $hedefBoy ?: null);
        }

        [$icerik, $kalite, $hedefTuttu] = $this->kodla($image, (int) ($spec['azami_kb'] ?? 0));

        $yol = 'medya/'.$slot.'/'.Str::uuid()->toString().'.webp';

        if (! Storage::disk('public')->put($yol, $icerik)) {
            throw new RuntimeException("Türev diske yazılamadı: {$slot}");
        }

        // Eski türevin dosyası artık çöp — kayıt üzerine yazılmadan ÖNCE silinir.
        $eski = $asset->renditions()->where('slot', $slot)->first();
        if ($eski && $eski->yol !== $yol) {
            Storage::disk('public')->delete($eski->yol);
        }

        // NOT: `$hedefTuttu` burada modele ATANMAZ. Eloquent'te tanımsız bir
        // özelliğe yazmak onu attribute'a çevirir ve ilk save()'de "bilinmeyen
        // sütun" hatası verir. Çağıran taraf ihtiyacı olan cevabı türevin
        // kendisine sorar: MediaRendition::hedefiTutuyorMu().
        unset($hedefTuttu);

        return $asset->renditions()->updateOrCreate(
            ['slot' => $slot],
            [
                'yol' => $yol,
                'en' => $image->width(),
                'boy' => $image->height(),
                'bayt' => strlen($icerik),
                'bicim' => 'webp',
                'kalite' => $kalite,
            ],
        );
    }

    /**
     * Kutuyu doldur, taşanı ODAK NOKTASINI merkeze almaya çalışarak kır.
     *
     * Intervention'ın hazır `cover()`'ı sabit hizalama alır ("center", "top"…);
     * bize yüzdelik odak lazım, bu yüzden ölçek + kırpım elle hesaplanıyor.
     */
    private function kapla($image, int $hedefEn, int $hedefBoy, int $odakX, int $odakY): void
    {
        /*
         * ÖNCE ORAN, SONRA BOYUT — sıra bu, ve sebebi bir kusurla öğrenildi.
         *
         * İlk yazımda önce ölçekleniyor, sonra `min(hedef, mevcut)` ile
         * kırpılıyordu. Yatay bir ana kopyadan (3000×1500) dikey mobil kare
         * (1080×1620) istendiğinde kaynak yeterince UZUN olmadığı için sonuç
         * 1080×1500 çıkıyordu: slot 2:3 isterken türev 1:1.39 oluyordu. Yani
         * kırpma "büyütme yapma" kuralına uyuyor ama SLOTUN ORANINI SESSİZCE
         * DEĞİŞTİRİYORDU — mobil hero yanlış oranda basılırdı.
         *
         * Doğrusu: hedef orana sahip, kaynağa sığan EN BÜYÜK dikdörtgeni kırp;
         * gerekiyorsa sonra küçült. Böylece oran her zaman doğru, büyütme hiç
         * yok — kaynak yetersizse türev yalnızca daha küçük olur.
         */
        $hedefOran = $hedefEn / $hedefBoy;
        $kaynakOran = $image->width() / $image->height();

        if ($kaynakOran > $hedefOran) {
            // Kaynak daha geniş: yükseklik tam kullanılır, genişlik kırpılır.
            $boy = $image->height();
            $en = (int) round($boy * $hedefOran);
        } else {
            // Kaynak daha dar/uzun: genişlik tam kullanılır, yükseklik kırpılır.
            $en = $image->width();
            $boy = (int) round($en / $hedefOran);
        }

        $en = min($en, $image->width());
        $boy = min($boy, $image->height());

        // Odak noktasını kırpım penceresinin ORTASINA getir, sonra pencereyi
        // görselin içinde kalacak şekilde sıkıştır (clamp).
        $x = (int) round($image->width() * ($odakX / 100) - $en / 2);
        $y = (int) round($image->height() * ($odakY / 100) - $boy / 2);

        $x = max(0, min($x, $image->width() - $en));
        $y = max(0, min($y, $image->height() - $boy));

        $image->crop($en, $boy, $x, $y);

        /*
         * Küçültme yalnız gerekiyorsa; BÜYÜTME YOK.
         *
         * `resize()` (tam boyut) kullanılıyor, `scaleDown()` değil: kırpım
         * zaten hedef oranda ama kenarlar tam sayıya yuvarlandığı için oran
         * binde birlik sapabiliyor; `scaleDown` bu sapmayı koruyup 2400 yerine
         * 2399 üretiyordu. Slotun sözleşmesi TAM KUTU — yüzeyler boyuta
         * güvenebilmeli. Yuvarlamadan doğan <%0,1'lik esneme görünmez.
         *
         * Kaynak küçükse hiç dokunulmaz: türev daha küçük ama ORANI doğru olur.
         */
        if ($image->width() >= $hedefEn && $image->height() >= $hedefBoy) {
            $image->resize($hedefEn, $hedefBoy);
        }
    }

    /**
     * WebP'ye kodlar; ağırlık hedefini tutturmak için kaliteyi kademeli düşürür.
     *
     * @return array{0: string, 1: int, 2: bool} içerik, kullanılan kalite, hedef tuttu mu
     */
    private function kodla($image, int $azamiKb): array
    {
        $icerik = '';
        $kalite = self::KALITE_KADEMELERI[0];

        foreach (self::KALITE_KADEMELERI as $k) {
            $kalite = $k;
            $icerik = (string) $image->encode(new WebpEncoder(quality: $k, strip: true));

            if ($azamiKb <= 0 || strlen($icerik) <= $azamiKb * 1024) {
                return [$icerik, $kalite, true];
            }
        }

        // En düşük kalitede de tutmadı: dosya yine de yazılır, çağıran uyarır.
        return [$icerik, $kalite, false];
    }
}

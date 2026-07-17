<?php

namespace App\Services;

use App\Models\ListingImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Yüklenen görselleri WebP olarak optimize eder, EXIF orientation düzeltir
 * ve tüm metadata'yı (GPS, kamera, yazılım) temizler.
 *
 * Gizlilik: Orijinal EXIF'te kullanıcının GPS koordinatları, cihaz bilgisi,
 * çekim zamanı gibi hassas veriler bulunabilir. WebP'ye dönüşüm sırasında
 * bu metadata kaybolur (WebP spec EXIF desteklese de biz encode ederken
 * dahil etmiyoruz).
 *
 * Orientation: Telefonla çekilen fotoğraflarda EXIF Orientation tag'i 1-8 arası
 * değer alır. Tarayıcılar bu tag'i yok sayar → dikey çekilmiş fotoğraf yatay
 * görünür. Intervention ile fiziksel olarak rotate/flip uyguluyoruz.
 *
 * Varyantlar:
 *   - thumb  : 300px genişlik (kart listeleri)
 *   - medium : 800px genişlik (detay sayfası küçük)
 *   - large  : 1600px genişlik (orijinal gösterim, lightbox)
 */
class ImageService
{
    public const VARIANTS = [
        'thumb' => 300,
        'medium' => 800,
        'large' => 1600,
    ];

    public const DEFAULT_QUALITY = 80;

    /**
     * Yüklenen görseli tüm varyantlarda WebP olarak sakla.
     * EXIF orientation düzeltilir, metadata temizlenir.
     *
     * Dönen 'exif_metadata' audit amaçlıdır: sadece izinli alanlar (kamera, çekim
     * ayarları, GPS koordinatları) tutulur. Diğer her şey (kullanıcı yorumu,
     * seri numarası vb.) filtrelenir. Sadece admin paneli tarafından
     * erişilebilir.
     *
     * @return array{
     *     thumb: string,
     *     medium: string,
     *     large: string,
     *     orientation_corrected: bool,
     *     original_dimensions: array{width: int, height: int},
     *     exif_metadata: array<string, mixed>,
     *     had_gps: bool
     * }
     */
    public function storeOptimized(UploadedFile $file, string $dir, int $maxWidth = 1600, int $quality = self::DEFAULT_QUALITY): array
    {
        return $this->processImage($file->getRealPath(), $dir, $maxWidth, $quality);
    }

    /**
     * storeOptimized() ile aynı işleme mantığı, ancak zaten diskte duran bir
     * dosya path'inden çalışır. Kuyruklanmış görsel işleme (ProcessListingImage
     * job'ı) tarafından kullanılır — kaynak dosya bir UploadedFile değil,
     * geçici/özel diskteki ham yükleme.
     *
     * @return array{
     *     thumb: string,
     *     medium: string,
     *     large: string,
     *     orientation_corrected: bool,
     *     original_dimensions: array{width: int, height: int},
     *     exif_metadata: array<string, mixed>,
     *     had_gps: bool
     * }
     */
    public function storeOptimizedFromPath(string $realPath, string $dir, int $maxWidth = 1600, int $quality = self::DEFAULT_QUALITY): array
    {
        return $this->processImage($realPath, $dir, $maxWidth, $quality);
    }

    private function processImage(string $realPath, string $dir, int $maxWidth, int $quality): array
    {
        $manager = new ImageManager(new Driver);

        try {
            $image = $manager->decode($realPath);

            // 1. EXIF metadata'sını orijinalden oku (orientation uygulanmadan)
            $rawExif = $image->exif()->toArray();

            // 2. EXIF orientation'ı fiziksel olarak uygula
            $orientationCorrected = $this->applyExifOrientation($image);

            $originalDims = [
                'width' => $image->width(),
                'height' => $image->height(),
            ];

            // 3. EXIF'i temizle (sadece izinli alanları audit için tut)
            $sanitizedExif = ListingImage::sanitizeExifForAudit($rawExif);
            $hadGps = isset($sanitizedExif['GPSLatitude']);
            $hasSensitiveExif = ListingImage::hasSensitiveExif($sanitizedExif);
            $gps = ListingImage::extractGpsFromExif($sanitizedExif);

            // 4. Her varyantı üret
            $paths = [];
            $uuid = Str::uuid()->toString();

            foreach (self::VARIANTS as $variant => $width) {
                $clone = clone $image;

                if ($clone->width() > $width) {
                    $clone->scaleDown(width: $width);
                }

                $path = $dir.'/'.$variant.'/'.$uuid.'.webp';
                Storage::disk('public')->put($path, (string) $clone->encode(new WebpEncoder(quality: $quality, strip: true)));
                $paths[$variant] = $path;
            }

            unset($image);

            return [
                'thumb' => $paths['thumb'],
                'medium' => $paths['medium'],
                'large' => $paths['large'],
                'orientation_corrected' => $orientationCorrected,
                'original_dimensions' => $originalDims,
                'exif_metadata' => $sanitizedExif,
                'had_gps' => $hadGps,
                'has_sensitive_exif' => $hasSensitiveExif,
                'gps_lat' => $gps['lat'],
                'gps_lng' => $gps['lng'],
            ];
        } catch (\Throwable $e) {
            // Gizlilik: işleme başarısız olursa ASLA ham (EXIF/GPS temizlenmemiş)
            // dosyayı "güvenli" bir varyantmış gibi yayınlama. Yarım kalmış
            // varyantları temizle ve hatayı yukarı ilet — yükleme başarısız olsun,
            // sızıntı olmasın.
            foreach ($paths ?? [] as $partial) {
                Storage::disk('public')->delete($partial);
            }

            Log::error('Görsel işleme başarısız oldu (varyant/EXIF üretimi)', [
                'dir' => $dir,
                'exception' => $e->getMessage(),
            ]);

            throw new RuntimeException('Görsel işlenemedi, lütfen başka bir dosyayla tekrar deneyin.', previous: $e);
        }
    }

    /**
     * EXIF Orientation tag'ini oku ve fiziksel olarak uygula.
     */
    public function applyExifOrientation($image): bool
    {
        try {
            $exif = $image->exif()->toArray();

            if (! isset($exif['Orientation'])) {
                return false;
            }

            $orientation = (int) $exif['Orientation'];

            if ($orientation === 1) {
                return false;
            }

            if (in_array($orientation, [3, 4], true)) {
                $image->rotate(180);
            } elseif (in_array($orientation, [5, 6], true)) {
                $image->rotate(-90);
            } elseif (in_array($orientation, [7, 8], true)) {
                $image->rotate(90);
            }

            if (in_array($orientation, [2, 4, 5, 7], true)) {
                $image->flip();
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Yüklenen görselden tüm EXIF metadata'sını temizle.
     */
    public function stripExif(string $sourcePath, string $outputPath): bool
    {
        $manager = new ImageManager(new Driver);

        try {
            $image = $manager->decode(Storage::disk('public')->path($sourcePath));
            $image->save(Storage::disk('public')->path($outputPath));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Bir görselin EXIF metadata'sından GPS koordinatlarını çıkar.
     *
     * @return array{lat: ?float, lng: ?float, has_gps: bool}
     */
    public function extractGpsCoordinates(UploadedFile $file): array
    {
        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->decode($file->getRealPath());
            $exif = $image->exif()->toArray();

            if (! isset($exif['GPSLatitude']) || ! isset($exif['GPSLongitude'])) {
                return ['lat' => null, 'lng' => null, 'has_gps' => false];
            }

            $lat = $this->gpsToDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
            $lng = $this->gpsToDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');

            return ['lat' => $lat, 'lng' => $lng, 'has_gps' => true];
        } catch (\Throwable) {
            return ['lat' => null, 'lng' => null, 'has_gps' => false];
        }
    }

    private function gpsToDecimal(array $dms, string $ref): float
    {
        $degrees = $this->gpsFraction($dms[0] ?? 0);
        $minutes = $this->gpsFraction($dms[1] ?? 0);
        $seconds = $this->gpsFraction($dms[2] ?? 0);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        return in_array($ref, ['S', 'W'], true) ? -$decimal : $decimal;
    }

    private function gpsFraction($value): float
    {
        if (is_array($value)) {
            return $value[0] / max($value[1] ?? 1, 1);
        }

        return (float) $value;
    }

    /**
     * Mevcut bir görseli tüm varyantlara dönüştür (toplu geriye uyumluluk).
     */
    public function generateVariants(string $sourcePath, string $dir): ?array
    {
        if (! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        $manager = new ImageManager(new Driver);
        $fullPath = Storage::disk('public')->path($sourcePath);

        try {
            $image = $manager->decode($fullPath);
            $this->applyExifOrientation($image);
        } catch (\Throwable) {
            return null;
        }

        $paths = [];
        $uuid = Str::uuid()->toString();

        foreach (self::VARIANTS as $variant => $width) {
            $clone = clone $image;
            if ($clone->width() > $width) {
                $clone->scaleDown(width: $width);
            }
            $path = $dir.'/'.$variant.'/'.$uuid.'.webp';
            Storage::disk('public')->put($path, (string) $clone->encode(new WebpEncoder(quality: self::DEFAULT_QUALITY, strip: true)));
            $paths[$variant] = $path;
        }

        return $paths;
    }

    /**
     * Tek varyantlı optimize görsel sakla (sohbet ekleri gibi 3 boyut
     * gerektirmeyen yerler için). EXIF orientation uygulanır, tüm metadata
     * (GPS dahil) strip edilir — sohbette paylaşılan fotoğraftan konum sızmaz.
     * İşlenmiş webp yolunu döndürür.
     */
    public function storeSingle(UploadedFile $file, string $dir, int $maxWidth = 1280, int $quality = self::DEFAULT_QUALITY): string
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->decode($file->getRealPath());
        $this->applyExifOrientation($image);

        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $path = $dir.'/'.Str::uuid()->toString().'.webp';
        Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(quality: $quality, strip: true)));

        return $path;
    }

    /**
     * Public diskteki bir görselden KARE kırpım üretir (avatar hizalama).
     * $x/$y/$size kaynak görselin piksel koordinatlarındadır; sınırlar
     * burada da doğrulanır (istemciden gelen değere körü körüne güvenme).
     * Kaynak zaten işlenmiş (EXIF'i temizlenmiş) bir dosya olduğundan ek
     * metadata temizliği gerekmez ama encode yine strip ile yapılır.
     * İşlenmiş webp yolunu döndürür; kaynak okunamaz ya da kırpım sınır
     * dışıysa RuntimeException fırlatır.
     */
    public function cropSquare(string $sourcePath, int $x, int $y, int $size, string $destDir, int $maxWidth = 400): string
    {
        if (! Storage::disk('public')->exists($sourcePath)) {
            throw new RuntimeException('Kaynak görsel bulunamadı: '.$sourcePath);
        }

        $manager = new ImageManager(new Driver);

        try {
            $image = $manager->decode(Storage::disk('public')->path($sourcePath));
        } catch (\Throwable $e) {
            throw new RuntimeException('Kaynak görsel okunamadı.', previous: $e);
        }

        if ($size < 1 || $x < 0 || $y < 0 || $x + $size > $image->width() || $y + $size > $image->height()) {
            throw new RuntimeException('Kırpım karesi görsel sınırlarının dışında.');
        }

        $image->crop($size, $size, $x, $y);

        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $path = $destDir.'/'.Str::uuid()->toString().'.webp';
        Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(quality: self::DEFAULT_QUALITY, strip: true)));

        return $path;
    }

    /**
     * Bir görselin tüm varyantlarını disk'ten sil.
     */
    public function deleteVariants(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * storeOptimized() tarafından "{dir}/{variant}/{uuid}.webp" biçiminde üretilen
     * tek bir varyant path'inden diğer kardeş varyantların path'lerini türetir.
     * Sadece tek path kolonu tutan modeller (ör. User::avatar_path) için —
     * üç path'i de ayrı kolonlarda saklayan ListingImage bunun yerine kendi
     * variantPaths() metodunu kullanır.
     *
     * @return array<string, string> ör. ['thumb' => ..., 'medium' => ..., 'large' => ...]
     */
    public function siblingVariantPaths(string $anyVariantPath): array
    {
        $variants = array_keys(self::VARIANTS);
        $pattern = '#/('.implode('|', $variants).')/#';

        if (! preg_match($pattern, $anyVariantPath, $match)) {
            return [$anyVariantPath => $anyVariantPath];
        }

        $paths = [];
        foreach ($variants as $variant) {
            $paths[$variant] = preg_replace($pattern, '/'.$variant.'/', $anyVariantPath, 1);
        }

        return $paths;
    }

    /**
     * Tek bir görseli AVIF formatına dönüştür.
     */
    public function toAvif(string $sourcePath, string $outputPath, int $quality = 70): bool
    {
        if (! function_exists('imageavif')) {
            return false;
        }

        $manager = new ImageManager(new Driver);

        try {
            $image = $manager->decode(Storage::disk('public')->path($sourcePath));
            $encoded = $image->encode(new AvifEncoder(quality: $quality, strip: true));
            Storage::disk('public')->put($outputPath, (string) $encoded);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Bir görselin orijinal EXIF metadata'sını döndür.
     *
     * @return array<string, mixed>
     */
    public function getExifMetadata(UploadedFile $file): array
    {
        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->decode($file->getRealPath());

            return $image->exif()->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}

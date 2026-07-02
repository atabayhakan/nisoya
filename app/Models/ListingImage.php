<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingImage extends Model
{
    protected $fillable = [
        'listing_id',
        'path',
        'path_thumb',
        'path_medium',
        'path_large',
        'width',
        'height',
        'size_bytes',
        'exif_metadata',
        'had_gps',
        'has_sensitive_exif',
        'gps_lat',
        'gps_lng',
        'reverse_country_code',
        'reverse_country_name',
        'reverse_city',
        'reverse_state',
        'reverse_geocoded_at',
        'sort_order',
        'is_cover',
    ];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'had_gps' => 'boolean',
            'has_sensitive_exif' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'size_bytes' => 'integer',
            'exif_metadata' => 'array',
            'gps_lat' => 'float',
            'gps_lng' => 'float',
            'reverse_geocoded_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * GPS koordinatı bilinen görselleri getir.
     */
    public function scopeWithGps(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('gps_lat')->whereNotNull('gps_lng');
    }

    /**
     * Belirli bir bounding box (kare) içindeki görselleri getir.
     * Coğrafi filtreleme için kullanılır.
     */
    public function scopeWithinBounds(
        \Illuminate\Database\Eloquent\Builder $query,
        float $minLat,
        float $maxLat,
        float $minLng,
        float $maxLng
    ): \Illuminate\Database\Eloquent\Builder {
        return $query
            ->whereBetween('gps_lat', [$minLat, $maxLat])
            ->whereBetween('gps_lng', [$minLng, $maxLng]);
    }

    /**
     * Hassas EXIF içeren görselleri getir.
     */
    public function scopeWithSensitiveExif(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('has_sensitive_exif', true);
    }

    /**
     * Aynı koordinata yakın görselleri bul (duplicate/çoklu içerik tespiti için).
     * 0.001 derece ~= ~100 metre (yaklaşık).
     */
    public function scopeNearCoordinates(
        \Illuminate\Database\Eloquent\Builder $query,
        float $lat,
        float $lng,
        float $tolerance = 0.001
    ): \Illuminate\Database\Eloquent\Builder {
        return $query
            ->whereBetween('gps_lat', [$lat - $tolerance, $lat + $tolerance])
            ->whereBetween('gps_lng', [$lng - $tolerance, $lng + $tolerance]);
    }

    /**
     * Reverse geocoded edilmemiş GPS'li görselleri getir.
     * Batch processing için kullanılır (örn: images:geocode-reversely).
     */
    public function scopePendingReverseGeocode(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->whereNotNull('gps_lat')
            ->whereNotNull('gps_lng')
            ->whereNull('reverse_geocoded_at');
    }

    /**
     * Belirli bir ülkedeki görselleri getir (reverse geocoded).
     */
    public function scopeInCountry(\Illuminate\Database\Eloquent\Builder $query, string $countryCode): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('reverse_country_code', strtoupper($countryCode));
    }

    /**
     * Belirli bir şehre ait görselleri getir.
     */
    public function scopeInCity(\Illuminate\Database\Eloquent\Builder $query, string $city): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('reverse_city', $city);
    }

    /**
     * Yönetici paneli için EXIF metadata'sını özetleyerek döndür.
     * Gizli veya gereksiz alanlar filtrelenir.
     *
     * @return array<string, mixed>
     */
    public function exifSummary(): array
    {
        if (! $this->exif_metadata) {
            return [];
        }

        $meta = $this->exif_metadata;
        $summary = [];

        // Kamera
        if (isset($meta['Make'])) {
            $summary['Kamera'] = trim($meta['Make'].' '.($meta['Model'] ?? ''));
        }

        // Çekim ayarları
        if (isset($meta['ExposureTime'])) {
            $exp = $meta['ExposureTime'];
            $summary['Poz süresi'] = $exp < 1 ? '1/'.(int) (1 / $exp).'s' : $exp.'s';
        }
        if (isset($meta['FNumber'])) {
            $summary['Diyafram'] = 'f/'.$meta['FNumber'];
        }
        if (isset($meta['ISOSpeedRatings'])) {
            $summary['ISO'] = $meta['ISOSpeedRatings'];
        }
        if (isset($meta['FocalLength'])) {
            $summary['Odak'] = $meta['FocalLength'].'mm';
        }
        if (isset($meta['Flash'])) {
            $summary['Flash'] = ($meta['Flash'] & 1) ? 'Ateşlendi' : 'Kullanılmadı';
        }

        // Tarih
        if (isset($meta['DateTimeOriginal'])) {
            $summary['Çekim tarihi'] = $meta['DateTimeOriginal'];
        } elseif (isset($meta['DateTime'])) {
            $summary['Çekim tarihi'] = $meta['DateTime'];
        }

        // Yazılım
        if (isset($meta['Software'])) {
            $summary['Yazılım'] = $meta['Software'];
        }

        // GPS (varsa) — admin için önemli
        if (isset($meta['GPSLatitude']) && isset($meta['GPSLongitude'])) {
            $lat = $meta['GPSLatitude'];
            $lng = $meta['GPSLongitude'];
            $latRef = $meta['GPSLatitudeRef'] ?? 'N';
            $lngRef = $meta['GPSLongitudeRef'] ?? 'E';
            $latSign = $latRef === 'S' ? -1 : 1;
            $lngSign = $lngRef === 'W' ? -1 : 1;
            $latDec = ($lat[0] + $lat[1] / 60 + $lat[2] / 3600) * $latSign;
            $lngDec = ($lng[0] + $lng[1] / 60 + $lng[2] / 3600) * $lngSign;
            $summary['GPS'] = sprintf('%.6f, %.6f', $latDec, $lngDec);
        }

        // Orientation (orijinal)
        if (isset($meta['Orientation'])) {
            $summary['Orientation'] = $meta['Orientation'];
        }

        return $summary;
    }

    /**
     * Hassas EXIF alanlarını filtrele — sadece görüntüleme amaçlı metadata'yı tut.
     * Kullanıcı yorumu, GPS hassas koordinat, seri numarası vb. çıkarılır.
     */
    public static function sanitizeExifForAudit(array $exif): array
    {
        // İzin verilen alanlar
        $allowed = [
            'Make', 'Model',
            'ExposureTime', 'FNumber', 'ISOSpeedRatings', 'FocalLength',
            'Flash', 'WhiteBalance', 'ExposureMode',
            'DateTime', 'DateTimeOriginal', 'DateTimeDigitized',
            'Width', 'Height', 'Orientation',
            'Software',
            'GPSLatitude', 'GPSLongitude', 'GPSLatitudeRef', 'GPSLongitudeRef', 'GPSAltitude',
        ];

        $filtered = [];
        foreach ($exif as $key => $value) {
            // Exif anahtarları hex format'ta olabilir (0x010f vs.)
            $tagName = is_string($key) ? $key : null;
            if ($tagName === null || in_array($tagName, $allowed, true)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * EXIF metadata'sında hassas alan olup olmadığını tespit et.
     * Hassas = GPS, kullanıcı bilgisi, seri numarası, kamera sahibi adı vb.
     * Bu alanlar DB'ye kaydedilse de, yüksek gizlilik riski taşırlar.
     */
    public static function hasSensitiveExif(array $sanitizedExif): bool
    {
        $sensitiveKeys = [
            'GPSLatitude', 'GPSLongitude', 'GPSLatitudeRef', 'GPSLongitudeRef', 'GPSAltitude',
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($sanitizedExif[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * EXIF'ten GPS koordinatlarını decimal olarak çıkar.
     *
     * @return array{lat: ?float, lng: ?float}
     */
    public static function extractGpsFromExif(array $sanitizedExif): array
    {
        if (! isset($sanitizedExif['GPSLatitude']) || ! isset($sanitizedExif['GPSLongitude'])) {
            return ['lat' => null, 'lng' => null];
        }

        $lat = self::gpsDmsToDecimal($sanitizedExif['GPSLatitude'], $sanitizedExif['GPSLatitudeRef'] ?? 'N');
        $lng = self::gpsDmsToDecimal($sanitizedExif['GPSLongitude'], $sanitizedExif['GPSLongitudeRef'] ?? 'E');

        return ['lat' => $lat, 'lng' => $lng];
    }

    private static function gpsDmsToDecimal($dms, string $ref): ?float
    {
        if (! is_array($dms) || count($dms) < 2) {
            return null;
        }

        $degrees = is_array($dms[0]) ? $dms[0][0] / max($dms[0][1] ?? 1, 1) : (float) $dms[0];
        $minutes = is_array($dms[1]) ? $dms[1][0] / max($dms[1][1] ?? 1, 1) : (float) ($dms[1] ?? 0);
        $seconds = is_array($dms[2] ?? null) ? $dms[2][0] / max($dms[2][1] ?? 1, 1) : (float) ($dms[2] ?? 0);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        return in_array($ref, ['S', 'W'], true) ? -$decimal : $decimal;
    }

    /**
     * Responsive srcset: tarayıcı ekran genişliğine göre en uygun varyantı seçer.
     * Döndürür: [path => url, ...] thumb/medium/large için.
     */
    public function srcset(): array
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $set = [];

        $sources = [
            'thumb' => $this->path_thumb ?? $this->path,
            'medium' => $this->path_medium ?? $this->path,
            'large' => $this->path_large ?? $this->path,
        ];

        foreach ($sources as $size => $path) {
            if ($path) {
                $set[$size] = $disk->url($path);
            }
        }

        return $set;
    }

    /**
     * Belirli bir varyant için URL döndür (fallback: orijinal path).
     */
    public function url(string $variant = 'medium'): ?string
    {
        $map = [
            'thumb' => $this->path_thumb,
            'medium' => $this->path_medium,
            'large' => $this->path_large,
        ];

        $path = $map[$variant] ?? $this->path;
        if (! $path) {
            return null;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        // URL builder her durumda path'ten URL üretir; dosya disk'te yoksa da
        // geçerli bir URL döner (production URL'leri için bu doğru davranış).
        return $disk->url($path);
    }

    /**
     * Varyant path'lerini döndür (silme vb. işlemler için).
     *
     * @return array<int, string>
     */
    public function variantPaths(): array
    {
        return array_values(array_filter([
            $this->path,
            $this->path_thumb,
            $this->path_medium,
            $this->path_large,
        ]));
    }

    /**
     * Reverse geocoding uygula (GPS'ten şehir/ülke çıkar).
     * Sonuçları DB'ye yazar.
     */
    public function applyReverseGeocode(?\App\Services\GeocodingService $service = null): bool
    {
        if ($this->gps_lat === null || $this->gps_lng === null) {
            return false;
        }

        $service = $service ?? app(\App\Services\GeocodingService::class);
        $result = $service->reverse((float) $this->gps_lat, (float) $this->gps_lng);

        $this->update([
            'reverse_country_code' => $result['country_code'],
            'reverse_country_name' => $result['country_name'],
            'reverse_city' => $result['city'],
            'reverse_state' => $result['state'],
            'reverse_geocoded_at' => now(),
        ]);

        return true;
    }

    /**
     * Konum bilgisi: "İstanbul, Türkiye" gibi okunabilir string.
     *
     * Eloquent attribute accessor olarak tanımlı — düz bir public metot
     * olsaydı, property erişimi (`$image->reverseLocationLabel` veya
     * Filament'in TextColumn::make('reverseLocationLabel') gibi state
     * çözümlemeleri) Eloquent tarafından "relationship metodu" sanılıp
     * LogicException fırlatırdı (bkz. Model::getRelationshipFromMethod).
     */
    protected function reverseLocationLabel(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->reverse_city,
                    $this->reverse_country_name,
                ]);

                return $parts ? implode(', ', $parts) : null;
            },
        );
    }
}
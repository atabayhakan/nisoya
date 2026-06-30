<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeocodingService
{
    /**
     * Şehir + ülkeden koordinat bulur. Nominatim (OSM) kullanır, sonucu önbelleğe alır;
     * başarısızsa veya şehir yoksa ülke merkezine düşer. Testte ağ kullanmaz.
     *
     * @return array{latitude: float|null, longitude: float|null}
     */
    public function locate(?string $city, ?string $countryCode): array
    {
        $fallback = $this->countryFallback($countryCode);
        $city = trim((string) $city);

        if ($city === '' || ! $countryCode || app()->runningUnitTests()) {
            return $fallback;
        }

        $country = Country::find($countryCode);
        $countryName = $country?->name_tr ?? $countryCode;
        $key = 'geo:'.Str::slug($city).':'.$countryCode;

        return Cache::remember($key, now()->addDays(30), function () use ($city, $countryName, $fallback) {
            try {
                $res = Http::withHeaders(['User-Agent' => 'Nisoya/1.0 (+https://nisoya.com)'])
                    ->timeout(6)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $city.', '.$countryName,
                        'format' => 'json',
                        'limit' => 1,
                    ]);

                if ($res->ok() && isset($res->json()[0]['lat'])) {
                    $hit = $res->json()[0];

                    return ['latitude' => (float) $hit['lat'], 'longitude' => (float) $hit['lon']];
                }
            } catch (\Throwable $e) {
                // Sessizce ülke merkezine düş.
            }

            return $fallback;
        });
    }

    /** @return array{latitude: float|null, longitude: float|null} */
    protected function countryFallback(?string $countryCode): array
    {
        $country = $countryCode ? Country::find($countryCode) : null;

        return [
            'latitude' => $country?->latitude,
            'longitude' => $country?->longitude,
        ];
    }
}

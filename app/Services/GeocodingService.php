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

    /**
     * GPS koordinatından adres bulur (reverse geocoding).
     * Nominatim (OpenStreetMap) kullanır, sonucu 30 gün önbelleğe alır.
     *
     * @return array{
     *     country_code: ?string,
     *     country_name: ?string,
     *     city: ?string,
     *     state: ?string,
     *     raw: ?array
     * }
     */
    public function reverse(float $latitude, float $longitude): array
    {
        // Test ortamında veya service disabled ise ağ çağrısı yapma
        if (app()->runningUnitTests() || ! $this->isEnabled()) {
            return $this->fallbackReverse($latitude, $longitude);
        }

        $key = sprintf('geo:rev:%.4f:%.4f', $latitude, $longitude);

        return Cache::remember($key, now()->addDays(30), function () use ($latitude, $longitude) {
            try {
                $res = Http::withHeaders([
                    'User-Agent' => 'Nisoya/1.0 (+https://nisoya.com)',
                    'Accept' => 'application/json',
                ])
                    ->timeout(6)
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'accept-language' => 'tr,en',
                    ]);

                if ($res->ok() && isset($res->json()['address'])) {
                    return $this->parseNominatimResponse($res->json());
                }
            } catch (\Throwable $e) {
                // Sessizce fallback'e düş
            }

            return $this->fallbackReverse($latitude, $longitude);
        });
    }

    /**
     * Nominatim yanıtını ayrıştır — ülke kodu, şehir, eyalet/il.
     */
    protected function parseNominatimResponse(array $response): array
    {
        $address = $response['address'] ?? [];

        $countryCode = $address['country_code'] ?? null;
        $countryName = $address['country'] ?? null;

        // Şehir birçok alandan gelebilir (Nominatim lokasyona göre değişir)
        $city = $address['city']
            ?? $address['town']
            ?? $address['village']
            ?? $address['municipality']
            ?? $address['hamlet']
            ?? null;

        $state = $address['state']
            ?? $address['province']
            ?? $address['region']
            ?? null;

        return [
            'country_code' => $countryCode ? strtoupper($countryCode) : null,
            'country_name' => $countryName,
            'city' => $city,
            'state' => $state,
            'raw' => $response,
        ];
    }

    /**
     * Test ortamı veya Nominatim başarısız olduğunda fallback.
     * Ülke bilgisi için yakın ülkeyi Country tablosundan bulmaya çalışır.
     */
    protected function fallbackReverse(float $latitude, float $longitude): array
    {
        // En yakın ülkeyi bul — SQLite RADIANS() desteklemez, basit
        // öklid mesafesi kullanıyoruz (yaklaşık, kısa mesafelerde doğru).
        $countries = \Illuminate\Support\Facades\DB::table('countries')
            ->select('code', 'name_tr', 'latitude', 'longitude')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $nearest = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($countries as $country) {
            $dlat = $latitude - $country->latitude;
            $dlng = $longitude - $country->longitude;
            $distance = ($dlat * $dlat) + ($dlng * $dlng);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $nearest = $country;
            }
        }

        return [
            'country_code' => $nearest?->code,
            'country_name' => $nearest?->name_tr,
            'city' => null,
            'state' => null,
            'raw' => null,
        ];
    }

    /**
     * Reverse geocoding aktif mi? .env ile kontrol edilebilir.
     */
    protected function isEnabled(): bool
    {
        return (bool) config('services.reverse_geocoding.enabled', true);
    }
}

<?php

namespace App\Services\Growth\Discovery;

use App\Services\GeocodingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ÜCRETSİZ keşif kaynağı — OpenStreetMap "Overpass API". API anahtarı, kart ve
 * faturalandırma GEREKTİRMEZ. Metin araması değil alan+etiket tabanlıdır: bir
 * şehrin merkezi etrafında (GeocodingService/Nominatim ile bulunur) belirli bir
 * OSM etiketine (ör. amenity=restaurant) sahip işletmeleri çeker. Türk süzmesini
 * yine TurkishBusinessDetector yapar (Overpass "Türk" araması yapamaz — tümünü
 * getirir, tespit motoru filtreler).
 *
 * Nisoya zaten harita/geocoding için OSM (Nominatim) kullanıyor — tutarlı ve
 * bedava. Overpass topluluk servisi olduğundan nazik kullanılır (User-Agent,
 * makul timeout, sınırlı sonuç).
 */
final class OverpassDiscoverySource implements BusinessDiscoverySource
{
    private const ENDPOINT = 'https://overpass-api.de/api/interpreter';

    /** Şehir merkezi etrafında tarama yarıçapı (metre). */
    private const RADIUS_METERS = 15000;

    public function __construct(private readonly GeocodingService $geocoder) {}

    public function name(): string
    {
        return 'openstreetmap';
    }

    public function isConfigured(): bool
    {
        return true; // ücretsiz, anahtar yok
    }

    /**
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     * @return list<DiscoveredBusiness>
     */
    public function discover(string $city, string $country, array $trade, int $limit = 20): array
    {
        $osm = $trade['osm'] ?? '';
        [$key, $value] = array_pad(explode('=', $osm, 2), 2, null);
        if (! $key || ! $value) {
            return []; // bu meslek için OSM etiketi tanımsız
        }

        $coords = $this->geocoder->locate($city, $country);
        if ($coords['latitude'] === null || $coords['longitude'] === null) {
            return []; // şehir koordinatı çözülemedi
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['User-Agent' => 'Nisoya/1.0 (+https://nisoya.com)'])
                ->timeout(30)
                ->post(self::ENDPOINT, [
                    'data' => $this->buildQuery($coords['latitude'], $coords['longitude'], $key, $value, $limit),
                ]);

            if (! $response->successful()) {
                Log::warning('Growth: Overpass sorgusu başarısız', ['status' => $response->status()]);

                return [];
            }

            return $this->mapElements($response->json('elements') ?? [], $country, $city, $trade);
        } catch (\Throwable $e) {
            Log::warning('Growth: Overpass istisna', ['message' => $e->getMessage()]);

            return [];
        }
    }

    private function buildQuery(float $lat, float $lng, string $key, string $value, int $limit): string
    {
        return sprintf(
            '[out:json][timeout:25];nwr(around:%d,%s,%s)["%s"="%s"]["name"];out center %d;',
            self::RADIUS_METERS,
            number_format($lat, 6, '.', ''),
            number_format($lng, 6, '.', ''),
            $this->esc($key),
            $this->esc($value),
            min(max($limit, 1), 60),
        );
    }

    /**
     * Overpass "elements" yanıtını DiscoveredBusiness listesine çevirir (saf —
     * ağ yok, test edilebilir). Adsız kayıtlar atlanır; web sitesi varsa alınır
     * (e-posta ayrı zenginleştirmeyle siteden çıkarılır).
     *
     * @param  list<array<string, mixed>>  $elements
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     * @return list<DiscoveredBusiness>
     */
    public function mapElements(array $elements, string $country, string $city, array $trade): array
    {
        $out = [];

        foreach ($elements as $element) {
            $tags = $element['tags'] ?? [];
            $name = $tags['name'] ?? null;
            if (! is_string($name) || $name === '') {
                continue;
            }

            $out[] = new DiscoveredBusiness(
                name: $name,
                category: $trade['en'] ?? null,
                country: $country,
                city: $tags['addr:city'] ?? $city,
                website: $tags['website'] ?? $tags['contact:website'] ?? null,
                externalId: 'osm-'.($element['type'] ?? 'x').'-'.($element['id'] ?? ''),
                sector: $trade['key'] ?? null,
            );
        }

        return $out;
    }

    /** Overpass sorgusuna enjeksiyonu önlemek için etiket anahtar/değerini temizle. */
    private function esc(string $value): string
    {
        return str_replace(['"', '\\', '[', ']', ';', '(', ')'], '', $value);
    }
}

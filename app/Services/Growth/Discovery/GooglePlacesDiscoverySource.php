<?php

namespace App\Services\Growth\Discovery;

use App\Services\GeocodingService;
use App\Services\Growth\QueryPermutationEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gerçek keşif kaynağı — Google Places API "Text Search (New)". Anahtar
 * config('growth.google_places.api_key') ile verilir; yoksa isConfigured()
 * false döner ve runner fixture kaynağına düşer.
 *
 * NOT: Places API işletme adı/adres/koordinat/web sitesi verir ama E-POSTA
 * VERMEZ — e-posta ayrı bir zenginleştirme adımıyla (işletmenin kendi sitesi)
 * elde edilir. ToS: Maps içeriğini toplu "dizin/telemarketing listesi" için
 * çekmek yasaktır; burada hedefli, hız-limitli arama yapılır.
 */
final class GooglePlacesDiscoverySource implements BusinessDiscoverySource
{
    private const ENDPOINT = 'https://places.googleapis.com/v1/places:searchText';

    /**
     * `addressComponents` EKLENDİ (2026-08-08) — ülkeyi sonuçtan okumak için.
     * Gerekçesi {@see map()} içinde; kısaca: eskiden ülke SORGUDAN alınıyordu
     * ve İstanbul'daki bir dükkân "US" damgası yiyordu.
     */
    private const FIELD_MASK = 'places.id,places.displayName,places.primaryTypeDisplayName,places.formattedAddress,places.websiteUri,places.addressComponents';

    /** Şehir merkezi etrafında arama kutusu (metre) — bkz. discover(). */
    private const RADIUS_METERS = 30000;

    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?GeocodingService $geocoder = null,
    ) {}

    public function name(): string
    {
        return 'google_places';
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     * @return list<DiscoveredBusiness>
     */
    public function discover(string $city, string $country, array $trade, int $limit = 20): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        /*
         * ARAMA COĞRAFİ OLARAK KISITLANIR (2026-08-08 — canlı taramada bulundu).
         *
         * Places "Text Search" serbest metin araması: kısıtlanmazsa sorgu
         * nereye uyarsa oradan sonuç döndürür. ÖLÇÜLDÜ: "US" taramasında
         * Lagos'tan "Türkiye Furniture", İstanbul'dan "çilingir türker" ve
         * "Divan Patisserie Elmadağ" geldi — yani hedef bölgenin binlerce km
         * dışından.
         *
         * `locationRestriction` YUMUŞAK BİR TERCİH DEĞİL, SERT SINIRDIR;
         * dikdörtgenin dışı hiç dönmez. Şehir koordinatı çözülemezse kısıt
         * konmaz (eski davranış) — arama yapmamaktansa geniş yapmak yeğdir,
         * ama o zaman da ülkeyi sonuçtan okuyan ikinci savunma devrede
         * ({@see map()}).
         */
        $kutu = $this->aramaKutusu($city, $country);

        // Şehir+meslek için çok-dilli + "Türk/Turkish" enjekteli metin sorguları
        // üret; her birini Places'te ara, sonuçları place_id ile tekilleştir.
        $term = ['tr' => $trade['tr'], 'en' => $trade['en'], 'local' => $trade['local'] ?? ''];

        $found = [];
        foreach ((new QueryPermutationEngine)->build([$city], [$term]) as $query) {
            foreach ($this->searchText($query, $country, $limit, $kutu) as $business) {
                $found[$business->id()] = $business;
            }
        }

        return array_values($found);
    }

    /**
     * @param  array<string, mixed>|null  $kutu  locationRestriction gövdesi (null = kısıtsız)
     * @return list<DiscoveredBusiness>
     */
    private function searchText(string $query, string $country, int $limit, ?array $kutu = null): array
    {
        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => self::FIELD_MASK,
            ])->timeout(10)->post(self::ENDPOINT, array_filter([
                'textQuery' => $query,
                'maxResultCount' => min(max($limit, 1), 20),
                'locationRestriction' => $kutu,
            ]));

            if (! $response->successful()) {
                Log::warning('Growth: Places araması başarısız', [
                    'status' => $response->status(),
                    'detail' => $response->json('error.message'),
                ]);

                return [];
            }

            return array_map(
                fn (array $place): DiscoveredBusiness => $this->map($place, $country),
                $response->json('places') ?? [],
            );
        } catch (\Throwable $e) {
            Log::warning('Growth: Places istisna', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Anahtarın gerçekten çalıştığını doğrular (admin panel "test et" için).
     * discover()'dan farklı: HTTP durumunu ayırt eder — boş sonuç ≠ hata.
     *
     * @return array{ok: bool, message: string}
     */
    public function probe(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'API anahtarı girilmemiş.'];
        }

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'places.id',
            ])->timeout(10)->post(self::ENDPOINT, [
                'textQuery' => 'Turkish restaurant New York',
                'maxResultCount' => 1,
            ]);

            if ($response->successful()) {
                $count = count($response->json('places') ?? []);

                return ['ok' => true, 'message' => "Google Places yanıt verdi ({$count} sonuç). Anahtar çalışıyor."];
            }

            return ['ok' => false, 'message' => $response->json('error.message') ?? ('HTTP '.$response->status())];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * ÜLKE SONUÇTAN OKUNUR, SORGUDAN DEĞİL (2026-08-08).
     *
     * Eskiden `country: $country` yazıyordu — yani taramanın hedef ülkesi.
     * Places serbest metin araması hedef bölge dışından sonuç döndürebildiği
     * için İstanbul'daki bir dükkân `country = 'US'` damgası alıyordu.
     *
     * BUNUN BEDELİ ETİKET DEĞİL, KAPI: `DiscoveryRunner` gönderim iznini
     * `RegionPolicy::marketingStatus($business->country)` ile veriyor. Yanlış
     * ülke = yanlış izin. Canlı taramada Türkiye'deki üç işletme
     * "gönderilebilir" işaretlenmişti — oysa TR bilerek engelli (İYS yok).
     *
     * Adres bileşenleri ISO-2 kodu taşıyor (`shortText`), yani doğru bilgi
     * yanıtta zaten vardı; yalnız kullanılmıyordu. Bileşen gelmezse sorgunun
     * ülkesine düşülür (eski davranış) — bilinmeyeni "izinli" saymaktansa
     * hedef ülkeyi varsaymak, hiç değilse tarama niyetiyle tutarlı.
     *
     * @param  array<string, mixed>  $place
     */
    private function map(array $place, ?string $country): DiscoveredBusiness
    {
        return new DiscoveredBusiness(
            name: (string) ($place['displayName']['text'] ?? 'Bilinmeyen'),
            category: $place['primaryTypeDisplayName']['text'] ?? null,
            country: $this->countryFromComponents($place['addressComponents'] ?? null) ?? $country,
            city: $this->cityFromAddress($place['formattedAddress'] ?? null),
            website: $place['websiteUri'] ?? null,
            externalId: $place['id'] ?? null,
        );
    }

    /**
     * Adres bileşenlerinden ISO-2 ülke kodu ("TR", "DE", "US").
     *
     * @param  mixed  $components
     */
    private function countryFromComponents($components): ?string
    {
        if (! is_array($components)) {
            return null;
        }

        foreach ($components as $parca) {
            if (! is_array($parca) || ! in_array('country', (array) ($parca['types'] ?? []), true)) {
                continue;
            }

            $kod = strtoupper(trim((string) ($parca['shortText'] ?? '')));

            // Yalnız iki harfli ISO-2 kabul edilir; Places bazen uzun ad
            // döndürüyor ve onu ülke kodu sanmak RegionPolicy'yi şaşırtır.
            if (preg_match('/^[A-Z]{2}$/', $kod)) {
                return $kod;
            }
        }

        return null;
    }

    /**
     * Şehir merkezi etrafında `locationRestriction` dikdörtgeni.
     *
     * Overpass kaynağındaki sınır kutusuyla aynı mantık (boylam derecesi
     * enleme göre daralır) ama yarıçap daha geniş: Places metin araması bir
     * metropolün tamamına yayılır, 15 km New York'un yarısını keserdi.
     *
     * @return array<string, mixed>|null
     */
    private function aramaKutusu(string $city, string $country): ?array
    {
        $koordinat = $this->geocoder?->locate($city, $country);
        $lat = $koordinat['latitude'] ?? null;
        $lng = $koordinat['longitude'] ?? null;

        if ($lat === null || $lng === null) {
            return null;
        }

        $enlemDelta = self::RADIUS_METERS / 111_320.0;
        $boylamDelta = self::RADIUS_METERS / (111_320.0 * max(cos(deg2rad($lat)), 0.01));

        return ['rectangle' => [
            'low' => ['latitude' => $lat - $enlemDelta, 'longitude' => $lng - $boylamDelta],
            'high' => ['latitude' => $lat + $enlemDelta, 'longitude' => $lng + $boylamDelta],
        ]];
    }

    /** Biçimli adresten kaba bir şehir tahmini (ilk anlamlı parça). */
    private function cityFromAddress(?string $address): ?string
    {
        if ($address === null || $address === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $address));

        // Genelde ".., Şehir, Bölge Posta, Ülke" — sondan bir önceki bölge/şehir.
        return $parts[count($parts) >= 2 ? count($parts) - 2 : 0] ?: null;
    }
}

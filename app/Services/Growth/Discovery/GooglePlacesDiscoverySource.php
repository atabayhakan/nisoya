<?php

namespace App\Services\Growth\Discovery;

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

    private const FIELD_MASK = 'places.id,places.displayName,places.primaryTypeDisplayName,places.formattedAddress,places.websiteUri';

    public function __construct(private readonly ?string $apiKey) {}

    public function name(): string
    {
        return 'google_places';
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function search(string $query, ?string $country = null, int $limit = 20): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => self::FIELD_MASK,
            ])->timeout(10)->post(self::ENDPOINT, [
                'textQuery' => $query,
                'maxResultCount' => min(max($limit, 1), 20),
            ]);

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
     * search()'ten farklı: HTTP durumunu ayırt eder — boş sonuç ≠ hata.
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

    /** @param  array<string, mixed>  $place */
    private function map(array $place, ?string $country): DiscoveredBusiness
    {
        return new DiscoveredBusiness(
            name: (string) ($place['displayName']['text'] ?? 'Bilinmeyen'),
            category: $place['primaryTypeDisplayName']['text'] ?? null,
            country: $country,
            city: $this->cityFromAddress($place['formattedAddress'] ?? null),
            website: $place['websiteUri'] ?? null,
            externalId: $place['id'] ?? null,
        );
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

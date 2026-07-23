<?php

namespace App\Services\Growth;

/**
 * Keşif sorgularını sistematik üretir: {şehir} × {meslek} × {dil varyantı}.
 *
 * Her meslek için yerel dil + İngilizce + Türkçe terim denenir; ayrıca
 * "Türk/Turkish" işareti enjekte edilmiş varyantlar eklenir (en yüksek isabetli
 * sonuçlar bunlardan gelir — ör. "Türk berber Bangkok"). Üretilen sorgular bir
 * yer arama sağlayıcısına (Google Places vb.) beslenir.
 *
 * Motor saf mantıktır (Laravel'e bağımsız, deterministik) — bu yüzden test
 * edilebilir ve tekrar üretilebilir.
 */
final class QueryPermutationEngine
{
    /** Sorgulara enjekte edilen Türk işaretleri. */
    private const MARKERS = ['Türk', 'Turkish'];

    /**
     * @param  list<string>  $cities  Hedef şehirler (ör. ["Almaty", "Bishkek"])
     * @param  list<array{tr?: string, en?: string, local?: string}>  $trades  Meslekler; her biri
     *                                                                         Türkçe/İngilizce/yerel terim taşır (eksik olan atlanır)
     * @param  bool  $withMarkers  "Türk/Turkish" enjekteli varyantları da üret
     * @return list<string> Tekilleştirilmiş sorgu listesi
     */
    public function build(array $cities, array $trades, bool $withMarkers = true): array
    {
        $queries = [];

        foreach ($cities as $city) {
            foreach ($trades as $trade) {
                $terms = array_values(array_filter([
                    $trade['local'] ?? null,
                    $trade['en'] ?? null,
                    $trade['tr'] ?? null,
                ], static fn (?string $t): bool => $t !== null && $t !== ''));

                foreach ($terms as $term) {
                    $queries[] = $this->normalize("{$term} {$city}");

                    if ($withMarkers) {
                        foreach (self::MARKERS as $marker) {
                            $queries[] = $this->normalize("{$marker} {$term} {$city}");
                        }
                    }
                }
            }
        }

        return array_values(array_unique($queries));
    }

    private function normalize(string $query): string
    {
        return trim(preg_replace('/\s+/', ' ', $query) ?? $query);
    }
}

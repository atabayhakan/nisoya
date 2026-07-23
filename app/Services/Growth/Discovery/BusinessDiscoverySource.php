<?php

namespace App\Services\Growth\Discovery;

/**
 * Keşif kaynağı sözleşmesi. Uygulamalar bir arama sorgusundan işletme listesi
 * döndürür — gerçek (Google Places) ya da yerel (fixture). Runner yalnızca bu
 * arayüzü konuşur; hangi kaynağın çalıştığını bilmez (AiProvider deseniyle aynı).
 */
interface BusinessDiscoverySource
{
    /** İnsan-okur kaynak adı (kayıtlarda `source` sütununa yazılır). */
    public function name(): string;

    /** Kaynak çalışabilir durumda mı (ör. API anahtarı var mı)? */
    public function isConfigured(): bool;

    /**
     * Bir şehir + meslek için işletme listesi döndürür. Metin-arama kaynakları
     * (Google Places) trade'in dil terimlerinden sorgu üretir; alan+etiket
     * kaynakları (Overpass) trade'in osm etiketini kullanır.
     *
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     * @return list<DiscoveredBusiness>
     */
    public function discover(string $city, string $country, array $trade, int $limit = 20): array;
}

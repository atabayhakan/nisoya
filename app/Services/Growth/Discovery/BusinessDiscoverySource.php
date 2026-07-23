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
     * Bir sorgu için işletme listesi döndürür.
     *
     * @return list<DiscoveredBusiness>
     */
    public function search(string $query, ?string $country = null, int $limit = 20): array;
}

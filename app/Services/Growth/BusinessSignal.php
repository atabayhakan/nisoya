<?php

namespace App\Services\Growth;

/**
 * Bir aday işletme hakkında, "Türk mü?" tespiti için toplanan sinyaller.
 * Keşif katmanı (Google Places + zenginleştirme) bunu doldurur; tespit motoru
 * bunu tüketir. Salt-okunur, hafif bir taşıyıcı — Laravel'e bağımlı değil.
 */
final class BusinessSignal
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $category = null,
        public readonly ?string $ownerName = null,
        public readonly ?string $country = null,
        public readonly ?string $siteLanguage = null,
        public readonly ?string $reviewSample = null,
    ) {}
}

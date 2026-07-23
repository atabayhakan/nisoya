<?php

namespace App\Services\Growth;

/**
 * Tespit motorunun bir işletme için verdiği karar. `band` üç durumdan biri:
 *  - turkish     → yüksek güvenle Türk (otomatik kabul edilebilir)
 *  - not_turkish → yüksek güvenle Türk değil (elenir)
 *  - ambiguous   → sınırda; LLM doğrulaması ve/veya admin onayı gerekir
 */
final class DetectionResult
{
    public const BAND_TURKISH = 'turkish';

    public const BAND_NOT = 'not_turkish';

    public const BAND_AMBIGUOUS = 'ambiguous';

    /** @param  list<string>  $signals */
    public function __construct(
        public readonly bool $isTurkish,
        public readonly float $confidence,
        public readonly string $band,
        public readonly array $signals,
        public readonly string $method,
        public readonly ?string $reasoning = null,
    ) {}

    /**
     * Bu kayıt insan onay kuyruğuna düşmeli mi? Sınırda olanlar ile düşük güvenli
     * "Türk" kararları — yanlış hedefleme itibarı yakar, o yüzden gözden geçirilir.
     */
    public function needsHumanReview(): bool
    {
        return $this->band === self::BAND_AMBIGUOUS
            || ($this->isTurkish && $this->confidence < 0.75);
    }
}

<?php

namespace App\Services\Growth\Discovery;

use App\Services\Growth\BusinessSignal;
use App\Support\Growth\TurkishLexicon;

/**
 * Bir keşif kaynağının (Google Places / fixture) döndürdüğü ham işletme kaydı.
 * Tespit için BusinessSignal'e dönüştürülür; kalıcılaştırmada external_id ile
 * tekilleştirilir.
 */
final class DiscoveredBusiness
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $category = null,
        public readonly ?string $ownerName = null,
        public readonly ?string $country = null,
        public readonly ?string $city = null,
        public readonly ?string $website = null,
        public readonly ?string $externalId = null,
        public readonly ?string $siteLanguage = null,
        public readonly ?string $reviewSample = null,
        public readonly ?string $sector = null,
    ) {}

    /** Tekilleştirme kimliği — kaynak bir ID vermediyse ad+şehirden türetilir. */
    public function id(): string
    {
        return $this->externalId
            ?? md5(TurkishLexicon::fold($this->name).'|'.TurkishLexicon::fold((string) $this->city));
    }

    public function toSignal(): BusinessSignal
    {
        return new BusinessSignal(
            $this->name,
            $this->category,
            $this->ownerName,
            $this->country,
            $this->siteLanguage,
            $this->reviewSample,
        );
    }
}

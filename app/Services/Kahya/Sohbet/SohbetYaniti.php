<?php

namespace App\Services\Kahya\Sohbet;

use App\Models\KahyaEylemKaydi;

/**
 * Kâhya'nın tek bir mesaja verdiği yanıt.
 *
 * Metin ile EYLEM ayrı tutuluyor: yapay zekânın cümlesi bir niyet beyanıdır,
 * ne olduğunu ise eylem kaydı söyler. İkisini karıştırmak, modelin "ekledim"
 * dediği ama hiçbir şeyin eklenmediği duruma kapı açar — bu sistemde en
 * tehlikeli hata sınıfı odur.
 */
final class SohbetYaniti
{
    public function __construct(
        public readonly string $metin,
        public readonly ?KahyaEylemKaydi $eylem = null,
        public readonly bool $onayBekliyor = false,
    ) {}
}

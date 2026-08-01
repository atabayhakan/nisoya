<?php

namespace App\Services\Kahya;

/**
 * Panel haritasında DOĞRULANMIŞ tek bir ekran hedefi.
 *
 * Bu nesne yalnızca {@see PanelHaritasi::bul} tarafından üretilir: adres her
 * zaman haritanın kendi kanonik adresidir (yol biçiminde, ör. `/yonetim/tags`),
 * modelin yazdığı ham metin değil. Yani bir PanelHedefi elindeyse "bu ekran
 * gerçekten var ve menüde görünüyor" güvencesi de elindedir — arayüz bu adresi
 * doğrulamadan kullanabilir.
 */
final class PanelHedefi
{
    public function __construct(
        public readonly string $etiket,
        public readonly string $adres,
    ) {}
}

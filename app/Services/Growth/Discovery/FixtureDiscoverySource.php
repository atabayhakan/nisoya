<?php

namespace App\Services\Growth\Discovery;

/**
 * Dış kaynak (Google/Overpass) seçili değilken (yerel geliştirme / test / demo)
 * devreye giren kaynak. Gerçekçi, ülkeye göre gruplu örnek işletmeler döndürür —
 * böylece tüm keşif → tespit → kalıcılaştırma hattı dış bağımlılık olmadan
 * uçtan uca çalışır ve test edilebilir. Şehir/meslek parametrelerini yok sayar
 * (ülkeye göre sabit set); tekilleştirme runner'da external_id ile yapılır.
 */
final class FixtureDiscoverySource implements BusinessDiscoverySource
{
    public function name(): string
    {
        return 'fixture';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function discover(string $city, string $country, array $trade, int $limit = 20): array
    {
        $c = strtoupper(trim($country));

        $rows = array_values(array_filter(
            $this->catalog(),
            static fn (DiscoveredBusiness $b): bool => $c === '' || $b->country === $c,
        ));

        return array_slice($rows, 0, $limit);
    }

    /** @return list<DiscoveredBusiness> */
    private function catalog(): array
    {
        return [
            // --- ABD ---
            new DiscoveredBusiness('Anadolu Kebap House', 'Restaurant', country: 'US', city: 'New York', website: 'https://anadolukebap.test', externalId: 'fx-us-1', sector: 'lokanta'),
            new DiscoveredBusiness('Mehmet Yılmaz Auto Repair', 'Auto repair', 'Mehmet Yılmaz', 'US', 'New Jersey', website: 'https://mehmetauto.test', externalId: 'fx-us-2', sector: 'oto'),
            new DiscoveredBusiness('Kaya Sushi Bar', 'Sushi restaurant', country: 'US', city: 'Los Angeles', externalId: 'fx-us-3', sector: 'lokanta'),
            new DiscoveredBusiness('Sunrise Bakery', 'Bakery', country: 'US', city: 'Chicago', externalId: 'fx-us-4', sector: 'lokanta'),

            // --- Kazakistan ---
            new DiscoveredBusiness('Almaty Döner', 'Fast food', country: 'KZ', city: 'Almaty', externalId: 'fx-kz-1', sector: 'lokanta'),
            new DiscoveredBusiness('Ali Usta Mobilya', 'Furniture', 'Ali Demir', 'KZ', 'Almaty', externalId: 'fx-kz-2', sector: 'mobilyaci'),
            new DiscoveredBusiness('Almaty Electric Solutions', 'Electrician', country: 'KZ', city: 'Almaty', externalId: 'fx-kz-3', sector: 'elektrikci'),

            // --- Kırgızistan ---
            new DiscoveredBusiness('Bishkek Anadolu Lokantası', 'Restaurant', country: 'KG', city: 'Bishkek', externalId: 'fx-kg-1', sector: 'lokanta'),
            new DiscoveredBusiness('Ahmet Kaya Berber', 'Barber', 'Ahmet Kaya', 'KG', 'Bishkek', externalId: 'fx-kg-2', sector: 'berber'),

            // --- Tayland ---
            new DiscoveredBusiness('Turkish Delight Restaurant', 'Turkish restaurant', country: 'TH', city: 'Phuket', externalId: 'fx-th-1', sector: 'lokanta'),
            new DiscoveredBusiness('Istanbul Cafe', 'Cafe', country: 'TH', city: 'Bangkok', externalId: 'fx-th-2', sector: 'lokanta'),
            new DiscoveredBusiness('Golden Dragon Restaurant', 'Chinese restaurant', country: 'TH', city: 'Bangkok', externalId: 'fx-th-3', sector: 'lokanta'),

            // --- Almanya (AB — keşifte var, gönderimde bloklanır: RegionPolicy demosu) ---
            new DiscoveredBusiness('Berlin Anadolu Kebap', 'Restaurant', country: 'DE', city: 'Berlin', website: 'https://berlin-anadolu.test', externalId: 'fx-de-1', sector: 'lokanta'),
        ];
    }
}

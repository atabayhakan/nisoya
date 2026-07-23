<?php

namespace Tests\Feature\Growth;

use App\Services\GeocodingService;
use App\Services\Growth\Discovery\OverpassDiscoverySource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverpassDiscoverySourceTest extends TestCase
{
    use RefreshDatabase;

    private function source(): OverpassDiscoverySource
    {
        return new OverpassDiscoverySource(app(GeocodingService::class));
    }

    public function test_maps_overpass_elements_to_businesses(): void
    {
        $elements = [
            ['type' => 'node', 'id' => 1, 'tags' => ['name' => 'Anadolu Kebap', 'website' => 'https://anadolu.example', 'addr:city' => 'Almaty']],
            ['type' => 'way', 'id' => 2, 'tags' => ['name' => 'Ali Berber', 'contact:website' => 'https://ali.example']],
            ['type' => 'node', 'id' => 3, 'tags' => ['amenity' => 'restaurant']], // adsız → atlanır
        ];
        $trade = ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant'];

        $result = $this->source()->mapElements($elements, 'KZ', 'Almaty', $trade);

        $this->assertCount(2, $result);
        $this->assertSame('Anadolu Kebap', $result[0]->name);
        $this->assertSame('https://anadolu.example', $result[0]->website);
        $this->assertSame('Almaty', $result[0]->city);
        $this->assertSame('osm-node-1', $result[0]->externalId);
        $this->assertSame('lokanta', $result[0]->sector);
        // İkinci kayıt: website yoksa contact:website'e düşer.
        $this->assertSame('https://ali.example', $result[1]->website);
    }

    public function test_discover_returns_empty_without_osm_tag(): void
    {
        $result = $this->source()->discover('Almaty', 'KZ', ['key' => 'x', 'tr' => 'x', 'en' => 'x']);

        $this->assertSame([], $result);
    }

    public function test_discover_returns_empty_when_city_cannot_be_geocoded(): void
    {
        // Testte GeocodingService ağ kullanmaz; seed'siz ülke → null koordinat → boş.
        $result = $this->source()->discover('Nowhereville', 'ZZ', [
            'key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant',
        ]);

        $this->assertSame([], $result);
    }

    public function test_source_is_always_configured_no_key_needed(): void
    {
        $this->assertTrue($this->source()->isConfigured());
        $this->assertSame('openstreetmap', $this->source()->name());
    }
}

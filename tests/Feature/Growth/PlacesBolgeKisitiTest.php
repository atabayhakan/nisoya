<?php

namespace Tests\Feature\Growth;

use App\Services\GeocodingService;
use App\Services\Growth\Discovery\GooglePlacesDiscoverySource;
use App\Support\Growth\RegionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Places aramasının bölge kapısı (2026-08-08).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR — CANLI TARAMADA BULUNDU
 *
 * `growth:discover US` çalıştırıldı ve havuza şunlar düştü:
 *
 *   Türkiye Furniture        | Lagos                        | gönderilebilir
 *   çilingir türker          | 34303 Küçükçekmece/İstanbul  | gönderilebilir
 *   Divan Patisserie Elmadağ | 34373 Şişli/İstanbul         | gönderilebilir
 *
 * İki kusur birleşiyordu:
 *   1. Places "Text Search" serbest metin araması; coğrafi kısıt verilmezse
 *      sorgu nereye uyarsa oradan sonuç döndürür.
 *   2. `map()` ülkeyi SORGUDAN alıyordu (`country: $country`), sonuçtan değil.
 *
 * Bedeli etiket değil KAPI: `DiscoveryRunner` gönderim iznini
 * `RegionPolicy::marketingStatus($business->country)` ile veriyor. Türkiye'deki
 * bir işletme "US" damgası yiyince "gönderilebilir" oluyordu — oysa TR bilerek
 * engelli (İYS yok).
 */
class PlacesBolgeKisitiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array{latitude: float|null, longitude: float|null}|null  $koordinat
     *                                                                              null → gerçek servis (testte ağ kullanmaz, DB'den çözer; seed yoksa null döner)
     */
    private function kaynak(?array $koordinat = null): GooglePlacesDiscoverySource
    {
        if ($koordinat === null) {
            return new GooglePlacesDiscoverySource('test-anahtar', app(GeocodingService::class));
        }

        $geocoder = $this->createMock(GeocodingService::class);
        $geocoder->method('locate')->willReturn($koordinat);

        return new GooglePlacesDiscoverySource('test-anahtar', $geocoder);
    }

    /** @param array<string, mixed> $ekBilesen */
    private function yanit(string $ad, string $adres, array $ekBilesen): array
    {
        return ['places' => [[
            'id' => 'p-1',
            'displayName' => ['text' => $ad],
            'formattedAddress' => $adres,
            'addressComponents' => [$ekBilesen],
        ]]];
    }

    // === Ülke SONUÇTAN okunur ========================================

    public function test_turkiyedeki_isletme_amerika_taramasinda_tr_kaydedilir(): void
    {
        /*
         * EN ÖNEMLİ TEST. Gerçek olay buydu: US taraması İstanbul'daki bir
         * çilingiri getirdi ve "gönderilebilir" işaretledi.
         */
        Http::fake([
            'places.googleapis.com/*' => Http::response($this->yanit(
                'çilingir türker',
                '34303 Küçükçekmece/İstanbul, Türkiye',
                ['types' => ['country'], 'shortText' => 'TR', 'longText' => 'Türkiye'],
            )),
        ]);

        $sonuc = $this->kaynak()->discover('New York', 'US', [
            'key' => 'cilingir', 'tr' => 'çilingir', 'en' => 'locksmith',
        ]);

        $this->assertNotEmpty($sonuc);
        $this->assertSame('TR', $sonuc[0]->country, 'Ülke sorgudan değil sonuçtan okunmalı.');
    }

    public function test_yanlis_ulke_bolge_kapisini_delerdi(): void
    {
        // Zincirin kapandığını göster: doğru ülke → doğru gönderim kararı.
        // TR engelli, US izinli; ikisi AYNI kod yolundan geçiyor.
        $this->assertNotSame(
            RegionPolicy::marketingStatus('US'),
            RegionPolicy::marketingStatus('TR'),
            'TR ile US aynı gönderim durumunu alıyorsa bölge kapısı zaten anlamsız.',
        );
        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus('TR'));
    }

    public function test_ulke_bileseni_yoksa_sorgunun_ulkesine_dusulur(): void
    {
        // Geriye dönük uyum: bileşen gelmezse eski davranış sürsün.
        Http::fake([
            'places.googleapis.com/*' => Http::response(['places' => [[
                'id' => 'p-2',
                'displayName' => ['text' => 'Anadolu Grill'],
                'formattedAddress' => 'Somewhere',
            ]]]),
        ]);

        $sonuc = $this->kaynak()->discover('Chicago', 'US', [
            'key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant',
        ]);

        $this->assertSame('US', $sonuc[0]->country);
    }

    public function test_ulke_kodu_iki_harf_degilse_yok_sayilir(): void
    {
        // Places bazen uzun ad döndürüyor; onu ülke kodu sanmak RegionPolicy'yi
        // şaşırtır ("Türkiye" allowlist'te yok → sessizce engelli sanılır).
        Http::fake([
            'places.googleapis.com/*' => Http::response($this->yanit(
                'Test',
                'Adres',
                ['types' => ['country'], 'shortText' => 'Türkiye'],
            )),
        ]);

        $sonuc = $this->kaynak()->discover('Houston', 'US', [
            'key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant',
        ]);

        $this->assertSame('US', $sonuc[0]->country);
    }

    // === Arama coğrafi olarak kısıtlanır =============================

    public function test_arama_sehir_kutusuyla_kisitlanir(): void
    {
        /*
         * `locationRestriction` YUMUŞAK TERCİH DEĞİL SERT SINIRDIR —
         * dikdörtgenin dışı hiç dönmez. İkinci savunma (ülkeyi sonuçtan okuma)
         * varken bu da gerekli: kotalı API'de binlerce km ötedeki sonuçları
         * çekip sonra atmak, sorguyu ve kotayı boşa harcamak olur.
         */
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => []])]);

        // Koordinat sahtelenir: GeocodingService testte ağa çıkmaz, DB'den
        // çözer ve RefreshDatabase yüzünden şehir tablosu boştur.
        $this->kaynak(['latitude' => 52.52, 'longitude' => 13.405])->discover('Berlin', 'DE', [
            'key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant',
        ]);

        Http::assertSent(function ($istek) {
            $govde = $istek->data();

            return isset($govde['locationRestriction']['rectangle']['low']['latitude'])
                && isset($govde['locationRestriction']['rectangle']['high']['longitude']);
        });
    }

    public function test_sehir_cozulemezse_kisit_konmaz_ama_arama_yapilir(): void
    {
        // Arama yapmamaktansa geniş yapmak yeğdir; ülkeyi sonuçtan okuyan
        // ikinci savunma zaten devrede.
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => []])]);

        $this->kaynak()->discover('Nowhereville', 'ZZ', [
            'key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant',
        ]);

        Http::assertSent(fn ($istek) => ! array_key_exists('locationRestriction', $istek->data()));
    }

    public function test_adres_bilesenleri_alan_maskesinde_istenir(): void
    {
        // Maskeden düşerse ülke bileşeni hiç gelmez ve düzeltme SESSİZCE
        // eski davranışa döner (her şey "sorgunun ülkesi" olur).
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => []])]);

        $this->kaynak(['latitude' => 52.52, 'longitude' => 13.405])->discover('Berlin', 'DE', [
            'key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant',
        ]);

        Http::assertSent(fn ($istek) => str_contains(
            (string) $istek->header('X-Goog-FieldMask')[0],
            'places.addressComponents',
        ));
    }
}

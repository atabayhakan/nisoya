<?php

namespace Tests\Unit\Growth;

use App\Support\Growth\GrowthCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Keşif hedef verisi — özellikle "şehir" sütunu (2026-08-07).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Katalogda "New Jersey" yazıyordu. Keşif zinciri şehir adını Nominatim'e
 * verip dönen NOKTA etrafında 15 km'lik bir kutu tarıyor; eyalet adı verilince
 * dönen nokta eyaletin coğrafi merkezi oluyor.
 *
 * ÖLÇÜLDÜ (2026-08-07, gerçek Overpass):
 *   "New Jersey"          → 40.076,-74.404 (eyalet ortası) →  0 aday
 *   "Clifton, New Jersey" → 40.858,-74.164 (şehir)         → 18 aday, 16 Türk
 *
 * Bu satır hata VERMİYORDU — sessizce boş dönüyordu. Sessiz başarısızlıklar
 * bu keşif hattında ikinci kez çıkıyor (bkz. PR #118), o yüzden mühürlü.
 */
class GrowthCatalogTest extends TestCase
{
    /**
     * ABD eyaletleri + DC. Katalogdaki "şehir" alanına biri girerse keşif o
     * satırda sessizce kırsala bakar.
     *
     * @var list<string>
     */
    private const US_EYALETLERI = [
        'alabama', 'alaska', 'arizona', 'arkansas', 'california', 'colorado',
        'connecticut', 'delaware', 'florida', 'georgia', 'hawaii', 'idaho',
        'illinois', 'indiana', 'iowa', 'kansas', 'kentucky', 'louisiana',
        'maine', 'maryland', 'massachusetts', 'michigan', 'minnesota',
        'mississippi', 'missouri', 'montana', 'nebraska', 'nevada',
        'new hampshire', 'new jersey', 'new mexico', 'north carolina',
        'north dakota', 'ohio', 'oklahoma', 'oregon', 'pennsylvania',
        'rhode island', 'south carolina', 'south dakota', 'tennessee', 'texas',
        'utah', 'vermont', 'virginia', 'washington', 'west virginia',
        'wisconsin', 'wyoming', 'district of columbia',
        // "New York" hem eyalet hem şehir; şehir olarak geçerli, listede yok.
    ];

    public function test_abd_sehir_listesinde_ciplak_eyalet_adi_yok(): void
    {
        foreach (GrowthCatalog::CITIES['US'] as $sehir) {
            $this->assertNotContains(
                mb_strtolower($sehir),
                self::US_EYALETLERI,
                "\"{$sehir}\" bir eyalet, şehir değil. Eyalet adı verilince Nominatim ".
                'eyaletin coğrafi merkezini döndürür ve 15 km\'lik tarama kutusu kırsala düşer '.
                '(ölçüldü: "New Jersey" → 0 aday). Şehir yaz: "Clifton, New Jersey".',
            );
        }
    }

    public function test_adasi_riskli_abd_sehirleri_eyaletle_nitelenir(): void
    {
        /*
         * Nominatim'e giden sorgu "şehir, ülke" biçiminde kuruluyor — eyalet
         * ancak katalogdaki dizeye yazılırsa aramaya girer.
         *
         * ÖLÇÜLDÜ: sade "Union City" → 37.587,-122.022, yani Kaliforniya'daki
         * adaşı. Hedef New Jersey'dekiydi. Sessizce yanlış kıtaya düşüyordu.
         */
        $adasiRiskli = ['clifton', 'union city', 'columbus', 'springfield', 'kansas city', 'portland'];

        foreach (GrowthCatalog::CITIES['US'] as $sehir) {
            $ilkParca = mb_strtolower(trim(explode(',', $sehir)[0]));

            if (in_array($ilkParca, $adasiRiskli, true)) {
                $this->assertStringContainsString(
                    ',',
                    $sehir,
                    "\"{$sehir}\" birden çok eyalette var; eyaletiyle yazılmalı (ör. \"Clifton, New Jersey\").",
                );
            }
        }
    }

    public function test_her_meslek_bir_osm_etiketi_tasir(): void
    {
        // osm alanı boşsa OverpassDiscoverySource o mesleği sessizce atlar
        // (erken `return []`) — yani meslek keşifte hiç görünmez.
        foreach (GrowthCatalog::TRADES as $meslek) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_:]+=[a-z_]+$/',
                $meslek['osm'] ?? '',
                "Meslek \"{$meslek['key']}\" geçerli bir OSM etiketi taşımıyor.",
            );
        }
    }
}

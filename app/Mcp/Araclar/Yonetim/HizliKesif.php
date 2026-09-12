<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Services\Growth\DiscoveryRunner;
use App\Support\Growth\GrowthCatalog;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_kesif_baslat')]
#[Title('Büyüme Keşfi — Diasporadaki Türk esnafları haritadan tara')]
#[Description(
    'Belirtilen ülke, şehir ve meslek dalında harita üzerinden (OpenStreetMap & Google Places) '.
    'canlı esnaf taraması başlatır. Türk işletmelerini filtreler ve potansiyel müşteri havuzuna ekler.'
)]
class HizliKesif extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ulke_kodu' => $schema->string()
                ->description('2 harfli ISO ülke kodu (ör. DE, FR, NL, AT, BE, UK, US). Varsayılan: DE.')
                ->required(),
            'sehir' => $schema->string()
                ->description('Taranacak şehir adı (ör. Berlin, Köln, Frankfurt, Münih, Viyana, Amsterdam).')
                ->required(),
            'meslek' => $schema->string()
                ->description('Meslek veya sektör anahtarı (ör. lokanta, market, berber, oto_tamir, firin). Varsayılan: lokanta.'),
            'adet' => $schema->integer()
                ->description('Her sorguda çekilecek işletme sayısı (1-15 arası, varsayılan 5).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $country = strtoupper(trim((string) $request->get('ulke_kodu', 'DE')));
        $city = trim((string) $request->get('sehir', 'Berlin'));
        $tradeKey = trim((string) $request->get('meslek', 'lokanta'));
        $perQuery = min(15, max(1, (int) $request->get('adet', 5)));

        $trades = GrowthCatalog::tradesForCountry($country);
        $selectedTrade = null;
        foreach ($trades as $t) {
            if ($t['key'] === $tradeKey || strcasecmp((string) $t['tr'], $tradeKey) === 0) {
                $selectedTrade = $t;
                break;
            }
        }

        if ($selectedTrade === null) {
            $selectedTrade = $trades[0] ?? [
                'key' => 'lokanta',
                'tr' => 'lokanta',
                'en' => 'restaurant',
                'osm' => 'amenity=restaurant',
            ];
        }

        $runner = app(DiscoveryRunner::class);
        $useLlm = (bool) (config('growth.use_llm') || (Settings::get('growth.use_llm') === '1'));

        $stats = $runner->runForCityTrade($country, $city, $selectedTrade, perQuery: $perQuery, useLlm: $useLlm);

        return [
            'basarili' => true,
            'bolge' => [
                'ulke' => $country,
                'sehir' => $city,
                'meslek' => $selectedTrade['tr'] ?? $tradeKey,
            ],
            'sonuclar' => [
                'taranan_isletme_sayisi' => $stats['discovered'] ?? 0,
                'dogrulanan_turk_esnafi' => $stats['turkish'] ?? 0,
                'havuza_eklenen' => $stats['saved'] ?? 0,
                'otomatik_vitrin_ilan' => $stats['created'] ?? 0,
            ],
            'mesaj' => "{$city} için {$selectedTrade['tr']} taraması tamamlandı. {$stats['turkish']} Türk işletmesi doğrulandı.",
        ];
    }
}

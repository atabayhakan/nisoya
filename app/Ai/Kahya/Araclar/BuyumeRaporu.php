<?php

namespace App\Ai\Kahya\Araclar;

use App\Services\Growth\BuyumeMetrikleriServisi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

/**
 * Kâhya Büyüme & Üye Kazanım Donanımı:
 * Nisoya'nın büyüme hunisini, hazırlanan vitrinleri ve sahiplenme oranlarını canlı sorgular.
 */
class BuyumeRaporu implements Tool
{
    public function __construct(private readonly BuyumeMetrikleriServisi $servis) {}

    public function name(): string
    {
        return 'buyume-raporu';
    }

    public function description(): Stringable|string
    {
        return 'Nisoya\'nın yeni üye kazanım ve büyüme hunisinin canlı durumunu raporlar: '
            .'Keşfedilen Türk işletmesi sayısı, hazırlanan vitrinler, bekleyen ve sahiplenilen dükkanlar, '
            .'sahiplenme dönüşüm oranı (%) ve lider şehirler. '
            .'Sahip "büyüme ne durumda?", "kaç vitrin sahiplenildi?", "üye kazanımı nasıl gidiyor?" '
            .'diye sorduğunda bu araçla gerçek verileri alıp sun.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $ozet = $this->servis->ozet();
            $sehirler = $this->servis->sehirBazli(5);

            $satirlar = [
                '📊 NİSOYA BÜYÜME & TERSİNE KATILIM RAPORU',
                "• Keşfedilen Türk İşletmesi: {$ozet['turk_isletmeler']} (Toplam havuz: {$ozet['toplam_kesif']})",
                "• Hazırlanan Ön Vitrin: {$ozet['hazirlanan_vitrinler']} adet",
                "• Sahiplenilen Vitrin (Yeni Üye): {$ozet['sahiplenilen_vitrinler']} adet",
                "• Sahiplenme Bekleyen Vitrin: {$ozet['bekleyen_vitrinler']} adet",
                "• Genel Sahiplenme Dönüşüm Oranı: %{$ozet['donusum_orani']}",
                "• Onay Bekleyen Dış Hamle (Davet Mektubu): {$ozet['onay_bekleyen_hamleler']} adet",
            ];

            if ($sehirler !== []) {
                $satirlar[] = '';
                $satirlar[] = '📍 ŞEHİR BAZLI VİTRİN VE SAHİPLENME DAĞILIMI:';
                foreach ($sehirler as $s) {
                    $satirlar[] = "- {$s['sehir']} ({$s['ulke']}): {$s['toplam_vitrin']} vitrin "
                        .($s['sahiplenilen'] > 0 ? "({$s['sahiplenilen']} sahiplenildi · %{$s['oran']})" : '(henüz sahiplenilmedi)');
                }
            }

            return implode("\n", $satirlar);
        } catch (Throwable $e) {
            return 'HATA: Büyüme raporu alınamadı — '.$e->getMessage();
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

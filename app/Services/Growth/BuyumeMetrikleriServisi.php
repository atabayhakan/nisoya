<?php

namespace App\Services\Growth;

use App\Models\BekleyenHamle;
use App\Models\Listing;
use App\Models\OutreachTarget;
use Illuminate\Support\Facades\DB;

/**
 * Türk işletmelerine yönelik Tersine Katılım (Reverse Onboarding) ve
 * dış erişim büyüme hunisinin metriklerini uçtan uca hesaplar.
 */
class BuyumeMetrikleriServisi
{
    /**
     * Genel büyüme hunisi özeti.
     *
     * @return array{
     *     toplam_kesif: int,
     *     turk_isletmeler: int,
     *     hazirlanan_vitrinler: int,
     *     bekleyen_vitrinler: int,
     *     sahiplenilen_vitrinler: int,
     *     donusum_orani: float,
     *     onay_bekleyen_hamleler: int
     * }
     */
    public function ozet(): array
    {
        $toplamKesif = OutreachTarget::count();
        $turkIsletmeler = OutreachTarget::where('detection_band', DetectionResult::BAND_TURKISH)->count();

        $hazirlananVitrinler = Listing::whereNotNull('claim_token')->count();
        $sahiplenilenVitrinler = Listing::where('is_claimed', true)
            ->whereNotNull('claimed_at')
            ->count();
        $bekleyenVitrinler = Listing::where('is_claimed', false)
            ->whereNotNull('claim_token')
            ->count();

        $donusumOrani = $hazirlananVitrinler > 0
            ? round(($sahiplenilenVitrinler / $hazirlananVitrinler) * 100, 1)
            : 0.0;

        $onayBekleyenHamleler = BekleyenHamle::query()->beklemede()->count();

        return [
            'toplam_kesif' => $toplamKesif,
            'turk_isletmeler' => $turkIsletmeler,
            'hazirlanan_vitrinler' => $hazirlananVitrinler,
            'bekleyen_vitrinler' => $bekleyenVitrinler,
            'sahiplenilen_vitrinler' => $sahiplenilenVitrinler,
            'donusum_orani' => $donusumOrani,
            'onay_bekleyen_hamleler' => $onayBekleyenHamleler,
        ];
    }

    /**
     * Şehirlere göre hazırlanan ve sahiplenilen vitrin dökümü.
     *
     * @return list<array{sehir: string, ulke: string, toplam_vitrin: int, sahiplenilen: int, oran: float}>
     */
    public function sehirBazli(int $limit = 5): array
    {
        $kayitlar = DB::table('listings')
            ->whereNotNull('claim_token')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->select('city', 'country_code')
            ->selectRaw('COUNT(*) as toplam_vitrin')
            ->selectRaw('SUM(CASE WHEN is_claimed = 1 THEN 1 ELSE 0 END) as sahiplenilen')
            ->groupBy('city', 'country_code')
            ->orderByDesc('toplam_vitrin')
            ->limit($limit)
            ->get();

        return $kayitlar->map(function ($k): array {
            $toplam = (int) $k->toplam_vitrin;
            $sahiplenilen = (int) $k->sahiplenilen;
            $oran = $toplam > 0 ? round(($sahiplenilen / $toplam) * 100, 1) : 0.0;

            return [
                'sehir' => (string) $k->city,
                'ulke' => (string) $k->country_code,
                'toplam_vitrin' => $toplam,
                'sahiplenilen' => $sahiplenilen,
                'oran' => $oran,
            ];
        })->values()->all();
    }
}

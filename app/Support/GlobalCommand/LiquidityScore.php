<?php

namespace App\Support\GlobalCommand;

final class LiquidityScore
{
    /** Heuristic supply score, not observed transaction liquidity or an AI prediction. */
    public static function calculate(int $listings, int $jobs, int $users, int $accounts): array
    {
        $score = (int) round(40 * min(max($listings, 0) / 100, 1)
            + 20 * min(max($jobs, 0) / 40, 1)
            + 25 * min(max($users, 0) / 200, 1)
            + 15 * min(max($accounts, 0) / 10, 1));
        $stage = ($listings + $jobs === 0 || $accounts === 0) ? 'cold_start'
            : (($score >= 70 && $listings >= 20 && $users >= 20) ? 'mature' : 'growing');

        return ['score' => $score, 'stage' => $stage, 'label' => match ($stage) {
            'mature' => 'Yüksek arz · Mature', 'growing' => 'Gelişen · Growing',
            default => 'Başlangıç · Cold-Start',
        }, 'next_action' => match (true) {
            $accounts === 0 => 'Yerel topluluk hesabı keşif görevi oluştur',
            $listings + $jobs === 0 => 'Yerel içerik ve ilk ilan kampanyası taslağı hazırla',
            $users < 20 => 'İzinli davet kampanyası taslağı hazırla',
            default => 'Talep ve yanıt sürelerini incele',
        }];
    }
}

<?php

namespace App\Filament\Resources\OutreachTargets\Widgets;

use App\Services\Growth\BuyumeMetrikleriServisi;
use Filament\Widgets\Widget;

/**
 * Türk işletmelerini keşfetme, vitrin hazırlama ve sahiplendirme
 * büyüme hunisini canlı olarak gösteren Filament widget'ı.
 */
class BuyumeHunisiWidget extends Widget
{
    protected string $view = 'filament.widgets.buyume-hunisi';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
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
    public function getMetrikler(): array
    {
        return app(BuyumeMetrikleriServisi::class)->ozet();
    }

    /**
     * @return list<array{sehir: string, ulke: string, toplam_vitrin: int, sahiplenilen: int, oran: float}>
     */
    public function getSehirler(): array
    {
        return app(BuyumeMetrikleriServisi::class)->sehirBazli(5);
    }
}

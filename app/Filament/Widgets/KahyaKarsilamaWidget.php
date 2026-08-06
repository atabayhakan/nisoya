<?php

namespace App\Filament\Widgets;

use App\Providers\Filament\AdminPanelProvider;
use App\Services\Kahya\Sohbet\KahyaSohbeti;
use Filament\Widgets\Widget;
use Throwable;

/**
 * Panel açıldığında Kâhya'nın selamı — sahibin isteğinin birebir karşılığı:
 * "yönetim panelini açtığımda kâhya bana selam versin ve hemen kısa bir
 * yapılacaklar özeti sunsun".
 *
 * Sahip birden çok projeyle çalışıyor; buraya girdiğinde ihtiyacı olan şey
 * gösterge tablosu değil, "bugün senden ne bekleniyor" cümlesi. Bu yüzden
 * widget panonun EN ÜSTÜNDE (sort -3, sağlık widget'ının üstünde) ve üç
 * maddeden uzun değil.
 *
 * KARŞILAMA YAPAY ZEKÂ ÇAĞIRMAZ. Selam + bekleyen işler + son konuşma
 * hatırlatması tamamen yerel veriden kurulur (~10 COUNT sorgusu). Panonun
 * açılışını bir API çağrısına bağlamak, sağlayıcı yavaşladığında panoyu
 * yavaşlatmak demektir; sohbet etmek isteyen zaten Kâhya sayfasına geçer.
 */
class KahyaKarsilamaWidget extends Widget
{
    /** Sıra merdiveni {@see AdminPanelProvider} içinde. */
    protected static ?int $sort = 20;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.kahya-karsilama';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        // Karşılama moderatöre de görünür — bekleyen işler onun da işi.
        // Sohbet sayfası ise yalnız admin (orada eylem yetkisi var).
        return auth()->user()?->isModerator() ?? false;
    }

    public function getKarsilama(): string
    {
        $sahip = auth()->user();

        if ($sahip === null) {
            return '';
        }

        try {
            return app(KahyaSohbeti::class)->karsila($sahip);
        } catch (Throwable $e) {
            report($e);

            // Karşılama süsleme değil ama panoyu da düşürmemeli: teşhis
            // patlarsa selam yine verilir, sebep log'a yazılır.
            return 'Merhaba. Şu an durumu okuyamadım — ayrıntı sunucu kayıtlarında.';
        }
    }
}

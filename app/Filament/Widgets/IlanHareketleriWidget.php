<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\GlobalCommand\GeoContext;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Son 8 ayın ilan hareketi (Vitrin Faz P4b): ay başına açılan ve
 * kapanan (pasif/reddedildi) ilan sayısı.
 *
 * Grafik SAF CSS/SVG ile çizilir — bu depoda grafik için npm bağımlılığı
 * eklenmez (handoff kuralı). Filament'in ChartWidget'ı yerine özel Blade
 * kullanılmasının sebebi budur.
 *
 * Sorgu maliyeti: 2 (açılan + kapanan). Veritabanı ay bazında sayar;
 * SQLite ve MySQL için ilgili tarih fonksiyonu seçilir.
 */
class IlanHareketleriWidget extends Widget
{
    protected string $view = 'filament.widgets.ilan-hareketleri';

    /** Sıra merdiveni {@see AdminPanelProvider} içinde. */
    protected static ?int $sort = 50;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 2;

    /**
     * @return array{aylar: array<int,array{etiket: string, acilan: int, kapanan: int}>, enYuksek: int, toplamAcilan: int}
     */
    public function getVeri(): array
    {
        $baslangic = now()->startOfMonth()->subMonths(7);

        $driver = DB::getDriverName();
        $createdMonth = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
        $updatedMonth = $driver === 'sqlite' ? "strftime('%Y-%m', updated_at)" : "DATE_FORMAT(updated_at, '%Y-%m')";
        $acilanlar = app(GeoContext::class)->apply(Listing::query())
            ->where('created_at', '>=', $baslangic)
            ->selectRaw($createdMonth.' AS month_key, COUNT(*) AS aggregate')
            ->groupBy('month_key')->pluck('aggregate', 'month_key');

        $kapananlar = app(GeoContext::class)->apply(Listing::query())
            ->whereIn('status', ['pasif', 'reddedildi'])
            ->where('updated_at', '>=', $baslangic)
            ->selectRaw($updatedMonth.' AS month_key, COUNT(*) AS aggregate')
            ->groupBy('month_key')->pluck('aggregate', 'month_key');

        $aylar = [];
        for ($i = 0; $i < 8; $i++) {
            $ay = (clone $baslangic)->addMonths($i);
            $anahtar = $ay->format('Y-m');
            $aylar[] = [
                'etiket' => $this->ayKisaltmasi($ay),
                'acilan' => (int) ($acilanlar[$anahtar] ?? 0),
                'kapanan' => (int) ($kapananlar[$anahtar] ?? 0),
            ];
        }

        $enYuksek = max(1, max(array_merge(
            array_column($aylar, 'acilan'),
            array_column($aylar, 'kapanan')
        )));

        return [
            'aylar' => $aylar,
            'enYuksek' => $enYuksek,
            'toplamAcilan' => array_sum(array_column($aylar, 'acilan')),
        ];
    }

    private function ayKisaltmasi(Carbon $ay): string
    {
        return ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'][$ay->month - 1];
    }
}

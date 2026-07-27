<?php

namespace App\Filament\Widgets;

use App\Models\Listing;
use Filament\Widgets\Widget;

/**
 * Aktif ilanların tipe göre dağılımı (Vitrin Faz P4b) — halka grafik,
 * ortasında toplam. SAF SVG (npm bağımlılığı yok).
 *
 * Kategori yerine TİP kırılımı kullanılır: 5 dikey (hizmet/ürün/emlak/
 * vasıta/davetiye) sabit ve okunur bir halka verir; 86 kategoriyle halka
 * anlamsızlaşırdı. Sorgu maliyeti: 1 (GROUP BY type).
 */
class KategoriDagilimiWidget extends Widget
{
    protected string $view = 'filament.widgets.kategori-dagilimi';

    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    /** Handoff'un kategori renk paleti: mavi / mint / amber / coral / mor. */
    private const RENKLER = [
        'hizmet' => '#3E63F0',
        'urun' => '#16a97f',
        'emlak' => '#f0a537',
        'vasita' => '#f4735e',
        'davetiye' => '#8a6bf2',
    ];

    private const ETIKETLER = [
        'hizmet' => 'Hizmet',
        'urun' => 'Ürün',
        'emlak' => 'Emlak',
        'vasita' => 'Vasıta',
        'davetiye' => 'Davetiye',
    ];

    /**
     * @return array{dilimler: array<int,array{etiket: string, adet: int, yuzde: float, renk: string, uzunluk: float, kayma: float}>, toplam: int}
     */
    public function getVeri(): array
    {
        $sayilar = Listing::query()->where('status', 'aktif')
            ->selectRaw('type, count(*) as adet')
            ->groupBy('type')
            ->pluck('adet', 'type')
            ->all();

        $toplam = array_sum($sayilar);

        if ($toplam === 0) {
            return ['dilimler' => [], 'toplam' => 0];
        }

        // SVG halka: r=36 → çevre ≈ 226.19. Her dilim stroke-dasharray ile
        // çizilir, kayma (offset) bir öncekilerin toplamı kadar geriye alınır.
        $cevre = 2 * M_PI * 36;
        $dilimler = [];
        $kayma = 0.0;

        foreach (self::RENKLER as $tip => $renk) {
            $adet = (int) ($sayilar[$tip] ?? 0);
            if ($adet === 0) {
                continue;   // boş dilim çizilmez
            }

            $yuzde = $adet / $toplam;
            $uzunluk = $yuzde * $cevre;

            $dilimler[] = [
                'etiket' => self::ETIKETLER[$tip],
                'adet' => $adet,
                'yuzde' => round($yuzde * 100),
                'renk' => $renk,
                'uzunluk' => round($uzunluk, 2),
                'kayma' => round(-$kayma, 2),
            ];

            $kayma += $uzunluk;
        }

        return ['dilimler' => $dilimler, 'toplam' => $toplam, 'cevre' => round($cevre, 2)];
    }
}

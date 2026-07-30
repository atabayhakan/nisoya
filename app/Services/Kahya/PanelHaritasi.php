<?php

namespace App\Services\Kahya;

use Filament\Facades\Filament;
use Throwable;

/**
 * Yönetim panelinin "neresi nerede" haritası — Kâhya'nın yol tarifi kaynağı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN ELLE YAZILMIŞ LİSTE DEĞİL
 *
 * Harita Filament'in KAYITLI panelinden türetilir: yeni bir ekran eklendiğinde
 * Kâhya onu kendiliğinden tanır, ekran silindiğinde haritadan düşer. Elle
 * yazılmış bir liste ilk hafta doğru, üçüncü ay yalan olurdu — ve "SEO nerede?"
 * sorusuna eski cevabı veren bir asistan, hiç cevap vermeyeninden kötüdür.
 *
 * Kenar çizgisi: harita yalnızca SOL MENÜDE GÖRÜNEN ekranları anlatır
 * (shouldRegisterNavigation). Menüde olmayan bir ekranın yol tarifi sahibi
 * bulamayacağı bir yere gönderir.
 */
class PanelHaritasi
{
    /**
     * Yapay zekâ yönergesine giren düz metin: grup → ekran adı → adres.
     *
     * Panel çözülemezse (ör. paneli olmayan bir konsol bağlamı) boş döner —
     * Kâhya yol tarifi veremez ama konuşmaya devam eder.
     */
    public function metin(): string
    {
        try {
            $panel = Filament::getPanel('admin');
        } catch (Throwable) {
            return '';
        }

        /** @var array<string, list<array{sira: int, satir: string}>> $gruplar */
        $gruplar = [];

        foreach ([...$panel->getResources(), ...$panel->getPages()] as $sinif) {
            try {
                if (! $sinif::shouldRegisterNavigation()) {
                    continue;
                }

                $grup = (string) ($sinif::getNavigationGroup() ?? 'Genel');
                $etiket = (string) $sinif::getNavigationLabel();
                $adres = (string) $sinif::getUrl();
                $sira = (int) ($sinif::getNavigationSort() ?? 0);
            } catch (Throwable) {
                // Rotası henüz kayıtlı olmayan tek bir ekran bütün haritayı
                // düşürmesin; o ekran bu seferlik tarifsiz kalır.
                continue;
            }

            $gruplar[$grup][] = ['sira' => $sira, 'satir' => "- {$etiket}: {$adres}"];
        }

        if ($gruplar === []) {
            return '';
        }

        // Grup sırası sidebar ile aynı olsun: sahibin ekranda gördüğü düzen
        // neyse Kâhya'nın tarif ettiği düzen de o. Provider'da olmayan bir
        // grup (ör. "Genel") listenin sonuna düşer — sidebar da öyle yapar.
        $kanonikSira = array_map(
            fn ($grup): string => (string) $grup->getLabel(),
            $panel->getNavigationGroups(),
        );

        uksort($gruplar, function (string $a, string $b) use ($kanonikSira): int {
            $ai = array_search($a, $kanonikSira, true);
            $bi = array_search($b, $kanonikSira, true);

            return ($ai === false ? PHP_INT_MAX : $ai) <=> ($bi === false ? PHP_INT_MAX : $bi);
        });

        $parcalar = [];

        foreach ($gruplar as $grup => $ekranlar) {
            usort($ekranlar, fn (array $a, array $b): int => $a['sira'] <=> $b['sira']);

            $parcalar[] = "### {$grup}\n".implode("\n", array_column($ekranlar, 'satir'));
        }

        return implode("\n\n", $parcalar);
    }
}

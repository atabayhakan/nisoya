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
        $ekranlar = $this->ekranlar();

        if ($ekranlar === []) {
            return '';
        }

        /** @var array<string, list<array{sira: int, satir: string}>> $gruplar */
        $gruplar = [];

        foreach ($ekranlar as $ekran) {
            $gruplar[$ekran['grup']][] = [
                'sira' => $ekran['sira'],
                'satir' => "- {$ekran['etiket']}: {$ekran['adres']}",
            ];
        }

        // Grup sırası sidebar ile aynı olsun: sahibin ekranda gördüğü düzen
        // neyse Kâhya'nın tarif ettiği düzen de o. Provider'da olmayan bir
        // grup (ör. "Genel") listenin sonuna düşer — sidebar da öyle yapar.
        $kanonikSira = array_map(
            fn ($grup): string => (string) $grup->getLabel(),
            Filament::getPanel('admin')->getNavigationGroups(),
        );

        uksort($gruplar, function (string $a, string $b) use ($kanonikSira): int {
            $ai = array_search($a, $kanonikSira, true);
            $bi = array_search($b, $kanonikSira, true);

            return ($ai === false ? PHP_INT_MAX : $ai) <=> ($bi === false ? PHP_INT_MAX : $bi);
        });

        $parcalar = [];

        foreach ($gruplar as $grup => $satirlar) {
            usort($satirlar, fn (array $a, array $b): int => $a['sira'] <=> $b['sira']);

            $parcalar[] = "### {$grup}\n".implode("\n", array_column($satirlar, 'satir'));
        }

        return implode("\n\n", $parcalar);
    }

    /**
     * Bir adresin haritada GERÇEKTEN var olan bir ekrana çıkıp çıkmadığı.
     *
     * Kâhya'nın yol göstermesi buradan geçer: model `panel-yonlendir` aracına
     * ne yazarsa yazsın, arayüze giden adres modelin metni değil haritanın
     * kendi kanonik adresidir. Uydurulmuş, eskimiş ya da alan adı değiştirilmiş
     * (`https://kotu.site/yonetim/tags`) bir adres ya null olur ya da yalnızca
     * YOL kısmı eşleştiği için haritadaki gerçek adresle DEĞİŞTİRİLİR.
     *
     * Dönen adres YOL biçimindedir (`/yonetim/tags`): panel her zaman sitenin
     * kendi alan adında yaşar; APP_URL ile gerçek sunum adresi ayrıştığında
     * (yerel geliştirme, proxy) mutlak adres yanlış kapıya götürürdü.
     */
    public function bul(string $adres): ?PanelHedefi
    {
        $aranan = $this->yol($adres);

        if ($aranan === null) {
            return null;
        }

        foreach ($this->ekranlar() as $ekran) {
            $yol = $this->yol($ekran['adres']);

            if ($yol === $aranan) {
                return new PanelHedefi($ekran['etiket'], $yol);
            }
        }

        return null;
    }

    /**
     * Menüde görünen ekranların ham listesi — metin() ve bul() aynı kaynaktan
     * beslensin; harita ile doğrulama asla birbirinden sapamasın.
     *
     * @return list<array{grup: string, etiket: string, adres: string, sira: int}>
     */
    private function ekranlar(): array
    {
        try {
            $panel = Filament::getPanel('admin');
        } catch (Throwable) {
            return [];
        }

        $ekranlar = [];

        foreach ([...$panel->getResources(), ...$panel->getPages()] as $sinif) {
            try {
                if (! $sinif::shouldRegisterNavigation()) {
                    continue;
                }

                $ekranlar[] = [
                    'grup' => (string) ($sinif::getNavigationGroup() ?? 'Genel'),
                    'etiket' => (string) $sinif::getNavigationLabel(),
                    'adres' => (string) $sinif::getUrl(),
                    'sira' => (int) ($sinif::getNavigationSort() ?? 0),
                ];
            } catch (Throwable) {
                // Rotası henüz kayıtlı olmayan tek bir ekran bütün haritayı
                // düşürmesin; o ekran bu seferlik tarifsiz kalır.
                continue;
            }
        }

        return $ekranlar;
    }

    /**
     * Karşılaştırma anahtarı: adresin yalnız yol kısmı, sondaki eğik çizgiler
     * atılmış hâlde. Model bazen tam adres, bazen `/yonetim/tags` yazar —
     * ikisi de aynı ekrana çıkmalı.
     */
    private function yol(string $adres): ?string
    {
        $adres = trim($adres);

        if ($adres === '') {
            return null;
        }

        $yol = parse_url($adres, PHP_URL_PATH);

        if (! is_string($yol) || $yol === '') {
            return null;
        }

        $yol = rtrim($yol, '/');

        return $yol === '' ? null : $yol;
    }
}

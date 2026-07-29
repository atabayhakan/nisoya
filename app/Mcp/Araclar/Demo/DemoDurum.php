<?php

namespace App\Mcp\Araclar\Demo;

use App\Services\Demo\DemoDefteri;
use App\Services\Demo\DemoTemizleyici;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * HER ZAMAN KAYITLI — kapı kapalıyken bile.
 *
 * Yazma araçları ayar kapalıyken `tools/list`'ten düşer. Bu araç düşmez ve
 * kapının kapalı olduğunu, nereden açılacağını söyler. Sessizce kaybolan bir
 * yetenek, hata ayıklanamaz bir yetenektir: "demo üret" isteği "böyle bir araç
 * yok" ile dönerse sebep anlaşılmaz.
 */
#[Name('demo-durum')]
#[Title('Örnek veri durumu')]
#[Description(
    'Üretilmiş örnek (demo) veri partilerini listeler: her partide kaç üye, ilan, görsel, sohbet, '.
    'anlaşma ve değerlendirme var. Ayrıca örnek veri üretme kapısının açık olup olmadığını ve '.
    'defter dışında kalmış artık kayıt bulunup bulunmadığını söyler. SALT OKUMA.'
)]
#[IsReadOnly]
class DemoDurum extends DemoAraci
{
    public function __construct(
        private readonly DemoDefteri $defter,
        private readonly DemoTemizleyici $temizleyici,
    ) {}

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $acik = self::kapiAcikMi();
        $artik = $this->temizleyici->artikSayisi();

        return [
            'uretme_kapisi' => [
                'acik' => $acik,
                'aciklama' => $acik
                    ? 'Örnek veri üretilebilir (demo-uret / demo-sil araçları kayıtlı).'
                    : 'KAPALI. Sahibi yönetim panelindeki "Örnek Veri" sayfasından açmalı; '.
                      'kapalıyken demo-uret ve demo-sil araçları hiç görünmez.',
            ],
            'ortam' => (string) app()->environment(),
            'partiler' => $this->defter->partiler(),
            'defter_disi_artik' => $artik,
            'artik_uyarisi' => $artik > 0
                ? 'Defterde karşılığı olmayan işaretli demo kaydı var — bir silme yarım kalmış '.
                  'ya da defter dışında veri üretilmiş olabilir. demo-sil bunları temizlemez.'
                : null,
        ];
    }
}

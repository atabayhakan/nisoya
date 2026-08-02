<?php

namespace App\Ai\Kahya\Araclar;

use App\Ai\Kahya\YonlendirmeToplayici;
use App\Services\Kahya\PanelHaritasi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * "X nerede?" sorusunun eli ayağı: cevabı SÖYLEMEKLE kalmaz, GÖSTERİR.
 *
 * Araç çağrıldığında ekran sol menüde birkaç saniye vurgulanır ve Kâhya'nın
 * cevabının altına tek tıkla o sayfaya giden bir "Aç" düğmesi eklenir
 * (bkz. resources/views/kahya/mesajlar.blade.php ve yol-gosterici.blade.php).
 *
 * GÜVENLİK: modelin yazdığı adres burada {@see PanelHaritasi::bul} ile
 * doğrulanır — arayüze yalnızca haritanın KENDİ kanonik adresi gider. Model
 * uydurma ya da harici bir adresi ne vurgulatabilir ne düğmeye bağlatabilir.
 *
 * Bu araç EylemCalistirici'den GEÇMEZ: veritabanına yazmaz, geri alınacak bir
 * şey üretmez — denetim defterine girmesi gereken bir "iş" değil, cevabın
 * görsel bir parçasıdır. Defter yalnız kalıcı etki bırakan eylemler içindir.
 */
class PanelYonlendir implements Tool
{
    public function __construct(
        private readonly PanelHaritasi $harita,
        private readonly YonlendirmeToplayici $yonlendirici,
    ) {}

    public function name(): string
    {
        return 'panel-yonlendir';
    }

    public function description(): Stringable|string
    {
        return 'Sahip bir ekranın ya da özelliğin NEREDE olduğunu sorduğunda veya bir ekrana '
            .'gitmek istediğinde çağır: ekran sol menüde birkaç saniye vurgulanır ve cevabına '
            .'tek tıkla giden bir "Aç" düğmesi eklenir. adres, yönergendeki panel haritasından '
            .'BİREBİR alınmalı — haritada olmayan adres reddedilir. Cevabında grup ve ekran '
            .'adını söylemen yeter; ham adresi ayrıca yazma, düğme zaten götürür.';
    }

    public function handle(Request $request): Stringable|string
    {
        $adres = (string) ($request->all()['adres'] ?? '');

        $hedef = $this->harita->bul($adres);

        if ($hedef === null) {
            return "HATA: \"{$adres}\" panel haritasında yok. Yalnız yönergendeki haritada"
                .' yazan adresleri kullanabilirsin; sahibe olmayan bir yeri tarif etme.';
        }

        $this->yonlendirici->isaretle($hedef);

        return "BAŞARILI: \"{$hedef->etiket}\" sol menüde vurgulanacak ve cevabına"
            .' "Aç" düğmesi eklenecek.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'adres' => $schema->string()
                ->description('Panel haritasındaki adres, birebir (ör. /yonetim/tags).')
                ->required(),
        ];
    }
}

<?php

namespace App\Services\Kahya\Sohbet;

use App\Models\KahyaEylemKaydi;
use App\Services\Kahya\PanelHedefi;

/**
 * Kâhya'nın tek bir mesaja verdiği yanıt.
 *
 * Metin ile EYLEM ayrı tutuluyor: yapay zekânın cümlesi bir niyet beyanıdır,
 * ne olduğunu ise eylem kaydı söyler. İkisini karıştırmak, modelin "ekledim"
 * dediği ama hiçbir şeyin eklenmediği duruma kapı açar — bu sistemde en
 * tehlikeli hata sınıfı odur.
 *
 * $hedef de aynı ilkeye tabidir: model "menüde vurguladım" DİYEMEZ — hedef
 * ancak panel-yonlendir aracı çağrılıp adres haritada doğrulandıysa dolar
 * (bkz. PanelYonlendir), arayüz vurgu ve "Aç" düğmesini yalnız ona bağlar.
 */
final class SohbetYaniti
{
    public function __construct(
        public readonly string $metin,
        public readonly ?KahyaEylemKaydi $eylem = null,
        public readonly bool $onayBekliyor = false,
        public readonly ?PanelHedefi $hedef = null,
    ) {}
}

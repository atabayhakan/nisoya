<?php

namespace App\Ai\Kahya;

use App\Services\Kahya\PanelHedefi;
use App\Services\Kahya\Sohbet\KahyaSohbeti;

/**
 * Bir sohbet turunda `panel-yonlendir` aracının işaretlediği ekran hedefi.
 *
 * {@see EylemToplayici} ile aynı desen: araç turun içinde buraya bırakır,
 * {@see KahyaSohbeti} tur bitince okur ve yanıtla birlikte arayüze taşır.
 * Modelin metnine güvenilmez — hedef ancak araç GERÇEKTEN çağrıldıysa ve
 * adres haritada doğrulandıysa var olur.
 *
 * Tek hedef tutulur: model bir turda iki kez yönlendirirse sonuncusu kalır;
 * iki ayrı menü ögesini aynı anda yakıp söndürmek yol göstermek değil,
 * şaşırtmak olurdu.
 */
class YonlendirmeToplayici
{
    private ?PanelHedefi $hedef = null;

    public function isaretle(PanelHedefi $hedef): void
    {
        $this->hedef = $hedef;
    }

    public function son(): ?PanelHedefi
    {
        return $this->hedef;
    }

    public function sifirla(): void
    {
        $this->hedef = null;
    }
}

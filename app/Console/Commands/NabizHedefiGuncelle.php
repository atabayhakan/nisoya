<?php

namespace App\Console\Commands;

use App\Services\NabizService;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Nisoya Nabzı hedefini geçmiş gerçek performansa göre günceller.
 *
 * YALNIZ `nabiz.hedef_otomatik` panelden ('İçerik' → Nisoya Nabzı grubu)
 * açıkken bir şey yapar — varsayılan KAPALI: sahip belirli bir sayı görmek
 * isteyebilir (ör. bir kampanya için "100" yazmış olabilir), otomasyon onu
 * sessizce ezmemeli.
 */
class NabizHedefiGuncelle extends Command
{
    protected $signature = 'nabiz:hedef-guncelle';

    protected $description = 'Nisoya Nabzı hedefini (otomatik modda) geçmiş 3 ayın gerçek ortalamasına göre günceller';

    public function handle(NabizService $nabiz): int
    {
        if (Settings::get('nabiz.hedef_otomatik', '0') !== '1') {
            $this->comment('Otomatik hedef kapalı — hiçbir şey değişmedi.');

            return self::SUCCESS;
        }

        $eski = (int) Settings::get('nabiz.hedef_sayi', '0');
        $yeni = $nabiz->suggestNextTarget();

        Settings::setMany(['nabiz.hedef_sayi' => (string) $yeni]);

        Log::info('Nisoya Nabzı hedefi otomatik güncellendi', ['eski' => $eski, 'yeni' => $yeni]);
        $this->info("Hedef güncellendi: {$eski} → {$yeni}");

        return self::SUCCESS;
    }
}

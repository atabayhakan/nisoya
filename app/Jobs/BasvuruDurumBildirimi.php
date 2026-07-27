<?php

namespace App\Jobs;

use App\Models\JobApplication;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Adaya başvuru durumu bildirimini GECİKMELİ gönderir (Kanban panosu, 2026-07-28).
 *
 * Neden gecikmeli: pano, durum değiştirmeyi bir sürükleme kadar ucuz hâle
 * getiriyor. Yanlış sütuna düşen bir kart, geri alınamaz bir e-postaya
 * dönüşmemeli. Bu iş kuyruğa GECİKME_DAKIKA sonrası için girer ve
 * çalıştığında başvurunun O ANKİ hâlini yeniden okur:
 *
 *  - işveren kartı geri sürüklediyse → status === notified_status → sessizce çıkar
 *  - arka arkaya birkaç kez taşındıysa → yalnız SON durum için tek e-posta gider
 *  - durum artık sessiz bir sütundaysa (Gönderildi/İncelendi) → hiç gönderilmez
 *
 * Bu yüzden iş, MODELİ DEĞİL kimliği taşır: kuyrukta beklerken serileştirilmiş
 * eski bir kopyayı değil, gerçeği okuması gerekiyor.
 */
class BasvuruDurumBildirimi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Geri alma penceresi. İşverenin "yanlış sütuna bıraktım" deyip kartı
     * geri taşıması için gereken gerçekçi süre; daha uzunu adayı gereksiz
     * bekletir, daha kısası pencereyi işe yaramaz kılar.
     */
    public const GECIKME_DAKIKA = 2;

    public int $tries = 3;

    public function __construct(public int $applicationId) {}

    public function handle(): void
    {
        $basvuru = JobApplication::query()
            ->with(['jobListing', 'applicant'])
            ->find($this->applicationId);

        // Başvuru ya da aday silinmişse yapacak bir şey yok.
        if (! $basvuru || ! $basvuru->applicant || ! $basvuru->jobListing) {
            return;
        }

        // Geri alındı ya da zaten bildirildi.
        if (! $basvuru->bildirimBekliyor()) {
            return;
        }

        // Kuyrukta beklerken sessiz bir sütuna taşınmış olabilir. Kararı
        // dispatch anındaki değil, GÜNCEL durum verir.
        if (! $basvuru->status->bildirimGerektirir()) {
            return;
        }

        $ilan = $basvuru->jobListing;

        $basvuru->applicant->notify(new ApplicationStatusNotification(
            $ilan->title,
            $basvuru->status->getLabel(),
            $ilan->id,
            $ilan->slug,
        ));

        // Yalnızca gerçekten gönderdikten sonra imleci ilerlet: gönderim
        // patlarsa iş yeniden denenir ve aday bildirimi almadan "bildirildi"
        // sayılmaz.
        $basvuru->update(['notified_status' => $basvuru->status->value]);
    }
}

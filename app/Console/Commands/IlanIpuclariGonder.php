<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Notifications\IlanIpuclariNotification;
use App\Services\Kahya\IlanEksikleri;
use Illuminate\Console\Command;

/**
 * Kâhya'nın satıcıya "ilanında şunlar eksik" önerisi.
 *
 * ---------------------------------------------------------------------------
 * ASIL TASARIM KARARI: SPAM OLMAMAK
 *
 * Satıcıya kendi ilanı hakkında bildirim göndermek, insanları siteden
 * uzaklaştırmanın en hızlı yoludur. Dört kapı var ve dördü de bunun için:
 *
 *   1. YALNIZ AKTİF ilan. Taslak henüz satıcının derdi değil.
 *   2. EN AZ 3 GÜNLÜK ilan. Yeni açtığı ilana ertesi gün akıl vermek,
 *      yardım değil dırdırdır; insanlar ilanı zamanla tamamlıyor.
 *   3. İLAN BAŞINA BİR KEZ, TEKRARI YOK. Görmezden gelindiyse cevabı budur.
 *      Tekrar etmek, ilanın düzelmesini değil bildirimlerin kapatılmasını
 *      sağlar.
 *   4. TUR BAŞINA ÜST SINIR. Geçmiş birikimi tek seferde herkese
 *      patlamasın; günlük çalışıyor, sıraya kendiliğinden yayılıyor.
 *
 * Demo ilanlar hariç: örnek veri gerçek arz değil, sahibine posta atmaz.
 */
class IlanIpuclariGonder extends Command
{
    protected $signature = 'kahya:ilan-ipuclari
        {--limit=50 : Bu turda en fazla kaç ilana bildirim gönderilsin}
        {--dry : Hiçbir şey gönderme, yalnız ne olacağını yaz}';

    protected $description = 'Eksikleri olan aktif ilanların sahiplerine bir kereliğine öneri gönderir';

    public function handle(IlanEksikleri $tarayici): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $kuru = (bool) $this->option('dry');

        $ilanlar = Listing::query()
            ->where('status', ListingStatus::Aktif)
            ->where('is_demo', false)
            ->whereNull('tips_notified_at')
            ->where('created_at', '<=', now()->subDays(3))
            ->with('user')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $gonderilen = 0;
        $atlanan = 0;

        foreach ($ilanlar as $ilan) {
            $eksikler = $tarayici->tara($ilan);

            if ($eksikler === []) {
                /*
                 * Eksiksiz ilan da DAMGALANIYOR: yoksa her gün yeniden
                 * taranır ve sorgu sonsuza dek aynı ilanları döndürür.
                 * Damga "bakıldı" demek; "gönderildi" demek değil.
                 */
                if (! $kuru) {
                    $ilan->forceFill(['tips_notified_at' => now()])->save();
                }
                $atlanan++;

                continue;
            }

            if ($kuru) {
                $this->line("#{$ilan->id} {$ilan->title} → ".count($eksikler).' öneri');
                $gonderilen++;

                continue;
            }

            $ilan->user?->notify(new IlanIpuclariNotification(
                $ilan->title,
                $eksikler,
                route('panel.listings.edit', $ilan),
            ));

            $ilan->forceFill(['tips_notified_at' => now()])->save();
            $gonderilen++;
        }

        $this->info($kuru
            ? "[kuru] {$gonderilen} ilana bildirim gidecekti, {$atlanan} ilan eksiksiz."
            : "{$gonderilen} bildirim gönderildi, {$atlanan} ilan eksiksiz (damgalandı).");

        return self::SUCCESS;
    }
}

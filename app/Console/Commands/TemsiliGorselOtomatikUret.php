<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\AhlakDenetimi;
use App\Services\TemsiliGorselUretici;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 2+ gündür görselsiz kalan aktif ilanlara otomatik temsilî kapak görseli
 * üretir — sahibin panelden elle bastığı düğmeyle (TemsiliGorselController)
 * AYNI üretim yolu, yalnız tetikleyici farklı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN 2 GÜN, NEDEN BİR KEZ
 *
 * Yeni açılan ilana hemen görsel dayatmak yanlış — sahibi birkaç saat/gün
 * içinde kendi fotoğrafını ekleyebilir, ona fırsat tanınır (bkz. İlan
 * İpuçları'ndaki "3 gün" beklemesiyle aynı gerekçe: erken müdahale yardım
 * değil dırdır olur). 2 gün sonra hâlâ görselsizse görselsiz kalma ihtimali
 * yüksektir ve sitede "boş kutu" olarak durur — bu komutun çözdüğü asıl
 * şikâyet ("resimsiz ilanlar siteyi çok kötü gösteriyor").
 *
 * `temsili_gorsel_denendi_at` damgası TEK DENEME garantisi — üretim
 * başarılı/başarısız/ahlaki-kapıda-durdurulmuş FARK ETMEZ, damga basılır.
 * Aksi hâlde kalıcı-başarısız bir ilan her gün yeniden denenir (gereksiz AI
 * çağrısı parası + sahibe gün gün "Beklemede" bildirimi). Sahip isterse
 * kendi fotoğrafını yükler; ikinci bir otomatik şans sunulmaz.
 *
 * ---------------------------------------------------------------------------
 * AHLAKİ KAPI ÖNCE, GÖRSEL SONRA
 *
 * Her ilan için önce {@see AhlakDenetimi} sorulur. "Uygun değil" derse görsel
 * HİÇ ÜRETİLMEZ — ilan Beklemede'ye alınır (aynı "Onay bekliyor" durumu, bkz.
 * ListingStatus::Beklemede — ayrı bir "onaylanacaklar" ekranı yok, mevcut
 * İlanlar tablosundaki durum filtresi bunu zaten gösteriyor) ve sahibi mevcut
 * ListingStatusNotification ile bilgilendirilir (Listing::booted() zaten
 * dinliyor). AI kapalı/başarısızsa FAIL-OPEN: görsel yine üretilir — bu bir
 * güvenlik ağı, engelleyici bir kapı değil.
 */
class TemsiliGorselOtomatikUret extends Command
{
    protected $signature = 'listings:generate-representative-images
        {--limit=50 : Bu turda en fazla kaç ilana bakılsın}
        {--dry : Hiçbir şey üretme/kaydetme, yalnız ne olacağını yaz}';

    protected $description = '2+ gündür görselsiz kalan aktif ilanlara otomatik temsilî kapak görseli üretir';

    public function handle(AhlakDenetimi $ahlak, TemsiliGorselUretici $uretici): int
    {
        if (! (bool) config('ai.features.auto_representative_image')) {
            $this->info('auto_representative_image özelliği kapalı, hiçbir şey yapılmadı.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $kuru = (bool) $this->option('dry');

        $ilanlar = Listing::query()
            ->where('status', ListingStatus::Aktif)
            ->where('is_demo', false)
            ->whereDoesntHave('images')
            ->whereNull('temsili_gorsel_denendi_at')
            ->where('created_at', '<=', now()->subDays(2))
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $uretildi = 0;
        $basarisiz = 0;
        $ahlakiDurduruldu = 0;

        foreach ($ilanlar as $ilan) {
            $denetim = $ahlak->kontrolEt($ilan);

            // null (AI kapalı/başarısız) → fail-open, uygun say ve devam et.
            if ($denetim !== null && $denetim['uygun'] === false) {
                $ahlakiDurduruldu++;

                if ($kuru) {
                    $this->line("#{$ilan->id} {$ilan->title} → AHLAKİ KAPI: {$denetim['sebep']} (Beklemede'ye alınacaktı)");

                    continue;
                }

                $ilan->status = ListingStatus::Beklemede;
                $ilan->temsili_gorsel_denendi_at = now();
                $ilan->save();

                Log::info('Otomatik temsilî görsel: ahlaki kapıda durduruldu', [
                    'listing_id' => $ilan->id,
                    'sebep' => $denetim['sebep'],
                ]);

                continue;
            }

            if ($kuru) {
                $this->line("#{$ilan->id} {$ilan->title} → görsel üretilecekti");
                $uretildi++;

                continue;
            }

            $gorsel = $uretici->uret($ilan);

            $ilan->temsili_gorsel_denendi_at = now();
            $ilan->save();

            if ($gorsel !== null) {
                $uretildi++;
            } else {
                $basarisiz++;
            }
        }

        $this->info($kuru
            ? "[kuru] {$ilanlar->count()} ilan tarandı: {$uretildi} görsel üretilecekti, {$ahlakiDurduruldu} ahlaki kapıda duracaktı."
            : "{$uretildi} görsel üretildi, {$basarisiz} başarısız, {$ahlakiDurduruldu} ahlaki kapıda Beklemede'ye alındı.");

        return self::SUCCESS;
    }
}

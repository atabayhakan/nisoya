<?php

namespace App\Http\Controllers\Kahya;

use App\Http\Controllers\Controller;
use App\Services\Kahya\Dis\EngelListesi;
use App\Services\Kahya\Dis\SnsDogrulayici;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Amazon SES geri bildirimi (bounce / şikâyet) → kalıcı engel listesi.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR (2026-08-07)
 *
 * Engel listesi tablosu F4'te kurulmuştu ama onu DOLDURAN hiçbir şey yoktu.
 * AWS üretim erişimi talebinde "her ret/şikâyet adresi kalıcı engel listesine
 * girer" yazılmıştı; kodda bunun karşılığı yalnız elle yazılan satırlardı.
 * Bu uç, o cümleyi doğru hâle getiriyor.
 *
 * Bu yalnız bir uyumluluk kutusu değil: şikâyet eden birine ikinci kez yazmak
 * gönderim alanının itibarını bitirir ve bir kez bittiğinde geri gelmez.
 *
 * ---------------------------------------------------------------------------
 * ÜÇ KAPI — ÜÇÜ DE GEREKLİ
 *
 * 1. KONU (TopicArn) yapılandırılmış mı? Değilse uç HİÇBİR ŞEY yapmaz.
 * 2. İmza gerçekten Amazon'un mu? ({@see SnsDogrulayici})
 * 3. Mesaj BİZİM konumuzdan mı geldi?
 *
 * 2 olmadan herkes sahte şikâyet gönderip hedef kitlemizi susturur.
 * 3 olmadan saldırgan KENDİ AWS hesabında konu açıp geçerli imzalı mesaj
 * gönderir — imza doğrulaması tek başına "Amazon'dan geldi" der, "bizim
 * için geldi" demez.
 *
 * HTTP 200 CÖMERTLİĞİ: doğrulamayı geçen ama işimize yaramayan mesajlara da
 * 200 döneriz. SNS 200 dışını başarısızlık sayıp saatlerce tekrar dener;
 * anlamadığımız bir bildirim için kuyruğu tıkamanın anlamı yok.
 */
class SesGeriBildirimController extends Controller
{
    public function __construct(
        private readonly SnsDogrulayici $dogrulayici,
        private readonly EngelListesi $engeller,
    ) {}

    public function __invoke(Request $request): Response
    {
        $konuArn = trim((string) Settings::get('kahya.ses_konu_arn', ''));

        if ($konuArn === '') {
            // Yapılandırılmamış uç sessizce "tamam" dememeli: AWS aboneliği
            // kurulmuş sanılır, bildirimler gelir ve hiçbiri işlenmez.
            Log::warning('SES geri bildirimi geldi ama kahya.ses_konu_arn boş — mesaj işlenmedi.');

            return response('Yapılandırılmamış', 503);
        }

        $mesaj = json_decode($request->getContent(), true);

        if (! is_array($mesaj) || ! $this->dogrulayici->dogrula($mesaj)) {
            Log::warning('SES geri bildirimi imza doğrulamasından geçemedi.');

            return response('Geçersiz imza', 403);
        }

        if ((string) ($mesaj['TopicArn'] ?? '') !== $konuArn) {
            Log::warning('SES geri bildirimi başka bir SNS konusundan geldi — yok sayıldı.');

            return response('Bilinmeyen konu', 403);
        }

        return match ((string) ($mesaj['Type'] ?? '')) {
            'SubscriptionConfirmation' => $this->aboneligiOnayla($mesaj),
            'Notification' => $this->bildirimiIsle($mesaj),
            default => response('Yok sayıldı', 200),
        };
    }

    /**
     * SNS aboneliğini onaylar.
     *
     * URL mesajın içinden geliyor — yani dışarıdan. İmza ve konu kapılarından
     * geçmiş olsa da host bir kez daha doğrulanıyor: sunucuya rastgele bir
     * adrese istek attırmanın (SSRF) maliyeti, bu üç satırdan çok daha yüksek.
     *
     * @param  array<string, mixed>  $mesaj
     */
    private function aboneligiOnayla(array $mesaj): Response
    {
        $url = (string) ($mesaj['SubscribeURL'] ?? '');
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || ! preg_match('/^sns\.[a-z0-9\-]+\.amazonaws\.com$/', $host)) {
            Log::warning('SNS abonelik onayı beklenmedik bir adrese işaret ediyor — açılmadı.');

            return response('Geçersiz onay adresi', 403);
        }

        try {
            Http::timeout(10)->get($url);
            Log::info('SNS aboneliği onaylandı (SES geri bildirimi).');
        } catch (\Throwable $e) {
            Log::warning('SNS abonelik onayı başarısız', ['sebep' => $e->getMessage()]);
        }

        return response('Onaylandı', 200);
    }

    /**
     * Bounce / şikâyet bildirimini engel listesine yazar.
     *
     * @param  array<string, mixed>  $mesaj
     */
    private function bildirimiIsle(array $mesaj): Response
    {
        $icerik = json_decode((string) ($mesaj['Message'] ?? ''), true);

        if (! is_array($icerik)) {
            return response('Okunamayan gövde', 200);
        }

        // Klasik SES bildirimi `notificationType`, Event Publishing `eventType`
        // kullanır — ikisi de aynı olayı anlatır, ikisini de kabul ederiz.
        $tur = (string) ($icerik['notificationType'] ?? $icerik['eventType'] ?? '');

        [$adresler, $neden] = match ($tur) {
            'Bounce' => [$this->kaliciBounceAdresleri($icerik), 'SES: kalıcı bounce'],
            'Complaint' => [$this->adresler($icerik['complaint']['complainedRecipients'] ?? []), 'SES: şikâyet'],
            default => [[], ''],
        };

        $eklenen = 0;
        foreach ($adresler as $adres) {
            $eklenen += $this->engeller->engelle($adres, $neden) ? 1 : 0;
        }

        if ($eklenen > 0) {
            Log::info('SES geri bildirimi engel listesine işlendi', ['tur' => $tur, 'eklenen' => $eklenen]);
        }

        return response('İşlendi', 200);
    }

    /**
     * YALNIZ KALICI bounce engellenir.
     *
     * Geçici bounce (dolu kutu, sunucu bakımda) adresin geçersiz olduğunu
     * göstermez; onu kalıcı engellemek, birkaç saat kutusu dolu olan gerçek
     * bir muhatabı temelli kaybetmek olurdu. SES ayrımı `bounceType` ile verir.
     *
     * @param  array<string, mixed>  $icerik
     * @return list<string>
     */
    private function kaliciBounceAdresleri(array $icerik): array
    {
        if ((string) ($icerik['bounce']['bounceType'] ?? '') !== 'Permanent') {
            return [];
        }

        return $this->adresler($icerik['bounce']['bouncedRecipients'] ?? []);
    }

    /**
     * SES alıcı listesinden e-posta adreslerini çıkarır.
     *
     * @param  mixed  $kayitlar
     * @return list<string>
     */
    private function adresler($kayitlar): array
    {
        if (! is_array($kayitlar)) {
            return [];
        }

        $out = [];
        foreach ($kayitlar as $kayit) {
            $adres = is_array($kayit) ? ($kayit['emailAddress'] ?? null) : null;
            if (is_string($adres) && $adres !== '') {
                $out[] = $adres;
            }
        }

        return $out;
    }
}

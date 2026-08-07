<?php

namespace App\Services\Kahya\Dis;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Amazon SNS bildirimlerinin imzasını doğrular.
 *
 * ---------------------------------------------------------------------------
 * NEDEN ŞART
 *
 * SES'in bounce/şikâyet bildirimleri SNS üzerinden herkese açık bir HTTP
 * ucuna düşer. Uç doğrulama yapmazsa isteyen istediği adresi "şikâyet etti"
 * diye gönderip ENGEL LİSTESİNE yazdırabilir — yani sisteme kendi hedef
 * kitlemizi susturtabilir. Bu bir veri sızıntısı değil, sessiz bir sabotaj:
 * hiçbir hata görünmez, sadece postalar gitmemeye başlar.
 *
 * İki kapı var ve ikisi de gerekli:
 *   1. İMZA — mesaj gerçekten Amazon'dan mı geldi? (bu sınıf)
 *   2. KONU (TopicArn) — BİZİM konumuzdan mı geldi? (controller)
 * Yalnız imza yetmez: saldırgan kendi AWS hesabında konu açıp geçerli imzalı
 * mesaj gönderebilir. Yalnız ARN de yetmez: ARN gövdede yazan bir dizedir.
 *
 * ---------------------------------------------------------------------------
 * SSRF NOTU
 *
 * Sertifika, mesajın İÇİNDE yazan bir URL'den indiriliyor — yani saldırganın
 * kontrolündeki bir dizeden. Bu yüzden indirmeden ÖNCE host doğrulanıyor
 * (`sns.<bölge>.amazonaws.com`); aksi hâlde uç, iç ağa istek attırmanın
 * aracına dönerdi.
 */
class SnsDogrulayici
{
    /** İmzalanan alanlar — SIRA ÖNEMLİ, Amazon bu sırayla imzalar. */
    private const ALANLAR = [
        'Notification' => ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'],
        'SubscriptionConfirmation' => ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'],
        'UnsubscribeConfirmation' => ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'],
    ];

    /**
     * İmzalanacak kanonik metin: her alan için "ad\ndeğer\n".
     *
     * `Subject` yalnız VARSA girer — yok sayılıp boş yazılırsa imza tutmaz.
     * Saf metot: ağ yok, test doğrudan çağırır.
     *
     * @param  array<string, mixed>  $mesaj
     */
    public static function imzaMetni(array $mesaj): ?string
    {
        $tur = (string) ($mesaj['Type'] ?? '');
        $alanlar = self::ALANLAR[$tur] ?? null;

        if ($alanlar === null) {
            return null;
        }

        $metin = '';
        foreach ($alanlar as $alan) {
            if (! array_key_exists($alan, $mesaj)) {
                if ($alan === 'Subject') {
                    continue; // isteğe bağlı
                }

                return null; // zorunlu alan eksik → doğrulanamaz
            }

            $metin .= $alan."\n".$mesaj[$alan]."\n";
        }

        return $metin;
    }

    /**
     * Sertifika URL'i gerçekten Amazon'un mu? İNDİRMEDEN ÖNCE sorulur.
     *
     * Host'un `.amazonaws.com` ile BİTMESİ yeterli değil: `evil-amazonaws.com`
     * ya da `sns.amazonaws.com.kotu.example` de öyle biter. Tam kalıp aranır.
     */
    public static function sertifikaUrlGecerli(string $url): bool
    {
        $parca = parse_url($url);

        if (! is_array($parca) || ($parca['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = $parca['host'] ?? '';
        $yol = $parca['path'] ?? '';

        return (bool) preg_match('/^sns\.[a-z0-9\-]+\.amazonaws\.com$/', $host)
            && str_ends_with($yol, '.pem');
    }

    /**
     * Mesaj gerçekten Amazon SNS'ten mi geldi?
     *
     * @param  array<string, mixed>  $mesaj
     */
    public function dogrula(array $mesaj): bool
    {
        $metin = self::imzaMetni($mesaj);
        $imza = (string) ($mesaj['Signature'] ?? '');
        $sertifikaUrl = (string) ($mesaj['SigningCertURL'] ?? $mesaj['SigningCertUrl'] ?? '');

        if ($metin === null || $imza === '' || ! self::sertifikaUrlGecerli($sertifikaUrl)) {
            return false;
        }

        $pem = $this->sertifika($sertifikaUrl);
        if ($pem === null) {
            return false;
        }

        $anahtar = openssl_pkey_get_public($pem);
        if ($anahtar === false) {
            return false;
        }

        // SignatureVersion 1 = SHA1 (eski), 2 = SHA256. Bilinmeyen sürüm
        // KABUL EDİLMEZ — "tanımadım, geçir" doğrulamayı anlamsız kılardı.
        $algoritma = match ((string) ($mesaj['SignatureVersion'] ?? '')) {
            '1' => OPENSSL_ALGO_SHA1,
            '2' => OPENSSL_ALGO_SHA256,
            default => null,
        };

        if ($algoritma === null) {
            return false;
        }

        return openssl_verify($metin, base64_decode($imza, true) ?: '', $anahtar, $algoritma) === 1;
    }

    /**
     * Sertifikayı indirir ve önbelleğe alır.
     *
     * Önbellek yalnız hız için değil: uç herkese açık, önbelleksiz her sahte
     * istek Amazon'a bir indirme tetiklerdi.
     */
    private function sertifika(string $url): ?string
    {
        return Cache::remember('sns:sertifika:'.sha1($url), now()->addDay(), function () use ($url): ?string {
            try {
                $yanit = Http::timeout(5)->get($url);
            } catch (\Throwable $e) {
                Log::warning('SNS sertifikası indirilemedi', ['sebep' => $e->getMessage()]);

                return null;
            }

            return $yanit->successful() ? $yanit->body() : null;
        });
    }
}

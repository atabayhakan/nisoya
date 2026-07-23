<?php

namespace App\Services\Growth;

use Illuminate\Support\Facades\Http;

/**
 * Bir işletmenin KENDİ web sitesinden herkese açık iletişim e-postasını çıkarır.
 * Google Places e-posta vermez; bu adım onu tamamlar. Rol tabanlı adresleri
 * (info@, iletisim@, contact@...) kişisel adreslere tercih eder — hem daha
 * kullanışlı hem gizlilik açısından daha az hassas.
 *
 * NOT: Bu "işletmenin kendi sitesindeki kamuya açık bilgi"dir, Maps kazıma
 * değil. Yine de kişisel veri olabileceğinden yalnızca gönderim-izinli
 * bölgelerde çağrılmalı (bkz. EnrichmentRunner + RegionPolicy).
 */
final class ContactEnricher
{
    /** Rol tabanlı yerel-parçalar (kişisel adreslere tercih edilir). */
    private const ROLE_LOCALS = [
        'info', 'contact', 'iletisim', 'hello', 'merhaba', 'destek', 'support',
        'sales', 'satis', 'bilgi', 'reservation', 'rezervasyon',
    ];

    /** Bariz çöp/üçüncü-parti alan adları (e-posta gibi görünen ama değil). */
    private const JUNK_DOMAINS = [
        'example.com', 'example.org', 'sentry.io', 'wixpress.com', 'w3.org',
        'schema.org', 'googleapis.com', 'gstatic.com', 'cloudflare.com',
        'sentry-next.wixpress.com', 'domain.com', 'email.com', 'yourdomain.com',
    ];

    /** Web sitesini çekip iletişim e-postası döndürür (yoksa null). */
    public function enrich(?string $website, int $timeout = 8): ?string
    {
        if ($website === null || trim($website) === '') {
            return null;
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['User-Agent' => 'Nisoya/1.0 (+https://nisoya.com)'])
                ->get($this->normalizeUrl($website));

            if (! $response->successful()) {
                return null;
            }

            return $this->extractFromHtml($response->body());
        } catch (\Throwable) {
            return null;
        }
    }

    /** HTML'den en uygun iletişim e-postasını seçer (ağ yok — saf, test edilebilir). */
    public function extractFromHtml(string $html): ?string
    {
        $mailto = $this->plausible($this->matchMailto($html));
        $text = $this->plausible($this->matchInText($html));

        return $this->preferRole($mailto)
            ?? ($mailto[0] ?? null)
            ?? $this->preferRole($text)
            ?? ($text[0] ?? null);
    }

    /**
     * mailto: bağlantılarındaki e-postalar (en güçlü sinyal).
     *
     * @return list<string>
     */
    private function matchMailto(string $html): array
    {
        preg_match_all('/mailto:([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', $html, $m);

        return $m[1];
    }

    /**
     * Düz metindeki e-postalar.
     *
     * @return list<string>
     */
    private function matchInText(string $html): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $html, $m);

        return $m[0];
    }

    /**
     * Küçük harfe indir, tekilleştir, çöp/görsel uzantılı olanları ayıkla.
     *
     * @param  list<string>  $emails
     * @return list<string>
     */
    private function plausible(array $emails): array
    {
        $clean = [];
        foreach (array_unique(array_map('strtolower', $emails)) as $email) {
            $domain = explode('@', $email)[1] ?? '';

            if (preg_match('/\.(png|jpe?g|gif|webp|svg|css|js)$/i', $email)) {
                continue; // görsel/asset dosya adı, e-posta değil
            }
            foreach (self::JUNK_DOMAINS as $junk) {
                if ($domain === $junk || str_ends_with($domain, '.'.$junk)) {
                    continue 2;
                }
            }
            $clean[] = $email;
        }

        return $clean;
    }

    /**
     * Rol tabanlı (info@, iletisim@...) bir adres varsa onu döndür.
     *
     * @param  list<string>  $emails
     */
    private function preferRole(array $emails): ?string
    {
        foreach ($emails as $email) {
            if (in_array(explode('@', $email)[0], self::ROLE_LOCALS, true)) {
                return $email;
            }
        }

        return null;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        return preg_match('#^https?://#i', $url) ? $url : 'https://'.$url;
    }
}

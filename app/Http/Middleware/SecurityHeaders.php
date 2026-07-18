<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Muhafazakâr Content-Security-Policy.
 *
 * script-src/style-src BİLİNÇLİ olarak KISITLANMAZ: uygulama Alpine.js +
 * çok sayıda inline <script>/<style> kullanıyor; bunları kısıtlamak siteyi
 * kırardı (tam koruma nonce tabanlı CSP + Alpine CSP-build'i gerektirir —
 * ayrı bir iş). Yine de düşük riskli, yüksek değerli üç vektör kapatılır:
 *
 *  - object-src 'none'      → eklenti/Flash/<object> enjeksiyonu engellenir
 *                             (site hiç <object>/<embed> kullanmıyor).
 *  - base-uri 'self'        → <base> tag enjeksiyonuyla göreli URL'lerin
 *                             saldırgan sunucusuna kaçırılması engellenir.
 *  - frame-ancestors 'self' → clickjacking koruması (X-Frame-Options'ın modern,
 *                             daha güçlü eşdeğeri). Site kendi dışında bir yere
 *                             gömülemez; AdSense vb. iframe'ler ETKİLENMEZ
 *                             (onlar çocuk çerçeve, ata değil).
 *
 * default-src yazılmadığı için diğer kaynaklar (img/script/style/connect...)
 * kısıtsız kalır → mevcut hiçbir davranış bozulmaz.
 */
class SecurityHeaders
{
    private const POLICY = "object-src 'none'; base-uri 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Başka bir katman (ör. admin özel head kodu bir gün header set ederse)
        // zaten bir CSP koymuşsa ezme.
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', self::POLICY);
        }

        return $response;
    }
}

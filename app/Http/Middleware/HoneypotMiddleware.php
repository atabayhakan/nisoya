<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honeypot bot koruması: formlarda gizli bir alan (varsayılan: "website")
 * tanımlanır; bot bu alanı doldurur, gerçek kullanıcı görmez.
 *
 * Formda şu yeterli:
 *   <input type="text" name="website" tabindex="-1" autocomplete="off"
 *          class="hidden" aria-hidden="true">
 *
 * Middleware'i route'a 'honeypot' alias'ı ile uygula. Sadece POST isteklerinde
 * çalışır (GET'i etkilemez). Eğer alan doldurulmuşsa, kullanıcı sanki
 * işlem başarılıymış gibi yönlendirilir ama DB'ye hiçbir şey yazılmaz
 * (silent drop — bot'a ipucu vermez).
 */
class HoneypotMiddleware
{
    /** Honeypot alan adı — botlar genelde tüm input'ları doldurur. */
    public const FIELD = 'website';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        // Honeypot alanı boş değilse → bot
        $value = $request->input(self::FIELD);

        if (is_string($value) && trim($value) !== '') {
            // Bot tespit edildi. Sessizce başarıymış gibi davran,
            // ama middleware pipeline'ı DURDURARAK controller'a ulaşmasını engelle.
            // Yönlendirme hedefi: geldiği sayfa (back) + status flash.
            return back()
                ->withInput($request->except([self::FIELD, 'password', 'password_confirmation', '_token']))
                ->with('status', 'İşlemin alındı.');
        }

        return $next($request);
    }
}

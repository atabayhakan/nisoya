<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Yönetim paneline giren ADMIN'lerde iki faktörlü doğrulamayı zorunlu kılar.
 *
 * ---------------------------------------------------------------------------
 * NEDEN YALNIZ ADMIN
 *
 * Sahibin kararı (2026-08-05). Moderatörler parolayla girmeye devam eder;
 * kapsam genişletilmek istenirse tek yer burası — `isAdmin()` yerine
 * `role?->canAccessAdminPanel()` yeterlidir ve testi de hazır
 * (`moderator_iki_faktorsuz_girebilir`, kararın kendisini mühürlüyor).
 *
 * ---------------------------------------------------------------------------
 * NEDEN 403 DEĞİL, YÖNLENDİRME
 *
 * 403 dönseydi, 2FA'sı olmayan tek yönetici paneline BİR DAHA giremezdi:
 * 2FA'yı açacağı sayfaya ulaşmak için panele girmesi gerekir, panele girmek
 * için 2FA gerekir. Klasik kilitlenme.
 *
 * Yönlendirme bunu yapısal olarak imkânsız kılar: kullanıcı zaten oturum
 * açmıştır ve doğrudan çözümün olduğu sayfaya bırakılır. "Zorunluluk" burada
 * bir kapı değil, bir yol ayrımıdır.
 *
 * ---------------------------------------------------------------------------
 * KAYBOLAN TELEFON — bu zorunluluğun YARATTIĞI yeni risk
 *
 * 2FA zorunlu olunca, authenticator uygulamasını kaybetmek panelden kalıcı
 * dışlanma demektir. Üç çıkış yolu bilinçle bırakıldı:
 *
 *   1. Kurulumda üretilen 8 tek kullanımlık yedek kod (kullanıcı tarafı),
 *   2. `php artisan admin:recover --iki-faktor-sifirla` (sunucu erişimi),
 *   3. Kurtarma Kiti sayfasındaki yönetici sayacı — ikinci bir yönetici,
 *      birincisi kilitlendiğinde tek gerçek güvencedir.
 *
 * Bunlardan biri olmadan 2FA zorunluluğu güvenlik değil, kendi ayağına
 * sıkmadır.
 */
class YonetimIkiFaktorZorunlu
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isAdmin() && ! $user->hasTwoFactorEnabled()) {
            return redirect()
                ->route('panel.profile.2fa')
                ->with('status', 'Yönetim paneline girebilmek için iki faktörlü doğrulamayı açman gerekiyor. '
                    .'Kurulumu bitirdiğinde panel açılacak — kurulum ekranındaki 8 yedek kodu mutlaka kaydet, '
                    .'telefonunu kaybedersen panele onlarla girersin.');
        }

        return $next($request);
    }
}

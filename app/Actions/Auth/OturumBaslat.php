<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kimliği DOĞRULANMIŞ bir kullanıcı için oturumu başlatan TEK nokta.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR SINIF
 *
 * İki faktörlü doğrulama kapısı eskiden yalnız parola akışının içinde,
 * `AuthenticatedSessionController::store()` gövdesinde yaşıyordu. Google ile
 * giriş eklenirken o mantığın ikinci bir kopyası yazılsaydı, kopyalardan biri
 * 2FA'yı atlardı ve bu **sessiz** bir atlatma olurdu: giriş çalışır görünür,
 * yalnız ikinci faktör sorulmaz.
 *
 * Bu varsayımsal bir korku değil — bu depoda bir kez oldu: Filament panelinin
 * kendi giriş kapısı mevcut 2FA'yı baypas ediyordu (PR #100). Ders: kimlik
 * doğrulamanın kaç GİRİŞ YOLU olursa olsun, oturumu başlatan yol TEK olmalı.
 *
 * Kural: `Auth::login()` bu sınıfın dışında çağrılmaz.
 */
class OturumBaslat
{
    /**
     * Kullanıcının kimliği çoktan doğrulanmıştır (parola, passkey ya da
     * Google). Buradan sonrası ortak: 2FA açıksa önce challenge, değilse
     * oturum.
     *
     * @param  string|null  $intended  Girişten sonra gidilecek adres; null ise
     *                                 kullanıcının gitmek istediği sayfa ya da panel.
     */
    public function calistir(Request $request, User $user, bool $remember = false, ?string $intended = null): RedirectResponse
    {
        if ($user->hasTwoFactorEnabled()) {
            // Tam giriş YAPILMADAN bekleyen kullanıcı session'a alınır; ikinci
            // faktör doğrulanana kadar Auth::login() çağrılmaz.
            $request->session()->put('login.2fa.user_id', $user->id);
            $request->session()->put('login.2fa.remember', $remember);

            if ($intended !== null) {
                redirect()->setIntendedUrl($intended);
            }

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        $user->forceFill(['last_seen_at' => now()])->save();

        return redirect()->intended($intended ?? route('dashboard'));
    }
}

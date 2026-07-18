<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.giris');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Parolayı doğrula ama henüz oturum AÇMA (2FA açıksa önce kod istenir).
        $user = $request->validateCredentials();

        if ($user->hasTwoFactorEnabled()) {
            // Tam giriş yapmadan bekleyen kullanıcıyı session'a al ve challenge'a
            // yönlendir. 'remember' tercihi ve intended URL session'da korunur.
            $request->session()->put('login.2fa.user_id', $user->id);
            $request->session()->put('login.2fa.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $user->forceFill(['last_seen_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

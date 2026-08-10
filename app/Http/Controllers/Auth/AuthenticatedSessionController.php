<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\OturumBaslat;
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

    public function store(LoginRequest $request, OturumBaslat $oturumBaslat): RedirectResponse
    {
        // Parolayı doğrula ama henüz oturum AÇMA — 2FA kapısı dahil oturum
        // başlatmanın tamamı OturumBaslat'ta, çünkü Google girişi de aynı
        // kapıdan geçmek zorunda (ikinci kopya = sessiz 2FA atlatması).
        $user = $request->validateCredentials();

        return $oturumBaslat->calistir($request, $user, $request->boolean('remember'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

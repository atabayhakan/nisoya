<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.sifremi-unuttum');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], attributes: ['email' => 'e-posta']);

        // Bağlantı yalnızca e-posta kayıtlıysa gerçekten gönderilir; ama YANIT
        // her durumda AYNIdır. Eskiden "kullanıcı bulunamadı" hatası dönerek
        // hangi e-postaların kayıtlı olduğunu sızdırıyordu (account enumeration).
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Eğer bu e-posta bir hesaba bağlıysa, şifre sıfırlama bağlantısı gönderildi. Gelen kutunu (ve spam klasörünü) kontrol et.');
    }
}

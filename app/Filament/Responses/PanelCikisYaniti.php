<?php

namespace App\Filament\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Panel çıkışı → doğrudan site giriş ekranı.
 *
 * Filament'in varsayılanı, panelin kendi giriş sayfası olmadığında (bkz.
 * AdminPanelProvider'daki `->login()` yokluğu ve gerekçesi) paneli KÖKÜNE
 * yönlendirir. Orası da misafiri anında /giris'e atar — çalışır ama kullanıcı
 * iki yönlendirme yaşar. Tek adıma indiriliyor.
 */
class PanelCikisYaniti implements LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('login');
    }
}

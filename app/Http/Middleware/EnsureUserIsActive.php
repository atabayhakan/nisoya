<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Silinmiş VEYA askıya alınmış kullanıcı oturumu sürdüremez. Eskiden
        // yalnızca "Silinmiş" kontrol ediliyordu; askıdaki kullanıcı giriş yapıp
        // mesajlaşmaya/başvuruya devam edebiliyordu (yalnızca yeni ilan engelliydi).
        if ($user && in_array($user->status, [UserStatus::Silinmis, UserStatus::Askida], true)) {
            $message = $user->status === UserStatus::Askida
                ? 'Hesabınız askıya alındığı için oturumunuz sonlandırıldı. Ayrıntı için bizimle iletişime geçebilirsiniz.'
                : 'Hesabınız kapatıldığı için oturumunuz sonlandırıldı.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}

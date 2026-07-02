<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plain routes/web.php rotalarını (Filament'in kendi panel yetkilendirmesinin
 * kapsamadığı /yonetim/health/detailed, /yonetim/harita/* gibi uçları) gerçek
 * admin/moderatör roluyle korur. "/yonetim" URL öneki tek başına koruma
 * sağlamaz — bu middleware'siz herhangi bir giriş yapmış üye bu rotalara
 * erişebilir.
 */
class EnsureUserCanAccessAdminPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->role?->canAccessAdminPanel()) {
            abort(403);
        }

        return $next($request);
    }
}

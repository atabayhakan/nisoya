<?php

namespace App\Http\Middleware;

use App\Support\GlobalCommand\GeoContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class ResolveAdminGeoContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = GeoContext::global();
        if ($request->user()?->isAdmin()) {
            $stored = $request->session()->get(GeoContext::SESSION_KEY, []);
            if (is_array($stored) && ($stored['actor_id'] ?? null) === $request->user()->getAuthIdentifier()) {
                try {
                    $context = GeoContext::fromSelection((array) ($stored['selection'] ?? []));
                } catch (ValidationException) {
                    // Do not silently widen a deactivated/stale lens to the whole world.
                    $request->session()->forget(GeoContext::SESSION_KEY);
                    abort(409, 'Coğrafi seçim artık geçerli değil. Sayfayı yenileyip yeniden seçin.');
                }
            }
        }

        app()->instance(GeoContext::class, $context);

        return $next($request);
    }
}

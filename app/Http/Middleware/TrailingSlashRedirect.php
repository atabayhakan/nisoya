<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `/ilanlar/` → 301 → `/ilanlar`.
 *
 * Router ikisini de aynı sayfa olarak eşliyor (200/200, yönlendirme yoktu) —
 * canonical etiketi çift içerik riskini zaten kapatıyordu ama tarama bütçesi
 * hâlâ ikisine birden harcanıyordu. Kök `/` hariç tutulur (baştan eğik
 * çizgisiz bir hâli yok).
 */
class TrailingSlashRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if (in_array($request->method(), ['GET', 'HEAD'], true) && $path !== '/' && str_ends_with($path, '/')) {
            $query = $request->getQueryString();

            return redirect()->to(rtrim($path, '/').($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bir dikey modül (emlak/vasıta/davetiye/iş ilanları) kapalıysa o modülün
 * rotalarını 404 yapar. Route'a 'module:<key>' alias'ı ile uygulanır (Faz 2 · G4).
 *
 * 404 (403 değil) bilinçli: kapalı modül "yok" gibi davranmalı — varlığını
 * sızdırmaz ve mevcut olmayan sayfa deneyimiyle tutarlıdır.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(Modules::enabled($module), 404);

        return $next($request);
    }
}

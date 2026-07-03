<?php

namespace App\Http\Middleware;

use App\Services\PerformanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Her isteğin performans metriklerini toplar.
 * Sadece PERFORMANCE_LOG env true olduğunda log'a yazar.
 * Development'te Laravel Debugbar bu işi yapar — bu middleware üretim
 * ortamı içindir.
 */
class PerformanceMetricsMiddleware
{
    public function __construct(private readonly PerformanceService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->service->start();

        $response = $next($request);

        $this->service->record($request, $response->getStatusCode());

        return $response;
    }
}

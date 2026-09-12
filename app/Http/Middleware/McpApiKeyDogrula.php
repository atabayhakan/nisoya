<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class McpApiKeyDogrula
{
    /**
     * Gelen MCP HTTP isteğinin Bearer API anahtarını doğrular.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = Settings::get('mcp.api_key')
            ?? config('ai.mcp.api_key');

        // Eğer sistemde hiçbir anahtar tanımlanmamışsa güvenlik gereği tüm erişim engellenir
        if (blank($expectedKey)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Nisoya MCP sunucusu henüz yapılandırılmamış. Lütfen yönetim panelinden bir MCP API anahtarı belirleyin.',
                ],
                'id' => null,
            ], 401);
        }

        $token = $request->bearerToken();

        // Eğer Bearer başlığı yoksa veya eşleşmiyorsa 401 dön
        if (blank($token) || ! hash_equals((string) $expectedKey, (string) $token)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32000,
                    'message' => 'Geçersiz veya eksik MCP API anahtarı (Unauthorized). Lütfen Authorization: Bearer <API_KEY> başlığını ekleyin.',
                ],
                'id' => null,
            ], 401);
        }

        return $next($request);
    }
}

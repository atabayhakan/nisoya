<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Throwable;

/**
 * Nisoya Yönetim MCP araçlarının ortak tabanı.
 *
 * 1. İstisna güvenliği — beklenmedik hataları yakalar, loglar ve yapay zekâya
 *    güvenli, açıklayıcı hata mesajı döner.
 * 2. Yapılandırılmış çıktı — Response::structured ile hem okunaklı metin
 *    hem de makine tarafından ayrıştırılabilir JSON çıktısı üretir.
 */
abstract class YonetimAraci extends Tool
{
    final public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $veri = $this->calistir($request);
        } catch (Throwable $e) {
            report($e);

            return Response::error(
                'Nisoya Yönetim Aracı hatası: '.$e->getMessage()
            );
        }

        if ($veri === []) {
            return Response::text('Sonuç bulunamadı.');
        }

        return Response::structured($veri);
    }

    /**
     * Aracın yürüttüğü ana işlem.
     *
     * @return array<string, mixed>
     */
    abstract protected function calistir(Request $request): array;
}

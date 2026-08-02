<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * PWA manifest'i — statik dosya DEĞİL (public/manifest.webmanifest silindi).
 *
 * Neden dinamik: statik dosyada theme_color sabit #059669 (zümrüt) gömülüydü;
 * sahip panelden marka rengini değiştirince ya da Vitrin teması aktifken site
 * mavi, yüklü PWA'nın çubuğu yeşil kalıyordu (açık işler envanteri: canlıda
 * ölçülen uyuşmazlık). Artık renk, tarayıcı sekmesindeki theme-color ve
 * favicon ile AYNI kaynaktan gelir: brandColorHex(). Ad/açıklama da panelden
 * yönetilen SEO ayarlarını izler — manifest tek başına eskiyemez.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json([
                'name' => setting('seo.default_title') ?: 'Nisoya',
                'short_name' => setting('genel.site_adi') ?: 'Nisoya',
                'description' => setting('seo.default_description') ?: 'Yurt dışındaki Türklerin yetenek, hizmet ve ev ürünleri pazaryeri.',
                'start_url' => '/',
                'scope' => '/',
                'display' => 'standalone',
                'orientation' => 'portrait',
                'lang' => 'tr',
                'dir' => 'ltr',
                'background_color' => '#fafaf9',
                'theme_color' => brandColorHex(),
                'icons' => [
                    ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                    ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                    ['src' => '/icons/icon-maskable.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                ],
            ], 200, [
                'Content-Type' => 'application/manifest+json',
                // Kısa önbellek: marka rengi değişikliği en geç bir saatte
                // yüklü PWA'lara da ulaşsın.
                'Cache-Control' => 'public, max-age=3600',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

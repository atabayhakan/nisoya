<?php

declare(strict_types=1);

namespace App\Services\Diaspora;

use App\Support\GlobalCommand\RadarMediaUrl;
use App\Support\InstagramMedia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Instagram Reels ve gönderilerinden anında metadata (başlık, yazar, kapak, embed kodu)
 * ayrıştıran akıllı servis.
 *
 * 1. Katman: Instagram oEmbed API (Resmi & Hızlı)
 * 2. Katman: OpenGraph HTML Meta Tag ayrıştırması
 * 3. Katman: URL Regex & Heuristics (Çevrimdışı / Hızlı Fallback)
 */
class InstagramMetadataExtractor
{
    /**
     * Verilen Instagram bağlantısını analiz eder ve zenginleştirilmiş metadata döner.
     *
     * @return array{
     *     url: string,
     *     shortcode: ?string,
     *     author_name: ?string,
     *     author_username: ?string,
     *     title: ?string,
     *     caption: ?string,
     *     thumbnail_url: ?string,
     *     embed_html: ?string,
     *     embed_url: ?string,
     *     success: bool
     * }
     */
    public function extract(string $url): array
    {
        $cleanUrl = RadarMediaUrl::instagram($url);
        $shortcode = $cleanUrl ? InstagramMedia::extractShortcode($cleanUrl) : null;

        $data = [
            'url' => $cleanUrl ?? '',
            'shortcode' => $shortcode,
            'author_name' => null,
            'author_username' => null,
            'title' => null,
            'caption' => null,
            'thumbnail_url' => null,
            'embed_html' => null,
            'embed_url' => $shortcode ? "https://www.instagram.com/reel/{$shortcode}/embed" : null,
            'success' => false,
        ];

        if ($cleanUrl === null) {
            return $data;
        }

        // 1. Aşama: Instagram oEmbed API sorgusu
        $oembedData = $this->fetchFromOembed($cleanUrl);
        if ($oembedData) {
            $data['author_name'] = $oembedData['author_name'] ?? null;
            $data['author_username'] = isset($oembedData['author_name'])
                ? InstagramMedia::normalizeUsername($oembedData['author_name'])
                : null;
            $data['title'] = $oembedData['title'] ?? null;
            $data['caption'] = $oembedData['title'] ?? null;
            $data['thumbnail_url'] = $oembedData['thumbnail_url'] ?? null;
            $data['embed_html'] = $oembedData['html'] ?? null;
            $data['success'] = true;

            return $data;
        }

        // 2. Aşama: OpenGraph HTML Taraması (Fallback)
        $ogData = $this->fetchFromOpenGraph($cleanUrl);
        if ($ogData) {
            $data['author_username'] = $ogData['author_username'] ?? null;
            $data['title'] = $ogData['title'] ?? null;
            $data['caption'] = $ogData['caption'] ?? null;
            $data['thumbnail_url'] = $ogData['thumbnail_url'] ?? null;
            $data['success'] = true;

            return $data;
        }

        // 3. Aşama: Yerel Heuristic Fallback
        if ($shortcode) {
            $data['success'] = false;
            $data['title'] = "Diaspora Reels (#{$shortcode})";
        }

        return $data;
    }

    /**
     * Resmi Instagram oEmbed uç noktasından veri çeker.
     *
     * @return array<string, mixed>|null
     */
    protected function fetchFromOembed(string $url): ?array
    {
        try {
            $endpoint = 'https://api.instagram.com/oembed/';
            $response = Http::connectTimeout(2)->timeout(4)->withoutRedirecting()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($endpoint, [
                    'url' => $url,
                    'omitscript' => 'true',
                ]);

            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json)) {
                    /** @var array<string, mixed> $json */
                    return $json;
                }
            }
        } catch (\Throwable $e) {
            Log::debug('Instagram oEmbed sorgusu başarısız veya zaman aşımına uğradı', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Sayfanın OpenGraph etiketlerini ayrıştırır.
     *
     * @return array{author_username: ?string, title: ?string, caption: ?string, thumbnail_url: ?string}|null
     */
    protected function fetchFromOpenGraph(string $url): ?array
    {
        try {
            $response = Http::connectTimeout(2)->timeout(5)->withoutRedirecting()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; Googlebot/2.1; +http://www.google.com/bot.html)',
                    'Accept-Language' => 'tr,en;q=0.9',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            if (empty($html)) {
                return null;
            }

            $ogTitle = $this->extractMetaTag($html, 'og:title');
            $ogDescription = $this->extractMetaTag($html, 'og:description');
            $ogImage = $this->extractMetaTag($html, 'og:image');

            $author = null;
            if ($ogTitle && preg_match('/@([a-zA-Z0-9._]+)/', $ogTitle, $matches)) {
                $author = '@'.$matches[1];
            } elseif ($ogDescription && preg_match('/@([a-zA-Z0-9._]+)/', $ogDescription, $matches)) {
                $author = '@'.$matches[1];
            }

            $caption = $ogDescription ?: $ogTitle;

            return [
                'author_username' => $author,
                'title' => $ogTitle ? mb_substr($ogTitle, 0, 120) : null,
                'caption' => $caption ? mb_substr($caption, 0, 500) : null,
                'thumbnail_url' => $ogImage,
            ];
        } catch (\Throwable $e) {
            Log::debug('Instagram OpenGraph ayrıştırma hatası', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractMetaTag(string $html, string $property): ?string
    {
        $patterns = [
            '/<meta\s+property=["\']'.preg_quote($property, '/').'["\']\s+content=["\'](.*?)["\']/is',
            '/<meta\s+content=["\'](.*?)["\']\s+property=["\']'.preg_quote($property, '/').'["\']/is',
            '/<meta\s+name=["\']'.preg_quote($property, '/').'["\']\s+content=["\'](.*?)["\']/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            }
        }

        return null;
    }
}

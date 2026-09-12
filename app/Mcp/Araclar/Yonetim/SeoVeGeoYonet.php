<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Services\Ai\GrowthMarketingAiAssistant;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_seo_ve_geo_yonet')]
#[Title('SEO & GEO Yönetimi — 2026 Generative Engine Optimization ve /llms.txt standardı')]
#[Description(
    'Nisoya platformunun arama motoru (Google) ve yapay zekâ botları (Claude, SearchGPT, Perplexity) görünürlüğünü yönetir. '.
    'islem="denetle" (SEO ve GEO hazırlık durumunu 0-100 puanlar, eksikleri listeler), '.
    'islem="llms_txt_uret" (2026 standardı /llms.txt dosyasını derler veya günceller), '.
    'islem="meta_optimize_et" (başlık veya açıklama için CTR odaklı AI tavsiyesi üretir), '.
    'islem="ayar_guncelle" (varsayılan başlık, açıklama ve robots_index ayarlarını kaydeder).'
)]
class SeoVeGeoYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "denetle", "llms_txt_uret", "meta_optimize_et", "ayar_guncelle".')
                ->required(),
            'alan' => $schema->string()
                ->description('meta_optimize_et işlemi için alan adı: "title" veya "description".'),
            'mevcut_metin' => $schema->string()
                ->description('Optimize edilecek mevcut başlık veya açıklama metni.'),
            'anahtar_kelimeler' => $schema->string()
                ->description('Hedef anahtar kelimeler (örn: "Almanya Türk esnaf, Berlin lokanta").'),
            'varsayilan_baslik' => $schema->string()
                ->description('ayar_guncelle için yeni varsayılan başlık.'),
            'varsayilan_aciklama' => $schema->string()
                ->description('ayar_guncelle için yeni varsayılan açıklama.'),
            'robots_index' => $schema->boolean()
                ->description('ayar_guncelle için arama motoru görünürlük durumu (true: indekslenir, false: noindex).'),
            'ozel_llms_txt' => $schema->string()
                ->description('ayar_guncelle için özel /llms.txt markdown içeriği.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'denetle'));

        return match ($islem) {
            'llms_txt_uret' => $this->llmsTxtUret(),
            'meta_optimize_et' => $this->metaOptimizeEt($request),
            'ayar_guncelle' => $this->ayarGuncelle($request),
            default => $this->denetle(),
        };
    }

    /** @return array<string, mixed> */
    private function denetle(): array
    {
        $assistant = app(GrowthMarketingAiAssistant::class);
        $settings = [
            'default_title' => (string) Settings::get('seo.default_title'),
            'default_description' => (string) Settings::get('seo.default_description'),
            'og_image' => (string) Settings::get('seo.og_image'),
            'robots_index' => (Settings::get('seo.robots_index') ?? '1') === '1',
        ];
        $stats = [
            'total_listings' => Listing::query()->count(),
            'total_categories' => Category::query()->where('is_active', true)->count(),
            'total_countries' => Country::query()->where('is_active', true)->count(),
            'has_llms_txt' => true,
        ];

        $audit = $assistant->auditSeoAndGeo($settings, $stats);

        return [
            'durum' => 'basarili',
            'skor' => $audit['score'],
            'seviye' => $audit['status'],
            'ozet' => $audit['summary'],
            'geo_hazirligi' => $audit['geo_readiness'],
            'guclu_yonler' => $audit['strengths'],
            'iyilestirmeler' => $audit['improvements'],
            'eylem_maddeleri' => $audit['action_items'],
            'mevcut_ayarlar' => $settings,
        ];
    }

    /** @return array<string, mixed> */
    private function llmsTxtUret(): array
    {
        $assistant = app(GrowthMarketingAiAssistant::class);
        $content = $assistant->generateLlmsTxt();

        Cache::forget('geo_llms_txt');
        Cache::forget('geo_llms_full_txt');

        return [
            'durum' => 'basarili',
            'mesaj' => '/llms.txt standardı başarıyla dinamik derlendi ve önbelleğe alındı.',
            'karakter_sayisi' => mb_strlen($content),
            'icerik_onizleme' => mb_substr($content, 0, 500).'...',
            'canli_url' => url('/llms.txt'),
            'tam_icerik_url' => url('/llms-full.txt'),
        ];
    }

    /** @return array<string, mixed> */
    private function metaOptimizeEt(Request $request): array
    {
        $type = strtolower((string) $request->get('alan', 'title'));
        $current = (string) $request->get('mevcut_metin', '');
        $keywords = $request->get('anahtar_kelimeler');

        $assistant = app(GrowthMarketingAiAssistant::class);
        $res = $assistant->optimizeMetaTags($type, $current, $keywords ? (string) $keywords : null);

        return [
            'durum' => 'basarili',
            'alan' => $type,
            'onerilen_metin' => $res['suggested'],
            'karakter_sayisi' => $res['char_count'],
            'gerekce' => $res['reason'],
            'skor' => $res['score'],
        ];
    }

    /** @return array<string, mixed> */
    private function ayarGuncelle(Request $request): array
    {
        $payload = [];

        if ($request->has('varsayilan_baslik')) {
            $payload['seo.default_title'] = (string) $request->get('varsayilan_baslik');
        }
        if ($request->has('varsayilan_aciklama')) {
            $payload['seo.default_description'] = (string) $request->get('varsayilan_aciklama');
        }
        if ($request->has('robots_index')) {
            $payload['seo.robots_index'] = $request->get('robots_index') ? '1' : '0';
        }
        if ($request->has('ozel_llms_txt')) {
            $payload['seo.llms_txt_custom'] = (string) $request->get('ozel_llms_txt');
        }

        if (empty($payload)) {
            return [
                'durum' => 'hata',
                'mesaj' => 'Güncellenecek en az bir SEO ayarı belirtilmelidir.',
            ];
        }

        Settings::setMany($payload);
        Cache::forget('geo_llms_txt');
        Cache::forget('geo_llms_full_txt');

        return [
            'durum' => 'basarili',
            'mesaj' => 'SEO ve GEO ayarları güncellendi.',
            'guncellenen_ayarlar' => array_keys($payload),
        ];
    }
}

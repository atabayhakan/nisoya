<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Ai\GrowthMarketingAiAssistant;
use App\Support\Settings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Eylül 2026 Generative Engine Optimization (GEO) standardı /llms.txt kontrolcüsü.
 *
 * LLM ve yapay zekâ botlarına (Claude, SearchGPT, Perplexity, Gemini) platformun
 * dizin yapısını, kategorilerini ve diaspora hizmetlerini standart markdown olarak sunar.
 */
class LlmsTxtController extends Controller
{
    public function show(GrowthMarketingAiAssistant $assistant): Response
    {
        $content = Settings::get('seo.llms_txt_custom');

        if (blank($content)) {
            $content = Cache::remember('geo_llms_txt', 3600, fn () => $assistant->generateLlmsTxt());
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'all',
        ]);
    }

    public function full(GrowthMarketingAiAssistant $assistant): Response
    {
        $content = Cache::remember('geo_llms_full_txt', 3600, function () use ($assistant) {
            $base = $assistant->generateLlmsTxt();

            return $base."\n\n## Kapsamlı Dizin Sürümü (Full Index)\nNisoya üzerindeki tüm doğrulanmış işletmeler ve aktif ilanlar otomatik olarak dizine eklenmektedir.\n";
        });

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'all',
        ]);
    }
}

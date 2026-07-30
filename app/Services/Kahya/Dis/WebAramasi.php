<?php

namespace App\Services\Kahya\Dis;

use App\Models\KahyaHarcamasi;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kâhya'nın web'e açılan gözü (F3 — tasarım §3): OpenRouter, Tavily ya da Brave.
 *
 * Üç sağlayıcı bilinçli: OpenRouter web eklentisi MEVCUT AI kredisiyle
 * çalışır (ek hesap yok — varsayılan bu yüzden o; sahibin kararı,
 * 2026-07-30), Tavily LLM-tüketimi için tasarlanmış ücretsiz kotalı bir
 * arama API'si, Brave klasik arama (geniş dizin). Sahip panelden seçer;
 * anahtar da panelden girilir — env dosyasına SSH gerekmez.
 *
 * HER ÇAĞRI DEFTERE YAZILIR ve aylık limite tabidir — dış kredi harcayan
 * hiçbir yol sayaçsız bırakılmaz (tasarım: "bütçe korkusu değil, bütçe
 * görünürlüğü").
 */
class WebAramasi
{
    public const KAYNAK = 'web-ara';

    /** @return bool sağlayıcı + anahtar yapılandırılmış mı */
    public function hazirMi(): bool
    {
        return $this->anahtar() !== '';
    }

    public function saglayici(): string
    {
        return trim((string) Settings::get('kahya.arama_saglayici', '')) ?: 'openrouter';
    }

    /** Bu ay yapılmış arama sayısı — limit kapısının okuduğu sayı. */
    public function buAykiKullanim(): int
    {
        return KahyaHarcamasi::query()
            ->where('kaynak', self::KAYNAK)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function aylikLimit(): int
    {
        return max(0, (int) (Settings::get('kahya.aylik_arama_limiti') ?: 300));
    }

    /**
     * Aramayı yapar; sonuçları sade satırlara indirger.
     *
     * @return list<array{baslik: string, url: string, ozet: string}>
     *
     * @throws \RuntimeException yapılandırma/sağlayıcı hatasında — çağıran
     *                           (araç katmanı) mesajı sahibe dürüstçe iletir
     */
    public function ara(string $sorgu, int $sonucSayisi = 5): array
    {
        $sonucSayisi = min(max($sonucSayisi, 1), 10);

        $sonuclar = match ($this->saglayici()) {
            'brave' => $this->braveAra($sorgu, $sonucSayisi),
            'tavily' => $this->tavilyAra($sorgu, $sonucSayisi),
            default => $this->openrouterAra($sorgu, $sonucSayisi),
        };

        // Defter, sonuç sayısından bağımsız yazılır: başarısız sorgu da
        // krediden düşer (sağlayıcı öyle sayar), sayaç da öyle saymalı.
        KahyaHarcamasi::create([
            'kaynak' => self::KAYNAK,
            'saglayici' => $this->saglayici(),
            'model' => '',
            'detay' => mb_substr($sorgu, 0, 200),
        ]);

        return $sonuclar;
    }

    /**
     * OpenRouter web eklentisi: ucuz bir modele tek çağrı, arama sonuçları
     * yanıtın url_citation ek açıklamalarında döner (~$4/1000 sonuç,
     * mevcut krediden). Alıntı yoksa modelin özet metni tek satır olarak
     * verilir — boş dönmekten iyidir, ama kaynaklı satırlar esastır.
     *
     * @return list<array{baslik: string, url: string, ozet: string}>
     */
    private function openrouterAra(string $sorgu, int $sonucSayisi): array
    {
        $model = trim((string) config('ai.providers.openrouter.model')) ?: 'openai/gpt-4o-mini';

        $yanit = Http::timeout(45)
            ->withToken($this->anahtar())
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'plugins' => [['id' => 'web', 'max_results' => $sonucSayisi]],
                'max_tokens' => 600,
                'messages' => [[
                    'role' => 'user',
                    'content' => "Web'de ara ve kısaca Türkçe özetle: {$sorgu}",
                ]],
            ]);

        if (! $yanit->successful()) {
            $this->hatayiLogla('openrouter', $yanit->status());

            throw new \RuntimeException("Arama sağlayıcısı hata döndürdü (HTTP {$yanit->status()}). OpenRouter kredini ve anahtarını kontrol et.");
        }

        $mesaj = $yanit->json('choices.0.message') ?? [];

        $sonuclar = collect($mesaj['annotations'] ?? [])
            ->filter(fn (array $a): bool => ($a['type'] ?? '') === 'url_citation')
            ->map(fn (array $a): array => [
                'baslik' => (string) (($a['url_citation']['title'] ?? null) ?? ''),
                'url' => (string) (($a['url_citation']['url'] ?? null) ?? ''),
                'ozet' => mb_substr((string) (($a['url_citation']['content'] ?? null) ?? ''), 0, 300),
            ])
            ->values()
            ->all();

        if ($sonuclar === [] && trim((string) ($mesaj['content'] ?? '')) !== '') {
            $sonuclar = [[
                'baslik' => 'Web özeti (kaynaksız)',
                'url' => '',
                'ozet' => mb_substr(trim((string) $mesaj['content']), 0, 600),
            ]];
        }

        return $sonuclar;
    }

    /** @return list<array{baslik: string, url: string, ozet: string}> */
    private function tavilyAra(string $sorgu, int $sonucSayisi): array
    {
        $yanit = Http::timeout(20)->post('https://api.tavily.com/search', [
            'api_key' => $this->anahtar(),
            'query' => $sorgu,
            'max_results' => $sonucSayisi,
        ]);

        if (! $yanit->successful()) {
            $this->hatayiLogla('tavily', $yanit->status());

            throw new \RuntimeException("Arama sağlayıcısı hata döndürdü (HTTP {$yanit->status()}). Anahtarı Kâhya Ayarları'ndan kontrol et.");
        }

        return collect($yanit->json('results') ?? [])
            ->map(fn (array $s): array => [
                'baslik' => (string) ($s['title'] ?? ''),
                'url' => (string) ($s['url'] ?? ''),
                'ozet' => mb_substr((string) ($s['content'] ?? ''), 0, 300),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{baslik: string, url: string, ozet: string}> */
    private function braveAra(string $sorgu, int $sonucSayisi): array
    {
        $yanit = Http::timeout(20)
            ->withHeaders(['X-Subscription-Token' => $this->anahtar(), 'Accept' => 'application/json'])
            ->get('https://api.search.brave.com/res/v1/web/search', [
                'q' => $sorgu,
                'count' => $sonucSayisi,
            ]);

        if (! $yanit->successful()) {
            $this->hatayiLogla('brave', $yanit->status());

            throw new \RuntimeException("Arama sağlayıcısı hata döndürdü (HTTP {$yanit->status()}). Anahtarı Kâhya Ayarları'ndan kontrol et.");
        }

        return collect($yanit->json('web.results') ?? [])
            ->map(fn (array $s): array => [
                'baslik' => (string) ($s['title'] ?? ''),
                'url' => (string) ($s['url'] ?? ''),
                'ozet' => mb_substr(strip_tags((string) ($s['description'] ?? '')), 0, 300),
            ])
            ->values()
            ->all();
    }

    /**
     * Anahtar çözümü: panele girilen arama anahtarı her sağlayıcıda önce
     * gelir. OpenRouter'da alan BOŞ bırakılabilir — sahibin AI anahtarına
     * düşülür (Yapay Zekâ Ayarları'ndaki), sonra config'e. "Ek hesap
     * gerekmez" vaadinin kodu bu üç satırdır.
     */
    private function anahtar(): string
    {
        $anahtar = trim((string) Settings::get('kahya.arama_anahtari', ''));

        if ($anahtar !== '' || $this->saglayici() !== 'openrouter') {
            return $anahtar;
        }

        if (trim((string) Settings::get('ai.saglayici', '')) === 'openrouter') {
            $anahtar = trim((string) Settings::get('ai.api_anahtari', ''));
        }

        return $anahtar !== '' ? $anahtar : trim((string) config('ai.providers.openrouter.key', ''));
    }

    private function hatayiLogla(string $saglayici, int $durum): void
    {
        // Gövde loglanmaz: hata gövdeleri bazen isteğin kendisini (sorguyu,
        // hatta anahtarı) geri yansıtır.
        Log::error('Kâhya web araması başarısız', ['saglayici' => $saglayici, 'status' => $durum]);
    }
}

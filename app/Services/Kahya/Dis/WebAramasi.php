<?php

namespace App\Services\Kahya\Dis;

use App\Models\KahyaHarcamasi;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kâhya'nın web'e açılan gözü (F3 — tasarım §3): Tavily ya da Brave ile arama.
 *
 * İki sağlayıcı bilinçli: Tavily LLM-tüketimi için tasarlanmış (temiz
 * özetli sonuç), Brave klasik arama (geniş dizin, cömert ücretsiz kota).
 * Sahip panelden seçer; anahtar da panelden girilir — env dosyasına SSH
 * gerekmez (AI anahtarlarıyla aynı ilke).
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
        return trim((string) Settings::get('kahya.arama_saglayici', '')) ?: 'tavily';
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

        $sonuclar = $this->saglayici() === 'brave'
            ? $this->braveAra($sorgu, $sonucSayisi)
            : $this->tavilyAra($sorgu, $sonucSayisi);

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

    private function anahtar(): string
    {
        return trim((string) Settings::get('kahya.arama_anahtari', ''));
    }

    private function hatayiLogla(string $saglayici, int $durum): void
    {
        // Gövde loglanmaz: hata gövdeleri bazen isteğin kendisini (sorguyu,
        // hatta anahtarı) geri yansıtır.
        Log::error('Kâhya web araması başarısız', ['saglayici' => $saglayici, 'status' => $durum]);
    }
}

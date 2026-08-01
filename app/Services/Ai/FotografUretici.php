<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI ile fotoğraf üretimi — şimdilik tek kullanıcı: demo ilan görselleri.
 *
 * NEDEN AYRI SINIF: AiProvider arayüzü analiz (görüntü→metin) sözleşmesi
 * taşıyor; üretim (metin→görüntü) her sağlayıcıda yok ve arayüze eklemek
 * dört sağlayıcıya birden boş metot dayatırdı. Bu servis bilinçli olarak
 * YALNIZ OpenRouter üzerinden çalışır (site zaten OpenRouter kredisiyle
 * dönüyor; görsel modelleri de aynı uçtan sunuluyor) — anahtar yoksa null
 * döner ve çağıran taraf kendi yedeğine düşer.
 *
 * API biçimi (OpenRouter chat completions + görsel çıktı):
 *   POST {base_url}/chat/completions
 *   { model, messages, modalities: ["image","text"] }
 * Yanıtta görsel base64 data-URL olarak choices[0].message.images[..]
 * altında gelir. Biçim değişirse null döneriz — asla istisna sızdırmayız,
 * çünkü bu servisin her kullanıcısında "AI yoksa/kırıksa zarif geri düşüş"
 * sözleşmesi var.
 */
class FotografUretici
{
    /** @var array<string, mixed> */
    private readonly array $config;

    public function __construct()
    {
        $this->config = (array) config('ai.providers.openrouter', []);
    }

    public function isConfigured(): bool
    {
        return ($this->config['api_key'] ?? '') !== '' && ($this->config['api_key'] ?? null) !== null;
    }

    /**
     * İstemden tek bir fotoğraf üretir; ham görsel baytlarını döndürür.
     * Anahtar yoksa, istek başarısızsa ya da yanıt beklenen biçimde değilse
     * null — HTTP hatası hiçbir koşulda yukarı fırlamaz.
     */
    public function uret(string $istem, int $timeoutSeconds = 90): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withToken((string) $this->config['api_key'])
                ->withHeaders(array_filter([
                    'HTTP-Referer' => $this->config['referer'] ?? null,
                    'X-Title' => $this->config['title'] ?? null,
                ]))
                ->timeout($timeoutSeconds)
                ->post(rtrim((string) ($this->config['base_url'] ?? 'https://openrouter.ai/api/v1'), '/').'/chat/completions', [
                    'model' => (string) config('ai.gorsel_model'),
                    'messages' => [['role' => 'user', 'content' => $istem]],
                    'modalities' => ['image', 'text'],
                ]);

            if (! $response->successful()) {
                Log::warning('FotografUretici: sağlayıcı hata döndü', [
                    'status' => $response->status(),
                    // Gövde kullanıcı verisi içermez (bizim istem + hata) ama
                    // yine de kısaltılır — log şişirmeye gerek yok.
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                return null;
            }

            return $this->gorseliCoz($response->json());
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Yanıttan ilk görselin baytlarını çıkarır. Sağlayıcı biçimi iki varyantta
     * görüldü: message.images[].image_url.url (data-URL) ve content parçaları
     * içinde image_url — ikisi de denenir.
     *
     * @param  array<string, mixed>|null  $json
     */
    private function gorseliCoz(?array $json): ?string
    {
        $mesaj = $json['choices'][0]['message'] ?? null;

        if (! is_array($mesaj)) {
            return null;
        }

        $adaylar = [];

        foreach ((array) ($mesaj['images'] ?? []) as $parca) {
            $adaylar[] = $parca['image_url']['url'] ?? ($parca['url'] ?? null);
        }

        if (is_array($mesaj['content'] ?? null)) {
            foreach ($mesaj['content'] as $parca) {
                if (($parca['type'] ?? '') === 'image_url') {
                    $adaylar[] = $parca['image_url']['url'] ?? null;
                }
            }
        }

        foreach ($adaylar as $url) {
            if (! is_string($url) || ! str_starts_with($url, 'data:image/')) {
                continue;
            }

            $base64 = substr($url, (int) strpos($url, ',') + 1);
            $bayt = base64_decode($base64, true);

            if ($bayt !== false && $bayt !== '') {
                return $bayt;
            }
        }

        Log::warning('FotografUretici: yanıtta çözülebilir görsel yok');

        return null;
    }
}

<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Services\Ai\Concerns\TracksLastError;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI (ve OpenAI-uyumlu: Azure OpenAI, OpenRouter, yerel sunucular)
 * Chat Completions sağlayıcısı. base_url ile uç değiştirilebilir.
 */
class OpenAiProvider implements AiProvider
{
    use TracksLastError;

    /** @param  array<string, mixed>  $config */
    public function __construct(protected readonly array $config) {}

    public function isConfigured(): bool
    {
        return filled($this->config['api_key'] ?? null);
    }

    public function name(): string
    {
        return 'OpenAI';
    }

    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
    {
        return $this->postChat([[
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$base64Image}"]],
            ],
        ]], $jsonSchema, $timeoutSeconds);
    }

    public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
    {
        return $this->postChat([[
            'role' => 'user',
            'content' => $prompt,
        ]], $jsonSchema, $timeoutSeconds);
    }

    /**
     * Chat Completions çağrısını yapıp yanıtı JSON'a çözer. analyzeImage ve
     * analyzeText yalnızca "messages" içeriğiyle farklılaşır; gerisi ortak.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>|null  $jsonSchema
     * @return array<string, mixed>|null
     */
    /**
     * JSON kipinde istem "JSON" kelimesini İÇERMEK ZORUNDA — talimatı burada,
     * TEK KAPIDAN ekliyoruz.
     *
     * -----------------------------------------------------------------------
     * CANLIDA BULUNDU (2026-08-13)
     *
     * İlan çevirisi ve dolandırıcılık tespiti üretimde ÇALIŞMIYORDU: sağlayıcı
     * her seferinde "Yanıt geçerli JSON değil (model JSON döndürmedi)" diyordu.
     *
     * Sebep: OpenRouter `responseFormat()` içinde şemayı KULLANMIYOR, OpenAI'ın
     * `json_object` kipini kullanıyor (birçok modelde strict şema desteği yok).
     * O kipin belgelenmiş şartı ise istemin içinde "JSON" kelimesinin geçmesi.
     * Şema veriliyordu ama talimat verilmiyordu; her çağıranın bunu ayrıca
     * hatırlaması gerekiyordu ve iki servis hatırlamamıştı.
     *
     * Talimatı servislere tek tek eklemek aynı hatayı bir sonraki serviste
     * tekrar üretirdi. Şema geçen HER çağrı buradan geçiyor.
     *
     * İstem zaten "JSON" diyorsa dokunulmaz — kendi biçim tarifini yazmış
     * servislerin (ListingTextService) metnini bozmamak için.
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  array<string, mixed>|null  $jsonSchema
     * @return list<array<string, mixed>>
     */
    private function jsonTalimatiEkle(array $messages, ?array $jsonSchema): array
    {
        if ($jsonSchema === null) {
            return $messages;
        }

        $metin = '';
        foreach ($messages as $m) {
            if (is_string($m['content'] ?? null)) {
                $metin .= $m['content'];
            }
        }

        if (stripos($metin, 'json') !== false) {
            return $messages;
        }

        $anahtarlar = array_keys((array) ($jsonSchema['properties'] ?? []));
        $talimat = 'Yanıtını SADECE JSON nesnesi olarak ver.';

        if ($anahtarlar !== []) {
            $talimat .= ' Anahtarlar: '.implode(', ', $anahtarlar).'. Başka metin yazma.';
        }

        $messages[] = ['role' => 'user', 'content' => $talimat];

        return $messages;
    }

    private function postChat(array $messages, ?array $jsonSchema, ?int $timeoutSeconds): ?array
    {
        $this->lastError = null;

        $messages = $this->jsonTalimatiEkle($messages, $jsonSchema);

        $body = [
            'model' => $this->config['model'],
            'max_tokens' => 1024,
            'messages' => $messages,
            'response_format' => $this->responseFormat($jsonSchema),
        ];

        $endpoint = rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/').'/chat/completions';

        $response = Http::withToken($this->config['api_key'])
            ->withHeaders($this->extraHeaders())
            ->timeout($timeoutSeconds ?? 30)
            ->post($endpoint, $body);

        if (! $response->successful()) {
            // OpenAI/OpenRouter hata gövdesi genelde {"error":{"message":...}}.
            $detail = $response->json('error.message') ?? mb_substr($response->body(), 0, 200);
            $this->lastError = 'HTTP '.$response->status().': '.$detail;
            Log::error('AI: '.$this->name().' yanıtı başarısız', ['status' => $response->status(), 'detail' => $detail]);

            return null;
        }

        $choice = $response->json('choices.0') ?? [];

        // İçerik filtresi tetiklendiyse güvenli değil.
        if (($choice['finish_reason'] ?? null) === 'content_filter') {
            $this->lastError = 'İçerik güvenlik filtresine takıldı.';

            return null;
        }

        // Model, görseli/isteği reddedip content yerine "refusal" alanı
        // döndürebilir (ör. güvenlik sınıflandırıcısı görselden rahatsız
        // oldu). Bu durumu "JSON döndürmedi" ile karıştırmamak için ayrı
        // ele alınır — aksi halde yanıltıcı bir hata mesajı görünür.
        $refusal = $choice['message']['refusal'] ?? null;
        if (filled($refusal)) {
            $this->lastError = 'Model isteği reddetti: '.mb_substr((string) $refusal, 0, 200);
            Log::warning('AI: '.$this->name().' modeli reddetti', ['refusal' => mb_substr((string) $refusal, 0, 200)]);

            return null;
        }

        $content = $choice['message']['content'] ?? null;
        $decoded = AiJson::decode($content);
        if ($decoded === null) {
            $this->lastError = 'Yanıt geçerli JSON değil (model JSON döndürmedi).';
            Log::warning('AI: '.$this->name().' yanıtı geçerli JSON değil', [
                'finish_reason' => $choice['finish_reason'] ?? null,
                'content_preview' => mb_substr((string) $content, 0, 200),
            ]);
        }

        return $decoded;
    }

    /**
     * Yanıt biçimi. Şema verilirse strict json_schema; yoksa geçerli-JSON modu.
     * Alt sınıflar (ör. OpenRouter) çok-modelli uyumluluk için override eder.
     *
     * @param  array<string, mixed>|null  $jsonSchema
     * @return array<string, mixed>
     */
    protected function responseFormat(?array $jsonSchema): array
    {
        return $jsonSchema
            ? ['type' => 'json_schema', 'json_schema' => ['name' => 'sonuc', 'strict' => true, 'schema' => $jsonSchema]]
            : ['type' => 'json_object'];
    }

    /**
     * İsteğe eklenecek ek başlıklar (alt sınıflar için — ör. OpenRouter
     * HTTP-Referer / X-Title).
     *
     * @return array<string, string>
     */
    protected function extraHeaders(): array
    {
        return [];
    }
}

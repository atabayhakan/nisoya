<?php

namespace Tests\Feature;

use App\Services\Ai\OpenRouterProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * JSON kipinde isteme "JSON" talimatı EKLENİYOR mu?
 *
 * ---------------------------------------------------------------------------
 * CANLIDA BULUNDU (2026-08-13)
 *
 * İlan çevirisi ve dolandırıcılık tespiti üretimde HİÇ ÇALIŞMIYORDU. Sağlayıcı
 * her çağrıda "Yanıt geçerli JSON değil (model JSON döndürmedi)" diyordu.
 *
 * Sebep: OpenRouter şemayı kullanmıyor, OpenAI'ın `json_object` kipini
 * kullanıyor; o kipin belgelenmiş şartı ise istemin içinde "JSON" kelimesinin
 * geçmesi. Şema veriliyordu, talimat verilmiyordu.
 *
 * Sunucuda ölçüldü: aynı istem talimatsız NULL, talimatlı
 * {"title":"Hausreinigungsservice",...} döndü.
 *
 * ---------------------------------------------------------------------------
 * NEDEN KATMANDA ÇÖZÜLDÜ
 *
 * Talimatı servislere tek tek eklemek aynı hatayı bir sonraki serviste tekrar
 * üretirdi — nitekim sekiz AI servisinden altısında yoktu. Şema geçen HER
 * çağrı tek kapıdan geçiyor; bu test o kapıyı koruyor.
 */
class AiJsonTalimatiTest extends TestCase
{
    private function saglayici(): OpenRouterProvider
    {
        return new OpenRouterProvider([
            'api_key' => 'test-anahtar',
            'model' => 'test/model',
            'base_url' => 'https://ornek.test/v1',
        ]);
    }

    /** @return array<string, mixed> Gönderilen istek gövdesi. */
    private function govdeyiYakala(callable $cagri): array
    {
        $govde = [];

        Http::fake(['ornek.test/*' => Http::response([
            'choices' => [['message' => ['content' => '{"title":"X","description":"Y"}']]],
        ])]);

        $cagri();

        Http::assertSent(function ($istek) use (&$govde) {
            $govde = $istek->data();

            return true;
        });

        return $govde;
    }

    private function tumMetin(array $govde): string
    {
        return collect($govde['messages'] ?? [])
            ->map(fn ($m) => is_string($m['content'] ?? null) ? $m['content'] : '')
            ->implode("\n");
    }

    public function test_sema_verilince_json_talimati_ekleniyor(): void
    {
        $saglayici = $this->saglayici();

        $govde = $this->govdeyiYakala(fn () => $saglayici->analyzeText(
            'Bu metni Almancaya çevir.',
            ['type' => 'object', 'properties' => ['title' => ['type' => 'string'], 'description' => ['type' => 'string']]],
        ));

        $metin = $this->tumMetin($govde);

        $this->assertStringContainsStringIgnoringCase('json', $metin,
            'Şema verildiği hâlde isteme JSON talimatı eklenmemiş — json_object kipi çalışmaz.');
        // Anahtarlar da söylenmeli: model neyi döndüreceğini bilsin.
        $this->assertStringContainsString('title', $metin);
        $this->assertStringContainsString('description', $metin);
    }

    public function test_istem_zaten_json_diyorsa_ikinci_talimat_eklenmiyor(): void
    {
        /*
         * Kendi biçim tarifini yazmış servislerin (ör. ListingTextService)
         * metnine ikinci bir talimat eklemek çelişkili yönerge üretirdi.
         */
        $saglayici = $this->saglayici();

        $govde = $this->govdeyiYakala(fn () => $saglayici->analyzeText(
            'Yanıtını SADECE JSON olarak ver: {"baslik": "..."}',
            ['type' => 'object', 'properties' => ['baslik' => ['type' => 'string']]],
        ));

        $this->assertCount(1, $govde['messages'],
            'İstem zaten JSON diyor; ikinci talimat eklenmemeliydi.');
    }

    public function test_sema_yoksa_istem_degistirilmiyor(): void
    {
        // Serbest metin isteyen çağrılar (şemasız) bozulmamalı.
        $saglayici = $this->saglayici();

        $govde = $this->govdeyiYakala(fn () => $saglayici->analyzeText('Merhaba de.'));

        $this->assertCount(1, $govde['messages']);
        $this->assertStringNotContainsStringIgnoringCase('json', $this->tumMetin($govde));
    }
}

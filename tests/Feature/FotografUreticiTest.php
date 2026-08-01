<?php

namespace Tests\Feature;

use App\Services\Ai\FotografUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * AI fotoğraf üretimi (App\Services\Ai\FotografUretici) — demo görselleri
 * için OpenRouter chat completions + modalities:[image,text] yolu.
 *
 * Sözleşmenin özü: her başarısızlık türü NULL döner, asla istisna sızmaz —
 * çağıran taraf (DemoGorselUretici) grafik tuvale düşer.
 */
class FotografUreticiTest extends TestCase
{
    use RefreshDatabase;

    /** 1×1 gerçek PNG — sahte sağlayıcı yanıtlarının gövdesi. */
    private function pngBaytlari(): string
    {
        $img = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($img);

        return (string) ob_get_clean();
    }

    public function test_basarili_yanittan_gorsel_baytlarini_cozer(): void
    {
        config(['ai.providers.openrouter.api_key' => 'sk-test']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'images' => [[
                            'image_url' => ['url' => 'data:image/png;base64,'.base64_encode($this->pngBaytlari())],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $bayt = app(FotografUretici::class)->uret('test istemi');

        $this->assertNotNull($bayt);
        $this->assertIsArray(getimagesizefromstring($bayt));

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/chat/completions')
                && $request['modalities'] === ['image', 'text']
                && $request['model'] === config('ai.gorsel_model');
        });
    }

    public function test_content_parcali_yanit_bicimi_de_cozulur(): void
    {
        config(['ai.providers.openrouter.api_key' => 'sk-test']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => [
                            ['type' => 'text', 'text' => 'İşte görsel'],
                            ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,'.base64_encode($this->pngBaytlari())]],
                        ],
                    ],
                ]],
            ]),
        ]);

        $this->assertNotNull(app(FotografUretici::class)->uret('test'));
    }

    public function test_anahtar_yoksa_http_cikmadan_null(): void
    {
        config(['ai.providers.openrouter.api_key' => null]);
        Http::fake();

        $this->assertNull(app(FotografUretici::class)->uret('test'));
        Http::assertNothingSent();
    }

    public function test_saglayici_hatasi_null_doner_istisna_sizdirmaz(): void
    {
        config(['ai.providers.openrouter.api_key' => 'sk-test']);
        Http::fake(['openrouter.ai/*' => Http::response(['error' => 'down'], 500)]);

        $this->assertNull(app(FotografUretici::class)->uret('test'));
    }

    public function test_gorselsiz_yanit_null_doner(): void
    {
        config(['ai.providers.openrouter.api_key' => 'sk-test']);
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'Görsel üretemedim.']]],
            ]),
        ]);

        $this->assertNull(app(FotografUretici::class)->uret('test'));
    }
}

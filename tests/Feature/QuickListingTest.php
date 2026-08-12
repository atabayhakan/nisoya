<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuickListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class, ProductCategorySeeder::class]);
    }

    private function enableVision(string $provider = 'anthropic'): void
    {
        config([
            'ai.default' => $provider,
            'ai.features.quick_listing' => true,
            'ai.providers.anthropic.api_key' => 'test-key',
            'ai.providers.anthropic.model' => 'claude-haiku-4-5',
            'ai.providers.openai.api_key' => 'test-key',
            'ai.providers.openai.base_url' => 'https://api.openai.com/v1',
            'ai.providers.gemini.api_key' => 'test-key',
        ]);
    }

    /** Anthropic API'sinden dönen sahte başarılı yanıt. */
    private function fakeApi(array $payload): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => json_encode($payload)]],
            ]),
        ]);
    }

    public function test_analyze_requires_auth(): void
    {
        $this->post('/panel/ilan/analiz')->assertRedirect('/giris');
    }

    public function test_quick_page_redirects_to_form_when_disabled(): void
    {
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.api_key' => null]);

        $this->actingAs(User::factory()->create())
            ->get('/panel/ilan/hizli')
            ->assertRedirect(route('panel.listings.create', ['tip' => 'urun']));
    }

    public function test_quick_page_loads_when_enabled(): void
    {
        /*
         * Ekran artık YALNIZ fotoğraf kapısı değil: metin kapısı da eklendi
         * (WhatsApp'tan yapıştır / birkaç kelime yaz) ve başlık buna göre
         * "Hızlı İlan" oldu. Bu test eski başlığı harfiyen arıyordu.
         *
         * İddia artık bölüm başlığına bakıyor — sayfa adı değişse bile
         * fotoğraf kapısının DURDUĞUNU sınıyor, ki asıl korunması gereken o.
         */
        $this->enableVision();

        $this->actingAs(User::factory()->create())
            ->get('/panel/ilan/hizli')
            ->assertOk()
            ->assertSee('Hızlı İlan')
            ->assertSee('Fotoğrafla');
    }

    public function test_analyze_prefills_form_from_photo(): void
    {
        $this->enableVision();
        $category = Category::where('type', 'urun')->whereNotNull('parent_id')->first();

        $this->fakeApi([
            'baslik' => 'İkinci el ahşap yemek masası',
            'kategori_slug' => $category->slug,
            'aciklama' => 'Koyu kahverengi, sağlam ahşap yemek masası, az kullanılmış durumda.',
            'durum' => 'Az kullanılmış',
            'fiyat_tahmini' => 120,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post('/panel/ilan/analiz', [
                'photo' => UploadedFile::fake()->image('masa.jpg', 800, 600),
            ]);

        $response->assertRedirect(route('panel.listings.create', ['tip' => 'urun']));
        $response->assertSessionHas('quick_prefill', true);
        $response->assertSessionHasInput('title', 'İkinci el ahşap yemek masası');
        $response->assertSessionHasInput('category_id', $category->id);
        $response->assertSessionHasInput('price', 120.0);
    }

    public function test_analyze_falls_back_gracefully_on_api_error(): void
    {
        $this->enableVision();
        Http::fake(['api.anthropic.com/*' => Http::response('server error', 500)]);

        $response = $this->actingAs(User::factory()->create())
            ->post('/panel/ilan/analiz', [
                'photo' => UploadedFile::fake()->image('masa.jpg'),
            ]);

        $response->assertRedirect(route('panel.listings.create', ['tip' => 'urun']));
        $response->assertSessionMissing('quick_prefill');
        $response->assertSessionHas('status');
    }

    public function test_analyze_falls_back_when_feature_disabled(): void
    {
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.api_key' => null]);
        Http::fake(); // hiç çağrı olmamalı

        $this->actingAs(User::factory()->create())
            ->post('/panel/ilan/analiz', [
                'photo' => UploadedFile::fake()->image('masa.jpg'),
            ])
            ->assertRedirect(route('panel.listings.create', ['tip' => 'urun']));

        Http::assertNothingSent();
    }

    public function test_analyze_validates_photo(): void
    {
        $this->enableVision();

        $this->actingAs(User::factory()->create())
            ->post('/panel/ilan/analiz', [])
            ->assertSessionHasErrors('photo');
    }

    /**
     * Sağlayıcı değişkenliği: AI_PROVIDER=openai olduğunda aynı akış OpenAI
     * ucuna gider ve OpenAI yanıt biçimi doğru ayrıştırılır — kod değişmeden.
     */
    public function test_analyze_uses_openai_when_selected(): void
    {
        $this->enableVision('openai');
        $category = Category::where('type', 'urun')->whereNotNull('parent_id')->first();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => json_encode([
                        'baslik' => 'Vintage deri koltuk',
                        'kategori_slug' => $category->slug,
                        'aciklama' => 'Kahverengi hakiki deri, tek kişilik vintage koltuk, iyi durumda.',
                        'durum' => 'İyi',
                        'fiyat_tahmini' => 250,
                    ])],
                ]],
            ]),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post('/panel/ilan/analiz', ['photo' => UploadedFile::fake()->image('koltuk.jpg')]);

        $response->assertRedirect(route('panel.listings.create', ['tip' => 'urun']));
        $response->assertSessionHas('quick_prefill', true);
        $response->assertSessionHasInput('title', 'Vintage deri koltuk');
        $response->assertSessionHasInput('category_id', $category->id);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }
}

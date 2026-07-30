<?php

namespace Tests\Feature;

use App\Ai\Kahya\Araclar\IsletmeKesfet;
use App\Ai\Kahya\Araclar\WebAra;
use App\Enums\UserRole;
use App\Models\KahyaHarcamasi;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

/**
 * Dış gözler (F3 — tasarım §3): web araması + işletme keşfi.
 *
 * Sınanan sözleşme: yapılandırılmamış araç TARİF verir (sessiz kalmaz),
 * limit aşımı dürüstçe reddeder, her çağrı deftere yazılır, sağlayıcı
 * yanıtları sade satırlara iner ve HTTP hatası sohbeti kırmaz.
 */
class KahyaDisGozlerTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------- web-ara

    public function test_anahtar_yoksa_yapilandirma_tarifi_doner(): void
    {
        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'test']));

        $this->assertStringContainsString('YAPILANDIRILMAMIŞ', $sonuc);
        $this->assertStringContainsString('Kâhya Ayarları', $sonuc);
        // Hiç HTTP çağrısı yapılmadı, defter boş.
        $this->assertSame(0, KahyaHarcamasi::query()->count());
    }

    /**
     * "Ek hesap gerekmez" vaadinin testi: arama anahtarı alanı BOŞKEN,
     * sahibin Yapay Zekâ Ayarları'ndaki OpenRouter anahtarı kullanılır ve
     * arama sonuçları web eklentisinin url_citation ek açıklamalarından
     * çözülür. (Üretimdeki gerçek durum tam olarak bu.)
     */
    public function test_openrouter_ai_anahtarina_duser_ve_alintilari_cozer(): void
    {
        Settings::setMany([
            'ai.saglayici' => 'openrouter',
            'ai.api_anahtari' => 'sk-or-mevcut-kredi',
            // kahya.arama_saglayici ve kahya.arama_anahtari bilerek BOŞ:
            // varsayılan openrouter + AI anahtarına düşüş sınanıyor.
        ]);
        Http::fake(['openrouter.ai/*' => Http::response([
            'choices' => [['message' => [
                'content' => 'İngiltere\'de Türk öğrenci birlikleri özeti...',
                'annotations' => [
                    ['type' => 'url_citation', 'url_citation' => [
                        'title' => 'TUSU — Turkish Student Union of UK',
                        'url' => 'https://tusu-uk.org',
                        'content' => '48 üniversite derneğini çatısında toplar.',
                    ]],
                ],
            ]]],
        ])]);

        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'UK Türk öğrenci birlikleri']));

        $this->assertStringContainsString('TUSU', $sonuc);
        $this->assertStringContainsString('https://tusu-uk.org', $sonuc);
        $this->assertDatabaseHas('kahya_harcamalar', ['kaynak' => 'web-ara', 'saglayici' => 'openrouter']);
        // Doğru anahtar gitti mi?
        Http::assertSent(fn ($istek): bool => $istek->hasHeader('Authorization', 'Bearer sk-or-mevcut-kredi'));
    }

    /** Alıntı yoksa modelin özeti kaynaksız tek satır olarak verilir — boş dönmez. */
    public function test_openrouter_alintisiz_yanitta_ozeti_verir(): void
    {
        Settings::setMany(['ai.saglayici' => 'openrouter', 'ai.api_anahtari' => 'sk-or-test']);
        Http::fake(['openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'Kaynak bulunamadı ama bilinen birlikler şunlar...']]],
        ])]);

        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'test']));

        $this->assertStringContainsString('kaynaksız', $sonuc);
        $this->assertStringContainsString('bilinen birlikler', $sonuc);
    }

    public function test_tavily_aramasi_calisir_ve_deftere_yazilir(): void
    {
        Settings::setMany(['kahya.arama_saglayici' => 'tavily', 'kahya.arama_anahtari' => 'test-anahtar']);
        Http::fake(['api.tavily.com/*' => Http::response([
            'results' => [
                ['title' => 'TUSU — Turkish Student Union', 'url' => 'https://tusu-uk.org', 'content' => 'İngiltere Türk öğrenci birliği.'],
            ],
        ])]);

        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'İngiltere Türk öğrenci birliği']));

        $this->assertStringContainsString('TUSU', $sonuc);
        $this->assertStringContainsString('https://tusu-uk.org', $sonuc);
        $this->assertDatabaseHas('kahya_harcamalar', [
            'kaynak' => 'web-ara',
            'saglayici' => 'tavily',
            'detay' => 'İngiltere Türk öğrenci birliği',
        ]);
    }

    public function test_brave_aramasi_kendi_bicimini_cozer(): void
    {
        Settings::setMany(['kahya.arama_saglayici' => 'brave', 'kahya.arama_anahtari' => 'test-anahtar']);
        Http::fake(['api.search.brave.com/*' => Http::response([
            'web' => ['results' => [
                ['title' => 'Dubai Türk Rehberi', 'url' => 'https://dubairehberi.com.tr', 'description' => 'Dubai <b>Türk</b> toplulukları.'],
            ]],
        ])]);

        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'Dubai Türk toplulukları']));

        $this->assertStringContainsString('Dubai Türk Rehberi', $sonuc);
        // HTML etiketleri temizlenir — model etiket çöpü okumasın.
        $this->assertStringNotContainsString('<b>', $sonuc);
    }

    public function test_limit_dolunca_durustce_reddeder(): void
    {
        Settings::setMany([
            'kahya.arama_anahtari' => 'test-anahtar',
            'kahya.aylik_arama_limiti' => '2',
        ]);
        KahyaHarcamasi::create(['kaynak' => 'web-ara', 'saglayici' => 'tavily', 'model' => '']);
        KahyaHarcamasi::create(['kaynak' => 'web-ara', 'saglayici' => 'tavily', 'model' => '']);
        Http::fake(); // hiç çağrı beklenmiyor

        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'test']));

        $this->assertStringContainsString('LİMİT DOLDU', $sonuc);
        $this->assertStringContainsString('2/2', $sonuc);
        Http::assertNothingSent();
    }

    public function test_saglayici_hatasi_sohbeti_kirmaz(): void
    {
        Settings::setMany(['kahya.arama_saglayici' => 'tavily', 'kahya.arama_anahtari' => 'bozuk']);
        Http::fake(['api.tavily.com/*' => Http::response(['error' => 'unauthorized'], 401)]);

        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'test']));

        $this->assertStringContainsString('HATA', $sonuc);
        $this->assertStringContainsString('401', $sonuc);
    }

    /**
     * Başarısız sorgu da deftere yazılır: sağlayıcı krediyi düşer, sayaç
     * da düşmeli — yoksa limit sayaçtan kaçar. (ara() defteri sonuçtan
     * bağımsız yazar; hata İSTİSNASI hariç — HTTP hatasında sağlayıcı
     * saymaz, biz de saymayız.)
     */
    public function test_bos_sonuc_da_deftere_yazilir(): void
    {
        Settings::setMany(['kahya.arama_saglayici' => 'tavily', 'kahya.arama_anahtari' => 'test-anahtar']);
        Http::fake(['api.tavily.com/*' => Http::response(['results' => []])]);

        $sonuc = (string) app(WebAra::class)->handle(new Request(['sorgu' => 'hiç sonuç çıkmayacak sorgu']));

        $this->assertStringContainsString('Sonuç yok', $sonuc);
        $this->assertSame(1, KahyaHarcamasi::query()->where('kaynak', 'web-ara')->count());
    }

    // ------------------------------------------------------ isletme-kesfet

    public function test_kesif_anahtarsiz_tarif_doner(): void
    {
        $sonuc = (string) app(IsletmeKesfet::class)->handle(new Request(['sorgu' => 'Turkish barber Rotterdam']));

        $this->assertStringContainsString('YAPILANDIRILMAMIŞ', $sonuc);
        $this->assertStringContainsString('Places', $sonuc);
    }

    public function test_kesif_calisir_ve_sade_satirlara_iner(): void
    {
        Settings::setMany(['kahya.places_anahtari' => 'test-anahtar']);
        Http::fake(['places.googleapis.com/*' => Http::response([
            'places' => [
                [
                    'id' => 'ChIJtest123',
                    'displayName' => ['text' => 'Anadolu Berber'],
                    'formattedAddress' => 'Hoogstraat 12, Rotterdam',
                    'rating' => 4.7,
                    'websiteUri' => 'https://anadoluberber.nl',
                ],
            ],
        ])]);

        $sonuc = (string) app(IsletmeKesfet::class)->handle(new Request(['sorgu' => 'Turkish barber Rotterdam']));

        $this->assertStringContainsString('Anadolu Berber', $sonuc);
        $this->assertStringContainsString('Rotterdam', $sonuc);
        $this->assertStringContainsString('anadoluberber.nl', $sonuc);
        $this->assertDatabaseHas('kahya_harcamalar', [
            'kaynak' => 'isletme-kesfet',
            'saglayici' => 'google-places',
        ]);
    }

    public function test_kesif_limiti_ayri_sayilir(): void
    {
        Settings::setMany([
            'kahya.places_anahtari' => 'test-anahtar',
            'kahya.aylik_kesif_limiti' => '1',
        ]);
        // Arama defteri dolu ama KEŞİF limiti ondan etkilenmemeli.
        KahyaHarcamasi::create(['kaynak' => 'web-ara', 'saglayici' => 'tavily', 'model' => '']);
        KahyaHarcamasi::create(['kaynak' => 'isletme-kesfet', 'saglayici' => 'google-places', 'model' => '']);
        Http::fake();

        $sonuc = (string) app(IsletmeKesfet::class)->handle(new Request(['sorgu' => 'test']));

        $this->assertStringContainsString('LİMİT DOLDU', $sonuc);
        $this->assertStringContainsString('1/1', $sonuc);
        Http::assertNothingSent();
    }

    // -------------------------------------------------------------- Panel

    public function test_harcama_ekrani_yalniz_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]))
            ->get('/yonetim/kahya-harcamalari')
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]))
            ->get('/yonetim/kahya-harcamalari')
            ->assertForbidden();
    }
}

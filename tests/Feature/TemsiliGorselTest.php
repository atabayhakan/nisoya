<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\Ai\FotografUretici;
use App\Services\TemsiliGorselUretici;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\ProductCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hizmet ilanlarına temsilî kapak görseli.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * 1. ÜRÜNE ASLA. Ürün ilanında fotoğraf "satılan şey budur" iddiasıdır;
 *    üretilmiş görsel oraya konursa alıcıya olmayan bir nesne gösterilir.
 *    Kapı bayrakla değil tip kontrolüyle — bayrak kapatılabilir, tip
 *    kontrolü kapatılamaz.
 * 2. ETİKET HER YÜZEYDE. Klasik kart, vitrin kartı, klasik detay, vitrin
 *    detayı. Vitrin dosyaları klasik görünümleri geçersiz kılıyor; birini
 *    güncelleyip diğerini unutmak, ETİKETSİZ bir yerde AI görselinin gerçek
 *    fotoğraf gibi durması demek. Bu depoda tam bu tuzağa beş kez düşüldü,
 *    beşi de ancak ölçümle yakalandı — o yüzden dört yüzey de ayrı ayrı
 *    render edilip sınanıyor.
 * 3. GERÇEK FOTOĞRAFIN ÜSTÜNE YAZILMAZ. Görseli olan ilana önerilmez.
 */
class TemsiliGorselTest extends TestCase
{
    use RefreshDatabase;

    /** Sağlayıcıyı taklit et: gerçek PNG baytı döndürür ya da null. */
    private function sahteUretici(bool $basarili = true, bool $anahtarVar = true): void
    {
        /*
         * Gerçek PNG baytı gerekiyor: servis bunu diske yazıp medya boru
         * hattından geçiriyor. UploadedFile::fake()->image() gerçek bir GD
         * görseli üretir; içeriğini okuyup ham bayt olarak kullanıyoruz.
         */
        $bayt = null;

        if ($basarili) {
            // Dosya nesnesini DEĞİŞKENDE TUT: UploadedFile::fake() geçici
            // dosyayı nesne yok olunca siliyor. Zincirleme yazınca
            // file_get_contents'a sıra geldiğinde dosya çoktan silinmiş
            // oluyordu.
            $sahteDosya = UploadedFile::fake()->image('temsili.png', 1280, 720);
            $bayt = (string) file_get_contents($sahteDosya->getRealPath());
        }

        $sahte = new class($bayt, $anahtarVar) extends FotografUretici
        {
            public function __construct(private ?string $bayt, private bool $anahtar)
            {
                // Üst sınıfın kurucusu config okur; burada gerek yok.
            }

            public function isConfigured(): bool
            {
                return $this->anahtar;
            }

            public function uret(string $istem, int $timeoutSeconds = 90): ?string
            {
                return $this->bayt;
            }
        };

        $this->app->instance(FotografUretici::class, $sahte);
    }

    private function ilan(string $tip = 'hizmet'): Listing
    {
        // Kategoriler seeder'dan geliyor (factory yok). Tip eşleşmesi
        // uygunMu için şart değil ama ilanın gerçekçi olması için doğrusunu
        // seçiyoruz.
        $kategori = Category::query()
            ->whereNotNull('parent_id')
            ->where('type', $tip === 'hizmet' ? 'hizmet' : 'urun')
            ->firstOrFail();

        return Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'type' => $tip,
            'status' => ListingStatus::Aktif,
            'title' => 'Ev temizliği hizmeti',
            'description' => 'Haftalık ev temizliği yapıyorum, referanslarım var.',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Hizmet kategorileri CategorySeeder'da, ürün kategorileri AYRI
        // seeder'da. Testte ürün ilanı da kuruyoruz (asıl kapı orada).
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class, ProductCategorySeeder::class]);
        Storage::fake('public');
        Storage::fake('local');
        config(['ai.features.service_image' => true]);
    }

    public function test_urun_ilanina_temsili_gorsel_onerilmez(): void
    {
        /*
         * EN ÖNEMLİ TEST. Ürün fotoğrafı bir iddiadır — üretilmiş görsel
         * oraya konarsa alıcı olmayan bir nesneyi görür.
         */
        $this->sahteUretici();

        foreach (['urun', 'emlak', 'vasita'] as $tip) {
            $ilan = $this->ilan($tip);

            $this->assertFalse(app(TemsiliGorselUretici::class)->uygunMu($ilan),
                "[$tip] ilanına temsilî görsel önerilmiş — ürün fotoğrafı bir iddiadır.");
            $this->assertNull(app(TemsiliGorselUretici::class)->uret($ilan));
            $this->assertSame(0, $ilan->images()->count());
        }
    }

    public function test_urun_ilaninda_ucu_dogrudan_cagrilsa_bile_uretmez(): void
    {
        // Düğmeyi gizlemek yetmez; uç noktanın kendisi de kapalı olmalı.
        $this->sahteUretici();
        $ilan = $this->ilan('urun');

        $this->actingAs($ilan->user)
            ->post(route('panel.listings.representative-image', $ilan))
            ->assertRedirect(route('panel.listings.edit', $ilan));

        $this->assertSame(0, $ilan->images()->count());
    }

    public function test_gorseli_olan_hizmet_ilanina_onerilmez(): void
    {
        $this->sahteUretici();
        $ilan = $this->ilan();
        $ilan->images()->create(['path_thumb' => 'a.webp', 'path_medium' => 'b.webp', 'path_large' => 'c.webp']);

        $this->assertFalse(app(TemsiliGorselUretici::class)->uygunMu($ilan),
            'Gerçek fotoğrafı olan ilana üretilmiş görsel önerilmemeli.');
    }

    public function test_baskasinin_ilanina_gorsel_uretilemez(): void
    {
        $this->sahteUretici();
        $ilan = $this->ilan();

        $this->actingAs(User::factory()->create())
            ->post(route('panel.listings.representative-image', $ilan))
            ->assertForbidden();

        $this->assertSame(0, $ilan->images()->count());
    }

    public function test_hizmet_ilanina_uretiliyor_ve_isaretleniyor(): void
    {
        $this->sahteUretici();
        $ilan = $this->ilan();

        $this->actingAs($ilan->user)
            ->post(route('panel.listings.representative-image', $ilan))
            ->assertRedirect(route('panel.listings.edit', $ilan));

        $gorsel = $ilan->images()->first();

        $this->assertNotNull($gorsel, 'Hizmet ilanına görsel üretilmemiş.');
        $this->assertTrue($gorsel->is_representative,
            'İşaret konmamış — görsel gerçek fotoğraftan ayırt edilemez hâle gelir.');
        $this->assertTrue($gorsel->is_cover);
        Storage::disk('public')->assertExists($gorsel->path_large);
    }

    public function test_uretim_basarisizsa_ilan_bozulmaz(): void
    {
        $this->sahteUretici(basarili: false);
        $ilan = $this->ilan();

        $this->actingAs($ilan->user)
            ->post(route('panel.listings.representative-image', $ilan))
            ->assertRedirect(route('panel.listings.edit', $ilan));

        $this->assertSame(0, $ilan->images()->count());
        // Geçici ham dosya arkada bırakılmamalı.
        $this->assertEmpty(Storage::disk('local')->files('temsili'));
    }

    public function test_ozellik_kapaliyken_dugme_ve_uc_kapali(): void
    {
        $this->sahteUretici();
        config(['ai.features.service_image' => false]);
        $ilan = $this->ilan();

        $this->assertFalse(app(TemsiliGorselUretici::class)->uygunMu($ilan));

        $this->actingAs($ilan->user)->post(route('panel.listings.representative-image', $ilan));
        $this->assertSame(0, $ilan->images()->count());
    }

    public function test_istem_insan_yazi_ve_logo_yasakliyor(): void
    {
        /*
         * Üretilmiş bir YÜZ, gerçek bir işletmenin yanında duran sahte bir
         * kişidir ("bu bizim ekibimiz" diye okunur). Yazı ve logo da aynı
         * sebeple yasak.
         */
        $this->sahteUretici();
        $istem = app(TemsiliGorselUretici::class)->istem($this->ilan());

        $this->assertStringContainsString('İNSAN veya YÜZ OLMASIN', $istem);
        $this->assertStringContainsString('YAZI', $istem);
        $this->assertStringContainsString('LOGO', $istem);
        $this->assertStringContainsString('Ev temizliği hizmeti', $istem, 'İlanın kendi bilgisi isteme girmemiş.');
    }

    public function test_duzenleme_sayfasinda_dugme_yalniz_uygun_ilanda(): void
    {
        $this->sahteUretici();

        $hizmet = $this->ilan();
        $this->actingAs($hizmet->user)
            ->get(route('panel.listings.edit', $hizmet))
            ->assertOk()
            ->assertSee('Temsilî görsel oluştur');

        $urun = $this->ilan('urun');
        $this->actingAs($urun->user)
            ->get(route('panel.listings.edit', $urun))
            ->assertOk()
            ->assertDontSee('Temsilî görsel oluştur');
    }

    /**
     * ETİKET DÖRT YÜZEYDE DE GÖRÜNMELİ.
     *
     * Vitrin dosyaları klasik görünümleri geçersiz kılar; yalnız birini
     * güncellemek SESSİZCE etiketsiz bir yüzey bırakır. Bu yüzden iki tema
     * da ayrı ayrı render edilip sınanıyor.
     */
    public function test_etiket_her_iki_temada_ve_her_iki_yuzeyde(): void
    {
        $this->sahteUretici();
        $ilan = $this->ilan();

        $this->actingAs($ilan->user)->post(route('panel.listings.representative-image', $ilan));
        $this->assertTrue($ilan->images()->first()->is_representative);

        foreach (['klasik', 'vitrin'] as $tema) {
            Settings::setMany(['gorunum.tema' => $tema]);

            // İlan detayı
            $this->get(route('listings.show', [$ilan, $ilan->slug]))
                ->assertOk()
                ->assertSee('Temsilî görsel', escape: false);

            // İlan listesi (kart)
            $this->get(route('listings.index'))
                ->assertOk()
                ->assertSee('Temsilî görsel', escape: false);
        }
    }
}

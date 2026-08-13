<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\DogalDilArama;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Doğal dille arama: cümleyi VAR OLAN süzgeçlere çevirir.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * 1. KULLANICININ SEÇİMİNİ EZMEZ. Ülkeyi/kategoriyi kendisi seçtiyse yorum
 *    hiç çalışmaz. Elle seçilmiş bir süzgecin AI tarafından değiştirilmesi,
 *    en sinir bozucu hata türüdür.
 * 2. GÖRÜNÜR + GERİ ALINABİLİR. Ne anlaşıldığı yazılır ve "aynen ara"
 *    bağlantısı vardır. Çıkışı olmayan otomatik davranış özellik değil tuzak.
 * 3. UYDURMAZ. Model listede olmayan bir kategori/ülke döndürürse o alan
 *    ATILIR — uydurulmuş bir slug, sebebi anlaşılmayan boş sonuç sayfasıdır.
 * 4. KISA SORGUYA DOKUNMAZ. "iphone 15" yazan kişi anahtar kelime arıyor;
 *    oraya AI sokmak para harcamak ve sonucu bozma riskidir.
 */
class DogalDilAramaTest extends TestCase
{
    use RefreshDatabase;

    private function sahteAi(?array $donen, ?string &$prompt = null): void
    {
        $sahte = new class($donen, $prompt) implements AiProvider
        {
            public int $cagriSayisi = 0;

            public function __construct(private ?array $donen, private ?string &$p) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function name(): string
            {
                return 'sahte';
            }

            public function lastError(): ?string
            {
                return null;
            }

            public function analyzeImage(string $b, string $m, string $pr, ?array $s = null, ?int $t = null): ?array
            {
                return null;
            }

            public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                $this->cagriSayisi++;
                $this->p = $prompt;

                return $this->donen;
            }
        };

        $this->app->instance(AiProvider::class, $sahte);
    }

    private function ilan(array $ustuneYaz = []): Listing
    {
        $kategori = Category::query()->whereNotNull('parent_id')->where('type', 'hizmet')->firstOrFail();

        return Listing::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'type' => 'hizmet',
            'status' => ListingStatus::Aktif,
            'is_demo' => false,
            'title' => 'Ev temizliği',
            // "temizlik" kelimesi BİLEREK burada: LIKE '%temizlik%' başlıktaki
            // "temizliği" ile eşleşmez (ek yüzünden). İlk yazışta test verisi
            // bu yüzden hiç eşleşmiyordu.
            'description' => 'Haftalık ev temizlik hizmeti veriyorum.',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'price' => 40,
        ], $ustuneYaz));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);
        config([
            'ai.features.natural_search' => true,
            // Metin denetimi ilan kurarken kuyruğa iş atmasın.
            'ai.features.text_moderation' => false,
        ]);
    }

    public function test_kisa_sorgu_yorumlanmaz(): void
    {
        // "iphone 15" anahtar kelimedir; AI'a para harcamaya değmez.
        $this->sahteAi(['q' => 'bozuk', 'sehir' => null, 'ulke' => null, 'tip' => null, 'kategori' => null, 'max' => null]);

        $arama = app(DogalDilArama::class);

        $this->assertFalse($arama->yorumlanmaliMi('iphone 15', false));
        $this->assertFalse($arama->yorumlanmaliMi('', false));
        $this->assertTrue($arama->yorumlanmaliMi('Berlin\'de ucuz ev temizliği', false));
    }

    public function test_kullanici_suzgec_sectiyse_yorumlanmaz(): void
    {
        /*
         * ASIL KURAL. Kullanıcı ülkeyi kendisi seçtiyse cümleyi yorumlayıp
         * o seçimi değiştirmek, kişinin elinden kontrolü almaktır.
         */
        $this->sahteAi(null);

        $this->assertFalse(
            app(DogalDilArama::class)->yorumlanmaliMi('Berlin\'de ucuz ev temizliği', baskaSuzgecVarMi: true),
        );
    }

    public function test_uydurulan_kategori_ve_ulke_atiliyor(): void
    {
        /*
         * Uydurulmuş bir slug, kullanıcıya sebebi anlaşılmayan BOŞ sonuç
         * sayfası gösterir. Listede olmayan her değer sessizce düşer.
         */
        $this->sahteAi([
            'q' => 'temizlik',
            'sehir' => 'Berlin',
            'ulke' => 'XX',
            'tip' => 'hizmet',
            'kategori' => 'boyle-bir-kategori-yok',
            'max' => 50,
        ]);

        $yorum = app(DogalDilArama::class)->yorumla('Berlin\'de ucuz ev temizliği');

        $this->assertNull($yorum['kategori'], 'Uydurulmuş kategori atılmamış.');
        $this->assertNull($yorum['ulke'], 'Olmayan ülke kodu atılmamış.');
        $this->assertSame('temizlik', $yorum['q']);
        $this->assertSame('Berlin', $yorum['sehir']);
        $this->assertSame(50.0, $yorum['max']);
    }

    public function test_gecersiz_tip_ve_negatif_fiyat_atiliyor(): void
    {
        $this->sahteAi([
            'q' => 'masa', 'sehir' => null, 'ulke' => null,
            'tip' => 'uydurma_tip', 'kategori' => null, 'max' => -5,
        ]);

        $yorum = app(DogalDilArama::class)->yorumla('ikinci el ahşap masa arıyorum');

        $this->assertNull($yorum['tip']);
        $this->assertNull($yorum['max']);
    }

    public function test_yorum_sonucu_daraltiyor(): void
    {
        /*
         * ÖZELLİĞİN VAR OLUŞ SEBEBİ. Cümlenin tamamı LIKE ile aranınca
         * hiçbir şey bulunmuyordu; ayrıldığında doğru ilan çıkıyor.
         */
        $this->sahteAi([
            'q' => 'temizlik', 'sehir' => 'Berlin', 'ulke' => 'DE',
            'tip' => 'hizmet', 'kategori' => null, 'max' => null,
        ]);

        $this->ilan(['title' => 'Ev temizliği', 'city' => 'Berlin']);
        $this->ilan(['title' => 'Ev temizliği', 'city' => 'Amsterdam', 'country_code' => 'NL']);

        $this->get('/ilanlar?q='.urlencode('Berlin\'de ucuz ev temizliği'))
            ->assertOk()
            ->assertSee('1 ilan bulundu');
    }

    public function test_yorum_kullaniciya_yaziliyor_ve_cikis_yolu_var(): void
    {
        $this->sahteAi([
            'q' => 'temizlik', 'sehir' => 'Berlin', 'ulke' => null,
            'tip' => null, 'kategori' => null, 'max' => 50,
        ]);

        $this->ilan();

        $yanit = $this->get('/ilanlar?q='.urlencode('Berlin\'de 50 euro altı ev temizliği'))->assertOk();

        $yanit->assertSee('Şunu anladım:', escape: false);
        $yanit->assertSee('Şehir: Berlin', escape: false);
        $yanit->assertSee('En fazla: 50', escape: false);
        $yanit->assertSee('ham=1', escape: false);
    }

    public function test_ham_parametresi_yorumu_kapatiyor(): void
    {
        // Çıkış yolunun GERÇEKTEN çalıştığı: yorum yapılmıyor, şerit yok.
        $this->sahteAi([
            'q' => 'temizlik', 'sehir' => 'Berlin', 'ulke' => null,
            'tip' => null, 'kategori' => null, 'max' => null,
        ]);

        $this->ilan();

        $this->get('/ilanlar?ham=1&q='.urlencode('Berlin\'de ucuz ev temizliği'))
            ->assertOk()
            ->assertDontSee('Şunu anladım:', escape: false);
    }

    public function test_arama_kutusunda_kullanicinin_kendi_cumlesi_kaliyor(): void
    {
        /*
         * Kutuda yorumun daralttığı kelime yazsaydı, kullanıcı yazdığını
         * düzeltemezdi — her aramada cümlesi elinden alınırdı.
         */
        $this->sahteAi([
            'q' => 'temizlik', 'sehir' => 'Berlin', 'ulke' => null,
            'tip' => null, 'kategori' => null, 'max' => null,
        ]);

        $this->ilan();

        $this->get('/ilanlar?q='.urlencode('Berlin\'de ucuz ev temizliği'))
            ->assertOk()
            ->assertSee('Berlin&#039;de ucuz ev temizliği', escape: false);
    }

    public function test_ayni_cumle_ikinci_kez_ai_cagirmiyor(): void
    {
        /*
         * Arama en çok çağrılan sayfa; her istekte AI çağırmak hem yavaş hem
         * pahalı olurdu. Sayfa 2'ye geçmek bile ikinci bir çağrı demekti.
         */
        $this->sahteAi([
            'q' => 'temizlik', 'sehir' => 'Berlin', 'ulke' => null,
            'tip' => null, 'kategori' => null, 'max' => null,
        ]);

        $arama = app(DogalDilArama::class);
        $arama->yorumla('Berlin\'de ucuz ev temizliği');
        $arama->yorumla('Berlin\'de ucuz ev temizliği');

        $this->assertSame(1, app(AiProvider::class)->cagriSayisi);
    }

    public function test_kategori_tuttugunda_serbest_metin_sonucu_daraltmiyor(): void
    {
        /*
         * CANLIDA ÖLÇÜLDÜ (2026-08-13). "Berlin'de 500 euro altı ev
         * temizliği" cümlesi hem `kategori=ev-temizligi` hem `q=ev temizliği`
         * üretiyordu ve ikisi birden uygulanıyordu.
         *
         * Sonuç: o kategoriye TAM OTURAN ama metninde "ev temizliği"
         * geçmeyen bir ilan listeden düşüyordu. Yalnız kategoriyle
         * arandığında bulunuyordu — yani yorum, aramayı iyileştirmek yerine
         * bozuyordu.
         */
        $this->sahteAi([
            'q' => 'ev temizliği', 'sehir' => null, 'ulke' => null,
            'tip' => 'hizmet', 'kategori' => 'ev-temizligi', 'max' => null,
        ]);

        $kategori = Category::query()->where('slug', 'ev-temizligi')->firstOrFail();

        Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'type' => 'hizmet',
            'status' => ListingStatus::Aktif,
            'is_demo' => false,
            'title' => 'Haftalık daire temizlik hizmeti',
            'description' => 'Daire ve ofis temizliği yapıyorum, referanslarım var.',
        ]);

        $this->get('/ilanlar?q='.urlencode('Berlinde ucuz ev temizliği'))
            ->assertOk()
            ->assertSee('Haftalık daire temizlik hizmeti');
    }

    public function test_serit_makine_degeri_degil_insan_dili_gosteriyor(): void
    {
        // Canlıda "Kategori: ev-temizligi · Ülke: DE" yazıyordu — kullanıcıya
        // URL slug'ı ve ülke kodu göstermek "anladım" demeyi boşa çıkarır.
        $this->sahteAi([
            'q' => null, 'sehir' => 'Berlin', 'ulke' => 'DE',
            'tip' => 'hizmet', 'kategori' => 'ev-temizligi', 'max' => 500,
        ]);

        $this->ilan();

        $yanit = $this->get('/ilanlar?q='.urlencode('Berlinde 500 euro altı ev temizliği'))->assertOk();

        $yanit->assertSee('Ülke: Almanya', escape: false);
        $yanit->assertSee('Tür: Hizmet', escape: false);
        $yanit->assertSee('Kategori: Ev Temizliği', escape: false);

        // Slug'ın kendisi kategori BAĞLANTILARINDA meşru olarak geçiyor;
        // yasak olan onu ŞERİTTE etiket değeri diye basmak.
        $yanit->assertDontSee('Ülke: DE', escape: false);
        $yanit->assertDontSee('Kategori: ev-temizligi', escape: false);
    }

    public function test_ozellik_kapaliyken_arama_bugunku_gibi_calisiyor(): void
    {
        config(['ai.features.natural_search' => false]);
        $this->sahteAi(['q' => 'temizlik', 'sehir' => 'Berlin', 'ulke' => null, 'tip' => null, 'kategori' => null, 'max' => null]);

        $this->ilan();

        $this->assertFalse(app(DogalDilArama::class)->yorumlanmaliMi('Berlin\'de ucuz ev temizliği', false));

        // Cümlenin tamamı LIKE ile aranıyor → eşleşme yok (bugünkü davranış).
        $this->get('/ilanlar?q='.urlencode('Berlin\'de ucuz ev temizliği'))
            ->assertOk()
            ->assertSee('0 ilan bulundu');
    }

    public function test_istem_kategori_listesini_veriyor_ve_uydurmayi_yasakliyor(): void
    {
        $this->sahteAi(null, $prompt);

        app(DogalDilArama::class)->yorumla('Berlin\'de ucuz ev temizliği');

        $this->assertStringContainsString('uydurma', $prompt);
        $this->assertStringContainsString('KATEGORİLER:', $prompt);
        $this->assertStringContainsString('ÜLKELER:', $prompt);
        $this->assertStringContainsString('SAYI UYDURMA', $prompt);
        $this->assertStringContainsString('DE (Almanya)', $prompt, 'Gerçek ülke listesi isteme girmemiş.');
    }

    public function test_serit_her_iki_temada_basiliyor(): void
    {
        $this->sahteAi([
            'q' => 'temizlik', 'sehir' => 'Berlin', 'ulke' => null,
            'tip' => null, 'kategori' => null, 'max' => null,
        ]);

        $this->ilan();

        foreach (['klasik', 'vitrin'] as $tema) {
            Settings::setMany(['gorunum.tema' => $tema]);

            $this->get('/ilanlar?q='.urlencode('Berlin\'de ucuz ev temizliği'))
                ->assertOk()
                ->assertSee('Şunu anladım:', escape: false);
        }
    }
}

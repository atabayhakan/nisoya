<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\EksikAlanTarayici;
use App\Services\Kahya\KahyaTeshisi;
use App\Services\Kahya\LogOzeti;
use App\Services\Kahya\MedyaDogrulayici;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kâhya teşhis servisleri (Faz C).
 *
 * Bu servisler günlük raporun dört bölümünü besler. Raporun değeri
 * doğruluğuna bağlı: yanlış alarm veren bir rapor birkaç gün sonra
 * okunmamaya başlar, okunmayan rapor ise hiç yazılmamış rapordur.
 */
class KahyaTeshisServisleriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    /** ContactMessage'ın factory'si yok; alanlar elle doldurulur. */
    private function bilet(): ContactMessage
    {
        return ContactMessage::create([
            'name' => 'Deneme Kişi',
            'email' => 'deneme@ornek.com',
            'category' => 'diger',
            'message' => 'Deneme mesajı',
            'status' => 'okundu',
            'first_replied_at' => null,
        ]);
    }

    // ----------------------------------------------------------- BekleyenIsler

    public function test_bekleyen_is_yoksa_bos_liste_doner(): void
    {
        $this->assertSame([], app(BekleyenIsler::class)->topla());
        $this->assertSame(0, app(BekleyenIsler::class)->toplam());
    }

    /**
     * ASIL DEĞER: moderasyonda bekleyen ilan hiçbir panoda görünmüyordu.
     * AI görsel moderasyonu ilanı yayından düşürüyor (ProcessListingImage:130)
     * ve sahip ancak ilan sahibi şikâyet edince öğreniyordu.
     */
    public function test_moderasyonda_bekleyen_ilan_raporlanir(): void
    {
        Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::query()->whereNotNull('parent_id')->value('id'),
            'status' => ListingStatus::Beklemede,
        ]);

        $kuyruklar = collect(app(BekleyenIsler::class)->topla());
        $kuyruk = $kuyruklar->firstWhere('anahtar', 'ilan_beklemede');

        $this->assertNotNull($kuyruk, 'Moderasyonda bekleyen ilan kuyruğu raporlanmalı.');
        $this->assertSame(1, $kuyruk['adet']);
        $this->assertSame('yuksek', $kuyruk['aciliyet']);
    }

    public function test_isaretlenmis_gorsel_raporlanir(): void
    {
        $ilan = Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::query()->whereNotNull('parent_id')->value('id'),
        ]);

        ListingImage::create([
            'listing_id' => $ilan->id,
            'path_thumb' => 'x/t.webp',
            'is_flagged' => true,
        ]);

        $kuyruk = collect(app(BekleyenIsler::class)->topla())->firstWhere('anahtar', 'gorsel_isaretli');

        $this->assertNotNull($kuyruk);
        $this->assertSame(1, $kuyruk['adet']);
    }

    public function test_sla_asan_bilet_raporlanir_taze_bilet_raporlanmaz(): void
    {
        // Tazesi: SLA'yı aşmadı.
        $this->bilet();

        // Gecikeni: 25 saattir yanıtsız.
        $geciken = $this->bilet();
        $geciken->forceFill(['created_at' => now()->subHours(25)])->saveQuietly();

        $kuyruk = collect(app(BekleyenIsler::class)->topla())->firstWhere('anahtar', 'bilet_geciken');

        $this->assertNotNull($kuyruk);
        $this->assertSame(1, $kuyruk['adet'], 'Yalnız SLA aşan bilet sayılmalı.');
    }

    // ------------------------------------------------------- MedyaDogrulayici

    /**
     * ASIL BEKÇİ: 2026-07-29'da canlıda bir kapak görselinin üç varyantı da
     * 404 veriyordu ve bunu ancak dışarıdan elle tarayarak bulduk.
     */
    public function test_diskte_olmayan_gorsel_yakalanir(): void
    {
        Storage::fake('public');

        $ilan = Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::query()->whereNotNull('parent_id')->value('id'),
            'title' => 'Kayıp görselli ilan',
        ]);

        // Yalnız thumb gerçekten diskte; medium ve large kayıp.
        Storage::disk('public')->put('listings/thumb/var.webp', 'x');

        ListingImage::create([
            'listing_id' => $ilan->id,
            'path_thumb' => 'listings/thumb/var.webp',
            'path_medium' => 'listings/medium/yok.webp',
            'path_large' => 'listings/large/yok.webp',
        ]);

        $sonuc = app(MedyaDogrulayici::class)->dogrula();

        $this->assertSame(1, $sonuc['kayip']);
        $this->assertSame(['medium', 'large'], $sonuc['ornekler'][0]['eksik']);
        $this->assertSame('Kayıp görselli ilan', $sonuc['ornekler'][0]['sahip']);
    }

    public function test_dosyalari_yerinde_olan_gorsel_kayip_sayilmaz(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/thumb/a.webp', 'x');

        $ilan = Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::query()->whereNotNull('parent_id')->value('id'),
        ]);

        ListingImage::create(['listing_id' => $ilan->id, 'path_thumb' => 'listings/thumb/a.webp']);

        $this->assertSame(0, app(MedyaDogrulayici::class)->dogrula()['kayip']);
    }

    /**
     * Boş kolon "kayıp dosya" DEĞİLDİR — hiç üretilmemiş varyanttır.
     * İkisi farklı sorunlar; karıştırmak yanlış alarm üretir.
     */
    public function test_bos_varyant_kolonu_kayip_sayilmaz(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/thumb/a.webp', 'x');

        $ilan = Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::query()->whereNotNull('parent_id')->value('id'),
        ]);

        ListingImage::create([
            'listing_id' => $ilan->id,
            'path_thumb' => 'listings/thumb/a.webp',
            'path_medium' => null,
        ]);

        $this->assertSame(0, app(MedyaDogrulayici::class)->dogrula()['kayip']);
    }

    // ------------------------------------------------------ EksikAlanTarayici

    public function test_kritik_bos_alan_listelenir_istege_bagli_yalniz_sayilir(): void
    {
        Settings::setMany(['gorunum.logo' => '', 'genel.site_adi' => 'Nisoya']);

        $sonuc = app(EksikAlanTarayici::class)->tara();

        $anahtarlar = array_column($sonuc['kritik'], 'anahtar');

        $this->assertContains('gorunum.logo', $anahtarlar, 'Boş logo kritik listede olmalı.');
        $this->assertNotContains('genel.site_adi', $anahtarlar, 'Dolu alan listelenmemeli.');
        $this->assertIsInt($sonuc['istege_bagli_sayi']);
    }

    public function test_ilansiz_kategoriler_sayilir(): void
    {
        $sonuc = app(EksikAlanTarayici::class)->tara();

        // Seeder tüm kategorileri kurar, hiç ilan yok → hepsi boş.
        $this->assertGreaterThan(0, $sonuc['ilansiz_kategori']['sayi']);
        $this->assertLessThanOrEqual(5, count($sonuc['ilansiz_kategori']['ornekler']), 'Örnek listesi kısa tutulmalı.');
    }

    public function test_ilani_olan_kategori_bos_sayilmaz(): void
    {
        $kategori = Category::query()->whereNotNull('parent_id')->firstOrFail();

        Listing::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'status' => ListingStatus::Aktif,
        ]);

        $ornekler = app(EksikAlanTarayici::class)->tara()['ilansiz_kategori'];
        $bosIsimler = Category::query()->where('is_active', true)
            ->whereDoesntHave('listings', fn ($q) => $q->active())->pluck('name')->all();

        $this->assertNotContains($kategori->name, $bosIsimler);
        $this->assertIsInt($ornekler['sayi']);
    }

    // ------------------------------------------------------------ KahyaTeshisi

    public function test_envanter_bos_pazaryerini_uyarir(): void
    {
        $envanter = app(KahyaTeshisi::class)->gercekEnvanter();

        $this->assertSame(0, $envanter['ilan']);
        $this->assertNotNull($envanter['uyari']);
        $this->assertStringContainsString('boş', $envanter['uyari']);
    }

    /**
     * "12 ilan" iyi görünür; "12 ilan, 1 satıcı" gerçeği söyler.
     * Bu uyarı raporun en kolay gizleyeceği şeyi görünür tutar.
     */
    public function test_tek_saticili_envanter_uyarilir(): void
    {
        $tekSatici = User::factory()->create();
        $kategori = Category::query()->whereNotNull('parent_id')->value('id');

        Listing::factory()->count(3)->create([
            'user_id' => $tekSatici->id,
            'category_id' => $kategori,
            'status' => ListingStatus::Aktif,
        ]);

        $envanter = app(KahyaTeshisi::class)->gercekEnvanter();

        $this->assertSame(3, $envanter['ilan']);
        $this->assertSame(1, $envanter['satici']);
        $this->assertStringContainsString('tek kişiye ait', (string) $envanter['uyari']);
    }

    public function test_cok_saticili_envanter_uyarilmaz(): void
    {
        $kategori = Category::query()->whereNotNull('parent_id')->value('id');

        foreach (range(1, 2) as $i) {
            Listing::factory()->create([
                'user_id' => User::factory()->create()->id,
                'category_id' => $kategori,
                'status' => ListingStatus::Aktif,
            ]);
        }

        $envanter = app(KahyaTeshisi::class)->gercekEnvanter();

        $this->assertSame(2, $envanter['satici']);
        $this->assertNull($envanter['uyari']);
    }

    // ---------------------------------------------------------------- LogOzeti

    /**
     * EN ÖNEMLİ TEST: log MESAJI rapora sızmamalı.
     *
     * QueryException mesajı SQL'i ve BAĞLANMIŞ DEĞERLERİ içerir — e-posta,
     * telefon, oturum jetonu, parola sıfırlama anahtarı hepsi bir sorgu
     * parametresi olarak geçebilir. Bu özet e-posta ile gönderiliyor ve
     * ileride MCP üzerinden okunacak.
     */
    public function test_log_mesaji_ozete_sizmaz(): void
    {
        $dosya = storage_path('logs/kahya-sizinti-denemesi.log');
        $gizli = 'gizli-kullanici@ornek.com';

        file_put_contents($dosya, sprintf(
            "[%s] production.ERROR: SQLSTATE[HY000]: QueryException select * from users where email = '%s' at /app/Http/Controllers/X.php:42\n",
            now()->format('Y-m-d H:i:s'),
            $gizli
        ));

        try {
            $ozet = app(LogOzeti::class)->ozetle();
            $kodlanmis = json_encode($ozet, JSON_UNESCAPED_UNICODE);

            $this->assertStringNotContainsString($gizli, (string) $kodlanmis, 'Log mesajındaki kullanıcı verisi özete sızdı.');
            $this->assertStringNotContainsString('select * from users', (string) $kodlanmis, 'Ham SQL özete sızdı.');
            $this->assertGreaterThanOrEqual(1, $ozet['toplam'], 'Hata yine de SAYILMALI — gizlemek görmezden gelmek değil.');
        } finally {
            @unlink($dosya);
        }
    }

    public function test_test_loglari_elenir(): void
    {
        $dosya = storage_path('logs/laravel-test-99.log');
        file_put_contents($dosya, sprintf("[%s] testing.ERROR: KahyaBenzersizTestException at /app/Y.php:7\n", now()->format('Y-m-d H:i:s')));

        try {
            $ozet = app(LogOzeti::class)->ozetle();

            $this->assertStringNotContainsString(
                'KahyaBenzersizTestException',
                json_encode($ozet, JSON_UNESCAPED_UNICODE) ?: '',
                'Test koşusu logları üretim hatası sayılmamalı.'
            );
        } finally {
            @unlink($dosya);
        }
    }

    public function test_eski_kayitlar_pencere_disinda_sayilmaz(): void
    {
        // MUTLAK sayı ölçülemez: storage/logs'ta başka dosyalar da var ve
        // içlerinde taze kayıtlar olabilir. Ölçülen şey FARK — eski kayıt
        // eklenince toplam DEĞİŞMEMELİ.
        $oncesi = app(LogOzeti::class)->ozetle(24)['toplam'];
        $dosya = storage_path('logs/kahya-eski-deneme.log');

        file_put_contents($dosya, sprintf(
            "[%s] production.ERROR: EskiException at /app/X.php:1\n",
            now()->subDays(5)->format('Y-m-d H:i:s')
        ));

        try {
            $this->assertSame($oncesi, app(LogOzeti::class)->ozetle(24)['toplam'], 'Pencere dışı kayıt sayıma girdi.');
        } finally {
            @unlink($dosya);
        }
    }
}

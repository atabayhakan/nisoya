<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Conversation;
use App\Models\Deal;
use App\Models\DemoKaydi;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Message;
use App\Models\Review;
use App\Models\User;
use App\Services\Ai\FotografUretici;
use App\Services\Demo\DemoDefteri;
use App\Services\Demo\DemoFabrikasi;
use App\Services\Demo\DemoGorselUretici;
use App\Services\Demo\DemoTemizleyici;
use App\Services\Kahya\KahyaTeshisi;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Örnek (demo) veri makinesi — Faz A.
 *
 * BU DOSYANIN EN ÖNEMLİ İKİ TESTİ:
 *   · `test_uret_sonra_sil_her_seyi_eski_haline_dondurur` — geri alınamayan
 *     bir demo makinesi zarardır. Test yalnız satır saymaz, DİSKTEKİ dosya
 *     sayısını da karşılaştırır.
 *   · `test_temizleyici_defter_disi_gercek_veriye_dokunmaz` — bir temizleme
 *     aracının en tehlikeli hatası, temizlemesi gerekmeyeni temizlemektir.
 */
class DemoVerisiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function fabrika(): DemoFabrikasi
    {
        return app(DemoFabrikasi::class);
    }

    private function diskDosyaSayisi(): int
    {
        return count(Storage::disk('public')->allFiles());
    }

    // --------------------------------------------------------------- Üretim

    public function test_uretim_beklenen_kayitlari_olusturur(): void
    {
        $sonuc = $this->fabrika()->uret(4, 2);

        $this->assertSame(4, $sonuc['uye']);
        $this->assertSame(8, $sonuc['ilan']);
        $this->assertSame(8, User::query()->where('is_demo', true)->count() + Listing::query()->where('is_demo', true)->count() - 4);
        $this->assertGreaterThan(0, $sonuc['sohbet']);
        $this->assertGreaterThan(0, $sonuc['anlasma']);
        $this->assertGreaterThan(0, Message::query()->count());
        $this->assertGreaterThan(0, Review::query()->count());
    }

    /** Demo hesaplara yanlışlıkla gerçek posta gitmesin diye ayrılmış TLD. */
    public function test_demo_uyeleri_gecersiz_alan_adi_kullanir(): void
    {
        $this->fabrika()->uret(2, 1);

        foreach (User::query()->where('is_demo', true)->pluck('email') as $eposta) {
            $this->assertStringEndsWith('@'.DemoFabrikasi::EPOSTA_ALANI, (string) $eposta);
        }
    }

    public function test_uretilen_her_sey_deftere_yazilir(): void
    {
        $sonuc = $this->fabrika()->uret(2, 1);

        $defterdeki = DemoKaydi::query()->parti($sonuc['parti'])->count();

        $beklenen = User::query()->where('is_demo', true)->count()
            + Listing::query()->where('is_demo', true)->count()
            + ListingImage::query()->count()
            + Conversation::query()->count()
            + Message::query()->count()
            + Deal::query()->count()
            + Review::query()->count();

        $this->assertSame($beklenen, $defterdeki, 'Deftere yazılmayan kayıt geri alınamaz.');
    }

    // ------------------------------------------------------------ Geri alma

    /** ASIL TEST: satırlar VE dosyalar tam eski hâline dönmeli. */
    public function test_uret_sonra_sil_her_seyi_eski_haline_dondurur(): void
    {
        $once = [
            'uye' => User::query()->count(),
            'ilan' => Listing::query()->count(),
            'sohbet' => Conversation::query()->count(),
            'mesaj' => Message::query()->count(),
            'anlasma' => Deal::query()->count(),
            'degerlendirme' => Review::query()->count(),
            'dosya' => $this->diskDosyaSayisi(),
        ];

        $sonuc = $this->fabrika()->uret(4, 2);

        $this->assertGreaterThan($once['dosya'], $this->diskDosyaSayisi(), 'Üretim gerçekten dosya yazmalı.');

        $temizlik = app(DemoTemizleyici::class)->sil($sonuc['parti']);

        $this->assertSame(0, $temizlik['bulunamayan'], 'Defterdeki her kaydın karşılığı bulunmalıydı.');
        $this->assertSame(0, $temizlik['artik']);

        $this->assertSame($once['uye'], User::query()->count());
        $this->assertSame($once['ilan'], Listing::query()->count());
        $this->assertSame($once['sohbet'], Conversation::query()->count());
        $this->assertSame($once['mesaj'], Message::query()->count());
        $this->assertSame($once['anlasma'], Deal::query()->count());
        $this->assertSame($once['degerlendirme'], Review::query()->count());
        $this->assertSame($once['dosya'], $this->diskDosyaSayisi(), 'Diskte dosya kalmamalı.');
        $this->assertSame(0, DemoKaydi::query()->count());
    }

    /**
     * BİR TEMİZLEME ARACININ EN TEHLİKELİ HATASI: temizlemesi gerekmeyeni
     * temizlemek. Defterde yazmayan hiçbir şeye dokunulmamalı — `is_demo`
     * işaretli olsa bile.
     */
    public function test_temizleyici_defter_disi_gercek_veriye_dokunmaz(): void
    {
        $gercekUye = User::factory()->create();
        $gercekIlan = Listing::factory()->create(['user_id' => $gercekUye->id]);

        // Defterde OLMAYAN, ama işaretli bir kayıt: bir silme yarım kalmış olabilir.
        $defterDisi = User::factory()->create(['is_demo' => true]);

        $sonuc = $this->fabrika()->uret(2, 1);
        app(DemoTemizleyici::class)->hepsiniSil();

        $this->assertModelExists($gercekUye);
        $this->assertModelExists($gercekIlan);
        $this->assertModelExists($defterDisi, 'Defterde yazmayan kayıt silinmemeli.');
        $this->assertSame(0, DemoKaydi::query()->count());
    }

    public function test_artik_sayaci_defterdekileri_saymaz(): void
    {
        $this->fabrika()->uret(2, 1);

        $this->assertSame(0, app(DemoTemizleyici::class)->artikSayisi(), 'Sağlıklı parti artık sayılmamalı.');

        User::factory()->create(['is_demo' => true]);

        $this->assertSame(1, app(DemoTemizleyici::class)->artikSayisi());
    }

    public function test_var_olmayan_parti_silinmeye_calisilinca_bos_sonuc_doner(): void
    {
        $sonuc = app(DemoTemizleyici::class)->sil('boyle-bir-parti-yok');

        $this->assertSame([], $sonuc['silinen']);
        $this->assertSame(0, $sonuc['dosya']);
    }

    // ---------------------------------------------------------- Görünürlük

    /**
     * Bu şemada herkese açık görünürlüğün TEK koşulu `status === 'aktif'`.
     * Taslak bırakmak, on dört herkese açık sorgu noktasının hepsinden birden
     * çıkarır — yani üretim sitesinde hiçbir şey değişmez.
     */
    public function test_varsayilan_gizli_ilanlar_taslak_dogar(): void
    {
        $this->fabrika()->uret(2, 1, gorunur: false);

        $this->assertSame(
            0,
            Listing::query()->where('is_demo', true)->where('status', ListingStatus::Aktif)->count(),
        );
        $this->assertSame(2, Listing::query()->where('is_demo', true)->where('status', ListingStatus::Taslak)->count());
    }

    public function test_gorunur_kipte_ilanlar_aktif_olur(): void
    {
        $this->fabrika()->uret(2, 1, gorunur: true);

        $this->assertSame(2, Listing::query()->where('is_demo', true)->where('status', ListingStatus::Aktif)->count());
    }

    public function test_gorunur_demo_ilan_sitede_ornek_isaretiyle_cikar(): void
    {
        $this->fabrika()->uret(2, 1, gorunur: true);

        $ilan = Listing::query()->where('is_demo', true)->firstOrFail();

        // Rota kanonik slug icin 301 doner; yonlendirmeyi izle.
        $this->followingRedirects()
            ->get(route('listings.show', $ilan))
            ->assertOk()
            ->assertSee('Bu bir ÖRNEK ilandır');
    }

    /**
     * Rozet uyarır ama engellemez. Rozeti okumayan biri sahte bir satıcıya
     * yazıp cevapsız kalmasın diye kapı ayrıca kapalı: cevapsız bir mesaj,
     * boş bir pazaryerinden daha kötüdür.
     */
    public function test_ornek_ilana_mesaj_gonderilemez(): void
    {
        $this->fabrika()->uret(2, 1, gorunur: true);

        $ilan = Listing::query()->where('is_demo', true)->firstOrFail();
        $gercekUye = User::factory()->create(['email_verified_at' => now()]);

        // Demo uretimi kendi ornek sohbetlerini yaziyor; asil iddia
        // "hic mesaj yok" degil, "YENI mesaj eklenmedi".
        $oncekiMesaj = Message::query()->count();

        $this->actingAs($gercekUye)
            ->post(route('messages.start', $ilan), ['body' => 'Merhaba, ilgileniyorum.'])
            ->assertSessionHas('status', fn (string $mesaj): bool => str_contains($mesaj, 'ÖRNEK'));

        $this->assertSame($oncekiMesaj, Message::query()->count(), 'Ornek ilana mesaj yazilmamali.');
    }

    // --------------------------------------------------- Kâhya bütünlüğü

    /**
     * KRİTİK: `gercekEnvanter()` sahibin kendi kendini kandırmasını engellemek
     * için var. Demo ilanlar o sayıya karışsaydı, örnek veri makinesi tam
     * olarak ölçmek için yazılmış aracı bozardı.
     */
    public function test_kahya_envanteri_demo_ilanlari_saymaz(): void
    {
        $gercekUye = User::factory()->create();
        Listing::factory()->create(['user_id' => $gercekUye->id, 'status' => ListingStatus::Aktif]);

        $this->fabrika()->uret(4, 3, gorunur: true);

        $envanter = app(KahyaTeshisi::class)->gercekEnvanter();

        $this->assertSame(1, $envanter['ilan'], 'Demo ilanlar gerçek envantere karışmamalı.');
        $this->assertSame(1, $envanter['satici']);
        $this->assertSame('Tüm ilanlar tek kişiye ait — üçüncü taraf envanteri yok.', $envanter['uyari']);
    }

    public function test_kahya_son_24_saat_demo_saymaz(): void
    {
        $this->fabrika()->uret(3, 2, gorunur: true);

        $sonGun = app(KahyaTeshisi::class)->sonYirmiDortSaat();

        $this->assertSame(0, $sonGun['yeni_uye']);
        $this->assertSame(0, $sonGun['yeni_ilan']);
    }

    // -------------------------------------------------- Kalite (2026-08-01)

    /**
     * Canlıda görülen hata: her demo ilana veritabanındaki İLK alt kategori
     * atanıyordu — ana sayfa "Bebek bakıcılığı" kartında "Yabancı Dil Dersi"
     * çipiyle dolmuştu. Katalog artık başlıkla eşleşen gerçek kategoriyi taşır.
     */
    public function test_ilan_kategorisi_basligiyla_eslesir(): void
    {
        $this->fabrika()->uret(1, 8);

        $bakim = Listing::query()->where('title', 'like', '%Bebek bakıcılığı%')->firstOrFail();
        $this->assertSame('Bebek Bakıcılığı', $bakim->category?->name);

        $tercume = Listing::query()->where('title', 'like', '%tercümesi%')->firstOrFail();
        $this->assertSame('Tercüme', $tercume->category?->name);

        // Hiçbir demo ilan kategorisiz kalmaz.
        $this->assertSame(0, Listing::query()->where('is_demo', true)->whereNull('category_id')->count());
    }

    /**
     * Canlıda görülen off-by-one: görsel `count($ilanlar)` push SONRASI
     * çağrılıyordu — her kartın görseli bir SONRAKİ ilanın başlığını
     * taşıyordu. Başlık ve görsel etiketi artık aynı sıradan gelir.
     */
    public function test_gorsel_etiketi_ilan_basligiyla_eslesir(): void
    {
        $etiketler = [];

        $sahte = \Mockery::mock(DemoGorselUretici::class);
        $sahte->shouldReceive('uret')
            ->andReturnUsing(function (string $etiket, string $dizin) use (&$etiketler): array {
                if ($dizin === 'listings') {
                    $etiketler[] = $etiket;
                }

                $yol = 'sahte/'.uniqid().'.webp';
                Storage::disk('public')->put($yol, 'x');

                return ['thumb' => $yol, 'medium' => $yol, 'large' => $yol];
            });
        $this->app->instance(DemoGorselUretici::class, $sahte);

        $this->fabrika()->uret(2, 3);

        $basliklar = Listing::query()->where('is_demo', true)->orderBy('id')
            ->pluck('title')->map(fn (string $b): string => str_replace('[ÖRNEK] ', '', $b))->all();

        $this->assertSame($basliklar, $etiketler);
    }

    public function test_fiyatlar_gercekci_araliktan_gelir(): void
    {
        $this->fabrika()->uret(1, 8);

        $bakim = Listing::query()->where('title', 'like', '%Bebek bakıcılığı%')->firstOrFail();
        $this->assertGreaterThanOrEqual(12, (float) $bakim->price);
        $this->assertLessThanOrEqual(18, (float) $bakim->price);
        $this->assertSame('saatlik', $bakim->price_unit->value ?? $bakim->price_unit);

        $foto = Listing::query()->where('title', 'like', '%Düğün fotoğrafçılığı%')->firstOrFail();
        $this->assertGreaterThanOrEqual(350, (float) $foto->price);
        $this->assertSame('is_basina', $foto->price_unit->value ?? $foto->price_unit);
    }

    /**
     * Vitrin kuralı: ana sayfanın "öne çıkan" listesi GERÇEK ilan öncelikli;
     * demo yalnız hiç gerçek aktif ilan yokken görünür. İstatistik ve canlı
     * akış demo'yu HİÇ saymaz (gerçek hareket iddiası şişirilemez).
     */
    public function test_ana_sayfa_gercek_ilan_varken_demo_gostermez(): void
    {
        $this->fabrika()->uret(2, 2, gorunur: true);

        $gercek = Listing::factory()->create(['status' => ListingStatus::Aktif, 'title' => 'Gerçek berber hizmeti']);

        $yanit = $this->get('/')->assertOk();

        $liste = $yanit->viewData('latestListings');
        $this->assertTrue($liste->contains('id', $gercek->id));
        $this->assertSame(0, $liste->where('is_demo', true)->count());

        $this->assertSame(1, $yanit->viewData('stats')['activeListings']);
        $this->assertSame(0, $yanit->viewData('activityFeed')->filter(fn ($i) => str_contains($i['href'], 'demo'))->count());
    }

    public function test_ana_sayfa_hic_gercek_ilan_yokken_demo_gosterir(): void
    {
        $this->fabrika()->uret(2, 2, gorunur: true);

        $yanit = $this->get('/')->assertOk();

        $liste = $yanit->viewData('latestListings');
        $this->assertGreaterThan(0, $liste->count());
        $this->assertSame($liste->count(), $liste->where('is_demo', true)->count());

        // Vitrin dolu görünse de "gerçek hareket" sayacı dürüst kalır.
        $this->assertSame(0, $yanit->viewData('stats')['activeListings']);
    }

    // ------------------------------------------------------------- Görsel

    public function test_gorsel_uretici_gecerli_png_uretir(): void
    {
        $png = app(DemoGorselUretici::class)->tuval('Deneme İlanı', 0, 600, 400);

        $boyut = getimagesizefromstring($png);

        $this->assertIsArray($boyut);
        $this->assertSame(600, $boyut[0]);
        $this->assertSame(400, $boyut[1]);
        $this->assertSame('image/png', $boyut['mime']);
    }

    public function test_gorsel_uretici_disaridan_indirmez(): void
    {
        // AI yolu bilinçli istisna (ayrı sınıfta, FotografUretici) — bu
        // sınıfın KENDİSİ ağ çağrısı yapamaz; varsayılan üretim ağsızdır.
        $kaynak = file_get_contents(app_path('Services/Demo/DemoGorselUretici.php'));

        $this->assertStringNotContainsString('Http::', (string) $kaynak);
        $this->assertStringNotContainsString('file_get_contents(\'http', (string) $kaynak);
    }

    /** AI kapalıyken (varsayılan) üretim TEK ağ çağrısı bile yapmaz. */
    public function test_ai_kapaliyken_uretim_ag_cagrisi_yapmaz(): void
    {
        Http::fake();

        $this->fabrika()->uret(1, 2);

        Http::assertNothingSent();
    }

    /**
     * AI fotoğraf yolu: sahte fotoğraf baytları döndüğünde görsel filigranlı
     * PNG olarak işlenir ve normal varyant boru hattından geçer; AI null
     * dönerse (anahtar yok/kırık) grafik tuvale düşülür — üretim KIRILMAZ.
     */
    public function test_ai_fotograf_yolu_ve_geri_dusme(): void
    {
        $sahteFoto = app(DemoGorselUretici::class)->tuval('ham foto', 0, 600, 400);

        $fotograf = \Mockery::mock(FotografUretici::class);
        $fotograf->shouldReceive('uret')->andReturn($sahteFoto, null);
        $this->app->instance(FotografUretici::class, $fotograf);

        // İki ilan: ilki AI fotoğraf alır, ikincisi null → tuvale düşer.
        $this->fabrika()->uret(1, 2, gorunur: false, aiGorsel: true);

        $this->assertSame(2, ListingImage::query()->count());

        foreach (ListingImage::query()->get() as $gorsel) {
            $this->assertTrue(Storage::disk('public')->exists($gorsel->path_large));
        }
    }

    public function test_avatarlar_ai_istese_de_grafik_kalir(): void
    {
        $fotograf = \Mockery::mock(FotografUretici::class);
        // Avatar üretimi FotografUretici'ye HİÇ uğramamalı (sahte kişiye
        // gerçekçi yüz üretilmez — bilinçli sınır).
        $fotograf->shouldReceive('uret')->never();
        $this->app->instance(FotografUretici::class, $fotograf);

        $this->fabrika()->uret(1, 0, gorunur: false, aiGorsel: true);

        $this->assertSame(1, User::query()->where('is_demo', true)->count());
    }

    // -------------------------------------------------------------- Komut

    public function test_uretim_ortaminda_komut_force_olmadan_calismaz(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->artisan('demo:uret', ['--uye' => 1, '--ilan' => 1])
            ->assertExitCode(1);

        $this->assertSame(0, User::query()->where('is_demo', true)->count());
    }

    public function test_durum_komutu_partileri_listeler(): void
    {
        $sonuc = $this->fabrika()->uret(2, 1);

        $this->artisan('demo:durum')
            ->expectsOutputToContain($sonuc['parti'])
            ->assertExitCode(0);
    }

    public function test_sil_komutu_bilinmeyen_partide_hata_verir(): void
    {
        $this->artisan('demo:sil', ['parti' => 'yok-boyle'])->assertExitCode(1);
    }

    public function test_defter_partileri_dokumuyle_listeler(): void
    {
        $sonuc = $this->fabrika()->uret(2, 1);

        $partiler = app(DemoDefteri::class)->partiler();

        $this->assertCount(1, $partiler);
        $this->assertSame($sonuc['parti'], $partiler[0]['parti']);
        $this->assertArrayHasKey('User', $partiler[0]['dokum']);
        $this->assertArrayHasKey('Listing', $partiler[0]['dokum']);
    }
}

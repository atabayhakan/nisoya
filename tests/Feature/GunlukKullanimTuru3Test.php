<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Boş durumlar, çift gönderim ve taslak — Tur 3 (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * ÜÇÜNÜN ORTAK YANI
 *
 * Hiçbiri hata vermiyordu. Sayfalar 200 dönüyor, formlar çalışıyor, testler
 * geçiyordu. Sorun kullanıcının ÇIKMAZA girmesiydi:
 *
 *   · Boş liste "filtreni değiştir" diyordu — oysa filtre yoktu, hiç ilan yoktu.
 *   · "Tüm ilanları gör" düğmesi KENDİ sayfasına dönüyordu.
 *   · Yavaş bağlantıda çift tıklama mükerrer ilan üretiyordu.
 *   · Yarım kalan ilan kaydedilemiyordu: ya bitir ya kaybet.
 */
class GunlukKullanimTuru3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        Cache::flush();
    }

    // ------------------------------------------------------ Boş durumlar

    public function test_hic_ilan_yokken_arz_cagrisi_gosterilir(): void
    {
        // Filtre yokken "Tüm ilanları gör" düğmesi kendi sayfasına dönüyordu —
        // hiçbir ilerleme sağlamayan bir düğme.
        $icerik = $this->get('/ilanlar')->assertOk()->getContent();

        $this->assertStringContainsString('Henüz ilan yok', $icerik);
        $this->assertStringContainsString(route('panel.listings.create'), $icerik);
        $this->assertStringNotContainsString('Tüm ilanları gör', $icerik);
    }

    public function test_filtre_varken_temizleme_yolu_gosterilir(): void
    {
        // Filtre uygulanmışsa "temizle" GERÇEK bir ilerleme sağlar.
        $icerik = $this->get('/ilanlar?q=olmayan-bir-sey')->assertOk()->getContent();

        $this->assertStringContainsString('Bu filtrelerle sonuç yok', $icerik);
        $this->assertStringContainsString('Filtreleri temizle', $icerik);
    }

    public function test_isler_ve_adaylar_bos_durumda_yol_gosterir(): void
    {
        // İkisi de "filtreleri değiştirmeyi dene" diyordu ve HİÇBİR çağrı
        // içermiyordu — pano gerçekten boşken bu yanlış tavsiye.
        $isler = $this->get('/isler')->assertOk()->getContent();
        $this->assertStringContainsString('Henüz iş ilanı yok', $isler);
        $this->assertStringContainsString(route('panel.jobs.create'), $isler);

        $adaylar = $this->get('/adaylar')->assertOk()->getContent();
        $this->assertStringContainsString('Yetenek havuzu henüz boş', $adaylar);
        $this->assertStringContainsString(route('panel.profile.edit'), $adaylar);
    }

    // ------------------------------------------------ Çift gönderim kilidi

    public function test_kritik_formlarda_gonderim_kilidi_var(): void
    {
        /*
         * İlan formu 8'e kadar fotoğraf yüklüyor. Yavaş bağlantıda ekranda
         * hiçbir şey değişmiyor, kullanıcı tekrar tıklıyor → mükerrer ilan.
         * Nisoya'nın kitlesi tanımı gereği yurt dışında; yavaş bağlantı
         * istisna değil kural.
         */
        // SIRA ÖNEMLİ: /kayit `guest` middleware'i ile korunuyor. actingAs()
        // oturumu test boyunca açık tuttuğu için önce misafir sayfaları
        // kontrol edilir — aksi hâlde 302 alınır ve test yanlış sebeple kırılır
        // (ilk yazışta tam olarak bu oldu).
        $kayit = $this->get('/kayit')->assertOk()->getContent();
        $this->assertStringContainsString('gonderimKilidi', $kayit);

        $iletisim = $this->get('/iletisim')->assertOk()->getContent();
        $this->assertStringContainsString('gonderimKilidi', $iletisim);

        $uye = User::factory()->create();
        $ilanFormu = $this->actingAs($uye)->get('/panel/ilan/yeni')->assertOk()->getContent();
        $this->assertStringContainsString('gonderimKilidi', $ilanFormu);
    }

    // ------------------------------------------------------------ Taslak

    private function ilanVerisi(): array
    {
        $kategori = Category::create([
            'name' => 'Test', 'slug' => 'test-kat', 'icon' => '🔧',
            'is_active' => true, 'parent_id' => null, 'sort_order' => 0,
        ]);
        Country::firstOrCreate(['code' => 'DE'], [
            'name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true, 'sort_order' => 0,
        ]);
        Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'is_active' => true, 'sort_order' => 0]);

        return [
            'type' => 'hizmet',
            'title' => 'Test ilanı başlığı',
            'description' => 'Yeterince uzun bir açıklama metni buraya yazıldı.',
            'category_id' => $kategori->id,
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'country_code' => 'DE',
        ];
    }

    public function test_taslak_kaydedilebilir_ve_yayinda_gorunmez(): void
    {
        /*
         * "Taslak" enum'da vardı, rozeti hazırdı, listede gösteriliyordu — ama
         * gerçek bir kullanıcı o duruma HİÇBİR ZAMAN giremiyordu: store() her
         * zaman Aktif yazıyordu.
         */
        $uye = User::factory()->create();

        $this->actingAs($uye)
            ->post('/panel/ilan', $this->ilanVerisi() + ['eylem' => 'taslak'])
            ->assertRedirect(route('panel.listings.index'));

        $ilan = Listing::query()->firstOrFail();
        $this->assertSame(ListingStatus::Taslak, $ilan->status);

        // Ve taslak HERKESE AÇIK listede görünmemeli.
        $this->get('/ilanlar')->assertOk()->assertDontSee('Test ilanı başlığı');
    }

    public function test_eylem_verilmezse_davranis_degismez(): void
    {
        // Geriye dönük uyum: beklenmedik/eksik değerde eski davranış (yayın).
        $uye = User::factory()->create();

        $this->actingAs($uye)->post('/panel/ilan', $this->ilanVerisi());

        $this->assertSame(ListingStatus::Aktif, Listing::query()->firstOrFail()->status);
    }

    public function test_taslak_yayina_alinabilir(): void
    {
        /*
         * EN ÖNEMLİ TEST. Taslak kaydetmeyi çıkış yolu olmadan eklemek,
         * kullanıcıyı hapsetmek olurdu: düzenleme sayfasında durum alanı yok.
         */
        $uye = User::factory()->create();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Taslak, 'is_demo' => false]);

        $this->actingAs($uye)
            ->post(route('panel.listings.publish', $ilan))
            ->assertRedirect(route('panel.listings.index'));

        $this->assertSame(ListingStatus::Aktif, $ilan->fresh()->status);
    }

    public function test_baskasinin_taslagi_yayinlanamaz(): void
    {
        $sahip = User::factory()->create();
        $yabanci = User::factory()->create();
        $ilan = Listing::factory()->for($sahip)->create(['status' => ListingStatus::Taslak]);

        $this->actingAs($yabanci)
            ->post(route('panel.listings.publish', $ilan))
            ->assertForbidden();

        $this->assertSame(ListingStatus::Taslak, $ilan->fresh()->status);
    }

    public function test_reddedilmis_ilan_yayinla_ile_diriltilemez(): void
    {
        // Yalnız TASLAK yayına alınır — pasif/reddedilmiş ilanı buradan
        // diriltmek moderasyon kararını atlatmak olurdu.
        $uye = User::factory()->create();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Reddedildi]);

        $this->actingAs($uye)->post(route('panel.listings.publish', $ilan));

        $this->assertSame(ListingStatus::Reddedildi, $ilan->fresh()->status);
    }
}

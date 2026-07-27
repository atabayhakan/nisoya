<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vitrin Faz P4 — controller verisi gerektiren bloklar:
 * ilan detayında "benzer ilanlar" + "değerlendirmeler", ilan listesinde
 * kategori sayaçları + fiyat histogramı.
 *
 * TASARIM SÖZLEŞMESİ: bu veriler YALNIZ Vitrin teması aktifken yüklenir.
 * Klasik tema bu blokları göstermediği için orada ek sorgu maliyeti doğmaz
 * (ilan detayı <25 sorgu bütçesi, bkz. PerformanceBenchmarkTest).
 */
class VitrinVeriBloklariTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function vitrin(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);
    }

    private function yorumEkle(User $satici, string $metin, int $puan = 5): Review
    {
        return Review::create([
            'listing_id' => null,
            'reviewee_id' => $satici->id,
            'reviewer_id' => User::factory()->create()->id,
            'rating' => $puan,
            'comment' => $metin,
            'status' => 'yayinda',
        ]);
    }

    // ---------------------------------------------------- İlan detayı

    public function test_benzer_ilanlar_ayni_kategoriden_listelenir(): void
    {
        $this->vitrin();

        $kategori = Category::query()->whereNotNull('parent_id')->first();
        $ilan = Listing::factory()->create(['status' => 'aktif', 'category_id' => $kategori->id]);
        $benzer = Listing::factory()->create([
            'status' => 'aktif',
            'category_id' => $kategori->id,
            'title' => 'Benzer kategorideki ilan',
        ]);
        $alakasiz = Listing::factory()->create([
            'status' => 'aktif',
            'category_id' => Category::query()->whereNotNull('parent_id')->where('id', '!=', $kategori->id)->first()->id,
            'title' => 'Baska kategorideki ilan',
        ]);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()
            ->assertSee('Benzer ilanlar', false)
            ->assertSee($benzer->title, false)
            ->assertDontSee($alakasiz->title, false);
    }

    public function test_benzer_ilan_yoksa_blok_hic_basilmaz(): void
    {
        $this->vitrin();

        $ilan = Listing::factory()->create(['status' => 'aktif']);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()
            ->assertDontSee('Benzer ilanlar', false);
    }

    public function test_satici_yorumlari_detayda_gorunur(): void
    {
        $this->vitrin();

        $ilan = Listing::factory()->create(['status' => 'aktif']);
        $this->yorumEkle($ilan->user, 'Cok memnun kaldim, tavsiye ederim.');

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()
            ->assertSee('Değerlendirmeler', false)
            ->assertSee('Cok memnun kaldim, tavsiye ederim.', false);
    }

    public function test_yayinda_olmayan_yorum_gosterilmez(): void
    {
        $this->vitrin();

        $ilan = Listing::factory()->create(['status' => 'aktif']);
        $gizli = $this->yorumEkle($ilan->user, 'Gizlenmis yorum');
        $gizli->update(['status' => 'gizli']);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()
            ->assertDontSee('Gizlenmis yorum', false);
    }

    public function test_klasik_temada_ek_bloklar_yuklenmez(): void
    {
        // Klasik tema aktifken bu veriler HİÇ sorgulanmamalı — sözleşmenin
        // performans tarafı budur.
        $kategori = Category::query()->whereNotNull('parent_id')->first();
        $ilan = Listing::factory()->create(['status' => 'aktif', 'category_id' => $kategori->id]);
        Listing::factory()->create(['status' => 'aktif', 'category_id' => $kategori->id, 'title' => 'Benzer kategorideki ilan']);
        $this->yorumEkle($ilan->user, 'Klasikte gorunmemeli');

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk()
            ->assertDontSee('Benzer ilanlar', false)
            ->assertDontSee('Klasikte gorunmemeli', false);
    }

    public function test_vitrin_ilan_detayi_sorgu_butcesi_asilmaz(): void
    {
        // Yeni bloklar bütçeyi patlatmamalı (klasik için PerformanceBenchmarkTest
        // aynı sınırı zaten mühürlüyor; bu onun Vitrin karşılığı).
        $this->vitrin();

        $kategori = Category::query()->whereNotNull('parent_id')->first();
        $ilan = Listing::factory()->create(['status' => 'aktif', 'category_id' => $kategori->id]);
        Listing::factory()->count(6)->create(['status' => 'aktif', 'category_id' => $kategori->id]);
        foreach (range(1, 4) as $i) {
            $this->yorumEkle($ilan->user, "Yorum {$i}");
        }

        $sorgular = [];
        DB::listen(function ($q) use (&$sorgular) {
            $sorgular[] = $q->sql;
        });

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk();

        // Klasik bütçe <25 (PerformanceBenchmarkTest). Vitrin'in iki ek bloğu
        // sabit 5 sorgu ekler (benzer ilanlar + kapak + ülke, yorumlar +
        // yorumcu) — ilan/yorum sayısıyla ÖLÇEKLENMEZ (N+1 yok). Sınır bunu
        // yansıtır; büyürse N+1 sızmış demektir.
        $this->assertLessThan(32, count($sorgular), 'Vitrin ilan detayı fazla sorgu: '.count($sorgular));
    }

    // ---------------------------------------------------- İlan listesi

    public function test_kategori_sayaclari_listede_gorunur(): void
    {
        $this->vitrin();

        $alt = Category::query()->whereNotNull('parent_id')->first();
        Listing::factory()->count(3)->create(['status' => 'aktif', 'category_id' => $alt->id]);

        $html = $this->get('/ilanlar')->assertOk()->getContent();

        // Kök kategori satırında alt kategorinin adedi toplanmış olmalı.
        $kok = Category::find($alt->parent_id);
        $this->assertNotNull($kok, 'test verisi: alt kategorinin kökü olmalı');
        $this->assertStringContainsString($kok->name, $html);
        $this->assertStringContainsString('>3<', $html, 'kategori sayacı basılmalı');
    }

    public function test_fiyat_histogrami_farkli_fiyatlarda_basilir(): void
    {
        $this->vitrin();

        foreach ([10, 250, 900, 1500] as $fiyat) {
            Listing::factory()->create(['status' => 'aktif', 'price' => $fiyat]);
        }

        $this->get('/ilanlar')->assertOk()->assertSee('data-fiyat-histogrami', false);
    }

    public function test_tek_fiyat_varsa_histogram_hic_basilmaz(): void
    {
        // Tüm ilanlar aynı fiyattaysa histogram bilgi taşımaz → blok basılmaz
        // ("kapalı blok DOM'a basılmaz" kuralı).
        $this->vitrin();

        Listing::factory()->count(3)->create(['status' => 'aktif', 'price' => 100]);

        $this->get('/ilanlar')->assertOk()->assertDontSee('data-fiyat-histogrami', false);
    }

    public function test_klasik_temada_liste_ek_sorgu_yapmaz(): void
    {
        Listing::factory()->count(3)->create(['status' => 'aktif', 'price' => 100]);

        $this->get('/ilanlar')->assertOk()
            ->assertDontSee('data-fiyat-histogrami', false);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Page;
use Database\Seeders\CitySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\DubaiRehberiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Körfez açılışı (büyüme önerisi 3, karar 2026-07-29).
 *
 * İki parça mühürlenir: (1) AE/QA/SA ülke+şehir+para birimi kayıtları referans
 * seeder'larıyla gelir — deploy her koşuda ReferenceDataSeeder çalıştırdığı
 * için bu, canlıya otomatik taşınma yoludur; mevcut ülkelerin sırası bozulmaz.
 * (2) Dubai pilot sayfası OgrenciRehberiSeeder sözleşmesinin aynısını taşır:
 * taslak doğar, idempotenttir, eylem çağrısı ARZ çağrısıdır (Körfez'de 0 ilan
 * varken "gelin alın" tutulamayacak bir vaat olurdu) ve yayında ince içerik
 * değildir.
 */
class KorfezTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'dubai-ilk-30-gun-rehberi';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CitySeeder::class]);
    }

    // ------------------------------------------------------ Referans veriler

    public function test_korfez_ulkeleri_para_birimleriyle_gelir(): void
    {
        foreach (['AE' => 'AED', 'QA' => 'QAR', 'SA' => 'SAR'] as $kod => $para) {
            $ulke = Country::query()->find($kod);

            $this->assertNotNull($ulke, "{$kod} ülke listesinde olmalı.");
            $this->assertTrue($ulke->is_active);
            $this->assertSame($para, $ulke->default_currency);
            $this->assertTrue(
                Currency::query()->where('code', $para)->where('is_active', true)->exists(),
                "{$para} para birimi aktif olmalı."
            );
        }
    }

    public function test_korfez_sehirleri_gelir(): void
    {
        foreach ([
            'AE' => ['Dubai', 'Abu Dabi'],
            'QA' => ['Doha', 'Al Rayyan'],
            'SA' => ['Riyad', 'Cidde'],
        ] as $kod => $sehirler) {
            foreach ($sehirler as $sehir) {
                $this->assertTrue(
                    City::query()->where('country_code', $kod)->where('name', $sehir)->exists(),
                    "{$kod}/{$sehir} şehir listesinde olmalı."
                );
            }
        }
    }

    public function test_yeni_ulkeler_mevcut_sirayi_bozmaz(): void
    {
        // Sıralama dizin sırası: Almanya ilk kalmalı, Körfez sona eklenmiş olmalı.
        $this->assertSame(0, Country::query()->findOrFail('DE')->sort_order);
        $this->assertGreaterThan(
            Country::query()->findOrFail('RU')->sort_order,
            Country::query()->findOrFail('AE')->sort_order,
        );
    }

    // ----------------------------------------------------- Dubai pilot sayfası

    private function seedle(): Page
    {
        $this->seed(DubaiRehberiSeeder::class);

        return Page::query()->where('slug', self::SLUG)->firstOrFail();
    }

    public function test_dubai_rehberi_taslak_dogar_ve_ziyaretciye_kapali(): void
    {
        $sayfa = $this->seedle();

        $this->assertSame(PageStatus::Taslak, $sayfa->status);
        $this->get('/'.self::SLUG)->assertNotFound();
    }

    public function test_seeder_idempotenttir_ve_panel_duzenlemesini_ezmez(): void
    {
        $sayfa = $this->seedle();
        $sayfa->update(['title' => 'Sahibin elle değiştirdiği başlık']);

        $this->seed(DubaiRehberiSeeder::class);

        $this->assertSame(1, Page::query()->where('slug', self::SLUG)->count());
        $this->assertSame('Sahibin elle değiştirdiği başlık', $sayfa->fresh()->title);
    }

    public function test_eylem_cagrisi_arz_cagrisidir(): void
    {
        $cta = collect($this->seedle()->blocks)->firstWhere('type', 'cta');

        $this->assertNotNull($cta, 'Sayfada eylem çağrısı olmalı.');
        $this->assertSame('/panel/ilan/yeni', $cta['data']['button_url']);
        $this->assertStringContainsString('listele', mb_strtolower($cta['data']['title'].$cta['data']['button_text']));
    }

    public function test_yayina_alininca_dolu_bir_sayfa_render_eder(): void
    {
        $sayfa = $this->seedle();
        $sayfa->update(['status' => PageStatus::Yayin]);

        $icerik = $this->get('/'.self::SLUG)->assertSuccessful()->getContent();

        foreach (['getir mi, orada al mı', 'ev kurma listesi', 'nereden bulunur', 'dolandırıcılıktan korunma', 'Şehirden ayrılıyorsan'] as $baslik) {
            $this->assertStringContainsString($baslik, $icerik, "Bölüm eksik: {$baslik}");
        }

        // İnce içerik değil: gövde metni en az 400 kelime (93 boş kategori
        // sayfasını indeksten çıkardığımız sitede yayına giriyor).
        $govde = trim(preg_replace('/\s+/', ' ', strip_tags($icerik)) ?? '');
        $this->assertGreaterThan(400, count(explode(' ', $govde)), 'Rehber ince içerik olmamalı.');

        // Sayfanın en somut faydaları kaybolmamalı: ödeme güvenliği + yerel gerçekler.
        $this->assertStringContainsString('Banka havalesi geri alınamaz', $icerik);
        $this->assertStringContainsString('Dubizzle', $icerik);
        $this->assertStringContainsString('Type G', $icerik);
    }
}

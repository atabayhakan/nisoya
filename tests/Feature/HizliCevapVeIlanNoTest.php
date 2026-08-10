<?php

namespace Tests\Feature;

use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Hızlı cevap çipleri + görünür ilan numarası + misafirin şikayet yolu.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * İlan detayındaki iletişim kutusu boş bir `required` textarea idi; ilk teması
 * kuracak kişi "ne yazsam" diye takılıyordu. Çipler cümleyi kutuya DÜŞÜRÜR,
 * göndermez.
 *
 * ---------------------------------------------------------------------------
 * ASIL KORUNAN ŞEY: ŞABLON ASİMETRİSİ
 *
 * `vitrin/` altındaki dosyalar klasik view'ları geçersiz kılar. Bu depoda
 * daha önce tam olarak bu yüzden iki kez özellik "eklendi ama görünmedi":
 * satıcı şeridi yalnız vitrin'e yazılmıştı, ilan numarası da öyle. Bu yüzden
 * BURADAKİ HER İDDİA İKİ TEMADA DA ölçülür — tek temada geçen bir test bu
 * sınıfta hiçbir şey kanıtlamaz.
 */
class HizliCevapVeIlanNoTest extends TestCase
{
    use RefreshDatabase;

    public static function temalar(): array
    {
        return ['klasik' => ['klasik'], 'vitrin' => ['vitrin']];
    }

    private function temayiKur(string $tema): void
    {
        Settings::setMany(['gorunum.tema' => $tema]);
        Cache::flush();
        // Temanın GERÇEKTEN değiştiğini doğrula: bu satır olmadan iki varyant
        // da klasik temayı ölçer ve test kurt masalı anlatır.
        $this->assertSame($tema === 'vitrin', Tema::vitrinMi());
    }

    // -----------------------------------------------------------------
    // HIZLI CEVAP ÇİPLERİ
    // -----------------------------------------------------------------

    #[DataProvider('temalar')]
    public function test_giris_yapmis_ziyaretci_ilan_turune_uygun_cipleri_gorur(string $tema): void
    {
        $this->temayiKur($tema);
        $ilan = Listing::factory()->create(['type' => ListingType::Urun]);

        $this->actingAs(User::factory()->create())
            ->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('Hızlı başlangıç')
            ->assertSee('Merhaba, hâlâ satılık mı?', false)
            ->assertSee('data-quick-reply', false);
    }

    #[DataProvider('temalar')]
    public function test_cipler_ilan_turune_gore_degisir(string $tema): void
    {
        // Tek bir metni sınamak yetmez: çipler türe göre DEĞİŞMİYORSA
        // yukarıdaki test yine geçerdi. Ürün metnini emlak ilanında
        // GÖRMEMEYİ de zorluyoruz.
        $this->temayiKur($tema);
        $ilan = Listing::factory()->create(['type' => ListingType::Emlak]);

        $this->actingAs(User::factory()->create())
            ->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('Yerinde görmek için gelebilir miyim?', false)
            ->assertDontSee('Merhaba, hâlâ satılık mı?', false);
    }

    #[DataProvider('temalar')]
    public function test_misafir_cipleri_gormez_cunku_mesaj_formu_yok(string $tema): void
    {
        // Çipler mesaj formunun İÇİNDE; form yoksa çip de olmamalı, yoksa
        // hiçbir şeye bağlı olmayan düğmeler basmış oluruz.
        $this->temayiKur($tema);
        $ilan = Listing::factory()->create(['type' => ListingType::Urun]);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertDontSee('Hızlı başlangıç')
            ->assertSee('Mesaj göndermek için giriş yap');
    }

    #[DataProvider('temalar')]
    public function test_ilan_sahibi_kendi_ilaninda_cip_gormez(string $tema): void
    {
        $this->temayiKur($tema);
        $satici = User::factory()->create();
        $ilan = Listing::factory()->for($satici)->create(['type' => ListingType::Urun]);

        $this->actingAs($satici)
            ->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertDontSee('Hızlı başlangıç');
    }

    public function test_her_ilan_turu_bos_olmayan_cip_listesi_dondurur(): void
    {
        // Enum'a yeni bir tür eklenirse match() patlar; bu test o günü
        // sessiz bir boşluk yerine kırmızı bir teste çevirir.
        foreach (ListingType::cases() as $tur) {
            $mesajlar = $tur->hizliMesajlar();

            $this->assertNotEmpty($mesajlar, "{$tur->value} için hızlı mesaj yok");
            foreach ($mesajlar as $mesaj) {
                $this->assertIsString($mesaj);
                $this->assertNotSame('', trim($mesaj));
            }
        }
    }

    // -----------------------------------------------------------------
    // GÖRÜNÜR İLAN NUMARASI
    // -----------------------------------------------------------------

    #[DataProvider('temalar')]
    public function test_ilan_numarasi_misafire_de_gorunur(string $tema): void
    {
        // Numara destek bileti/şikayet için var; giriş şartına bağlanamaz.
        $this->temayiKur($tema);
        $ilan = Listing::factory()->create();

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('İlan no')
            ->assertSee("NS-{$ilan->id}");
    }

    // -----------------------------------------------------------------
    // ŞİKAYET YOLU
    // -----------------------------------------------------------------

    #[DataProvider('temalar')]
    public function test_misafire_sikayet_yolu_gosterilir(string $tema): void
    {
        // Eskiden şikayet bloğu tamamen @auth içindeydi: giriş yapmamış biri
        // şüpheli ilanı görüyor ama HİÇBİR çıkışı yoktu.
        $this->temayiKur($tema);
        $ilan = Listing::factory()->create();

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('Bu ilanı şikayet et')
            ->assertSee(route('login'), false);
    }

    #[DataProvider('temalar')]
    public function test_giris_yapan_sikayet_formunu_gorur(string $tema): void
    {
        $this->temayiKur($tema);
        $ilan = Listing::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('Bu ilanı şikayet et')
            ->assertSee(route('reports.store', $ilan), false);
    }

    #[DataProvider('temalar')]
    public function test_ilan_sahibi_kendi_ilanini_sikayet_edemez(string $tema): void
    {
        $this->temayiKur($tema);
        $satici = User::factory()->create();
        $ilan = Listing::factory()->for($satici)->create();

        $this->actingAs($satici)
            ->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertDontSee('Bu ilanı şikayet et');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Widgets\BekleyenIslerWidget;
use App\Filament\Widgets\EntegrasyonlarWidget;
use App\Filament\Widgets\ExifPrivacyWidget;
use App\Filament\Widgets\IlanHareketleriWidget;
use App\Filament\Widgets\KahyaKarsilamaWidget;
use App\Filament\Widgets\KategoriDagilimiWidget;
use App\Filament\Widgets\KesifIlerlemeWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SystemHealthWidget;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Yönetim panosunun sırası ve kimlik satırı (2026-08-06).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Panoda sekiz widget ÜÇ sort değerine yığılmıştı (-3'te üç, -2'de üç, -1'de
 * üç). Eşit sort'ta sıra fiilen rastgeleydi ve sağlayıcıdaki yorum gerçekleşen
 * sıradan BAŞKA bir sıra anlatıyordu: "en sonda hesap kartları" yazıyordu ama
 * vendor'ın AccountWidget'i kendi sort'unu (-3) getirdiği için tam ortaya
 * düşüyordu.
 *
 * Ders bugünün dersiyle aynı: NİYETİ YORUMA YAZMAK, KODUN ONU YAPTIĞINI
 * KANITLAMAZ. Sıra artık burada mühürlü.
 */
class PanoSiralamasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Belgelenen merdiven — {@see AdminPanelProvider}.
     *
     * @return list<class-string>
     */
    private function beklenenSira(): array
    {
        return [
            BekleyenIslerWidget::class,     // 10 · kim girdi + bekleyen iş
            KahyaKarsilamaWidget::class,    // 20 · kâhyanın özeti
            KesifIlerlemeWidget::class,     // 30 · yarım kalan keşif
            StatsOverview::class,           // 40 · pazaryeri sayıları
            IlanHareketleriWidget::class,   // 50 · eğilim
            KategoriDagilimiWidget::class,  // 60 · eğilim
            SystemHealthWidget::class,      // 70 · makine sağlığı
            EntegrasyonlarWidget::class,    // 80 · yapılandırma
            ExifPrivacyWidget::class,       // 90 · gizlilik denetimi
        ];
    }

    /** @return list<class-string> */
    private function kayitliWidgetlar(): array
    {
        return array_values(array_map(
            fn ($widget) => is_string($widget) ? $widget : $widget::class,
            Filament::getPanel('admin')->getWidgets(),
        ));
    }

    public function test_hicbir_widget_ayni_sort_degerini_paylasmaz(): void
    {
        /*
         * ASIL BEKÇİ BU. Çakışan sort sessizdir: hata vermez, testi kırmaz,
         * yalnızca sıra rastgeleleşir. Yeni bir widget eklerken kopyala-yapıştır
         * bir sort değeri geldiğinde burada yakalanır.
         */
        $sortlar = [];

        foreach ($this->kayitliWidgetlar() as $widget) {
            $sortlar[$widget] = $widget::getSort();
        }

        $yinelenen = array_diff_assoc($sortlar, array_unique($sortlar));

        $this->assertSame(
            [],
            $yinelenen,
            'Şu widget(lar) başka bir widget ile aynı sort değerini paylaşıyor: '
                .json_encode($yinelenen, JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_pano_belgelenen_merdiven_sirasina_gore_dizilir(): void
    {
        $this->assertSame($this->beklenenSira(), $this->kayitliWidgetlar());
    }

    public function test_vendor_hesap_ve_marka_kartlari_panoda_degil(): void
    {
        /*
         * AccountWidget "Hoş geldin + ad + çıkış" gösteriyordu — ilk karttaki
         * selamlamanın tekrarıydı ve e-postayı göstermiyordu. Geri gelirse
         * hem tekrar hem de kendi sort'uyla (-3) merdiveni bozar.
         */
        $kayitli = $this->kayitliWidgetlar();

        $this->assertNotContains(AccountWidget::class, $kayitli);
        $this->assertNotContains(FilamentInfoWidget::class, $kayitli);
    }

    public function test_giris_yapan_yoneticinin_eposta_ve_rolu_panoda_gorunur(): void
    {
        /*
         * Sahibin isteğinin birebir karşılığı: "hangi yönetici şu an aktif,
         * hangi mail ile girmiş". İki yönetici varken ad ayırt etmez.
         */
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'name' => 'Hakan Atabay',
            'email' => 'birinci@ornek.com',
        ]);

        $this->actingAs($admin)
            ->get('/yonetim')
            ->assertOk()
            ->assertSee('birinci@ornek.com')
            ->assertSee('Yönetici');
    }

    public function test_ikinci_yonetici_varken_yonetici_sayisi_gosterilir(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/yonetim')
            ->assertOk()
            ->assertSee('2 yönetici tanımlı');
    }

    public function test_tek_yonetici_varken_sayi_satiri_basilmaz(): void
    {
        // "1 yönetici tanımlı" bilgi değil gürültü — satırın var olma sebebi
        // "hangi yönetici" sorusunu anlamlı kılmak.
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/yonetim')
            ->assertOk()
            ->assertDontSee('yönetici tanımlı');
    }

    public function test_entegrasyon_karti_moderatore_gosterilmez(): void
    {
        // Moderatörün AdSense yayıncı kimliğiyle yapabileceği bir şey yok.
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        $this->actingAs($moderator)
            ->get('/yonetim')
            ->assertOk()
            ->assertDontSee('AdSense');
    }

    public function test_iki_faktor_kapaliysa_uyari_gosterilir(): void
    {
        /*
         * Moderatör 2FA zorunluluğuna tabi değil (zorunluluk yalnız adminde),
         * yani bu satır onun için gerçekten eyleme çağırır.
         */
        $moderator = User::factory()->ikiFaktorsuz()->create(['role' => UserRole::Moderator]);

        $this->actingAs($moderator)
            ->get('/yonetim')
            ->assertOk()
            ->assertSee('2FA kapalı');
    }
}

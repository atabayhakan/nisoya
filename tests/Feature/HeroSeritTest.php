<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hero'nun kanıt şeridi — üç hâl.
 *
 * Şerit bir kez "97 kategori · 44 şehir"den gerçek harekete çevrilmişti; bu
 * testler o düzeltmenin geri alınmadığını (katalog sayısına dönülmediğini) ve
 * yeni kuralın tuttuğunu birlikte koruyor.
 *
 * En kritik bekçi `envanter_azken_aktif_ilan_sayisi_basilmaz`: mobilde ilk
 * ekranda görünen somut sayının "3 aktif ilan" olması, ziyaretçiye "burada
 * kimse yok" demektir. Sayıyı şişirmek (demo saymak) daha önce bilerek
 * elendi — geriye kalan dürüst hamle sayıyı hiç göstermemek.
 */
class HeroSeritTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Kanıt şeridi yalnız Vitrin hero'sunda var (klasik temada bu üçlü yok).
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->assertTrue(Tema::vitrinMi());
    }

    private function ilanlar(int $adet, bool $demo = false): void
    {
        Listing::factory()
            ->count($adet)
            ->for(User::factory())
            ->create(['status' => 'aktif', 'is_demo' => $demo]);
    }

    /**
     * "aktif ilan" metni hero'da BAŞKA bir yerde de geçiyor (ülke hareketi
     * satırı), o yüzden düz assertDontSee yanlış sebeple geçebilir — ülke
     * kaydı yokken o satır zaten basılmaz. İddia şeridin SAYI kalıbına
     * bağlanıyor: ">N< ... aktif ilan".
     */
    private function seritSayisiniOku(string $html): ?string
    {
        preg_match('/>\s*(\d+)\s*<[^>]*>\s*<[^>]*>\s*aktif ilan/su', $html, $m);

        return $m[1] ?? null;
    }

    public function test_envanter_azken_aktif_ilan_sayisi_basilmaz(): void
    {
        $this->ilanlar(3);

        $yanit = $this->get('/');

        $yanit->assertOk();
        $this->assertNull(
            $this->seritSayisiniOku($yanit->getContent()),
            'Envanter azken kanıt şeridi sayı bastı — ziyaretçiye "burada kimse yok" diyor.'
        );
    }

    public function test_envanter_azken_arz_cagrisi_gosterilir(): void
    {
        $this->ilanlar(3);

        $this->get('/')->assertSee('Şehrinde ilk ilanı sen ver');
    }

    public function test_envanter_yeterince_buyukse_sayilar_geri_gelir(): void
    {
        // Eşik ilan listesinin sayfa boyutu (12) — bir sayfayı dolduran
        // envanter kendi lehine tanıklık edebilir.
        $this->ilanlar(12);

        $yanit = $this->get('/');

        $this->assertSame('12', $this->seritSayisiniOku($yanit->getContent()));
        $yanit->assertDontSee('Şehrinde ilk ilanı sen ver');
    }

    public function test_demo_ilanlar_esigi_gecirmez(): void
    {
        // 30 demo ilan eşiği geçirseydi şerit dolu bir pazaryeri iddia ederdi.
        $this->ilanlar(30, demo: true);

        $this->assertNull($this->seritSayisiniOku($this->get('/')->getContent()));
    }

    public function test_katalog_sayilarina_geri_donulmez(): void
    {
        $this->ilanlar(12);

        // Eski sürüm "kategori" sayısını kanıt diye basıyordu; o karar
        // bilerek geri alınmıştı, şerit yine katalog büyüklüğü göstermemeli.
        $this->get('/')->assertDontSee('kategori</span>', false);
    }

    public function test_ulke_kaydi_olmadan_ana_sayfa_kirilmaz(): void
    {
        $this->assertSame(0, Country::query()->count());

        $this->get('/')->assertOk();
    }
}

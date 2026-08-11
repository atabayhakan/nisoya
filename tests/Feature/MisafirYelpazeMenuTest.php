<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Başlıktaki misafir "yelpaze" menüsü.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * Başlıkta yer yoktu: "Acil" etiketi geri gelince "Giriş" metni dar ekranlarda
 * gizlenmek zorunda kalmıştı, yani bir eylem tamamen kayboluyordu. Yelpaze tek
 * düğme kadar yer kaplayıp üç eylem taşıyor. Bu testler o üç eylemin ve
 * erişilebilirlik kancalarının kaybolmadığını zorlar.
 *
 * ---------------------------------------------------------------------------
 * SINIR — BU TESTİN ÖLÇEMEDİĞİ ŞEY
 *
 * Açılma animasyonu ve ODAK davranışı (menü açılınca ilk eyleme odak, Escape'te
 * tetiğe dönüş) tarayıcı gerektirir; PHPUnit bunları çalıştıramaz. Burada
 * yalnız kancaların DOM'da bulunduğu ölçülür:
 *   - `x-ref="ilk"` (odaklanacak öğe)
 *   - `aria-controls` / `aria-expanded`
 * Ayrıca öğelerde `x-show` OLMADIĞI zorlanır: ilk sürümde her öğenin kendi
 * `x-show`'u vardı ve öğe odak anında henüz görünmediği için `focus()` sessizce
 * çalışmıyordu. O regresyon bu testle geri gelemez.
 */
class MisafirYelpazeMenuTest extends TestCase
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
        $this->assertSame($tema === 'vitrin', Tema::vitrinMi());
    }

    #[DataProvider('temalar')]
    public function test_misafir_uc_eylemi_de_gorur(string $tema): void
    {
        $this->temayiKur($tema);

        $this->get('/')
            ->assertOk()
            ->assertSee('misafir-yelpaze', false)
            ->assertSee('Üye ol')
            ->assertSee('Giriş yap')
            ->assertSee('İlan Ver');
    }

    #[DataProvider('temalar')]
    public function test_erisilebilirlik_kancalari_yerinde(string $tema): void
    {
        $this->temayiKur($tema);

        $this->get('/')
            ->assertOk()
            ->assertSee('aria-controls="misafir-yelpaze"', false)
            ->assertSee('aria-expanded', false)
            ->assertSee('x-ref="ilk"', false);
    }

    #[DataProvider('temalar')]
    public function test_yelpaze_ogelerinde_x_show_olmamali(string $tema): void
    {
        /*
         * REGRESYON BEKÇİSİ. Öğelerin kendi `x-show`'u varken, menü açıldığı
         * anda ilk öğe henüz görünür değildi (`offsetParent === null`) ve
         * odak taşınamıyordu — klavye kullanıcısı menüye giremiyordu. Bu
         * tarayıcıda ölçüldü. Görünürlük artık YALNIZ sarmalayıcıdan gelir.
         */
        $this->temayiKur($tema);

        $html = $this->get('/')->assertOk()->getContent();

        $panelBaslangic = strpos($html, 'id="misafir-yelpaze"');
        $this->assertNotFalse($panelBaslangic, 'Yelpaze paneli basılmamış');

        // Panelin açılış etiketinden sonraki bölümde öğeler var; oradaki
        // her `x-show` sarmalayıcıya değil öğeye aitse hata.
        $panelSonu = strpos($html, '</div>', strpos($html, 'yelpaze-oge'));
        $ogeBolgesi = substr($html, strpos($html, 'yelpaze-oge'), max(0, $panelSonu - strpos($html, 'yelpaze-oge')));

        $this->assertStringNotContainsString('x-show', $ogeBolgesi,
            'Yelpaze öğesinde x-show var — odak taşınmaz, erişilebilirlik kırılır.');
    }

    #[DataProvider('temalar')]
    public function test_giris_yapmis_uye_yelpaze_gormez(string $tema): void
    {
        // Yelpaze misafire özel; üyenin başlığında kendi hesap menüsü var.
        $this->temayiKur($tema);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertDontSee('misafir-yelpaze', false);
    }
}

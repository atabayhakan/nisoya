<?php

namespace Tests\Feature;

use App\Support\Settings;
use App\Support\Tema;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Başlıktaki mobil düğme kümesi (ara/ülke/acil) hizalaması (2026-08-19).
 *
 * ÖLÇÜLEN HATA (sahip ekran görüntüsüyle bildirdi): canlıda dört düğme
 * 28-38px arası yükseklikte saçılmıştı ve iki farklı köşe yuvarlaklığı
 * (rounded-lg / rounded-full) arasında dönüyordu — sıra "birbirinden
 * bağımsız" görünüyordu. Gerçek geometri `getBoundingClientRect()` ile
 * ölçüldü, tahmin edilmedi (bkz. PR açıklaması: önce 28-38px saçılma,
 * sonra dördü de tam 36px/aynı top).
 *
 * Bu test piksel ölçemez (headless HTTP, tarayıcı değil) — DOM üzerinden
 * düğmelerin AYNI `h-9` yükseklik sınıfını taşıdığını doğrular.
 *
 * DÖRTTEN ÜÇE (2026-08-21): "Üye ol" bu pill sırasından çıkıp alt sekme
 * çubuğundaki "İlan Ver" yelpazesine taşındı (bkz. x-mobile-tab-bar) —
 * artık raised bir FAB, bu satırın h-9/rounded-full ailesine ait değil.
 * Kalan üç düğme (ara/ülke/acil) hizalama kaygısını hâlâ taşıyor.
 */
class BaslikDugmeHizalamaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

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

    /** @return list<string> */
    private function classlariBul(string $html, string $xpath): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $x = new DOMXPath($dom);

        $sonuc = [];
        foreach ($x->query($xpath) as $node) {
            $sonuc[] = $node->getAttribute('class');
        }

        return $sonuc;
    }

    #[DataProvider('temalar')]
    public function test_misafir_basligindaki_dort_dugme_de_h9_yuksekliginde(string $tema): void
    {
        $this->temayiKur($tema);

        $html = $this->get('/')->assertOk()->getContent();

        $anchors = [
            'ara' => '//*[@aria-label="Ara (Cmd/Ctrl+K)"]',
            'ülke' => '//*[@aria-label="Ülke seç"]',
            'acil' => '//*[@aria-label="Acil yardım — hızlı erişim"]',
        ];

        foreach ($anchors as $isim => $xpath) {
            $classlar = $this->classlariBul($html, $xpath);
            $this->assertNotEmpty($classlar, "[{$tema}] '{$isim}' düğmesi bulunamadı");
            $this->assertStringContainsString('h-9', $classlar[0], "[{$tema}] '{$isim}' düğmesi h-9 yükseklik sınıfını taşımıyor");
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\User;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Mobil başlık: kimlik, ülke seçici, favoriler (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * SAHİBİN BİLDİRDİĞİ EKSİK
 *
 * "Üye olarak giriş yaptığımda giriş yaptığıma dair hiçbir şey görünmüyor —
 * sağ üst köşede resim, kullanıcı adı, çıkış yap butonu gibi bir şey de yok."
 *
 * Doğruydu ve iki yönlüydü: giriş yapmışken avatar/ad/çıkış `hidden md:*`
 * idi; MİSAFİRKEN de "Giriş"/"Kayıt" aynı şekilde gizliydi — yani telefonda
 * giriş yapma yolu da başlıkta yoktu.
 *
 * ---------------------------------------------------------------------------
 * NEDEN İKİ LAYOUT
 *
 * Vitrin teması `resources/views/vitrin/components/layouts/app.blade.php` ile
 * klasik layout'u geçersiz kılar. Bu turda AYNI TUZAĞA İKİ KEZ düşüldü:
 * düzenleme klasik dosyaya yapıldı, tarayıcıda hiç görünmedi. Testler her iki
 * temada da koşuyor ki tema değişince eksik geri gelmesin.
 */
class MobilBaslikTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function temalar(): array
    {
        return ['klasik' => ['klasik'], 'vitrin' => ['vitrin']];
    }

    private function temayiSec(string $tema): void
    {
        Settings::setMany(['gorunum.tema' => $tema]);
        Cache::flush();
        $this->assertSame($tema === 'vitrin', Tema::vitrinMi());
    }

    #[DataProvider('temalar')]
    public function test_giris_yapan_uye_baslikta_kendini_gorur(string $tema): void
    {
        $this->temayiSec($tema);
        $user = User::factory()->create(['name' => 'Hakan Atabay', 'email' => 'uye@nisoya.test']);

        $icerik = $this->actingAs($user)->get('/')->assertOk()->getContent();

        // Kimlik: ad ve e-posta hesap sayfasında yazılı.
        $this->assertStringContainsString('Hakan Atabay', $icerik, "[$tema] Kullanıcı adı başlıkta görünmüyor.");
        $this->assertStringContainsString('uye@nisoya.test', $icerik, "[$tema] Hangi hesapla girildiği görünmüyor.");
        $this->assertStringContainsString('Hesabım', $icerik, "[$tema] Hesap düğmesi yok.");
        $this->assertStringContainsString('Çıkış yap', $icerik, "[$tema] Mobilde çıkış yolu yok.");
        $this->assertStringContainsString('Favorilerim', $icerik, "[$tema] Favoriler erişilebilir değil.");
    }

    #[DataProvider('temalar')]
    public function test_misafir_baslikta_giris_yolu_gorur(string $tema): void
    {
        // Eskiden "Giriş" bağlantısı `hidden md:inline-block` idi: telefonda
        // başlıktan giriş yapmanın yolu yoktu.
        $this->temayiSec($tema);

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/md:hidden[^>]*>\s*Giriş|>\s*Giriş\s*<\/a>/u',
            $icerik,
            "[$tema] Misafir için mobil giriş düğmesi yok."
        );
        // Giriş yapmamışken kimlik/çıkış öğeleri basılmamalı.
        $this->assertStringNotContainsString('Çıkış yap', $icerik);
    }

    #[DataProvider('temalar')]
    public function test_ulke_secici_tiklanabilir_ve_bir_yere_goturur(string $tema): void
    {
        // Eski rozet mobilde hiç görünmüyor, masaüstünde de tıklanamıyordu —
        // yalnız "neredesin" diyen ölü bir etiketti. Dekoratif bir "seçici"
        // görüntüsü vermiyoruz: tıklanan her şey bir yere götürmeli.
        $this->temayiSec($tema);
        Country::firstOrCreate(['code' => 'DE'], [
            'name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true, 'sort_order' => 0,
        ]);

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Ülke seç"', $icerik);
        $this->assertStringContainsString('?ulke=DE', $icerik, "[$tema] Ülke seçimi bir ilan listesine gitmiyor.");
    }

    #[DataProvider('temalar')]
    public function test_alt_sayfalarin_kapatma_yolu_vardir(string $tema): void
    {
        /*
         * Sahibin bildirimi: "Keşfet'i açtığımda tekrar kapatamıyorum, arka
         * plan hareket ediyor."
         *
         * İki kusur birdendi: (1) kapatma düğmesi YOKTU — tek yol arka plana
         * dokunmaktı, o da panel ekranın %80'ini kaplayınca ince bir şeride
         * iniyordu; Escape ise telefonda yok. (2) Gövde kaydırma kilidi yoktu.
         *
         * Kilit davranışı JavaScript'te (Alpine `altSayfa`) ve tarayıcıda
         * doğrulandı; burada işaretlemenin gerilemesi mühürleniyor — kapatma
         * düğmesi silinirse ya da paylaşılan davranıştan çıkılırsa kırılır.
         */
        $this->temayiSec($tema);
        $user = User::factory()->create();

        $icerik = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertGreaterThanOrEqual(
            3,
            substr_count($icerik, 'aria-label="Kapat"'),
            "[$tema] Alt sayfaların kapatma düğmesi eksik (Keşfet · ülke seçici · hesap)."
        );

        // Üçü de ortak davranışı kullanmalı: biri elle yazılmış duruma dönerse
        // kaydırma kilidi o sayfada sessizce kaybolur.
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($icerik, 'x-data="altSayfa"'),
            "[$tema] Bir alt sayfa ortak kilit davranışını kullanmıyor."
        );
    }

    #[DataProvider('temalar')]
    public function test_tanitim_karti_katalog_sayisi_gostermez(string $tema): void
    {
        // "25 ülke · 50 şehir · 97 kategori" katalog büyüklüğüydü, gerçek
        // hareket değil. Aynı yanıltıcı üçlü hero'dan kaldırılmıştı; bu kartta
        // unutulmuştu.
        $this->temayiSec($tema);

        $icerik = $this->get('/')->assertOk()->getContent();

        // İlk yazdığım iddia fazla dardı ('>kategori<') ve VİTRİN'de yanlış
        // sebeple geçti: metin "kategoride hizmet" biçimindeydi. Yanıltıcı
        // üçlü aslında İKİ TEMADA DA duruyordu. Artık gerçek ifadeler aranıyor.
        foreach (['kategoride hizmet', 'ülke · ', ' şehir</'] as $yaniltici) {
            $this->assertStringNotContainsString($yaniltici, $icerik, "[$tema] Katalog sayısı geri gelmiş: {$yaniltici}");
        }

        $this->assertStringContainsString('Nerede olursan ol', $icerik);
    }
}

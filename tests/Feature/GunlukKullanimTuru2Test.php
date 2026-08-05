<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Günlük kullanım — Tur 2 (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * DENETİMİN ORTAYA ÇIKARDIĞI DESEN
 *
 * Sorun "mobil ihmal edilmiş" değildi; İKİ YÖNLÜ ASİMETRİ vardı. Kimse iki
 * yüzeyi yan yana koyup yürümemişti:
 *
 *   · Mesajlar  → mobilde tek dokunuş + rozet, masaüstünde HİÇ YOK
 *   · Favoriler → mobilde başlıkta ikon, listelerde yönetilemiyor
 *   · Bildirim  → masaüstünde zil + rozet, mobilde HİÇ YOK
 *
 * Yani sahibin bulduğu "mobilde çıkış yok" hatası tek bir gözden kaçırma
 * değil, sistematik olarak yürünmemiş iki yüzeyin belirtisiydi.
 */
class GunlukKullanimTuru2Test extends TestCase
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

    private function ilan(): Listing
    {
        return Listing::factory()->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => false,
        ]);
    }

    // ------------------------------------------------------- Favoriler

    #[DataProvider('temalar')]
    public function test_favorilerim_sayfasindan_cikarilabilir(string $tema): void
    {
        /*
         * "Favorilerim" favorileri GÖSTERİYOR ama YÖNETMİYORDU: kartlarda kalp
         * yoktu, çıkarmak için her ilanın detayına girmek gerekiyordu.
         */
        $this->temayiSec($tema);
        $uye = User::factory()->create();
        $ilan = $this->ilan();
        Favorite::query()->create(['user_id' => $uye->id, 'listing_id' => $ilan->id]);

        $icerik = $this->actingAs($uye)->get('/panel/favorilerim')->assertOk()->getContent();

        $this->assertStringContainsString(
            route('favorites.toggle', $ilan),
            $icerik,
            "[$tema] Favorilerim sayfasında çıkarma yolu yok."
        );
        $this->assertStringContainsString('Favorilerden çıkar', $icerik);
    }

    #[DataProvider('temalar')]
    public function test_listede_favoriye_eklenebilir(string $tema): void
    {
        $this->temayiSec($tema);
        $uye = User::factory()->create();
        $ilan = $this->ilan();

        $icerik = $this->actingAs($uye)->get('/ilanlar')->assertOk()->getContent();

        $this->assertStringContainsString(route('favorites.toggle', $ilan), $icerik);
        $this->assertStringContainsString('Favorilere ekle', $icerik);
    }

    public function test_misafire_kalp_gosterilmez(): void
    {
        // Favori hesap gerektiriyor; misafire kalp göstermek tutulamayacak
        // bir vaat olurdu.
        $this->temayiSec('vitrin');
        $ilan = $this->ilan();

        $icerik = $this->get('/ilanlar')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('favorites.toggle', $ilan), $icerik);
    }

    public function test_kart_izgarasi_favori_icin_tek_sorgu_yapar(): void
    {
        /*
         * Kart başına ayrı sorgu, 12'lik bir ızgarada 12 sorgu demekti.
         * `favoriIlanKimlikleri()` istek başına bir kez okur.
         */
        $this->temayiSec('vitrin');
        $uye = User::factory()->create();
        foreach (range(1, 6) as $i) {
            $ilan = $this->ilan();
            Favorite::query()->create(['user_id' => $uye->id, 'listing_id' => $ilan->id]);
        }

        $this->actingAs($uye);
        DB::enableQueryLog();
        $this->get('/ilanlar')->assertOk();
        $sorgular = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'favorites'))
            ->count();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(2, $sorgular, "Favori durumu için {$sorgular} sorgu yapıldı — N+1.");
    }

    // ------------------------------------------------------- Bildirimler

    #[DataProvider('temalar')]
    public function test_bildirimler_mobilde_erisilebilir(string $tema): void
    {
        /*
         * Bildirimler mobilde HİÇBİR YERDE yoktu: ne alt sekme çubuğunda ne
         * hesap sayfasında. Tek yol /panel'e gidip "Bölümler" ızgarasında
         * kartı bulmaktı — yani ortam sinyali de yoktu, kullanıcı okunmamış
         * bildirimi olduğunu bilemiyordu.
         */
        $this->temayiSec($tema);
        $uye = User::factory()->create();

        $icerik = $this->actingAs($uye)->get('/')->assertOk()->getContent();

        // Önce YALNIZ masaüstü zili vardı (tek geçiş). Mobil hesap sayfasına
        // satır eklenince iki giriş noktası olmalı. İlk yazdığım regex fazla
        // katıydı ve yanlış sebeple kırıldı: `md:hidden` ile "Bildirimler"
        // arasında onlarca </div> var.
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($icerik, route('panel.notifications.index')),
            "[$tema] Bildirimlere tek giriş noktası var — mobilde hâlâ erişilemiyor."
        );
        $this->assertStringContainsString('Bildirimler', $icerik);
    }

    // ------------------------------------------------------- Mesajlar

    #[DataProvider('temalar')]
    public function test_mesajlar_masaustu_basliginda_var(string $tema): void
    {
        /*
         * Mobilde alt sekmede rozetle duruyordu; masaüstünde başlıkta HİÇ
         * yoktu ve okunmamış sinyali de yoktu.
         */
        $this->temayiSec($tema);
        $uye = User::factory()->create();

        $icerik = $this->actingAs($uye)->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/md:inline-flex(?:(?!<\/a>).)*title="Mesajlar"|title="Mesajlar"(?:(?!<\/a>).)*/su',
            $icerik,
            "[$tema] Masaüstü başlığında Mesajlar yok."
        );
        $this->assertStringContainsString(route('panel.messages.index'), $icerik);
    }

    public function test_misafire_mesaj_ve_bildirim_gosterilmez(): void
    {
        $this->temayiSec('vitrin');

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('title="Mesajlar"', $icerik);
        $this->assertStringNotContainsString('Bildirimler', $icerik);
    }
}

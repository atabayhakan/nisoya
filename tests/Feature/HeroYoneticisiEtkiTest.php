<?php

namespace Tests\Feature;

use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Hero Yöneticisi ana sayfaya GERÇEKTEN etki ediyor mu? (2026-08-06)
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Sahip "Hero Yöneticisi'nde değişiklik yapıyorum, ana sayfada değişmiyor"
 * dedi ve bire bir haklıydı. Sebep iki AYRI ANAHTAR UZAYIydı:
 *
 *   Hero Yöneticisi YAZAR : hero.rozet, hero.baslik, hero.vurgu, hero.alt_baslik
 *   Klasik ana sayfa OKUR : home.hero_badge, home.hero_satir1, home.hero_vurgu…
 *
 * Arada `App\Support\Hero` vardı ve o ikisini zaten köprülüyordu
 * (`hero.baslik ?: home.hero_satir1`) — ama Hero'yu YALNIZCA Vitrin hero'su
 * kullanıyordu. Klasik ana sayfa ayarları doğrudan okuyordu, yani yönetici
 * ekranı kaydediyor ama hiçbir yere bağlanmıyordu: ekran vardı, kablo yoktu.
 *
 * Bu dosya kabloyu mühürler. Sessiz kopması mümkün bir bağ — kopunca hata
 * vermez, sadece "değişmiyor".
 */
class HeroYoneticisiEtkiTest extends TestCase
{
    use RefreshDatabase;

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
    public function test_hero_yoneticisinin_metinleri_ana_sayfada_gorunur(string $tema): void
    {
        $this->temayiSec($tema);

        Settings::setMany([
            'hero.rozet' => 'ROZET-YONETICIDEN',
            'hero.baslik' => 'BASLIK-YONETICIDEN',
            'hero.vurgu' => 'VURGU-YONETICIDEN',
            'hero.alt_baslik' => 'ALTBASLIK-YONETICIDEN',
        ]);
        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('ROZET-YONETICIDEN')
            ->assertSee('BASLIK-YONETICIDEN')
            ->assertSee('VURGU-YONETICIDEN')
            ->assertSee('ALTBASLIK-YONETICIDEN');
    }

    #[DataProvider('temalar')]
    public function test_yonetici_bos_birakilinca_klasik_metinlere_duser(string $tema): void
    {
        /*
         * GERİYE DÖNÜK UYUM — bu testin varlık sebebi düzeltmenin kimseyi
         * kırmamasını kanıtlamak. Hero Yöneticisi'ni hiç açmamış bir sitede
         * `hero.*` boştur; sayfa eskisi gibi İçerik (Metinler) sayfasındaki
         * `home.*` metinlerini basmalı.
         */
        $this->temayiSec($tema);

        Settings::setMany([
            'hero.rozet' => '',
            'hero.baslik' => '',
            'hero.vurgu' => '',
            'hero.alt_baslik' => '',
            'home.hero_badge' => 'ROZET-ICERIKTEN',
            'home.hero_satir1' => 'BASLIK-ICERIKTEN',
            'home.hero_vurgu' => 'VURGU-ICERIKTEN',
            'home.hero_aciklama' => 'ALTBASLIK-ICERIKTEN',
        ]);
        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('ROZET-ICERIKTEN')
            ->assertSee('BASLIK-ICERIKTEN')
            ->assertSee('VURGU-ICERIKTEN')
            ->assertSee('ALTBASLIK-ICERIKTEN');
    }

    #[DataProvider('temalar')]
    public function test_yonetici_icerigi_ezer(string $tema): void
    {
        // İkisi de doluysa yönetici kazanır — aksi hâlde "değişmiyor"
        // şikâyeti başka bir kılıkta geri gelirdi.
        $this->temayiSec($tema);

        Settings::setMany([
            'hero.baslik' => 'YONETICI-KAZANIR',
            'home.hero_satir1' => 'ICERIK-KAYBEDER',
        ]);
        Cache::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('YONETICI-KAZANIR')
            ->assertDontSee('ICERIK-KAYBEDER');
    }
}

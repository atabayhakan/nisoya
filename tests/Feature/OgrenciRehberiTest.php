<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Page;
use Database\Seeders\OgrenciRehberiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * İngiltere öğrenci rehberi sayfası (büyüme önerisi 4).
 *
 * Bu sayfa bir SEO içeriğidir ve az önce 93 boş kategori sayfasını indeksten
 * çıkardığımız yerde duruyor — dolayısıyla kendisi ince içerik OLMAMALI.
 * Testler üç şeyi mühürlüyor:
 *
 *   1. Seeder taslak üretir (Claude halka açık içeriği kendi başına yayınlamaz;
 *      yayına alma kararı sahibindir).
 *   2. Seeder idempotenttir — ikinci çalıştırma paneldeki düzenlemeyi ezmez.
 *   3. Eylem çağrısı ARZ çağrısıdır. Ölçüm (2026-07-29): İngiltere'de 0 ilan
 *      var. "Gelin alışveriş yapın" tutulamayacak bir vaat olurdu; sayfa
 *      "eşyanı ücretsiz listele" demeli. Bu, sayfanın varlık sebebidir ve
 *      sessizce değiştirilirse öneri 1'in (TUSU) tüm çerçevesi çöker.
 */
class OgrenciRehberiTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'ingiltere-ogrenci-ev-kurma-rehberi';

    private function seedle(): Page
    {
        $this->seed(OgrenciRehberiSeeder::class);

        return Page::query()->where('slug', self::SLUG)->firstOrFail();
    }

    public function test_seeder_sayfayi_taslak_olarak_olusturur(): void
    {
        $sayfa = $this->seedle();

        $this->assertSame(PageStatus::Taslak, $sayfa->status);
        $this->assertFalse(
            Page::published()->where('slug', self::SLUG)->exists(),
            'Rehber taslak olmalı — yayına alma kararı sahibindedir.'
        );
    }

    public function test_taslak_sayfa_ziyaretciye_kapali(): void
    {
        $this->seedle();

        $this->get('/'.self::SLUG)->assertNotFound();
    }

    public function test_seeder_idempotenttir_ve_panel_duzenlemesini_ezmez(): void
    {
        $sayfa = $this->seedle();
        $sayfa->update(['title' => 'Sahibin elle değiştirdiği başlık']);

        $this->seed(OgrenciRehberiSeeder::class);

        $this->assertSame(1, Page::query()->where('slug', self::SLUG)->count(), 'İkinci seed kopya oluşturmamalı.');
        $this->assertSame('Sahibin elle değiştirdiği başlık', $sayfa->fresh()->title);
    }

    public function test_eylem_cagrisi_arz_cagrisidir(): void
    {
        $sayfa = $this->seedle();

        $cta = collect($sayfa->blocks)->firstWhere('type', 'cta');

        $this->assertNotNull($cta, 'Sayfada eylem çağrısı olmalı.');
        $this->assertSame('/panel/ilan/yeni', $cta['data']['button_url']);
        $this->assertStringContainsString('listele', mb_strtolower($cta['data']['title'].$cta['data']['button_text']));
    }

    /**
     * Yayına alındığında gerçekten dolu bir sayfa olmalı.
     *
     * Kelime sayısı eşiği keyfî değil: bu sayfa, içeriksiz sayfaların
     * indeksten çıkarıldığı bir sitede yayına giriyor. İnce bir rehber
     * yazmak, temizlediğimiz sorunun aynısını geri getirirdi.
     */
    public function test_yayina_alininca_dolu_bir_sayfa_render_eder(): void
    {
        $sayfa = $this->seedle();
        $sayfa->update(['status' => PageStatus::Yayin]);

        $yanit = $this->get('/'.self::SLUG)->assertSuccessful();

        $icerik = $yanit->getContent();

        // Beş bölüm başlığı da render edilmeli.
        foreach (['getir mi, orada al mı', 'ev kurma listesi', 'nereden bulunur', 'dolandırıcılıktan korunma', 'Mezun oluyorsan'] as $baslik) {
            $this->assertStringContainsString($baslik, $icerik, "Bölüm eksik: {$baslik}");
        }

        // İnce içerik değil: gövde metni en az 400 kelime.
        $govde = trim(preg_replace('/\s+/', ' ', strip_tags($icerik)) ?? '');
        $this->assertGreaterThan(400, count(explode(' ', $govde)), 'Rehber ince içerik olmamalı.');

        // Ödeme güvenliği uyarısı bu sayfanın en somut faydası — kaybolmamalı.
        $this->assertStringContainsString('Mal ve Hizmetler', $icerik);
    }
}

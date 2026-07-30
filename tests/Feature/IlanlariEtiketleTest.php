<?php

namespace Tests\Feature;

use App\Models\KahyaEylemKaydi;
use App\Models\Listing;
use App\Models\Tag;
use App\Services\Ai\AiManager;
use App\Services\Kahya\Eylem\EylemCalistirici;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SahteAiSaglayici;
use Tests\Support\SahteAiYonetici;
use Tests\TestCase;

/**
 * ilanlari-etiketle — "etiketleri Kâhya otomatik yapsın" eyleminin sözleşmesi.
 *
 * Modelin zekâsı sınanmaz (etiket kararını test yazar); sınanan şey kararın
 * etrafındaki korkuluklar: onay kapısı, mevcut etikete bağlanma, yeni etiket
 * açma, uydurma ilan kimliğinin atlanması ve geri almanın dürüstlüğü.
 */
class IlanlariEtiketleTest extends TestCase
{
    use RefreshDatabase;

    private SahteAiSaglayici $sahte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $this->sahte = new SahteAiSaglayici;
        $this->app->instance(AiManager::class, new SahteAiYonetici($this->sahte));
    }

    private function calistirici(): EylemCalistirici
    {
        return app(EylemCalistirici::class);
    }

    /**
     * F0 KARARI (2026-07-30, tasarım §2.2): iç yazma için onay kapısı kalktı —
     * eylem artık DOĞRUDAN uygulanır; korkuluk denetim izi + geri-al +
     * günlük yedek. Eski "önce beklemede, onayla, sonra uygula" sözleşmesinin
     * testi bu değişiklikle birlikte yeni sözleşmeye geçti.
     */
    public function test_dogrudan_uygulanir_ve_etiketler_baglanir(): void
    {
        $ilan = Listing::factory()->create(['title' => 'Ev yapımı mantı']);
        Tag::create(['name' => 'El Yapımı', 'slug' => 'el-yapimi']);

        $this->sahte->yanit = [
            'ilanlar' => [
                ['id' => $ilan->id, 'etiketler' => ['El Yapımı', 'Ev Yemeği']],
            ],
        ];

        $kayit = $this->calistirici()->calistir('ilanlari-etiketle', []);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        // Mevcut etikete bağlandı, olmayan için yenisi açıldı.
        $this->assertSame(2, $ilan->tags()->count());
        $this->assertDatabaseHas('tags', ['slug' => 'ev-yemegi', 'name' => 'Ev Yemeği']);
        $this->assertSame(1, Tag::query()->where('slug', 'el-yapimi')->count(), 'Mevcut etiket kopyalanmamalı.');
        $this->assertStringContainsString('1 ilana toplam 2 etiket', (string) $kayit->sonuc);
        // Geri alınabilirlik onay kapısının yerine geçen güvence — izi olmalı.
        $this->assertTrue($kayit->geriAlinabilirMi());
    }

    public function test_geri_alma_baglari_cozer_ve_oksuz_yeni_etiketi_siler(): void
    {
        $ilan = Listing::factory()->create();
        $acil = Tag::create(['name' => 'Acil', 'slug' => 'acil']);

        $this->sahte->yanit = [
            'ilanlar' => [['id' => $ilan->id, 'etiketler' => ['Acil', 'Ev Yemeği']]],
        ];

        $kayit = $this->calistirici()->calistir('ilanlari-etiketle', []);
        $this->assertSame(2, $ilan->tags()->count());

        $kayit = $this->calistirici()->geriAl($kayit);

        $this->assertSame(KahyaEylemKaydi::DURUM_GERI_ALINDI, $kayit->durum);
        $this->assertSame(0, $ilan->tags()->count());
        // Bu koşunun açtığı ve artık kimsenin kullanmadığı etiket silinir;
        // önceden var olan etiket listede kalır.
        $this->assertDatabaseMissing('tags', ['slug' => 'ev-yemegi']);
        $this->assertDatabaseHas('tags', ['id' => $acil->id]);
    }

    public function test_uydurma_ilan_kimligi_atlanir(): void
    {
        Listing::factory()->create();

        $this->sahte->yanit = [
            'ilanlar' => [['id' => 999999, 'etiketler' => ['Hayalet']]],
        ];

        $kayit = $this->calistirici()->calistir('ilanlari-etiketle', []);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $this->assertStringContainsString('0 ilana toplam 0 etiket', (string) $kayit->sonuc);
        $this->assertDatabaseMissing('tags', ['slug' => 'hayalet']);
    }

    public function test_etiketsiz_ilan_yoksa_durustce_soyler(): void
    {
        $kayit = $this->calistirici()->calistir('ilanlari-etiketle', []);

        $this->assertStringContainsString('Etiketi olmayan aktif ilan yok', $kayit->onizleme);
        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $this->assertStringContainsString('hiçbir şey değişmedi', (string) $kayit->sonuc);
    }

    public function test_yapay_zeka_cokerse_hata_kaydi_kalir(): void
    {
        Listing::factory()->create();
        $this->sahte->yanit = null;
        $this->sahte->sonHata = 'bağlantı zaman aşımı';

        $kayit = $this->calistirici()->calistir('ilanlari-etiketle', []);

        // Sessiz başarısızlık yok: defterde hata, veritabanında değişiklik yok.
        $this->assertSame(KahyaEylemKaydi::DURUM_HATA, $kayit->durum);
        $this->assertSame(0, Tag::query()->count());
    }

    /**
     * `response_format: json_object` kullanan OpenAI-uyumlu uçlar yönergede
     * "json" kelimesi geçmezse 400 döner — KahyaSohbetTest'teki mezar taşının
     * (2026-07-29 canlı hatası) buradaki karşılığı: bu eylem kendi yönergesini
     * yazdığı için aynı tuzağa ayrıca düşebilirdi.
     */
    public function test_yonerge_json_kelimesini_ve_ilanlari_icerir(): void
    {
        $ilan = Listing::factory()->create(['title' => 'Ev yapımı mantı']);
        $this->sahte->yanit = ['ilanlar' => []];

        $this->calistirici()->calistir('ilanlari-etiketle', []);

        $this->assertStringContainsStringIgnoringCase('json', (string) $this->sahte->sonYonerge);
        $this->assertStringContainsString('Ev yapımı mantı', (string) $this->sahte->sonYonerge);
    }
}

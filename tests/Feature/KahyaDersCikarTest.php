<?php

namespace Tests\Feature;

use App\Ai\Kahya\DersCikariciAjani;
use App\Enums\HafizaTuru;
use App\Models\BekleyenHamle;
use App\Models\KahyaCalismasi;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaHafizasi;
use App\Services\Kahya\DersCikarici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Tests\TestCase;

/**
 * Öğrenme döngüsü (F5): karar sinyallerinden ders damıtma.
 *
 * Sınanan sözleşme: sinyal yoksa model HİÇ çağrılmaz (uydurma ders hafızayı
 * zehirler), üretilen dersler kahya-cikarimi kaynağıyla yazılır, birebir
 * tekrar ve tavanlar korunur, koşu her durumda deftere düşer.
 */
class KahyaDersCikarTest extends TestCase
{
    use RefreshDatabase;

    /** İki sinyal (eşik): bir geri alma + notlu bir hamle reddi. */
    private function sinyalUret(): void
    {
        KahyaEylemKaydi::create([
            'eylem' => 'seo-doldur',
            'durum' => KahyaEylemKaydi::DURUM_GERI_ALINDI,
            'risk' => 'dusuk',
            'parametreler' => [],
            'onizleme' => 'SEO başlığı "..." olarak yazılacaktı.',
            'geri_alindi_at' => now()->subDay(),
        ]);

        BekleyenHamle::create([
            'baslik' => 'Facebook grubuna tanıtım',
            'gerekce' => 'Kitleye ulaşmak.',
            'icerik' => 'Merhaba, sizlere Nisoya\'yı tanıtmak isterim...',
            'tur' => 'sosyal',
        ])->refresh()->kararVer(BekleyenHamle::DURUM_REDDEDILDI, 'Facebook gruplarında reklam sevilmez, önce ilişki kur.');
    }

    public function test_sinyal_yoksa_model_cagrilmaz(): void
    {
        Http::fake(); // hiç LLM çağrısı beklenmiyor

        $sonuc = app(DersCikarici::class)->calis();

        $this->assertSame('sinyal-yok', $sonuc['durum']);
        $this->assertSame(0, $sonuc['uretilen']);
        Http::assertNothingSent();
        $this->assertSame(0, KahyaHafizasi::query()->count());
    }

    public function test_sinyallerden_ders_damitir_ve_cikarim_kaynagiyla_yazar(): void
    {
        $this->sinyalUret();
        Ai::fakeAgent(DersCikariciAjani::class, [[
            'dersler' => [
                ['metin' => 'Facebook gruplarına doğrudan tanıtım önerme; önce toplulukla ilişki kuran hamleler öner.', 'gerekce' => 'Sahip sosyal hamleyi bu notla reddetti.'],
            ],
        ]]);

        $sonuc = app(DersCikarici::class)->calis();

        $this->assertSame('tamam', $sonuc['durum']);
        $this->assertSame(2, $sonuc['sinyal']);
        $this->assertSame(1, $sonuc['uretilen']);
        $this->assertDatabaseHas('kahya_hafiza', [
            'tur' => HafizaTuru::Ders->value,
            'kaynak' => KahyaHafizasi::KAYNAK_CIKARIM,
            'aktif' => true,
        ]);
    }

    public function test_birebir_tekrar_yazilmaz_ve_kosu_tavani_beslenir(): void
    {
        $this->sinyalUret();
        KahyaHafizasi::create([
            'tur' => HafizaTuru::Ders,
            'metin' => 'Zaten bilinen ders.',
            'kaynak' => KahyaHafizasi::KAYNAK_CIKARIM,
        ]);

        // 7 ders döner: 1 tekrar + 6 yeni → tekrar atlanır, tavan 5'te keser.
        $dersler = [['metin' => 'Zaten bilinen ders.', 'gerekce' => 'tekrar']];
        for ($i = 1; $i <= 6; $i++) {
            $dersler[] = ['metin' => "Yeni ders numara {$i} — sahibin kararlarından çıkarım.", 'gerekce' => 'sinyal'];
        }
        Ai::fakeAgent(DersCikariciAjani::class, [['dersler' => $dersler]]);

        $sonuc = app(DersCikarici::class)->calis();

        // Tavan 5 girdiye uygulanır (1 tekrar + 4 yeni) → 4 yeni yazılır.
        $this->assertSame(4, $sonuc['uretilen']);
        $this->assertSame(1, KahyaHafizasi::query()->where('metin', 'Zaten bilinen ders.')->count());
    }

    public function test_toplam_cikarim_tavani_doluyken_uretmez(): void
    {
        $this->sinyalUret();
        for ($i = 1; $i <= 30; $i++) {
            KahyaHafizasi::create([
                'tur' => HafizaTuru::Ders,
                'metin' => "Eski çıkarım {$i} — dolgu metni burada.",
                'kaynak' => KahyaHafizasi::KAYNAK_CIKARIM,
            ]);
        }
        Http::fake(); // model çağrılmamalı

        $sonuc = app(DersCikarici::class)->calis();

        $this->assertSame('cikarim-tavani-dolu', $sonuc['durum']);
        Http::assertNothingSent();
    }

    public function test_bos_liste_donerse_hicbir_sey_yazilmaz(): void
    {
        $this->sinyalUret();
        Ai::fakeAgent(DersCikariciAjani::class, [['dersler' => []]]);

        $sonuc = app(DersCikarici::class)->calis();

        $this->assertSame('tamam', $sonuc['durum']);
        $this->assertSame(0, $sonuc['uretilen']);
        $this->assertSame(0, KahyaHafizasi::query()->count());
    }

    public function test_komut_deftere_yazar_ve_hata_durumunda_cokmez(): void
    {
        $this->sinyalUret();
        // Sahte ajan YOK + HTTP kapalı → LLM çağrısı patlar; komut yine SUCCESS.
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);

        $this->artisan('kahya:ders-cikar')->assertSuccessful();

        $kosu = KahyaCalismasi::query()->where('tur', 'ders_cikar')->firstOrFail();
        $this->assertNotNull($kosu->hata);
    }

    public function test_basarili_kosu_defterde_ozet_tasir(): void
    {
        $this->sinyalUret();
        Ai::fakeAgent(DersCikariciAjani::class, [[
            'dersler' => [['metin' => 'Defter özeti testi için üretilen ders.', 'gerekce' => 'test']],
        ]]);

        $this->artisan('kahya:ders-cikar')->assertSuccessful();

        $kosu = KahyaCalismasi::query()->where('tur', 'ders_cikar')->firstOrFail();
        $this->assertNull($kosu->hata);
        $this->assertSame('tamam', $kosu->ozet['durum']);
        $this->assertSame(1, $kosu->ozet['uretilen']);
    }
}

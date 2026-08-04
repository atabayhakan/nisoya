<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rehber yayın kapısı.
 *
 * Bu içerikler vekaletname, pasaport, doğum bildirimi anlatıyor; insan sayfaya
 * bakıp randevu alıyor ve yol gidiyor. K7 kararı ("resmî kaynaktan, taslak-önce")
 * şimdiye kadar yalnız bir TASARIM BELGESİNDE yazılıydı — belge kimseyi
 * durdurmaz. Bu testler kuralın kodda kalmasını sağlıyor.
 *
 * En kritik bekçi `genel_kaynak_adresi_yayinlanamaz`: 210 taslağın hepsi
 * konsolosluk.gov.tr ana sayfasına işaret ediyordu ve "Resmî kaynağı aç"
 * düğmesi ziyaretçiyi işiyle ilgisiz bir sayfaya bırakıyordu.
 */
class RehberYayinKapisiTest extends TestCase
{
    use RefreshDatabase;

    private function kayit(array $nitelikler = []): TemsilcilikIslemi
    {
        Country::firstOrCreate(['code' => 'DE'], ['name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true]);

        $temsilcilik = Temsilcilik::firstOrCreate(
            ['slug' => 'koeln'],
            ['country_code' => 'DE', 'ad' => 'Köln Başkonsolosluğu', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Köln', 'is_active' => true, 'sort_order' => 0],
        );

        $tur = IslemTuru::firstOrCreate(['slug' => 'vekaletname'], ['ad' => 'Vekaletname', 'is_active' => true, 'sort_order' => 0]);

        return TemsilcilikIslemi::create(array_merge([
            'temsilcilik_id' => $temsilcilik->id,
            'islem_turu_id' => $tur->id,
            'evraklar' => ['Nüfus cüzdanı', 'İki adet fotoğraf'],
            'resmi_kaynak_url' => 'https://koln-bk.mfa.gov.tr/Mission/ShowInfoNote/374152',
            'dogrulanma_tarihi' => now()->toDateString(),
            'status' => TemsilcilikIslemi::STATUS_TASLAK,
        ], $nitelikler));
    }

    private function yayinla(array $secenekler = []): void
    {
        $this->artisan('rehber:yayinla', $secenekler)->assertSuccessful();
    }

    public function test_eksiksiz_kayit_yayina_alinir(): void
    {
        $kayit = $this->kayit();

        $this->yayinla();

        $this->assertSame(TemsilcilikIslemi::STATUS_YAYIN, $kayit->fresh()->status);
    }

    public function test_evrak_listesi_bos_olan_yayinlanamaz(): void
    {
        $kayit = $this->kayit(['evraklar' => []]);

        $this->yayinla();

        $this->assertSame(TemsilcilikIslemi::STATUS_TASLAK, $kayit->fresh()->status);
    }

    public function test_genel_kaynak_adresi_yayinlanamaz(): void
    {
        // 210 taslağın hepsi buydu: "Resmî kaynağı aç" düğmesi ziyaretçiyi
        // işiyle ilgisiz bir ana sayfaya bırakıyordu.
        $kayit = $this->kayit(['resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr']);

        $this->yayinla();

        $this->assertSame(TemsilcilikIslemi::STATUS_TASLAK, $kayit->fresh()->status);
    }

    public function test_dogrulanmamis_kayit_yayinlanamaz(): void
    {
        // Sayfa bu tarihi ziyaretçiye gösteriyor; boşken "henüz doğrulanmadı"
        // yazıyor ve o hâlde yayında olmamalı.
        $kayit = $this->kayit(['dogrulanma_tarihi' => null]);

        $this->yayinla();

        $this->assertSame(TemsilcilikIslemi::STATUS_TASLAK, $kayit->fresh()->status);
    }

    public function test_ucret_ve_sure_zorunlu_degil(): void
    {
        // Sahip kararı (2026-08-04): ücret YAYINLANMAYACAK — yıllık tarifeyle
        // değişiyor, bayatlaması en muhtemel alan. Kapı bunları arasaydı hiçbir
        // içerik yayınlanamazdı; bu test o kararı mühürlüyor.
        $kayit = $this->kayit(['ucret_metni' => null, 'sure_metni' => null]);

        $this->yayinla();

        $this->assertSame(TemsilcilikIslemi::STATUS_YAYIN, $kayit->fresh()->status);
    }

    public function test_rapor_secenegi_hicbir_seyi_yayinlamaz(): void
    {
        $kayit = $this->kayit();

        $this->yayinla(['--rapor' => true]);

        $this->assertSame(TemsilcilikIslemi::STATUS_TASLAK, $kayit->fresh()->status);
    }

    public function test_ulke_suzgeci_baska_ulkeye_dokunmaz(): void
    {
        $kayit = $this->kayit();

        $this->yayinla(['--ulke' => 'US']);

        $this->assertSame(TemsilcilikIslemi::STATUS_TASLAK, $kayit->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\TemsilcilikIslemi;
use Database\Seeders\RehberAlmanyaSeeder;
use Database\Seeders\RehberVefatCenazeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Vefat ve Cenaze İşlemleri" rehber içeriği.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * Bu sayfa, ailesini yeni kaybetmiş birinin okuyacağı sayfa. İki şey kritik:
 * bilgi GERÇEK olmalı ve sayfa OKUNABİLİR olmalı. Testler ikisini de zorluyor:
 *
 *   · Uydurma sayı yok — harç ve süre için doğrulanmış rakam bulunamadığı için
 *     rakam YAZILMADI; bunun yerine teyit yolu gösterildi. Test, ücret metnine
 *     bir para tutarı sızmasını engelliyor.
 *   · Markdown yok — alan görünümde kaçırılarak basılıyor. İlk yazışta
 *     `**kalın**` kullandım ve sayfada 11 ham yıldız göründü.
 *   · İnsan doğrulaması ezilmiyor — sahip panelden bir sayfayı doğruladıysa
 *     seeder ona dokunmaz.
 */
class RehberVefatCenazeTest extends TestCase
{
    use RefreshDatabase;

    private function iskeletVeIcerik(): void
    {
        // Rehber denetleyicisi AKTİF ülke arar; ülkeler ayrı seeder'dan gelir
        // ve rehber seeder'ı onları oluşturmaz. Testte sayfa 404 verince
        // fark edildi.
        Country::query()->firstOrCreate(
            ['code' => 'DE'],
            ['name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true, 'sort_order' => 1]
        );

        $this->seed(RehberAlmanyaSeeder::class);
        $this->seed(RehberVefatCenazeSeeder::class);
    }

    private function kayitlar()
    {
        $tur = IslemTuru::query()->where('slug', 'olum-ve-cenaze')->firstOrFail();

        return TemsilcilikIslemi::query()->where('islem_turu_id', $tur->id)->get();
    }

    public function test_icerik_yayina_alinir_ve_kaynagi_vardir(): void
    {
        $this->iskeletVeIcerik();
        $kayitlar = $this->kayitlar();

        $this->assertGreaterThan(0, $kayitlar->count());

        foreach ($kayitlar as $k) {
            $this->assertSame(TemsilcilikIslemi::STATUS_YAYIN, $k->status);
            $this->assertNotNull($k->dogrulanma_tarihi, 'Doğrulanma tarihi boş');
            $this->assertStringContainsString('konsolosluk.gov.tr', (string) $k->resmi_kaynak_url);
            // Genel ana sayfa DEĞİL, işleme özel sayfa olmalı.
            $this->assertStringContainsString('/Procedure/', (string) $k->resmi_kaynak_url);
        }
    }

    public function test_evrak_listesi_gercek_belgeleri_sayar(): void
    {
        $this->iskeletVeIcerik();
        $evraklar = collect($this->kayitlar()->first()->evraklar)->pluck('ad')->implode(' | ');

        $this->assertStringContainsString('ölüm belgesi', $evraklar);
        $this->assertStringContainsString('pasaport', $evraklar);
        $this->assertStringContainsString('Cenaze bilgi formu', $evraklar);
    }

    public function test_uydurma_rakam_yok(): void
    {
        /*
         * GERÇEK BİLGİ KURALI. Harç tutarı ve işlem süresi için doğrulanmış
         * kaynak bulunamadı; bu yüzden rakam yazılmadı. Biri sonradan
         * "yaklaşık 50 Euro" gibi bir tahmin eklerse test kırmızıya döner.
         */
        $this->iskeletVeIcerik();

        foreach ($this->kayitlar() as $k) {
            $this->assertDoesNotMatchRegularExpression(
                '/\d+\s*(€|EUR|Euro|TL|USD|\$)/iu',
                (string) $k->ucret_metni,
                'Ücret metnine doğrulanmamış bir tutar girmiş'
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\d+\s*(iş\s*)?(gün|hafta|ay)/iu',
                (string) $k->sure_metni,
                'Süre metnine doğrulanmamış bir rakam girmiş'
            );
        }
    }

    public function test_notlarda_markdown_isareti_yok(): void
    {
        // Alan görünümde kaçırılarak basılıyor; `**` ham yıldız olarak çıkar.
        $this->iskeletVeIcerik();

        foreach ($this->kayitlar() as $k) {
            $this->assertStringNotContainsString('**', (string) $k->notlar);
            $this->assertStringNotContainsString('##', (string) $k->notlar);
        }
    }

    public function test_bilinmesi_zor_ama_ise_yarar_bilgi_iceriyor(): void
    {
        /*
         * Bu sayfanın değeri, herkesin bildiği şeyi tekrar etmesinde değil.
         * İki bilgi kritik: başvurunun o ülkedeki HERHANGİ bir temsilciliğe
         * yapılabilmesi, ve vatandaşlık durumu özel olanlarda İçişleri
         * Bakanlığı izni gerekmesi. Biri metni sadeleştirirken bunları
         * atarsa sayfa sıradanlaşır.
         */
        $this->iskeletVeIcerik();
        $k = $this->kayitlar()->first();

        $this->assertStringContainsString('herhangi bir temsilciliğe', (string) $k->notlar);
        $this->assertStringContainsString('İçişleri Bakanlığı', (string) $k->sure_metni);
        $this->assertStringContainsString('292 29 29', (string) $k->notlar);
    }

    public function test_insan_dogrulamasini_ezmez(): void
    {
        /*
         * En önemli davranış: sahip bir temsilciliğin sayfasını panelden elle
         * doğruladıysa (tarih dolar), seeder tekrar koşsa bile ona dokunmaz.
         * Aksi hâlde her deploy, insanın düzelttiği bilgiyi geri alırdı.
         */
        $this->iskeletVeIcerik();

        $kayit = $this->kayitlar()->first();
        $kayit->update([
            'notlar' => 'Sahibin elle doğruladığı özel not.',
            'dogrulanma_tarihi' => '2026-12-01',
        ]);

        $this->seed(RehberVefatCenazeSeeder::class);   // tekrar koş

        $kayit->refresh();
        $this->assertSame('Sahibin elle doğruladığı özel not.', $kayit->notlar);
        $this->assertSame('2026-12-01', $kayit->dogrulanma_tarihi->toDateString());
    }

    public function test_sayfa_acilir_ve_icerigi_basar(): void
    {
        $this->iskeletVeIcerik();
        $kayit = $this->kayitlar()->load('temsilcilik')->first();

        $this->get('/'.mb_strtolower($kayit->temsilcilik->country_code).'/'.$kayit->temsilcilik->slug.'/olum-ve-cenaze')
            ->assertOk()
            ->assertSee('Cenaze bilgi formu')
            ->assertSee('herhangi bir temsilciliğe')
            ->assertDontSee('başlangıç taslağıdır');   // eski yer tutucu gitti
    }
}

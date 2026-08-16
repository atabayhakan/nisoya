<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\KahyaEylemKaydi;
use App\Models\Listing;
use App\Models\User;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Eylem\EylemKatalogu;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Kâhya'nın eylem omurgası — "şunu yap deyince gidip yapsın"ın güvenlik katmanı.
 *
 * BU DOSYANIN EN ÖNEMLİ ÜÇ TESTİ:
 *   · katalog dışı ad ÇALIŞMAZ — güvenlik sınırı cümle anlama değil, liste.
 *   · her eylem GERİ ALINABİLİR iz bırakır — iz yoksa "geri aldım" yalan olur.
 *   · sır anahtarına yazılamaz — ayar doldurma aracı SMTP parolasına dokunamaz.
 */
class KahyaEylemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function calistirici(): EylemCalistirici
    {
        return app(EylemCalistirici::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    // ------------------------------------------------------------- Katalog

    /** Katalogdaki her eylem çözülebilmeli ve kendini anlatabilmeli. */
    public function test_katalog_tutarli(): void
    {
        $katalog = app(EylemKatalogu::class);
        $hepsi = $katalog->hepsi();

        $this->assertNotEmpty($hepsi);

        foreach ($hepsi as $ad => $eylem) {
            $this->assertSame($ad, $eylem->ad());
            $this->assertNotSame('', $eylem->aciklama(), "{$ad} açıklamasız — yapay zekâ onu seçemez.");
            $this->assertNotSame('', $eylem->baslik());
        }

        // Yapay zekâya giden metin her eylemin adını içermeli.
        $metin = $katalog->yapayZekaIcin();

        foreach (array_keys($hepsi) as $ad) {
            $this->assertStringContainsString("### {$ad}", $metin);
        }
    }

    /**
     * ASIL GÜVENLİK SINIRI: katalogda olmayan ad çalışmaz.
     *
     * Model "ilanları sil" gibi bir ad uydurabilir; olması gereken tek şey
     * istisnadır — deftere bile yazılmaz, ortada eylem yok.
     */
    public function test_katalog_disi_ad_calismaz(): void
    {
        $this->expectException(RuntimeException::class);

        $this->calistirici()->calistir('tum-ilanlari-sil', []);

        $this->assertSame(0, KahyaEylemKaydi::query()->count());
    }

    /** Geçersiz parametre eylemi ÇALIŞTIRMAZ ama defterde iz bırakır. */
    public function test_gecersiz_parametre_hata_kaydi_birakir(): void
    {
        $once = Country::query()->count();

        $kayit = $this->calistirici()->calistir('ulke-ekle', ['kod' => 'JAPONYA', 'ad' => '']);

        $this->assertSame(KahyaEylemKaydi::DURUM_HATA, $kayit->durum);
        $this->assertNotNull($kayit->hata);
        $this->assertSame($once, Country::query()->count(), 'Geçersiz istek hiçbir şey değiştirmemeli.');
    }

    // ---------------------------------------------- Sahibin örneği: Fiji
    //
    // Orijinali "Japonya"ydı (sahibin gerçek sözü: "Kâhya ülkeler kısmına
    // Japonya ekle") ama 2026-08-16'daki gelişmişlik-seviyesi genişlemesi
    // Japonya'yı GERÇEKTEN ekledi — test artık "var olan ülke" senaryosunu
    // sınardı. Fiji seçildi çünkü platformun bugüne kadarki iki seçim
    // ölçütünden de (diaspora yoğunluğu, HDI sırası) uzak — yeniden
    // çakışma riski düşük.

    public function test_fiji_eklenir_ve_geri_alinir(): void
    {
        $admin = $this->admin();

        $kayit = $this->calistirici()->calistir('ulke-ekle', [
            'kod' => 'FJ',
            'ad' => 'Fiji',
            'emoji' => '🇫🇯',
        ], $admin);

        // Düşük risk: onay beklemeden uygulanır.
        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $this->assertTrue($kayit->geriAlinabilirMi());
        $this->assertDatabaseHas('countries', ['code' => 'FJ', 'name_tr' => 'Fiji', 'is_active' => true]);

        // Yeni ülke listenin SONUNA girer — mevcut sıra sahibin düzenidir.
        $fiji = Country::query()->where('code', 'FJ')->firstOrFail();
        $this->assertSame((int) Country::query()->max('sort_order'), (int) $fiji->sort_order);

        $this->calistirici()->geriAl($kayit->refresh());

        $this->assertDatabaseMissing('countries', ['code' => 'FJ']);
        $this->assertSame(KahyaEylemKaydi::DURUM_GERI_ALINDI, $kayit->refresh()->durum);
    }

    public function test_var_olan_ulke_yeniden_eklenemez(): void
    {
        $kayit = $this->calistirici()->calistir('ulke-ekle', ['kod' => 'DE', 'ad' => 'Almanya']);

        $this->assertSame(KahyaEylemKaydi::DURUM_HATA, $kayit->durum);
    }

    /**
     * GERİ ALMA VERİ BOZAMAZ: ilan girilmiş bir ülkeyi silmek o ilanların
     * bağını koparır. Geri alma dürüstçe pasife çekmeye dönüşür.
     */
    public function test_ilanli_ulkenin_geri_alinmasi_silmez_pasife_ceker(): void
    {
        $kayit = $this->calistirici()->calistir('ulke-ekle', ['kod' => 'FJ', 'ad' => 'Fiji']);

        Listing::factory()->create(['country_code' => 'FJ']);

        $sonucKaydi = $this->calistirici()->geriAl($kayit->refresh());

        $this->assertDatabaseHas('countries', ['code' => 'FJ', 'is_active' => false]);
        $this->assertStringContainsString('silinmedi', (string) $sonucKaydi->sonuc);
    }

    // ------------------------------------------------------- Risk kapısı

    /** Yüksek riskli eylem UYGULANMAZ — önce onay bekler. */
    public function test_yuksek_risk_once_onay_bekler(): void
    {
        $kayit = $this->calistirici()->calistir('ulke-durum-degistir', ['kod' => 'DE', 'aktif' => false]);

        $this->assertSame(KahyaEylemKaydi::DURUM_BEKLEMEDE, $kayit->durum);
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => true]);

        $this->calistirici()->onayla($kayit);

        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => false]);

        // Geri alma önceki durumu aynen geri getirir.
        $this->calistirici()->geriAl($kayit->refresh());

        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => true]);
    }

    public function test_reddedilen_eylem_hicbir_sey_degistirmez(): void
    {
        $kayit = $this->calistirici()->calistir('ulke-durum-degistir', ['kod' => 'DE', 'aktif' => false]);

        $this->calistirici()->reddet($kayit);

        $this->assertSame(KahyaEylemKaydi::DURUM_REDDEDILDI, $kayit->refresh()->durum);
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => true]);

        // Reddedilmiş eylem sonradan da uygulanamaz.
        $this->expectException(RuntimeException::class);
        $this->calistirici()->onayla($kayit->refresh());
    }

    // ------------------------------------------------------- Ayar doldurma

    public function test_izinli_ayar_doldurulur_ve_geri_alinir(): void
    {
        Settings::setMany(['seo.default_title' => 'Eski başlık']);

        $kayit = $this->calistirici()->calistir('ayar-doldur', [
            'anahtar' => 'seo.default_title',
            'deger' => 'Yeni başlık',
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $this->assertSame('Yeni başlık', Settings::get('seo.default_title'));

        $this->calistirici()->geriAl($kayit->refresh());

        $this->assertSame('Eski başlık', Settings::get('seo.default_title'));
    }

    /**
     * SIR KORUMASI: `site_settings` içinde SMTP parolası ve API anahtarları
     * duruyor. Bir yapay zekânın onlara YAZABİLMESİ, ilk yanlış anlaşılan
     * cümlede kimlik bilgisi değiştirmesi demektir. Allow-list dışı her
     * anahtar reddedilir — sır olanlar da, tanınmayanlar da.
     */
    public function test_sir_anahtarina_yazilamaz(): void
    {
        foreach (['mail.password', 'ai.api_anahtari', 'growth.google_places_api_key', 'mail.host'] as $anahtar) {
            $kayit = $this->calistirici()->calistir('ayar-doldur', [
                'anahtar' => $anahtar,
                'deger' => 'ele-gecirilmis-deger',
            ]);

            $this->assertSame(
                KahyaEylemKaydi::DURUM_HATA,
                $kayit->durum,
                "{$anahtar} anahtarına yazılabildi — sır koruması delik.",
            );
        }

        // Değerlerin hiçbirine dokunulmamış olmalı.
        $this->assertNotSame('ele-gecirilmis-deger', Settings::get('mail.password'));
    }

    // ------------------------------------------------------ Sıra değiştirme

    /**
     * Bu test bir hatanın mezar taşı: ülke ve para birimi tablolarının
     * birincil anahtarı `code`'dur ve `id` kolonları YOKTUR. İlk yazımda iz
     * `$kayit->id` saklıyordu — o değer sessizce null'du ve geri alma hedefini
     * hiç bulamıyordu. Üç tür de burada geri almasına kadar sınanıyor.
     */
    public function test_sira_degistirme_uc_turde_de_geri_alinir(): void
    {
        $kategoriId = (string) Category::query()->value('id');

        $turler = [
            ['tur' => 'ulke', 'kimlik' => 'DE', 'model' => Country::class, 'anahtar' => ['code', 'DE']],
            ['tur' => 'para_birimi', 'kimlik' => 'EUR', 'model' => Currency::class, 'anahtar' => ['code', 'EUR']],
            ['tur' => 'kategori', 'kimlik' => $kategoriId, 'model' => Category::class, 'anahtar' => ['id', (int) $kategoriId]],
        ];

        foreach ($turler as $t) {
            $eski = (int) $t['model']::query()->where($t['anahtar'][0], $t['anahtar'][1])->value('sort_order');

            $kayit = $this->calistirici()->calistir('sira-degistir', [
                'tur' => $t['tur'], 'kimlik' => $t['kimlik'], 'sira' => 77,
            ]);

            $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum, "{$t['tur']} sırası değiştirilemedi: {$kayit->hata}");
            $this->assertSame(77, (int) $t['model']::query()->where($t['anahtar'][0], $t['anahtar'][1])->value('sort_order'));

            $this->calistirici()->geriAl($kayit->refresh());

            $this->assertSame(
                $eski,
                (int) $t['model']::query()->where($t['anahtar'][0], $t['anahtar'][1])->value('sort_order'),
                "{$t['tur']} geri alınamadı — iz kaydı hedefini bulamıyor olabilir.",
            );
        }
    }

    // ----------------------------------------------------- Defter bütünlüğü

    public function test_uygulanan_eylemin_izi_tam(): void
    {
        $admin = $this->admin();

        $kayit = $this->calistirici()->calistir('etiket-ekle', ['ad' => 'Deneme Etiketi'], $admin);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $this->assertNotNull($kayit->uygulandi_at);
        $this->assertNotNull($kayit->sonuc);
        $this->assertNotEmpty($kayit->geri_alma, 'Geri alma izi yazılmamış — "geri al" düğmesi yalan söylerdi.');
        $this->assertSame($admin->id, $kayit->user_id);
        $this->assertNotSame('', $kayit->onizleme);
    }

    public function test_geri_alinmis_eylem_tekrar_geri_alinamaz(): void
    {
        $kayit = $this->calistirici()->calistir('etiket-ekle', ['ad' => 'Tek Seferlik']);
        $this->calistirici()->geriAl($kayit->refresh());

        $this->expectException(RuntimeException::class);
        $this->calistirici()->geriAl($kayit->refresh());
    }
}

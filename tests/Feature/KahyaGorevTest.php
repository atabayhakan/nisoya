<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BekleyenHamle;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaGorevi;
use App\Models\User;
use App\Notifications\GunlukKahyaRaporu;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\KahyaTeshisi;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Görev defteri + hamle kartları (F2 — tasarım §2.2/2.3).
 *
 * Sınanan sözleşme: görev açma/güncelleme/geri-alma döngüleri, hamle
 * kartının onay akışı (karar bir kez verilir; karar sonrası geri çekilemez),
 * yönerge/rapor entegrasyonu ve ilerleme izinin denetim dürüstlüğü.
 */
class KahyaGorevTest extends TestCase
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

    // ------------------------------------------------------------- gorev-ac

    public function test_gorev_ac_adim_planini_ayristirir(): void
    {
        $kayit = $this->calistirici()->calistir('gorev-ac', [
            'baslik' => 'Gerçek kullanıcı bulma misyonu',
            'hedef' => 'Pazaryerinde sahibin dışında 10 gerçek satıcı olacak.',
            'adimlar' => "1. Hedef toplulukları listele\n2) TUSU'ya taslak hazırla\n- Sonuçları ölç",
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);

        $gorev = KahyaGorevi::query()->firstOrFail();
        // Numara/madde imleri temizlenir, sıra korunur, hepsi "bekliyor" doğar.
        $this->assertSame(
            ['Hedef toplulukları listele', "TUSU'ya taslak hazırla", 'Sonuçları ölç'],
            array_column($gorev->adimlar, 'metin'),
        );
        $this->assertSame(['bekliyor'], array_values(array_unique(array_column($gorev->adimlar, 'durum'))));
    }

    public function test_gorev_ac_geri_alma_temiz_gorevi_siler_izli_gorevi_iptal_eder(): void
    {
        $temiz = $this->calistirici()->calistir('gorev-ac', [
            'baslik' => 'Hemen vazgeçilen görev',
            'hedef' => 'Bu görev iz bırakmadan silinebilmeli.',
        ]);

        $this->calistirici()->geriAl($temiz);
        $this->assertSame(0, KahyaGorevi::query()->count());

        // İz birikmiş görev: silinmez, iptale çekilir.
        $izli = $this->calistirici()->calistir('gorev-ac', [
            'baslik' => 'İlerleme kaydedilen görev',
            'hedef' => 'Bu görevin izi denetim için korunmalı.',
        ]);
        $gorev = KahyaGorevi::query()->latest('id')->firstOrFail();
        $this->calistirici()->calistir('gorev-guncelle', ['id' => $gorev->id, 'not' => 'İlk adım için araştırma yapıldı.']);

        $this->calistirici()->geriAl($izli);

        $this->assertSame(KahyaGorevi::DURUM_IPTAL, $gorev->refresh()->durum);
    }

    // ------------------------------------------------------- gorev-guncelle

    public function test_gorev_guncelle_adim_not_ve_durumu_tek_cagrida_isler(): void
    {
        $gorev = KahyaGorevi::create([
            'baslik' => 'Deneme görevi',
            'hedef' => 'Güncelleme sözleşmesini sınamak.',
            'adimlar' => [['metin' => 'İlk adım', 'durum' => 'bekliyor'], ['metin' => 'İkinci adım', 'durum' => 'bekliyor']],
        ]);

        $kayit = $this->calistirici()->calistir('gorev-guncelle', [
            'id' => $gorev->id,
            'adim_no' => 1,
            'adim_durum' => 'yapildi',
            'not' => 'İlk adım bitti, ikinciye geçiliyor.',
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $gorev->refresh();
        $this->assertSame('yapildi', $gorev->adimlar[0]['durum']);
        $this->assertCount(1, $gorev->ilerleme_notlari);
        $this->assertSame('İkinci adım', $gorev->siradakiAdim());

        // Geri alma: adım, not ve durum güncellemeden önceki hâline döner.
        $this->calistirici()->geriAl($kayit);
        $gorev->refresh();
        $this->assertSame('bekliyor', $gorev->adimlar[0]['durum']);
        $this->assertSame([], $gorev->ilerleme_notlari ?? []);
    }

    public function test_gorev_guncelle_olmayan_adim_reddedilir(): void
    {
        $gorev = KahyaGorevi::create([
            'baslik' => 'Tek adımlı görev',
            'hedef' => 'Adım sınırı sözleşmesi.',
            'adimlar' => [['metin' => 'Tek adım', 'durum' => 'bekliyor']],
        ]);

        $kayit = $this->calistirici()->calistir('gorev-guncelle', [
            'id' => $gorev->id,
            'adim_no' => 5,
            'adim_durum' => 'yapildi',
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_HATA, $kayit->durum);
    }

    // ---------------------------------------------------------- hamle-oner

    public function test_hamle_oner_kart_acar_karar_akisi_calisir(): void
    {
        $kayit = $this->calistirici()->calistir('hamle-oner', [
            'baslik' => "TUSU'ya tanıtım mesajı",
            'gerekce' => 'Eylül dönem başında binlerce yeni öğrenci geliyor.',
            'icerik' => 'Merhaba, Nisoya yurtdışındaki Türkler için ücretsiz bir pazaryeri...',
            'tur' => 'eposta',
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);

        $hamle = BekleyenHamle::query()->firstOrFail();
        $this->assertSame(BekleyenHamle::DURUM_BEKLEMEDE, $hamle->durum);

        $hamle->kararVer(BekleyenHamle::DURUM_ONAYLANDI, 'Güzel taslak, gönderiyorum.');

        $this->assertSame(BekleyenHamle::DURUM_ONAYLANDI, $hamle->refresh()->durum);
        $this->assertNotNull($hamle->karar_at);

        // Karar BİR KEZ verilir.
        $this->expectException(\RuntimeException::class);
        $hamle->kararVer(BekleyenHamle::DURUM_REDDEDILDI);
    }

    public function test_hamle_geri_cekme_yalniz_karar_verilmemisken(): void
    {
        $kayit = $this->calistirici()->calistir('hamle-oner', [
            'baslik' => 'Geri çekilecek öneri',
            'gerekce' => 'Sözleşme testi için açılan kart.',
            'icerik' => 'Bu içerik kartla birlikte silinip gidecek.',
        ]);

        $this->calistirici()->geriAl($kayit);
        $this->assertSame(0, BekleyenHamle::query()->count());

        // Karar verilmiş kart geri çekilemez — sahibin kararı silinemez.
        $kayit2 = $this->calistirici()->calistir('hamle-oner', [
            'baslik' => 'Kararı verilen öneri',
            'gerekce' => 'Bu kartın kararı korunmalı.',
            'icerik' => 'Sahip bu kartı okuyup reddedecek; kart kalmalı.',
        ]);
        BekleyenHamle::query()->firstOrFail()->kararVer(BekleyenHamle::DURUM_REDDEDILDI, 'Şimdi değil.');

        $sonuc = $this->calistirici()->geriAl($kayit2);

        $this->assertSame(1, BekleyenHamle::query()->count());
        $this->assertStringContainsString('Geri alınamadı', (string) $sonuc->sonuc);
    }

    // ------------------------------------------------- Yönerge + rapor

    public function test_teshis_gorev_durumunu_toplar_ve_rapor_tasir(): void
    {
        KahyaGorevi::create([
            'baslik' => 'Rapor testi görevi',
            'hedef' => 'Günlük raporda görünmek.',
            'adimlar' => [['metin' => 'Adım bir', 'durum' => 'yapildi'], ['metin' => 'Adım iki', 'durum' => 'bekliyor']],
            'son_islem_at' => now()->subDays(5),
        ]);
        BekleyenHamle::create([
            'baslik' => 'Bekleyen kart',
            'gerekce' => 'Rapor sayacı testi.',
            'icerik' => 'İçerik.',
        ]);

        $veri = app(KahyaTeshisi::class)->gorevDurumu();
        $this->assertCount(1, $veri['acik']);
        $this->assertSame('Adım iki', $veri['acik'][0]['siradaki']);
        $this->assertSame(1, $veri['bekleyen_hamle']);

        // Rapor e-postası: görev satırı + hareketsizlik uyarısı + hamle sayısı.
        $mail = (new GunlukKahyaRaporu(app(KahyaTeshisi::class)->topla(medyaLimit: 1, logSaat: 1), ['yeni_uye' => 0, 'yeni_ilan' => 0]))
            ->toMail(new \stdClass);
        $metin = implode("\n", array_map('strval', $mail->introLines));

        $this->assertStringContainsString('Görevlerde durum', $metin);
        $this->assertStringContainsString('Rapor testi görevi', $metin);
        $this->assertStringContainsString('gündür hareketsiz', $metin);
        $this->assertStringContainsString('hamle kartı kararını bekliyor', $metin);
    }

    /** F2 öncesi defter kayıtları `gorevler` anahtarı taşımaz — rapor çökmemeli. */
    public function test_rapor_gorevler_anahtari_olmayan_eski_veriyle_cokmez(): void
    {
        $eskiTeshis = app(KahyaTeshisi::class)->topla(medyaLimit: 1, logSaat: 1);
        unset($eskiTeshis['gorevler']);

        $mail = (new GunlukKahyaRaporu($eskiTeshis, ['yeni_uye' => 0, 'yeni_ilan' => 0]))->toMail(new \stdClass);

        $this->assertStringNotContainsString('Görevlerde durum', implode("\n", array_map('strval', $mail->introLines)));
    }

    // -------------------------------------------------------------- Arayüz

    public function test_ekranlar_yalniz_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $moderator = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get('/yonetim/kahya-gorevleri')->assertOk();
        $this->actingAs($admin)->get('/yonetim/bekleyen-hamleler')->assertOk();
        $this->actingAs($moderator)->get('/yonetim/kahya-gorevleri')->assertForbidden();
        $this->actingAs($moderator)->get('/yonetim/bekleyen-hamleler')->assertForbidden();
    }
}

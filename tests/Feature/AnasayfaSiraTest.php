<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\AnasayfaBolumleri;
use App\Models\User;
use App\Support\HomeSections;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Anasayfa bölüm SIRASI (Faz 2 · G5, 2026-07-28).
 *
 * Bu iş bir refactor'du: 13 bölüm iki temada partial'lara çıkarıldı. Testlerin
 * ilk görevi, o refactor'ün sayfayı BOZMADIĞINI mühürlemek — her bölüm hâlâ
 * render oluyor, görünürlük kapıları hâlâ çalışıyor, sıra değişince gerçekten
 * yer değiştiriyor.
 */
class AnasayfaSiraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    // ------------------------------------------------ Sıra mantığı

    public function test_ayar_yokken_temanin_varsayilan_sirasi_kullanilir(): void
    {
        $this->assertSame(HomeSections::VARSAYILAN_SIRA['klasik'], HomeSections::sirali('klasik'));
        $this->assertSame(HomeSections::VARSAYILAN_SIRA['vitrin'], HomeSections::sirali('vitrin'));
    }

    public function test_iki_temanin_varsayilan_sirasi_farklidir(): void
    {
        // Bu yüzden sıra tema başına ayrı tutuluyor; ortak tek ayar birini bozardı.
        $this->assertNotSame(
            HomeSections::VARSAYILAN_SIRA['klasik'],
            HomeSections::VARSAYILAN_SIRA['vitrin']
        );
    }

    public function test_kaydedilen_sira_okunur(): void
    {
        HomeSections::siraKaydet('klasik', ['cta', 'kategoriler']);

        $sira = HomeSections::sirali('klasik');
        $this->assertSame('cta', $sira[0]);
        $this->assertSame('kategoriler', $sira[1]);
    }

    public function test_eksik_bozuk_veya_yinelenen_sira_bolum_kaybettirmez(): void
    {
        // Sayfa ASLA bölüm kaybetmemeli: bilinmeyen atılır, yinelenen tekilleşir,
        // eksik olanlar varsayılan sırasıyla sona eklenir.
        Settings::setMany(['home.sira.klasik' => 'cta,uydurma,cta,ulkeler']);

        $sira = HomeSections::sirali('klasik');

        $this->assertSame(['cta', 'ulkeler'], array_slice($sira, 0, 2));
        $this->assertNotContains('uydurma', $sira);
        $this->assertSame(
            count(HomeSections::VARSAYILAN_SIRA['klasik']),
            count($sira),
            'Bozuk ayar bölüm kaybettirdi'
        );
        foreach (HomeSections::VARSAYILAN_SIRA['klasik'] as $anahtar) {
            $this->assertContains($anahtar, $sira);
        }
    }

    public function test_vitrine_ait_olmayan_bolum_vitrin_sirasina_giremez(): void
    {
        // canli_akis Vitrin'de yok (hero kendi akışını taşır).
        HomeSections::siraKaydet('vitrin', ['canli_akis', 'cta']);

        $this->assertNotContains('canli_akis', HomeSections::sirali('vitrin'));
    }

    // ------------------------------------------------ Refactor mührü

    public function test_klasik_anasayfa_tum_bolumleri_render_eder(): void
    {
        Settings::setMany(['gorunum.tema' => 'klasik']);

        $yanit = $this->get('/')->assertOk();

        foreach (['Kategoriler', 'Nasıl çalışır'] as $metin) {
            $yanit->assertSee($metin, false);
        }
    }

    public function test_vitrin_anasayfa_tum_bolumleri_render_eder(): void
    {
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->get('/')->assertOk()->assertSee('data-tema="vitrin"', false);
    }

    public function test_gizlenen_bolum_sirada_kalsa_da_render_edilmez(): void
    {
        Settings::setMany(['gorunum.tema' => 'klasik', 'home.section.nasil_calisir' => '0']);

        $this->get('/')->assertOk()->assertDontSee('Nasıl çalışır', false);
    }

    public function test_sira_degisince_bolumler_gercekten_yer_degistirir(): void
    {
        Settings::setMany(['gorunum.tema' => 'klasik']);
        $once = $this->get('/')->getContent();
        $varsayilanKonum = strpos($once, 'Nasıl çalışır');

        HomeSections::siraKaydet('klasik', ['nasil_calisir', 'canli_akis', 'deger_onerileri', 'kategoriler', 'ulkeler', 'yeni_ilanlar', 'cta']);

        $sonra = $this->get('/')->getContent();
        $yeniKonum = strpos($sonra, 'Nasıl çalışır');

        $this->assertNotFalse($varsayilanKonum);
        $this->assertNotFalse($yeniKonum);
        $this->assertLessThan($varsayilanKonum, $yeniKonum, 'Bölüm başa alındığı hâlde sayfada yukarı çıkmadı');
    }

    public function test_her_bolumun_partiali_iki_temada_da_mevcuttur(): void
    {
        // Eksik partial = sayfa 500. Bekçi: sıradaki her anahtarın dosyası var mı.
        $kokler = [
            'klasik' => resource_path('views/partials/home/'),
            'vitrin' => resource_path('views/vitrin/partials/home/'),
        ];

        foreach ($kokler as $tema => $kok) {
            foreach (HomeSections::VARSAYILAN_SIRA[$tema] as $anahtar) {
                $this->assertTrue(
                    File::exists($kok.$anahtar.'.blade.php'),
                    "{$tema} temasında '{$anahtar}' bölümünün partial'ı yok — sayfa 500 verir."
                );
            }

            foreach (array_keys(HomeSections::CAPALAR[$tema]) as $capa) {
                $dosya = $capa === 'zone_orta' ? 'capa-zone-orta' : 'capa-'.$capa;
                $this->assertTrue(
                    File::exists($kok.$dosya.'.blade.php'),
                    "{$tema} temasında '{$capa}' çapasının partial'ı yok."
                );
            }
        }
    }

    // ------------------------------------------------ Panel

    public function test_panel_sirayi_tasir_ve_kaydeder(): void
    {
        $admin = $this->admin();
        Settings::setMany(['gorunum.tema' => 'klasik']);

        Livewire::actingAs($admin)
            ->test(AnasayfaBolumleri::class)
            ->assertSet('siraTema', 'klasik')
            ->call('asagi', 0)
            ->call('siraKaydet');

        $sira = HomeSections::sirali('klasik');
        $this->assertSame('deger_onerileri', $sira[0], 'İlk bölüm aşağı alınınca ikinci sıradaki başa geçmeli');
        $this->assertSame('canli_akis', $sira[1]);
    }

    public function test_panel_sinirlarda_tasimayi_yok_sayar(): void
    {
        $admin = $this->admin();

        $bilesen = Livewire::actingAs($admin)->test(AnasayfaBolumleri::class);
        $ilk = $bilesen->get('sira');

        $bilesen->call('yukari', 0)->call('asagi', count($ilk) - 1);

        $this->assertSame($ilk, $bilesen->get('sira'), 'Sınır dışına taşıma listeyi değiştirmemeli');
    }

    public function test_panel_tema_degistirince_o_temanin_sirasini_gosterir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(AnasayfaBolumleri::class)
            ->call('temaSec', 'vitrin')
            ->assertSet('siraTema', 'vitrin')
            ->assertSet('sira', HomeSections::VARSAYILAN_SIRA['vitrin']);
    }

    public function test_varsayilana_donme_calisir(): void
    {
        HomeSections::siraKaydet('klasik', ['cta']);

        Livewire::actingAs($this->admin())
            ->test(AnasayfaBolumleri::class)
            ->call('siraVarsayilana')
            ->call('siraKaydet');

        $this->assertSame(HomeSections::VARSAYILAN_SIRA['klasik'], HomeSections::sirali('klasik'));
    }

    public function test_uye_sayfaya_erisemez(): void
    {
        $uye = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($uye)->get(AnasayfaBolumleri::getUrl())->assertRedirect(route('dashboard'));
    }
}

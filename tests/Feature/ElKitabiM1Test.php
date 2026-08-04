<?php

namespace Tests\Feature;

use App\Ai\Kahya\EylemToplayici;
use App\Ai\Kahya\KahyaAjani;
use App\Ai\Kahya\YonlendirmeToplayici;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Filament\Pages\GenelBakis;
use App\Models\KahyaMesaji;
use App\Models\Listing;
use App\Models\User;
use App\Reports\NisoyaDosyasi;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Eylem\EylemKatalogu;
use App\Services\Kahya\KahyaTeshisi;
use App\Services\Kahya\PanelHaritasi;
use App\Services\Rehber\ElKitabiRehberi;
use App\Services\Rehber\RehberBoslukAvcisi;
use App\Services\Rehber\RehberSayfasi;
use App\Services\Rehber\SurecSeridi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El Kitabı M1 — süreç şeridi · genel bakış belgesi · rehber boşluk avcısı.
 *
 * En kritik bekçi `seritte_her_ilan_durumu_var`: şeridin adımları elle
 * yazılmıyor ama akış (ana hat / yan dal) bir ÜRÜN kararı olduğu için kodda
 * duruyor. Enum'a yeni bir durum eklenip şeride eklenmezse diyagram sessizce
 * ESKİR — kırılmaz, yalan söyler. Bu test o sessizliği bozar.
 */
class ElKitabiM1Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    // ---------------------------------------------------------------- şerit

    public function test_seritte_her_ilan_durumu_var(): void
    {
        $kapsanan = app(SurecSeridi::class)->kapsananDurumlar();

        foreach (ListingStatus::cases() as $durum) {
            $this->assertContains(
                $durum,
                $kapsanan,
                "ListingStatus::{$durum->name} süreç şeridinde yok. Yeni bir durum eklendiyse ".
                'SurecSeridi::AKIS dizisine de eklenmeli, yoksa diyagram sessizce eskir.'
            );
        }
    }

    public function test_serit_sayilari_canli_ve_demo_haric(): void
    {
        Listing::factory()->count(3)->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => false,
        ]);
        Listing::factory()->count(5)->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => true,
        ]);

        $aktif = collect(app(SurecSeridi::class)->adimlar())->firstWhere('anahtar', 'aktif');

        // 5 demo ilan sayıya girseydi şerit dolu bir pazaryeri iddia ederdi.
        $this->assertSame(3, $aktif['adet']);
    }

    public function test_rehber_sayfasinda_serit_basiliyor(): void
    {
        $sayfa = app(ElKitabiRehberi::class)->bul('ilan-yasam-dongusu');

        $this->assertNotNull($sayfa);

        $html = $sayfa->html();

        // Yer tutucu ÇÖZÜLMÜŞ olmalı: ham `{{surec:...}}` metni görünürse
        // kullanıcı diyagram yerine kod parçası görür.
        $this->assertStringNotContainsString('{{surec:', $html);
        $this->assertStringContainsString('Yeniden oynat', $html);
        $this->assertStringContainsString('wire:ignore', $html);
    }

    public function test_bilinmeyen_serit_adi_ham_metin_birakmaz(): void
    {
        $sayfa = app(ElKitabiRehberi::class)->bul('ilan-yasam-dongusu');
        $sahte = new RehberSayfasi(
            slug: 'deneme', baslik: 'Deneme', ozet: 'x',
            govde: "Metin\n\n{{surec:olmayan-akis}}\n\nDevam", sira: 1, ekran: null, etiketler: [],
        );

        $this->assertNotNull($sayfa);
        $this->assertStringNotContainsString('{{surec:', $sahte->html());
    }

    // -------------------------------------------------------------- belge

    public function test_genel_bakis_belgesi_acilir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(GenelBakis::class)
            ->assertOk()
            ->assertSee('Bugünkü gerçek envanter');
    }

    public function test_belge_demo_ilanlari_saymaz(): void
    {
        Listing::factory()->count(2)->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => false,
        ]);
        Listing::factory()->count(9)->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => true,
        ]);

        // Tek doğrulanan şişik rakam tüm belgeyi çöpe atar.
        $this->assertSame(2, app(NisoyaDosyasi::class)->envanter()['ilan']);
    }

    public function test_envanter_zayifken_darbogaz_acikca_yazilir(): void
    {
        Listing::factory()->count(3)->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => false,
        ]);

        // Gizlemek yerine yazmak, okuyanın ilk soracağı soruyu öne almaktır.
        Livewire::actingAs($this->admin())
            ->test(GenelBakis::class)
            ->assertSee('Darboğaz açıkça burada');
    }

    // ------------------------------------------------------- boşluk avcısı

    public function test_bosluk_avcisi_kaynaksiz_cevabi_yakalar(): void
    {
        $sahip = $this->admin();

        KahyaMesaji::create(['user_id' => $sahip->id, 'rol' => KahyaMesaji::ROL_SAHIP, 'metin' => 'Fatura nasıl kesilir?']);
        KahyaMesaji::create(['user_id' => $sahip->id, 'rol' => KahyaMesaji::ROL_KAHYA, 'metin' => 'Maalesef rehberde bu konu yok.']);

        $bosluklar = app(RehberBoslukAvcisi::class)->bosluklar();

        $this->assertCount(1, $bosluklar);
        $this->assertStringContainsString('Fatura', $bosluklar->first()['soru']);
    }

    public function test_cevaplanan_soru_bosluk_sayilmaz(): void
    {
        $sahip = $this->admin();

        KahyaMesaji::create(['user_id' => $sahip->id, 'rol' => KahyaMesaji::ROL_SAHIP, 'metin' => 'Yedek nasıl alınır?']);
        KahyaMesaji::create(['user_id' => $sahip->id, 'rol' => KahyaMesaji::ROL_KAHYA, 'metin' => 'Yedekleme sayfasındaki düğmeyle.']);

        $this->assertCount(0, app(RehberBoslukAvcisi::class)->bosluklar());
    }

    public function test_yonerge_ile_avci_ayni_ifadeyi_kullanir(): void
    {
        // İkisi kayarsa avcı sessizce körelir ve rehber boşlukları hiç
        // görünmez. Bu testin tek işi o kaymayı engellemek.
        $ajan = new KahyaAjani(
            app(KahyaTeshisi::class),
            app(BekleyenIsler::class),
            app(PanelHaritasi::class),
            app(EylemKatalogu::class),
            app(EylemCalistirici::class),
            new EylemToplayici,
            new YonlendirmeToplayici,
            collect(),
            $this->admin(),
        );

        $this->assertStringContainsString(RehberBoslukAvcisi::ISARET, (string) $ajan->instructions());
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ElKitabi;
use App\Filament\Pages\KurtarmaKiti;
use App\Filament\Pages\MailAyarlari;
use App\Filament\Pages\Yedekleme;
use App\Models\User;
use App\Services\Rehber\ElKitabiRehberi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El Kitabı — markdown omurgası (M0).
 *
 * En kritik bekçi `ekran_bagi_kopmaz`: rehber sayfalarının `ekran:` alanı
 * Filament sınıf ADINI taşıyor. Sınıf yeniden adlandırılır ya da taşınırsa
 * "Yardım" düğmesi SESSİZCE kaybolur — hata vermez, kimse fark etmez.
 * Bu test o sessiz kopmayı sesli hâle getiriyor.
 */
class ElKitabiRehberiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function rehber(): ElKitabiRehberi
    {
        return app(ElKitabiRehberi::class);
    }

    public function test_rehber_sayfalari_okunuyor(): void
    {
        $sayfalar = $this->rehber()->tumSayfalar();

        $this->assertGreaterThanOrEqual(8, $sayfalar->count(), 'M0 en az 8 sayfa öngörüyordu.');
        $this->assertNotEmpty($sayfalar->first()->baslik);
        $this->assertNotEmpty($sayfalar->first()->govde);
    }

    public function test_her_sayfanin_basligi_ve_ozeti_var(): void
    {
        // Özet üç yerde kullanılıyor: arama sonucu, slide-over açıklaması ve
        // Kâhya'nın yönerge dizini. Boş özet üçünü birden köreltir.
        foreach ($this->rehber()->tumSayfalar() as $sayfa) {
            $this->assertNotSame('', trim($sayfa->baslik), "{$sayfa->slug}: başlık boş");
            $this->assertNotSame('', trim($sayfa->ozet), "{$sayfa->slug}: özet boş");
        }
    }

    public function test_ekran_bagi_kopmaz(): void
    {
        foreach ($this->rehber()->tumSayfalar() as $sayfa) {
            if ($sayfa->ekran === null) {
                continue;
            }

            $this->assertTrue(
                class_exists($sayfa->ekran),
                "{$sayfa->slug}: `ekran` alanı var olmayan bir sınıfa işaret ediyor ({$sayfa->ekran}). ".
                'Sınıf taşındıysa markdown front-matter da güncellenmeli, yoksa Yardım düğmesi sessizce kaybolur.'
            );
        }
    }

    public function test_kritik_ekranlarin_rehber_sayfasi_var(): void
    {
        // M0 kapsamı: üç kritik ekran. Biri kapsam dışına düşerse burada görülür.
        foreach ([Yedekleme::class, MailAyarlari::class, KurtarmaKiti::class] as $sinif) {
            $this->assertNotNull(
                $this->rehber()->ekranIcin($sinif),
                "{$sinif} için rehber sayfası yok — Yardım düğmesi görünmez."
            );
        }
    }

    public function test_arama_baslikta_gecen_sayfayi_one_alir(): void
    {
        $sonuc = $this->rehber()->ara('yedek');

        $this->assertNotEmpty($sonuc);
        $this->assertStringContainsStringIgnoringCase('yedek', $sonuc->first()->baslik);
    }

    public function test_arama_sonucsuz_kalabilir(): void
    {
        $this->assertCount(0, $this->rehber()->ara('zzzbulunmayanterim'));
    }

    public function test_yonerge_dizini_govde_icermez(): void
    {
        $dizin = $this->rehber()->yonergeDizini();
        $ilk = $this->rehber()->tumSayfalar()->first();

        $this->assertStringContainsString($ilk->slug, $dizin);
        $this->assertStringContainsString($ilk->baslik, $dizin);

        // Gövdeler yönergeye girerse bağlam penceresi şişer ve panel haritası,
        // hafıza, görev defteri dışarı itilir. Dizin yalnız başlık+özet.
        $this->assertStringNotContainsString($ilk->govde, $dizin);
    }

    public function test_el_kitabi_ekrani_acilir_ve_arama_suzer(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ElKitabi::class)
            ->assertOk()
            ->set('arama', 'yedek')
            ->assertSee('Yedekleme');
    }

    public function test_kritik_ekranda_yardim_dugmesi_var(): void
    {
        // M0'ın asıl çıktısı bu: kullanıcı işini bırakıp El Kitabı'na gitmeden,
        // bulunduğu ekranda yardımı açabiliyor.
        Livewire::actingAs($this->admin())
            ->test(Yedekleme::class)
            ->assertOk()
            ->assertActionExists('rehberYardim');
    }

    public function test_rehber_sayfasi_olmayan_ekranda_yardim_dugmesi_yok(): void
    {
        // Boş bir yardım penceresi, yardım olmamasından kötüdür: sayfa yoksa
        // düğme hiç görünmemeli. Bu davranış RehberYardimi'de kurulu.
        $rehber = $this->rehber();

        $this->assertNull(
            $rehber->ekranIcin(ElKitabi::class),
            'El Kitabı ekranının kendi rehber sayfası yok; bu test o varsayıma dayanıyor.'
        );
    }

    public function test_misafir_el_kitabini_goremez(): void
    {
        $this->get(ElKitabi::getUrl())->assertRedirect();
    }
}

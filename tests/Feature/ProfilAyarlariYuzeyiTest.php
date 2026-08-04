<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Profil ayarları ekranının yüzeyi.
 *
 * Bildirilen hata: "QR resmi yüklüyorum, Profili Kaydet'e basıyorum,
 * kaydedilmiyor." Kök neden kod değil ARAYÜZ: QR ayrı bir forma ait ve o form
 * yalnız kendi düğmesiyle gönderiliyor; profil düğmesi onu sessizce yok
 * sayıyordu. Buradaki bekçiler o karışıklığın geri gelmesini engelliyor.
 */
class ProfilAyarlariYuzeyiTest extends TestCase
{
    use RefreshDatabase;

    private function ekran(): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get(route('panel.profile.edit'));
    }

    public function test_kaydet_dugmeleri_kapsamini_soyluyor(): void
    {
        $yanit = $this->ekran();

        // "Profili Kaydet" sayfanın tamamını kaydediyormuş gibi okunuyordu.
        $yanit->assertSee('Profil bilgilerini kaydet');
        $yanit->assertSee('Ödeme yöntemini kaydet');
        $yanit->assertDontSee('>Profili Kaydet<', false);
    }

    public function test_alt_formlar_yanlis_dugme_korumasi_icin_isaretli(): void
    {
        // Koruma betiği bu işareti okuyor; işaret düşerse uyarı sessizce ölür
        // ve hata ilk hâline döner.
        $this->ekran()->assertSee('data-alt-form="Ödeme yöntemlerim"', false);
    }

    public function test_cikis_profil_ekranindan_erisilebilir(): void
    {
        // Mobilde çıkış hiçbir yerde yoktu (header'daki düğme hidden md:block).
        $this->ekran()->assertSee('Çıkış yap');
    }

    public function test_dosya_secici_turkce_bos_durum_gosterir(): void
    {
        // Yerel input[type=file] "No file chosen" basıyordu ve bu metin CSS ile
        // değiştirilemiyor — Türkçe panelde İngilizce iki etiket kalıyordu.
        $this->ekran()->assertSee('Henüz dosya seçilmedi');
    }

    public function test_misafir_profil_ekranini_goremez(): void
    {
        $this->get(route('panel.profile.edit'))->assertRedirect();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * 2FA kurulum ekranı (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * BU DOSYA GERÇEK BİR OLAYDAN DOĞDU
 *
 * Ekran "QR kod oluşturulacak" diyordu ama QR BASMIYORDU — yalnız otpauth
 * URL'ini ve gizli anahtarı düz metin gösteriyordu. Sahip ekranı paylaştı ve
 * anahtar yandı: anahtarı gören biri o hesap için geçerli 6 haneli kod
 * üretebilir, yani ikinci faktör hiç yokmuş gibi olur.
 *
 * Ayrıca QR yalnız flash session'da yaşıyordu: sayfa yenilenince kayboluyor,
 * ekran "Kur" düğmesine dönüyordu ve o düğme YENİ anahtar üretiyordu. Eski
 * anahtarı uygulamasına çoktan eklemiş kullanıcının kaydı sessizce ölüyordu.
 */
class IkiFaktorKurulumEkraniTest extends TestCase
{
    use RefreshDatabase;

    public function test_kurulum_gercek_bir_qr_kodu_basar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('panel.profile.2fa.setup'));

        $icerik = $this->actingAs($user)->get(route('panel.profile.2fa'))->assertOk()->getContent();

        $this->assertStringContainsString('<svg', $icerik, 'QR kodu basılmıyor — ekran yine yalnız metin gösteriyor.');
        // XML bildirimi HTML gövdesinin ortasında geçersizdir ve `<?` kısa
        // etiket yorumlamasına kapı bırakır.
        $this->assertStringNotContainsString('<?xml', $icerik);
    }

    public function test_gizli_anahtar_artik_ekranda_one_cikmaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('panel.profile.2fa.setup'));

        $icerik = $this->actingAs($user)->get(route('panel.profile.2fa'))->getContent();

        // Anahtar hâlâ sayfada (kamerası olmayan kullanıcı için) ama KATLI:
        // omuz üstünden bakan biri ve ekran görüntüsü için gereksiz risk.
        $this->assertStringContainsString('QR okutamıyorum', $icerik);
        $this->assertStringContainsString('görüntüsünü kimseyle paylaşma', $icerik);

        // Ham otpauth URL'i artık basılmıyor — anahtarı ikinci kez, hem de
        // kopyalanmaya hazır biçimde göstermenin bir faydası yoktu.
        $this->assertStringNotContainsString('otpauth://', $icerik);
    }

    public function test_sayfa_yenilenince_qr_kaybolmaz(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('panel.profile.2fa.setup'));

        $secret = $user->fresh()->two_factor_secret;

        // Flash tüketildikten SONRA ikinci kez yüklemek eski kodda "Kur"
        // düğmesine geri dönüyordu.
        $this->actingAs($user)->get(route('panel.profile.2fa'));
        $this->actingAs($user)->get(route('panel.profile.2fa'))
            ->assertOk()
            ->assertSee('QR kodu okut');

        // Ve anahtar kendiliğinden DEĞİŞMEMELİ — değişirse kullanıcının
        // uygulamasına eklediği kayıt ölür.
        $this->assertSame($secret, $user->fresh()->two_factor_secret);
    }

    public function test_yeni_qr_uretmek_anahtari_degistirir(): void
    {
        // Anahtarını paylaşan kullanıcının çıkış yolu. Bilinçli bir eylem
        // olması için ayrı bir düğme (ve onay) arkasında.
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('panel.profile.2fa.setup'));
        $ilk = $user->fresh()->two_factor_secret;

        $this->actingAs($user)->post(route('panel.profile.2fa.setup'));
        $ikinci = $user->fresh()->two_factor_secret;

        $this->assertNotSame($ilk, $ikinci);
    }

    public function test_kurtarma_kodlari_bir_daha_gosterilmeyecegini_soyler(): void
    {
        // Panel 2FA olmadan açılmadığı için bu kodlar kullanıcı tarafındaki
        // tek geri dönüş yolu. Eskiden "kaydet" diyordu ama kaydetmenin bir
        // yolunu sunmuyor ve tek seferlik olduğunu söylemiyordu.
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('panel.profile.2fa.setup'));

        $google2fa = app(Google2FA::class);
        $kod = $google2fa->getCurrentOtp($user->fresh()->two_factor_secret);

        $this->actingAs($user)
            ->post(route('panel.profile.2fa.confirm'), ['code' => $kod])
            ->assertRedirect(route('panel.profile.2fa'));

        $this->actingAs($user)->get(route('panel.profile.2fa'))
            ->assertSee('bir daha gösterilmeyecek')
            ->assertSee('Kodları kopyala');
    }
}

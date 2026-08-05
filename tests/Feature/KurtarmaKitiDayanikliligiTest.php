<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kurtarma Kiti ÇÖKMEMELİ — bu sayfa "cam kır" sayfasıdır.
 *
 * Canlıda /yonetim/kurtarma-kiti 500 döndü. Sayfayı HTTP üzerinden render
 * eden bir test zaten vardı ve geçiyordu; demek ki fark KODDA değil VERİDE.
 *
 * `account_recovery_codes` alanı `encrypted:array` cast'i taşıyor. Çözülemeyen
 * bir değer (APP_KEY değişmiş, elle yazılmış ya da bozulmuş satır) okuma
 * anında DecryptException fırlatır ve sayfayı komple düşürür.
 *
 * Bu, tüm sayfalar arasında en kötü yerde olan hata: kurtarma kiti tam da
 * işler bozulduğunda açılması gereken sayfa. Kilitlenmeye karşı güvence
 * olarak tasarlanmış bir ekranın, tek bozuk satır yüzünden erişilemez olması
 * güvencenin kendisini yok eder.
 */
class KurtarmaKitiDayanikliligiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cozulemeyen_kurtarma_kodu_sayfayi_dusurmez(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Cast'i atlayarak ham (şifresiz, dolayısıyla çözülemez) değer yaz —
        // APP_KEY değişmiş bir satırın davranışını taklit eder.
        DB::table('users')->where('id', $admin->id)
            ->update(['account_recovery_codes' => 'bu-cozulemez-bir-deger']);

        $this->actingAs($admin->fresh())
            ->get('/yonetim/kurtarma-kiti')
            ->assertOk()
            ->assertSee('Kurtarma Kiti')
            // Ve DÜRÜST olmalı: sessizce "0 kod" demek, sahibin "hiç
            // üretmemişim" sanmasına yol açar — oysa elindeki yazılı kodlar
            // artık çalışmıyor. Olmayan bir güvenceye güvenmek, güvencesiz
            // olduğunu bilmekten kötüdür.
            ->assertSee('kurtarma kodların okunamıyor', false);
    }

    public function test_kod_hic_uretilmemisse_uyari_cikmaz(): void
    {
        // Uyarı yalnız GERÇEK bozulmada çıkmalı. Her yönetici her açtığında
        // kırmızı bir kutu görürse uyarı anlamını yitirir.
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/yonetim/kurtarma-kiti')
            ->assertOk()
            ->assertDontSee('kurtarma kodların okunamıyor', false);
    }

    public function test_bozuk_kod_kurtarma_akisini_cokertmez(): void
    {
        // /hesap-kurtar akışı da aynı alanı okur. Sayfayı ayakta tutup
        // asıl kurtarma yolunun çökmesine izin vermek yarım iş olurdu.
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        DB::table('users')->where('id', $admin->id)
            ->update(['account_recovery_codes' => 'bu-cozulemez-bir-deger']);

        $this->assertFalse($admin->fresh()->consumeAccountRecoveryCode('HERHANGI-BIRKOD'));
    }

    public function test_cozulemeyen_iki_faktor_gizli_anahtari_sayfayi_dusurmez(): void
    {
        // Aynı risk two_factor_secret'ta da var ve daha da kritik: bozuksa
        // kullanıcı panele hiç giremez, çünkü zorunluluk middleware'i
        // hasTwoFactorEnabled() okur.
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        DB::table('users')->where('id', $admin->id)->update([
            'two_factor_secret' => 'bu-da-cozulemez',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin->fresh())
            ->get('/yonetim/kurtarma-kiti')
            ->assertOk();
    }
}

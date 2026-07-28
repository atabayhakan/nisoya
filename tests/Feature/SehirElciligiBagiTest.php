<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NabizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Davet sayfası ↔ Şehir Elçileri bağı (2026-07-29).
 *
 * BULUNAN KOPUKLUK: sitede iki ayrı tanınma yüzeyi vardı ve birbirlerinden
 * habersizdiler. `/panel/davet` davet sayısını gösteriyor ama bu sayının bir
 * şeye yaradığını söylemiyordu; `/nabiz` "arkadaşlarını davet et, şehrinin
 * elçisi ol" diyor ama davet sayfasına hiç bağlanmıyordu. Yani davet etmek
 * için sebep, tam davet edilecek ekranda görünmüyordu.
 *
 * Büyüme önerisi burada YENİ bir rozet katmanı istiyordu; ölçüm başka bir şey
 * söyledi: aylık davet sayısı sitede şu an 0. Sıfır davetin olduğu bir yerde
 * yeni rozet kimseye görünmez. Önce var olan katmanı görünür kılmak, yeni
 * katman eklemekten ucuz ve dürüst.
 */
class SehirElciligiBagiTest extends TestCase
{
    use RefreshDatabase;

    private function uye(?string $sehir = 'Berlin'): User
    {
        return User::factory()->create(['city' => $sehir, 'email_verified_at' => now()]);
    }

    private function davetEt(User $davetci, int $adet): void
    {
        User::factory()->count($adet)->create([
            'referred_by' => $davetci->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_sehri_olmayan_uye_yarista_degildir(): void
    {
        $durum = app(NabizService::class)->sehirElciligiDurumu($this->uye(null));

        $this->assertNull($durum['sehir']);
        $this->assertFalse($durum['elciMiyim']);
    }

    public function test_sehrinde_kimse_davet_etmemisse_lider_sifirdir(): void
    {
        $durum = app(NabizService::class)->sehirElciligiDurumu($this->uye());

        $this->assertSame('Berlin', $durum['sehir']);
        $this->assertSame(0, $durum['lider']);
        $this->assertSame(0, $durum['benimBuAy']);
        $this->assertFalse($durum['elciMiyim'], 'Sıfır davetle elçi olunmaz.');
    }

    public function test_en_cok_davet_eden_sehrinin_elcisidir(): void
    {
        $ben = $this->uye();
        $this->davetEt($ben, 3);

        $rakip = $this->uye();
        $this->davetEt($rakip, 1);

        $durum = app(NabizService::class)->sehirElciligiDurumu($ben);

        $this->assertSame(3, $durum['benimBuAy']);
        $this->assertTrue($durum['elciMiyim']);
    }

    public function test_geride_olan_uyeye_kac_davet_gerektigi_soylenir(): void
    {
        $ben = $this->uye();
        $this->davetEt($ben, 1);

        $lider = $this->uye();
        $this->davetEt($lider, 4);

        $durum = app(NabizService::class)->sehirElciligiDurumu($ben);

        $this->assertFalse($durum['elciMiyim']);
        $this->assertSame(4, $durum['lider']);
        // 4 - 1 = 3 fark, geçmek için 4 gerekir.
        $this->assertSame(4, $durum['fark']);
    }

    /**
     * Başka şehirdeki davetler bu şehrin eşiğini yükseltmemeli — elçilik
     * şehir başınadır.
     */
    public function test_baska_sehirdeki_davetler_esigi_etkilemez(): void
    {
        $ben = $this->uye('Berlin');

        $baskaSehir = $this->uye('Londra');
        $this->davetEt($baskaSehir, 9);

        $durum = app(NabizService::class)->sehirElciligiDurumu($ben);

        $this->assertSame(0, $durum['lider'], 'Londra\'daki davetler Berlin eşiğini yükseltmemeli.');
    }

    public function test_davet_sayfasi_nabiz_baglantisini_gosterir(): void
    {
        $this->actingAs($this->uye())
            ->get('/panel/davet')
            ->assertSuccessful()
            ->assertSee(route('nabiz'))
            ->assertSee('elçisi', false);
    }
}

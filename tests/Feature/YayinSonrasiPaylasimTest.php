<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Yayın sonrası paylaşım anı (2026-08-06).
 *
 * ---------------------------------------------------------------------------
 * NEDEN
 *
 * Pazaryerinin darboğazı tasarım değil ARZ: sitede 3 gerçek ilan var ve hepsi
 * sahibin. Bu tur o tarafa ayrıldı.
 *
 * Paylaşım kartı (WhatsApp durumu için 1080x1920) ve paylaş düğmeleri ilan
 * DETAY sayfasında zaten vardı — ama kullanıcı yayınladıktan sonra
 * İlanlarım'a düşüyor ve orada paylaşımdan söz eden hiçbir şey yoktu. Yani en
 * istekli an boşa gidiyordu: paylaşmak için ilanı bulup detayına gitmesi
 * gerekiyordu.
 *
 * Yeni ilan veren kişi, o ilanın en doğal dağıtım kanalı — kendi çevresi.
 *
 * ---------------------------------------------------------------------------
 * ARAŞTIRIP BULMADIĞIM (kopya yazmaktan kurtaran kontrol)
 *
 * "Yeni üyeye ilan ver dürtmesi" eklemeyi planlamıştım; panoda ZATEN VAR
 * (panel/partials/baslangic.blade.php — "Nereden başlamak istersin?" iki eşit
 * kapıyla). Varsaymak yerine bakınca gereksiz bir kopya yazmaktan kurtuldum.
 */
class YayinSonrasiPaylasimTest extends TestCase
{
    use RefreshDatabase;

    public function test_yayindan_sonra_paylasim_bloku_gosterilir(): void
    {
        $uye = User::factory()->create();
        $ilan = Listing::factory()->for($uye)->create([
            'status' => ListingStatus::Taslak, 'is_demo' => false,
        ]);

        $yanit = $this->actingAs($uye)->post(route('panel.listings.publish', $ilan));
        $yanit->assertRedirect(route('panel.listings.index'));

        // followRedirects() TestCase'in metodu, yanıtın değil.
        $icerik = $this->followRedirects($yanit)->assertOk()->getContent();

        $this->assertStringContainsString('İlanını ilk görecek kişiler', $icerik);
        // Kart URL'i `@js()` ile basıldığı için eğik çizgiler JSON kaçışlıdır
        // (http:\/\/...) — tam URL araması bu yüzden eşleşmez. Uç noktanın
        // ayırt edici parçası aranıyor.
        $this->assertStringContainsString('kart.png', $icerik, 'WhatsApp durum kartı bağlantısı yok.');
        $this->assertStringContainsString('wa.me', $icerik);
    }

    public function test_paylasim_bloku_yalnizca_yayin_anindan_sonra_cikar(): void
    {
        // Her ziyarette çıkarsa uyarı olmaktan çıkıp gürültüye dönüşür.
        $uye = User::factory()->create();
        Listing::factory()->for($uye)->create(['status' => ListingStatus::Aktif, 'is_demo' => false]);

        $this->actingAs($uye)->get(route('panel.listings.index'))
            ->assertOk()
            ->assertDontSee('İlanını ilk görecek kişiler');
    }

    public function test_taslak_kaydetmek_paylasim_istemez(): void
    {
        // Taslak yayında değil; paylaşmasını istemek yanlış olurdu.
        $uye = User::factory()->create();

        $this->actingAs($uye)->get(route('panel.listings.index'))
            ->assertOk()
            ->assertDontSee('İlanını ilk görecek kişiler');
    }

    public function test_vaat_abartilmaz(): void
    {
        /*
         * Doktrin: sitedeki her bilgi gerçek. "Binlerce kişiye ulaş" demek
         * uydurma olurdu — 3 ilanlık bir pazaryerinde kimseye ulaşmıyor.
         * Söylenen şey doğru: ilanı ilk görecek olan kişinin kendi çevresi.
         */
        $uye = User::factory()->create();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Taslak]);

        $yanit = $this->actingAs($uye)->post(route('panel.listings.publish', $ilan));
        $icerik = $this->followRedirects($yanit)->getContent();

        foreach (['binlerce', 'on binlerce', 'milyon'] as $abarti) {
            $this->assertStringNotContainsString($abarti, $icerik, "Abartılı erişim vaadi: {$abarti}");
        }
    }
}

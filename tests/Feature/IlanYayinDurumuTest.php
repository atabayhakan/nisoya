<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Üyenin kendi yayın kararı: yayından kaldır / geri yayınla (2026-08-06).
 *
 * ---------------------------------------------------------------------------
 * NEDEN BU TESTLER VAR
 *
 * `ListingStatus::Pasif` uzun süre YARIM bir durumdu: sistemde tanımlıydı,
 * El Kitabı şeridi onu "üye kendi kaldırdı, geri açabilir" diye anlatıyordu,
 * ama üyeye böyle bir düğme HİÇ verilmemişti. Tek üreteni
 * `UserObserver::suspendActiveListings()` idi. Yani belge kodu değil, hayali
 * bir ürünü anlatıyordu.
 *
 * Eylem eklenirken asıl risk özelliğin kendisi değil, tek durum değerine iki
 * anlam yüklenmesiydi: "üye kaldırdı" ile "yönetim kaldırdı" ayırt
 * edilemeseydi, üye yönetimin kararını kendi düğmesiyle geri alabilirdi.
 * Aşağıdaki testlerin çoğu o ayrımı ve geçiş kapılarını mühürlüyor —
 * özellik çalışıyor mu diye değil, YANLIŞ ÇALIŞMIYOR mu diye.
 */
class IlanYayinDurumuTest extends TestCase
{
    use RefreshDatabase;

    private function uye(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    // === Mutlu yol ===================================================

    public function test_sahibi_kendi_ilanini_yayindan_kaldirir(): void
    {
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Aktif]);

        $this->actingAs($uye)
            ->post(route('panel.listings.unpublish', $ilan))
            ->assertRedirect(route('panel.listings.index'));

        $ilan->refresh();
        $this->assertSame(ListingStatus::Pasif, $ilan->status);
        $this->assertNotNull($ilan->unpublished_at, 'Geri açma hakkının tek kanıtı bu damga; boş kalırsa üye kendi ilanını açamaz.');
    }

    public function test_sahibi_kaldirdigi_ilani_geri_yayinlar(): void
    {
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create([
            'status' => ListingStatus::Pasif,
            'unpublished_at' => now()->subDay(),
        ]);

        $this->actingAs($uye)
            ->post(route('panel.listings.republish', $ilan))
            ->assertRedirect(route('panel.listings.index'));

        $ilan->refresh();
        $this->assertSame(ListingStatus::Aktif, $ilan->status);
        $this->assertNull($ilan->unpublished_at, 'Yayına dönen ilan hâlâ "kaldırılmış" görünmemeli.');
    }

    public function test_geri_yayinlama_tekrar_moderasyona_girmez(): void
    {
        /*
         * Bilinçli karar: `yayinla` (Taslak → Aktif) da doğrudan geçiyor.
         * Zaten bir kez yayında kalmış ilanı geri açmayı kuyruğa sokmak
         * üyeye "kendi ilanın sana ait değil" demek olurdu. Tek istisna
         * işaretli görsel — aşağıda ayrı testi var.
         */
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create([
            'status' => ListingStatus::Pasif,
            'unpublished_at' => now(),
        ]);

        $this->actingAs($uye)->post(route('panel.listings.republish', $ilan));

        $this->assertNotSame(ListingStatus::Beklemede, $ilan->fresh()->status);
    }

    // === Yetki: yalnız sahibi ========================================

    public function test_baskasi_ilani_yayindan_kaldiramaz(): void
    {
        $sahip = $this->uye();
        $yabanci = $this->uye();
        $ilan = Listing::factory()->for($sahip)->create(['status' => ListingStatus::Aktif]);

        $this->actingAs($yabanci)
            ->post(route('panel.listings.unpublish', $ilan))
            ->assertForbidden();

        $this->assertSame(ListingStatus::Aktif, $ilan->fresh()->status);
    }

    public function test_baskasi_ilani_geri_yayinlayamaz(): void
    {
        $sahip = $this->uye();
        $yabanci = $this->uye();
        $ilan = Listing::factory()->for($sahip)->create([
            'status' => ListingStatus::Pasif,
            'unpublished_at' => now(),
        ]);

        $this->actingAs($yabanci)
            ->post(route('panel.listings.republish', $ilan))
            ->assertForbidden();

        $this->assertSame(ListingStatus::Pasif, $ilan->fresh()->status);
    }

    public function test_moderator_uye_rotasindan_yayin_durumunu_degistiremez(): void
    {
        /*
         * `update`/`delete` moderatörü KAPSAR (içerik müdahalesi), bu yetenek
         * kapsamaz. Kapsasaydı moderatör, yönetim panelinde verdiği susturma
         * kararını üyenin düğmesiyle geri alabileceği ikinci bir yola sahip
         * olurdu. Moderatörün yolu Filament, üyenin yolu bu rota.
         */
        $sahip = $this->uye();
        $moderator = User::factory()->create([
            'role' => UserRole::Moderator,
            'email_verified_at' => now(),
        ]);
        $ilan = Listing::factory()->for($sahip)->create(['status' => ListingStatus::Aktif]);

        $this->actingAs($moderator)
            ->post(route('panel.listings.unpublish', $ilan))
            ->assertForbidden();

        $this->assertSame(ListingStatus::Aktif, $ilan->fresh()->status);
    }

    // === Geçiş kapıları: yalnız Aktif↔Pasif ==========================

    /**
     * @return array<string, array{ListingStatus}>
     */
    public static function kaldirilamayanDurumlar(): array
    {
        return [
            // Taslak zaten yayında değil — "yayından kaldırmak" anlamsız.
            'taslak' => [ListingStatus::Taslak],
            // Beklemede olanı kaldırmak moderasyon kuyruğundan kaçmak olurdu.
            'beklemede' => [ListingStatus::Beklemede],
            // Reddedilmişi "kaldırmak", reddi üyenin kendi kararıymış gibi
            // gösterip geri açma hakkı doğururdu (unpublished_at dolardı).
            'reddedildi' => [ListingStatus::Reddedildi],
        ];
    }

    #[DataProvider('kaldirilamayanDurumlar')]
    public function test_yalnizca_aktif_ilan_yayindan_kaldirilabilir(ListingStatus $durum): void
    {
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create(['status' => $durum]);

        $this->actingAs($uye)
            ->post(route('panel.listings.unpublish', $ilan))
            ->assertRedirect(route('panel.listings.index'));

        $ilan->refresh();
        $this->assertSame($durum, $ilan->status);
        $this->assertNull($ilan->unpublished_at);
    }

    public function test_reddedilmis_ilan_geri_yayinla_ile_diriltilemez(): void
    {
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Reddedildi]);

        $this->actingAs($uye)->post(route('panel.listings.republish', $ilan));

        $this->assertSame(ListingStatus::Reddedildi, $ilan->fresh()->status);
    }

    public function test_taslak_geri_yayinla_ile_yayina_alinamaz(): void
    {
        // Taslağın kendi kapısı var (`panel.listings.publish`); iki kapı
        // arasında sızıntı olmamalı.
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Taslak]);

        $this->actingAs($uye)->post(route('panel.listings.republish', $ilan));

        $this->assertSame(ListingStatus::Taslak, $ilan->fresh()->status);
    }

    // === Pasif'in iki anlamı =========================================

    public function test_yonetimin_pasife_cektigi_ilan_uye_tarafindan_geri_acilamaz(): void
    {
        /*
         * BU DOSYANIN EN ÖNEMLİ TESTİ. Hesabı askıya alınan üyenin ilanları
         * Pasif'e düşüyor; hesap sonradan geri açılınca ilanlar Pasif kalıyor.
         * "Geri yayınla" düğmesi durum değerine bakarak çalışsaydı, üye
         * yönetimin susturma kararını tek tıkla geri alırdı.
         */
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Aktif]);

        // Yönetim askıya alır → UserObserver ilanı Pasif'e çeker, unpublished_at DOLMAZ.
        $uye->update(['status' => UserStatus::Askida]);
        $this->assertSame(ListingStatus::Pasif, $ilan->fresh()->status);
        $this->assertNull($ilan->fresh()->unpublished_at);

        // Hesap geri açılır (askıdayken zaten oturum açamaz — EnsureUserIsActive).
        $uye->update(['status' => UserStatus::Aktif]);

        $this->actingAs($uye)
            ->post(route('panel.listings.republish', $ilan))
            ->assertRedirect(route('panel.listings.index'));

        $this->assertSame(ListingStatus::Pasif, $ilan->fresh()->status);
    }

    // === İşaretli görsel: tek moderasyon istisnası ===================

    public function test_isaretli_gorseli_olan_ilan_geri_yayinlaninca_incelemeye_duser(): void
    {
        /*
         * KAPATILAN BOŞLUK: `ProcessListingImage::moderate()` işaretlediği
         * görselin ilanını yalnızca ilan O ANDA AKTİFSE incelemeye alır.
         * Yayından kaldır → işaretli görsel yükle → geri yayınla dizisi, AI
         * elemesini hiç uğramadan siteye çıkarırdı.
         */
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create([
            'status' => ListingStatus::Pasif,
            'unpublished_at' => now(),
        ]);
        ListingImage::create([
            'listing_id' => $ilan->id,
            'path_large' => 'listings/isaretli.webp',
            'is_flagged' => true,
            'flagged_reason' => 'test',
        ]);

        $this->actingAs($uye)->post(route('panel.listings.republish', $ilan));

        $ilan->refresh();
        $this->assertSame(ListingStatus::Beklemede, $ilan->status);
        $this->assertNull($ilan->unpublished_at, 'Kuyruğa giren ilan "üye kaldırdı" damgasını taşımamalı; artık karar admin\'de.');
    }

    public function test_isaretli_gorseli_olan_taslak_da_incelemeye_duser(): void
    {
        // Aynı boşluk taslak yolunda da vardı; iki yayına-alma kapısı aynı
        // kuralı paylaşmak zorunda (ListingController::yayinaAl).
        $uye = $this->uye();
        $ilan = Listing::factory()->for($uye)->create(['status' => ListingStatus::Taslak]);
        ListingImage::create([
            'listing_id' => $ilan->id,
            'path_large' => 'listings/isaretli.webp',
            'is_flagged' => true,
        ]);

        $this->actingAs($uye)->post(route('panel.listings.publish', $ilan));

        $this->assertSame(ListingStatus::Beklemede, $ilan->fresh()->status);
    }

    // === Ekran: düğme gerçekten görünüyor mu =========================

    public function test_ilanlarim_ekraninda_dogru_dugmeler_gorunur(): void
    {
        /*
         * Kod doğru olup düğmenin görünmemesi bu projede yaşanmış bir hata
         * sınıfı. Üç Pasif/Aktif hâli aynı listede: hangisinin hangi eylemi
         * gösterdiği ekranda mühürleniyor.
         */
        $uye = $this->uye();
        $aktif = Listing::factory()->for($uye)->create(['status' => ListingStatus::Aktif]);
        $uyeKaldirdi = Listing::factory()->for($uye)->create([
            'status' => ListingStatus::Pasif,
            'unpublished_at' => now(),
        ]);
        $yonetimKaldirdi = Listing::factory()->for($uye)->create(['status' => ListingStatus::Pasif]);

        $this->actingAs($uye)
            ->get(route('panel.listings.index'))
            ->assertOk()
            ->assertSee(route('panel.listings.unpublish', $aktif))
            ->assertSee(route('panel.listings.republish', $uyeKaldirdi))
            ->assertDontSee(route('panel.listings.republish', $yonetimKaldirdi))
            ->assertSee('Yönetim yayından kaldırdı', false);
    }
}

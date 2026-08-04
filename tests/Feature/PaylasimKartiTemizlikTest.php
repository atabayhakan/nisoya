<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Services\PaylasimKartiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Paylaşım kartı önbelleğinin çöp toplaması.
 *
 * En kritik bekçi `guncel_kart_asla_silinmez`: temizlik fazla hevesli olursa
 * her istekte kart yeniden üretilir — 1080×1920 PNG + QR çizimi pahalıdır ve
 * önbellek anlamsızlaşır. Silme mantığının "bayat" tanımı daralırsa değil,
 * GENİŞLERSE tehlikeli.
 */
class PaylasimKartiTemizlikTest extends TestCase
{
    use RefreshDatabase;

    private function ilan(array $nitelikler = []): Listing
    {
        return Listing::factory()->for(User::factory())->create($nitelikler);
    }

    private function sahteKart(string $ad): void
    {
        Storage::disk('public')->put(PaylasimKartiUretici::KLASOR.'/'.$ad, 'sahte');
    }

    public function test_yeni_kart_uretilince_ayni_ilanin_eskileri_silinir(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan();
        $this->sahteKart($ilan->id.'-e5c1a00b12.png');

        $guncel = app(PaylasimKartiUretici::class)->hazirla($ilan);

        Storage::disk('public')->assertExists($guncel);
        Storage::disk('public')->assertMissing(PaylasimKartiUretici::KLASOR.'/'.$ilan->id.'-e5c1a00b12.png');
    }

    public function test_baska_ilanin_karti_silinmez(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan();
        $baska = $this->ilan();

        // id=1 ile id=11 karışmasın diye ayraç tireyle kontrol ediliyor;
        // bu test o ayracın kaybolmasını da yakalar.
        $this->sahteKart($baska->id.'-ba54a1c0de.png');

        app(PaylasimKartiUretici::class)->hazirla($ilan);

        Storage::disk('public')->assertExists(PaylasimKartiUretici::KLASOR.'/'.$baska->id.'-ba54a1c0de.png');
    }

    public function test_guncel_kart_asla_silinmez(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan();
        $guncel = app(PaylasimKartiUretici::class)->hazirla($ilan);

        $this->artisan('paylasim-kartlari:temizle')->assertSuccessful();

        Storage::disk('public')->assertExists($guncel);
    }

    public function test_supurme_bayat_ve_yetim_dosyalari_siler(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan();
        $guncel = app(PaylasimKartiUretici::class)->hazirla($ilan);

        $bayat = PaylasimKartiUretici::KLASOR.'/'.$ilan->id.'-c0c0eeaa11.png';
        $yetim = PaylasimKartiUretici::KLASOR.'/999999-decafbad01.png';

        $this->sahteKart($ilan->id.'-c0c0eeaa11.png');
        $this->sahteKart('999999-decafbad01.png');

        $this->artisan('paylasim-kartlari:temizle')->assertSuccessful();

        Storage::disk('public')->assertExists($guncel);
        Storage::disk('public')->assertMissing($bayat);
        Storage::disk('public')->assertMissing($yetim);
    }

    public function test_rapor_secenegi_hicbir_seyi_silmez(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan();
        app(PaylasimKartiUretici::class)->hazirla($ilan);

        $bayat = PaylasimKartiUretici::KLASOR.'/'.$ilan->id.'-c0c0eeaa11.png';
        $this->sahteKart($ilan->id.'-c0c0eeaa11.png');

        $this->artisan('paylasim-kartlari:temizle --rapor')->assertSuccessful();

        Storage::disk('public')->assertExists($bayat);
    }

    public function test_desene_uymayan_dosyaya_dokunulmaz(): void
    {
        Storage::fake('public');

        // Klasöre elle konmuş ya da başka bir amaçla duran dosyayı silmek,
        // "yetim" tanımını dosya adı desenine bağlamamanın bedeli olurdu.
        $this->sahteKart('okuma-notu.txt');
        $this->sahteKart('kapak.png');

        $this->artisan('paylasim-kartlari:temizle')->assertSuccessful();

        Storage::disk('public')->assertExists(PaylasimKartiUretici::KLASOR.'/okuma-notu.txt');
        Storage::disk('public')->assertExists(PaylasimKartiUretici::KLASOR.'/kapak.png');
    }

    public function test_iki_temizlik_yolu_ayni_kurali_kullanir(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan();

        // İlan id'siyle BAŞLAYAN ama kart desenine uymayan dosya. İlk sürümde
        // anında temizlik bunu siliyordu (yalnız `{id}-` önekine bakıyordu),
        // süpürme ise koruyordu — aynı dosya birine göre çöp, diğerine göre
        // korunacaktı. İkisi de artık dosyaAdindanIlanId()'yi kullanıyor.
        $yabanci = PaylasimKartiUretici::KLASOR.'/'.$ilan->id.'-elle-konmus.png';
        $this->sahteKart($ilan->id.'-elle-konmus.png');

        app(PaylasimKartiUretici::class)->hazirla($ilan);
        Storage::disk('public')->assertExists($yabanci);

        $this->artisan('paylasim-kartlari:temizle')->assertSuccessful();
        Storage::disk('public')->assertExists($yabanci);
    }
}

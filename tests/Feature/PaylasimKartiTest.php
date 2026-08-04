<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\PaylasimKartiUretici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * WhatsApp durumu paylaşım kartı.
 *
 * En kritik bekçi `kart_rotasi_ilan_detayina_yutulmaz`: kart rotası
 * `/ilan/{listing}/{slug?}`'dan SONRA tanımlanırsa `{slug?}` "kart.png"i
 * yakalar ve istek kanonik ilan URL'ine 301'lenir — kart hiç üretilmez.
 * Rota sırası görünmez bir bağımlılık olduğu için testle sabitlendi.
 */
class PaylasimKartiTest extends TestCase
{
    use RefreshDatabase;

    private function ilan(array $nitelikler = []): Listing
    {
        return Listing::factory()->for(User::factory())->create($nitelikler);
    }

    public function test_kart_rotasi_ilan_detayina_yutulmaz(): void
    {
        $ilan = $this->ilan();

        $yanit = $this->get(route('listings.card', $ilan));

        $yanit->assertOk();
        $yanit->assertHeader('Content-Type', 'image/png');
    }

    public function test_kart_1080x1920_uretilir(): void
    {
        $png = app(PaylasimKartiUretici::class)->uret($this->ilan());

        $boyut = getimagesizefromstring($png);

        $this->assertNotFalse($boyut);
        $this->assertSame(PaylasimKartiUretici::GENISLIK, $boyut[0]);
        $this->assertSame(PaylasimKartiUretici::YUKSEKLIK, $boyut[1]);
    }

    public function test_kart_diske_onbelleklenir_ve_imza_degisince_tazelenir(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan();
        $uretici = app(PaylasimKartiUretici::class);
        $ilkYol = $uretici->yol($ilan);

        $this->get(route('listings.card', $ilan))->assertOk();
        Storage::disk('public')->assertExists($ilkYol);

        // Kartta GÖRÜNEN alan değişince imza da değişmeli.
        $ilan->update(['title' => 'Yeni başlık']);
        $this->assertNotSame($ilkYol, $uretici->yol($ilan->fresh()));
    }

    public function test_goruntulenme_sayaci_karti_bosuna_tazelemez(): void
    {
        $ilan = $this->ilan();
        $uretici = app(PaylasimKartiUretici::class);
        $once = $uretici->yol($ilan);

        // views_count artışı updated_at'e dokunur ama kartta görünmez.
        // İmza buna tepki verirse kart her sayfa görüntülemesinde yeniden
        // üretilir — pahalı ve gereksiz.
        $ilan->increment('views_count');

        $this->assertSame($once, $uretici->yol($ilan->fresh()));
    }

    public function test_pasif_ilanin_karti_misafire_kapali_sahibine_acik(): void
    {
        $ilan = $this->ilan(['status' => ListingStatus::Pasif]);

        $this->get(route('listings.card', $ilan))->assertNotFound();

        $this->actingAs($ilan->user)
            ->get(route('listings.card', $ilan))
            ->assertOk();
    }

    public function test_demo_ilan_kartinda_ornek_damgasi_var(): void
    {
        $uretici = app(PaylasimKartiUretici::class);

        // AYNI ilan üzerinde bayrağı çevirerek karşılaştırıyoruz. İki AYRI ilan
        // kurmak yanlış sebeple geçen bir test verirdi: id/başlık/fiyat/şehir
        // farkı bir yana, QR ilanın kendi URL'ini kodladığı için iki kart
        // damga hiç basılmasa da bayt bayt farklı çıkardı.
        $ilan = $this->ilan(['is_demo' => false]);
        $damgasiz = md5($uretici->uret($ilan));

        $ilan->is_demo = true;
        $damgali = md5($uretici->uret($ilan));

        $this->assertNotSame(
            $damgasiz,
            $damgali,
            'Demo ilanın kartı damgasız üretildi — sahte ilan gerçek bir ağa damgasız düşebilir.'
        );
    }

    public function test_demo_damgasi_kapak_gorseli_varken_de_basilir(): void
    {
        $uretici = app(PaylasimKartiUretici::class);

        Storage::fake('public');

        $ilan = $this->ilan(['is_demo' => true]);

        // Gerçek bir kapak dosyası şart: dosya yoksa kapak basılmaz, kart
        // yedek yola düşer ve damga oradan gelir — test doğru sebeple değil,
        // yanlış sebeple geçerdi.
        $yol = 'listings/large/kapak.png';
        Storage::disk('public')->put($yol, (string) (new ImageManager(new Driver))
            ->createImage(1200, 800)->fill('#334155')->encode(new PngEncoder));

        ListingImage::create(['listing_id' => $ilan->id, 'path_large' => $yol]);

        $damgali = md5($uretici->uret($ilan->fresh()));

        $ilan->is_demo = false;

        // Kapak varken damgayı atlamak cazip bir kısayol ama YANLIŞ: kapağı
        // kare kırptığımız için demo görselinin köşe rozeti kesiliyor ve
        // grafik tuvalde kırpmaya dayanıklı çapraz filigran yok — kısayol
        // bazı demo ilanlarını damgasız bırakır.
        $this->assertNotSame(
            $damgali,
            md5($uretici->uret($ilan)),
            'Kapak görseli olan demo ilanın kartına damga basılmadı.'
        );
    }

    public function test_kapak_gorseli_karta_gercekten_basilir(): void
    {
        Storage::fake('public');

        $ilan = $this->ilan(['is_demo' => false]);
        $yol = 'listings/large/kapak.png';

        // Ayırt edici düz renk: kapak basıldıysa kartın üst yarısındaki
        // pikseller bu renk olmalı. (insert() imzası bir kez yanlış
        // kullanıldı ve geniş catch bunu sessizce yutup yedek yola düşürdü —
        // bu test o sessiz düşüşü görünür kılıyor.)
        Storage::disk('public')->put($yol, (string) (new ImageManager(new Driver))
            ->createImage(1200, 800)->fill('#ff0000')->encode(new PngEncoder));

        ListingImage::create(['listing_id' => $ilan->id, 'path_large' => $yol]);

        $png = imagecreatefromstring(app(PaylasimKartiUretici::class)->uret($ilan->fresh()));
        $this->assertNotFalse($png);

        $renk = imagecolorsforindex($png, imagecolorat($png, 540, 400));

        $this->assertGreaterThan(200, $renk['red']);
        $this->assertLessThan(60, $renk['green']);
    }

    public function test_gorselsiz_ilan_kart_uretimini_kirmaz(): void
    {
        $ilan = $this->ilan();

        $this->assertSame(0, $ilan->images()->count());

        $png = getimagesizefromstring(app(PaylasimKartiUretici::class)->uret($ilan));

        $this->assertNotFalse($png);
    }
}

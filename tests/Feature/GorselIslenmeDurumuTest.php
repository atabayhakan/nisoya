<?php

namespace Tests\Feature;

use App\Jobs\ProcessListingImage;
use App\Models\Listing;
use App\Models\User;
use App\Services\ImageModerationService;
use App\Services\ImageService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Görsellerin işlenme durumu kullanıcıya görünüyor mu?
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * `ListingImage` kaydı YALNIZCA `ProcessListingImage` kuyruk işinde doğuyor.
 * Yükleme ile görselin belirmesi arasındaki boşlukta ilan görselsiz görünüyor
 * ve o boşlukta BOŞ KUTU ile ARIZA birbirinden ayırt edilemiyordu.
 *
 * Sahip 2026-08-12'de tam bunu yaşadı: fotoğrafı ekledi, göremedi, moderasyona
 * takıldığını sandı. Görsel aslında işlenmişti — sadece kimse ona "işleniyor"
 * demedi. Kusur görselde değil, SESSİZLİKTEYDİ.
 */
class GorselIslenmeDurumuTest extends TestCase
{
    use RefreshDatabase;

    private function uye(): User
    {
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);

        return User::factory()->create(['country_code' => 'DE']);
    }

    public function test_gorselsiz_ilan_isleniyor_demez(): void
    {
        // Hiç görsel yüklenmemişse "işleniyor" demek yalan olurdu.
        $ilan = Listing::factory()->create();

        $this->assertSame('hazir', $ilan->gorselDurumu());
    }

    public function test_kuyruga_atilan_gorsel_isleniyor_olarak_isaretlenir(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = $this->uye();
        $ilan = Listing::factory()->create(['user_id' => $user->id]);

        /*
         * METİN SABİT, FABRİKADAN GELMİYOR — bu test bir kez kararsızdı.
         *
         * `Listing::factory()` başlık/açıklamayı Faker'ın Latince lorem
         * metninden üretiyor ve o metinde ara sıra tek başına "it" geçiyor.
         * "it" Türkçede hakaret olarak küfür filtresinin listesinde; istek
         * doğrulamadan dönüyor, ilan güncellenmiyor ve sayaç 0 kalıyordu.
         * Yerelde 8 koşudan 1'i, CI'da paralel koşuda düştü.
         *
         * Rastgele metin, doğrulamadan geçen bir yolu sınayan testlerde
         * gizli bir zar atışıdır.
         */
        $this->actingAs($user)->patch(route('panel.listings.update', $ilan), [
            'type' => $ilan->type->value,
            'title' => 'Kablosuz kulaklık satılık',
            'description' => 'Az kullanılmış kablosuz kulaklık, kutusu ve kablosuyla birlikte satılıktır.',
            'category_id' => $ilan->category_id,
            'country_code' => 'DE',
            'city' => 'Berlin',
            'price' => 50,
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'images' => [UploadedFile::fake()->image('foto.jpg', 800, 600)],
        ])
            /*
             * `assertSessionHasNoErrors()` ŞART. Hedefsiz `assertRedirect()`
             * doğrulama hatası yönlendirmesini de kabul ediyor: istek forma
             * geri dönse bile test yeşil kalıyor, sonra "sayaç 0" diye
             * anlaşılmaz bir hata veriyordu. Sebebi hatanın kendisi söylesin.
             */
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('panel.listings.index'));

        $ilan->refresh();

        $this->assertSame(1, $ilan->pending_images);
        $this->assertNotNull($ilan->images_queued_at);
        $this->assertFalse($ilan->images_failed);
        $this->assertSame('isleniyor', $ilan->gorselDurumu());
    }

    public function test_isleniyor_uyarisi_ekranda_gorunur(): void
    {
        $user = $this->uye();
        $ilan = Listing::factory()->create(['user_id' => $user->id]);
        $ilan->forceFill(['pending_images' => 2, 'images_queued_at' => now()])->save();

        $this->actingAs($user)->get(route('panel.listings.index'))
            ->assertOk()
            ->assertSee('Görsel işleniyor');

        $this->actingAs($user)->get(route('panel.listings.edit', $ilan))
            ->assertOk()
            ->assertSee('2 görsel işleniyor');
    }

    public function test_bayat_kuyruk_kaydi_sonsuza_dek_isleniyor_demez(): void
    {
        /*
         * ASIL TUZAK. Worker sert öldürülürse sayaç sıfırlanmaz. Yaş kontrolü
         * olmasaydı ekran SONSUZA DEK "işleniyor" derdi — sessiz kayıp,
         * sonsuza dek bekleyen kayba dönüşürdü. İkisi de yalan.
         */
        $ilan = Listing::factory()->create();
        $ilan->forceFill([
            'pending_images' => 1,
            'images_queued_at' => now()->subMinutes(20),
        ])->save();

        $this->assertSame('hata', $ilan->fresh()->gorselDurumu());
    }

    public function test_dusen_is_kullaniciya_hata_olarak_yansir(): void
    {
        $ilan = Listing::factory()->create();
        $ilan->forceFill(['pending_images' => 1, 'images_queued_at' => now()])->save();

        Storage::fake('local');

        (new ProcessListingImage(
            listingId: $ilan->id,
            rawPath: 'pending-listing-images/yok.jpg',
            rawDisk: 'local',
            sortOrder: 1,
            isCover: true,
        ))->failed(new \RuntimeException('deneme'));

        $ilan->refresh();

        $this->assertSame(0, $ilan->pending_images, 'Sayaç düşmemiş — ilan sonsuza dek işleniyor görünür.');
        $this->assertTrue($ilan->images_failed);
        $this->assertSame('hata', $ilan->gorselDurumu());
    }

    public function test_hata_uyarisi_ekranda_gorunur(): void
    {
        $user = $this->uye();
        $ilan = Listing::factory()->create(['user_id' => $user->id]);
        $ilan->forceFill(['images_failed' => true])->save();

        $this->actingAs($user)->get(route('panel.listings.index'))
            ->assertOk()
            ->assertSee('Görsel yüklenemedi');
    }

    public function test_basarili_is_sayaci_dusurur_ve_ilan_hazira_doner(): void
    {
        /*
         * EN ÖNEMLİ TEST — çünkü bu YAYGIN yol.
         *
         * Hata dalları düzgün çalışsa bile başarı dalı sayacı düşürmezse HER
         * ilan, görseli sorunsuz işlendiği hâlde sonsuza dek "işleniyor" der.
         * Yani düzeltmenin kendisi yeni bir yalana dönüşür.
         */
        Storage::fake('local');
        Storage::fake('public');

        $ilan = Listing::factory()->create();
        $ilan->forceFill(['pending_images' => 1, 'images_queued_at' => now()])->save();

        // Job gerçek bir dosya bekliyor; sahte diske gerçek bir görsel koy.
        $dosya = UploadedFile::fake()->image('foto.jpg', 900, 700);
        $yol = $dosya->store('pending-listing-images', 'local');

        (new ProcessListingImage(
            listingId: $ilan->id,
            rawPath: $yol,
            rawDisk: 'local',
            sortOrder: 1,
            isCover: true,
        ))->handle(app(ImageService::class), app(ImageModerationService::class));

        $ilan->refresh();

        $this->assertSame(1, $ilan->images()->count(), 'Görsel kaydı oluşmamış.');
        $this->assertSame(0, $ilan->pending_images, 'Sayaç düşmemiş — ilan sonsuza dek işleniyor görünür.');
        $this->assertFalse($ilan->images_failed);
        $this->assertSame('hazir', $ilan->gorselDurumu());
    }

    public function test_sayac_negatife_dusmez(): void
    {
        /*
         * `tries=2` ve kısmi başarı hâllerinde aynı iş iki kez sayacı
         * düşürebilir. Negatif sayaç, "hazır" ile "işleniyor" ayrımını
         * sessizce bozardı.
         */
        $ilan = Listing::factory()->create();
        $ilan->forceFill(['pending_images' => 0])->save();

        Storage::fake('local');

        $is = new ProcessListingImage(
            listingId: $ilan->id, rawPath: 'yok.jpg', rawDisk: 'local', sortOrder: 1, isCover: true,
        );
        $is->failed(new \RuntimeException('bir'));
        $is->failed(new \RuntimeException('iki'));

        $this->assertSame(0, $ilan->fresh()->pending_images);
    }
}

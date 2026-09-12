<?php

namespace Tests\Feature;

use App\Ai\Kahya\Araclar\VitrinHazirla;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\Growth\ClaimableListingCreator;
use App\Services\Kahya\Dis\IsletmeKesfi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Tools\Request;
use Mockery;
use Tests\TestCase;

class PlacesEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Country::firstOrCreate(
            ['code' => 'NL'],
            ['name_tr' => 'Hollanda', 'name_en' => 'Netherlands', 'is_active' => true, 'currency_code' => 'EUR', 'sort_order' => 1]
        );

        Country::firstOrCreate(
            ['code' => 'TR'],
            ['name_tr' => 'Türkiye', 'name_en' => 'Turkey', 'is_active' => true, 'currency_code' => 'TRY', 'sort_order' => 2]
        );

        Category::firstOrCreate(
            ['slug' => 'hizmet'],
            ['name' => 'Hizmetler', 'type' => 'hizmet', 'is_active' => true, 'sort_order' => 1]
        );
    }

    private function generateSampleJpegBytes(): string
    {
        $im = imagecreatetruecolor(200, 150);
        $bg = imagecolorallocate($im, 240, 200, 100);
        imagefilledrectangle($im, 0, 0, 200, 150, $bg);

        ob_start();
        imagejpeg($im, null, 90);
        $data = ob_get_clean();
        imagedestroy($im);

        return (string) $data;
    }

    public function test_creator_includes_google_rating_and_reviews_in_description(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Antep Baklava Rotterdam',
            'city' => 'Rotterdam',
            'country_code' => 'NL',
            'rating' => 4.9,
            'review_count' => 186,
        ]);

        /** @var Listing $listing */
        $listing = $result['listing'];

        $this->assertStringContainsString('⭐ Google Puanı: 4.9 (186 değerlendirme)', $listing->description);
        $this->assertStringContainsString('Antep Baklava Rotterdam', $listing->description);
    }

    public function test_attach_photo_bytes_optimizes_and_saves_listing_image(): void
    {
        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Saray Lokantası',
            'city' => 'Amsterdam',
            'country_code' => 'NL',
        ]);

        /** @var Listing $listing */
        $listing = $result['listing'];

        $jpegBytes = $this->generateSampleJpegBytes();
        $listingImage = $creator->attachPhotoBytes($listing, $jpegBytes);

        $this->assertInstanceOf(ListingImage::class, $listingImage);
        $this->assertTrue($listingImage->is_cover);
        $this->assertSame(0, $listingImage->sort_order);
        $this->assertSame($listing->id, $listingImage->listing_id);

        $this->assertTrue(Storage::disk('public')->exists($listingImage->path_large));
        $this->assertTrue(Storage::disk('public')->exists($listingImage->path_medium));
        $this->assertTrue(Storage::disk('public')->exists($listingImage->path_thumb));
        $this->assertStringEndsWith('.webp', $listingImage->path_large);
    }

    public function test_attach_photo_from_places_fetches_via_isletme_kesfi(): void
    {
        $jpegBytes = $this->generateSampleJpegBytes();

        $mockKesif = Mockery::mock(IsletmeKesfi::class);
        $mockKesif->shouldReceive('fotoIndir')
            ->once()
            ->with('places/test_place/photos/photo_abc123')
            ->andReturn($jpegBytes);

        $this->app->instance(IsletmeKesfi::class, $mockKesif);

        $creator = app(ClaimableListingCreator::class);
        $result = $creator->createFromData([
            'name' => 'Mevlana Pide',
            'city' => 'Den Haag',
            'country_code' => 'NL',
            'photo_reference' => 'places/test_place/photos/photo_abc123',
        ]);

        /** @var Listing $listing */
        $listing = $result['listing'];

        $this->assertSame(1, $listing->images()->count());
        $image = $listing->images()->first();
        $this->assertTrue($image->is_cover);
        $this->assertTrue(Storage::disk('public')->exists($image->path_large));
    }

    public function test_vitrin_hazirla_tool_processes_google_rating_and_photo(): void
    {
        $jpegBytes = $this->generateSampleJpegBytes();

        $mockKesif = Mockery::mock(IsletmeKesfi::class);
        $mockKesif->shouldReceive('fotoIndir')
            ->once()
            ->with('places/rotterdam_kebap/photos/ref1')
            ->andReturn($jpegBytes);

        $this->app->instance(IsletmeKesfi::class, $mockKesif);

        $tool = app(VitrinHazirla::class);
        $response = (string) $tool->handle(new Request([
            'isletme_adi' => 'Huzur Döner',
            'ulke_kodu' => 'NL',
            'sehir' => 'Rotterdam',
            'telefon' => '+31 10 999 8877',
            'google_puani' => 4.7,
            'yorum_sayisi' => 92,
            'foto_referansi' => 'places/rotterdam_kebap/photos/ref1',
        ]));

        $this->assertStringContainsString('BAŞARILI: Sahiplenilebilir vitrin oluşturuldu.', $response);
        $this->assertStringContainsString('Kapak Görseli: Google Places fotoğrafı WebP olarak eklendi.', $response);
        $this->assertStringContainsString('WhatsApp Doğrudan Davet: https://wa.me/31109998877?text=', $response);

        $listing = Listing::where('title', 'Huzur Döner')->first();
        $this->assertNotNull($listing);
        $this->assertStringContainsString('⭐ Google Puanı: 4.7 (92 değerlendirme)', $listing->description);
        $this->assertSame(1, $listing->images()->count());
    }
}

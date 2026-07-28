<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ListingImages\Pages\ViewListingImage;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin "İlan görseli → Görüntüle" sayfasının duman testi (2026-07-29).
 *
 * BULUNAN HATA: sayfa canlıda 500 veriyordu. ListingImageInfolist.php
 * `Filament\Infolists\Components\Section` import ediyordu; Filament 5'te
 * Section yerleşim bileşenidir ve `Filament\Schemas\Components` altında
 * yaşar — entry sınıflarının (ImageEntry/KeyValueEntry/TextEntry) aksine.
 *
 * NEDEN HİÇ FARK EDİLMEDİ: PHPStan hatayı buluyordu ama girdi 2026-07-05'ten
 * beri phpstan-baseline.neon'da `class.notFound / count: 4` olarak bastırılmıştı,
 * yani CI yeşil kalıyordu. Sayfayı açan tek test de yoktu. Statik analiz
 * susturulduğunda geriye kalan tek ağ testtir; bu dosya o ağ.
 *
 * Moderatörün EXIF/GPS denetimi yaptığı ekran burasıdır.
 */
class ListingImageGoruntulemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function gorsel(): ListingImage
    {
        $sahip = User::factory()->create(['email_verified_at' => now()]);

        $ilan = Listing::factory()->create([
            'user_id' => $sahip->id,
            'category_id' => Category::query()->whereNotNull('parent_id')->value('id'),
            'status' => ListingStatus::Aktif,
        ]);

        // ListingImage'in factory'si yok; alanlar elle doldurulur.
        return ListingImage::create([
            'listing_id' => $ilan->id,
            'path_thumb' => 'listings/test-thumb.jpg',
            'path_medium' => 'listings/test-medium.jpg',
            'path_large' => 'listings/test-large.jpg',
            'width' => 1600,
            'height' => 1200,
            'size_bytes' => 245_000,
        ]);
    }

    public function test_admin_ilan_gorseli_goruntuleme_sayfasini_acabilir(): void
    {
        $gorsel = $this->gorsel();

        Livewire::actingAs(User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]))
            ->test(ViewListingImage::class, ['record' => $gorsel->getKey()])
            ->assertSuccessful();
    }

    /**
     * EXIF verisi olan görselde de açılmalı: Section'lar KeyValueEntry ile
     * dolduğunda yerleşim bileşeni gerçekten render ediliyor.
     */
    public function test_exif_verisi_olan_gorselde_de_acilir(): void
    {
        $gorsel = $this->gorsel();
        $gorsel->update([
            'exif_metadata' => ['Make' => 'Apple', 'Model' => 'iPhone 15', 'DateTimeOriginal' => '2026:07:29 10:00:00'],
            'had_gps' => true,
        ]);

        Livewire::actingAs(User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]))
            ->test(ViewListingImage::class, ['record' => $gorsel->getKey()])
            ->assertSuccessful();
    }

    /**
     * Asıl kök nedenin bekçisi: Section DOĞRU namespace'ten geliyor mu.
     *
     * Sayfa testi hatayı yakalar ama sebebini söylemez; bu iddia doğrudan
     * hatanın kendisini adlandırır ve yanlış import geri gelirse mesajı
     * okuyan kişi ne yapacağını bilir.
     */
    public function test_infolist_section_dogru_namespaceden_gelir(): void
    {
        $kaynak = file_get_contents(app_path('Filament/Resources/Listings/Infolists/ListingImageInfolist.php'));

        $this->assertStringContainsString(
            'use Filament\Schemas\Components\Section;',
            $kaynak,
            'Section, Filament\Schemas\Components altından import edilmeli.'
        );

        $this->assertStringNotContainsString(
            'use Filament\Infolists\Components\Section;',
            $kaynak,
            'Filament\Infolists\Components\Section SINIFI YOKTUR — bu import sayfayı 500 yapar.'
        );
    }
}

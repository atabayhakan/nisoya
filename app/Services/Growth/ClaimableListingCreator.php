<?php

namespace App\Services\Growth;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\OutreachTarget;
use App\Models\User;
use App\Services\ImageService;
use App\Services\Kahya\Dis\IsletmeKesfi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Keşfedilen veya harici kaynaklardan toplanan Türk işletmeleri için
 * "Sahiplenilebilir Taslak Vitrin" (Claimable Listing) üretir.
 *
 * Reverse Onboarding Akışı (2026-09):
 * 1. İşletme bilgileriyle `is_claimed = false` ve benzersiz `claim_token` taşıyan bir vitrin açılır.
 * 2. İlan yayındadır; esnaf davet linkine (`/sahiplen/{token}`) tıkladığında 15 saniyede
 *    hesabını oluşturup dükkanını sahiplenir (`is_claimed = true`).
 */
class ClaimableListingCreator
{
    public const SYSTEM_BOT_EMAIL = 'rehber@nisoya.com';

    /**
     * Dışarıdan gelen işletme verisinden sahiplenilebilir vitrin ilanı oluşturur.
     *
     * @param array{
     *     name: string,
     *     country_code: string,
     *     city: string,
     *     category_id?: ?int,
     *     category_name?: ?string,
     *     address?: ?string,
     *     phone?: ?string,
     *     email?: ?string,
     *     website?: ?string,
     *     description?: ?string,
     *     rating?: ?float,
     *     review_count?: ?int,
     *     photo_reference?: ?string,
     *     photo_bytes?: ?string,
     *     source_external_id?: ?string,
     * } $data
     * @return array{listing: Listing, claim_url: string, claim_token: string}
     */
    public function createFromData(array $data, ?OutreachTarget $target = null): array
    {
        $systemUser = $this->resolveSystemUser();
        $categoryId = $data['category_id'] ?? $this->resolveCategoryId($data['category_name'] ?? null);
        $claimToken = Str::random(48);

        $name = trim($data['name']);
        $city = trim($data['city']);
        $countryCode = strtoupper(trim($data['country_code']));

        $description = $data['description'] ?? $this->generateDefaultDescription(
            name: $name,
            city: $city,
            countryCode: $countryCode,
            address: $data['address'] ?? null,
            phone: $data['phone'] ?? null,
            website: $data['website'] ?? null,
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            reviewCount: isset($data['review_count']) ? (int) $data['review_count'] : null,
        );

        $slug = $this->generateUniqueSlug($name, $city);

        $listing = Listing::create([
            'user_id' => $systemUser->id,
            'category_id' => $categoryId,
            'type' => ListingType::Hizmet,
            'title' => $name,
            'slug' => $slug,
            'description' => $description,
            'country_code' => $countryCode,
            'city' => $city,
            'status' => ListingStatus::Aktif,
            'is_claimed' => false,
            'claim_token' => $claimToken,
            'claim_email' => $data['email'] ?? null,
            'claim_phone' => $data['phone'] ?? null,
            'source_external_id' => $data['source_external_id'] ?? null,
        ]);

        if (! empty($data['photo_bytes'])) {
            $this->attachPhotoBytes($listing, (string) $data['photo_bytes']);
        } elseif (! empty($data['photo_reference'])) {
            $this->attachPhotoFromPlaces($listing, (string) $data['photo_reference']);
        }

        if ($target !== null) {
            $target->update(['listing_id' => $listing->id]);
        }

        return [
            'listing' => $listing,
            'claim_url' => url('/sahiplen/'.$claimToken),
            'claim_token' => $claimToken,
        ];
    }

    /**
     * Var olan bir OutreachTarget adayından doğrudan vitrin üretir.
     *
     * @return array{listing: Listing, claim_url: string, claim_token: string}
     */
    public function createFromTarget(OutreachTarget $target): array
    {
        if ($target->listing_id !== null && ($existing = Listing::find($target->listing_id))) {
            $token = $existing->claim_token ?? Str::random(48);
            if ($existing->claim_token === null && ! $existing->is_claimed) {
                $existing->update(['claim_token' => $token]);
            }

            return [
                'listing' => $existing,
                'claim_url' => url('/sahiplen/'.$token),
                'claim_token' => $token,
            ];
        }

        return $this->createFromData([
            'name' => $target->name,
            'country_code' => $target->country ?? 'DE',
            'city' => $target->city ?? '',
            'category_name' => $target->category ?? $target->sector,
            'email' => $target->contact_email,
            'phone' => $target->detection_signals['phone'] ?? null,
            'website' => $target->website,
            'rating' => isset($target->detection_signals['rating']) ? (float) $target->detection_signals['rating'] : null,
            'review_count' => isset($target->detection_signals['review_count']) ? (int) $target->detection_signals['review_count'] : null,
            'photo_reference' => isset($target->detection_signals['photo_reference']) ? (string) $target->detection_signals['photo_reference'] : null,
            'source_external_id' => $target->external_id,
        ], $target);
    }

    /**
     * İşletme görselini optimize ederek (WebP) vitrin ilanı kapak görseli olarak iliştirir.
     */
    public function attachPhotoBytes(Listing $listing, string $binaryData): ?ListingImage
    {
        if (empty($binaryData)) {
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'places_photo_');
        if (! $tempFile) {
            return null;
        }

        file_put_contents($tempFile, $binaryData);

        try {
            $imageService = app(ImageService::class);
            $result = $imageService->storeOptimizedFromPath($tempFile, 'listings');

            $sizeBytes = 0;
            try {
                $sizeBytes = Storage::disk('public')->size($result['large']);
            } catch (\Throwable) {
                // ignore
            }

            return $listing->images()->create([
                'path_thumb' => $result['thumb'],
                'path_medium' => $result['medium'],
                'path_large' => $result['large'],
                'width' => $result['original_dimensions']['width'],
                'height' => $result['original_dimensions']['height'],
                'size_bytes' => $sizeBytes,
                'exif_metadata' => $result['exif_metadata'] ?? [],
                'had_gps' => $result['had_gps'] ?? false,
                'has_sensitive_exif' => $result['has_sensitive_exif'] ?? false,
                'gps_lat' => $result['gps_lat'] ?? null,
                'gps_lng' => $result['gps_lng'] ?? null,
                'sort_order' => 0,
                'is_cover' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Vitrin görseli oluşturulamadı: '.$e->getMessage());

            return null;
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Google Places New Photos endpoint'inden fotoğrafı çekip vitrine kapak görseli olarak kaydeder.
     */
    public function attachPhotoFromPlaces(Listing $listing, string $photoReference): ?ListingImage
    {
        try {
            $kesif = app(IsletmeKesfi::class);
            $bytes = $kesif->fotoIndir($photoReference);
            if ($bytes !== null) {
                return $this->attachPhotoBytes($listing, $bytes);
            }
        } catch (\Throwable $e) {
            Log::warning('Google Places fotoğrafı indirilemedi: '.$e->getMessage());
        }

        return null;
    }

    private function resolveSystemUser(): User
    {
        return User::firstOrCreate(
            ['email' => self::SYSTEM_BOT_EMAIL],
            [
                'name' => 'Nisoya Rehber',
                'username' => 'nisoya-rehber',
                'country_code' => 'TR',
                'preferred_currency' => 'TRY',
                'password' => bcrypt(Str::random(32)),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );
    }

    private function resolveCategoryId(?string $categoryName): int
    {
        if (filled($categoryName)) {
            $matched = Category::query()
                ->where('name', 'like', "%{$categoryName}%")
                ->orWhere('slug', 'like', "%{$categoryName}%")
                ->first();

            if ($matched) {
                return $matched->id;
            }
        }

        return (int) (Category::query()->where('is_active', true)->value('id') ?? 1);
    }

    private function generateDefaultDescription(
        string $name,
        string $city,
        string $countryCode,
        ?string $address,
        ?string $phone,
        ?string $website,
        ?float $rating = null,
        ?int $reviewCount = null,
    ): string {
        $lines = [
            "{$name}, {$city} ({$countryCode}) bölgesinde hizmet veren Türkçe konuşan işletmedir.",
        ];

        if ($rating !== null && $rating > 0) {
            $ratingText = '⭐ Google Puanı: '.number_format($rating, 1, '.', '');
            if ($reviewCount !== null && $reviewCount > 0) {
                $ratingText .= " ({$reviewCount} değerlendirme)";
            }
            $lines[] = $ratingText;
        }

        $lines[] = '';
        $lines[] = 'İletişim & Konum Bilgileri:';

        if (filled($address)) {
            $lines[] = "• Adres: {$address}";
        }
        if (filled($phone)) {
            $lines[] = "• Telefon: {$phone}";
        }
        if (filled($website)) {
            $lines[] = "• Web: {$website}";
        }

        $lines[] = '';
        $lines[] = '📢 Bu işletmenin sahibi misiniz? Profilinizi ücretsiz sahiplenerek bilgilerinizi güncelleyebilir ve yeni müşterilere ulaşabilirsiniz.';

        return implode("\n", $lines);
    }

    private function generateUniqueSlug(string $name, string $city): string
    {
        $base = Str::slug("{$name} {$city}");
        if ($base === '') {
            $base = 'isletme-'.Str::lower(Str::random(6));
        }

        $slug = $base;
        $counter = 1;

        while (Listing::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}

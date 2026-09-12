<?php

namespace App\Services\Growth;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Models\User;
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
            website: $data['website'] ?? null
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
            'website' => $target->website,
            'source_external_id' => $target->external_id,
        ], $target);
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
        ?string $website
    ): string {
        $lines = [
            "{$name}, {$city} ({$countryCode}) bölgesinde hizmet veren Türkçe konuşan işletmedir.",
            '',
            'İletişim & Konum Bilgileri:',
        ];

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

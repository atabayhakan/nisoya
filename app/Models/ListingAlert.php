<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ListingAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'keywords',
        'category_id',
        'country_code',
        'city',
        'type',
        'is_active',
        'last_notified_at',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * İlan ile uyarı eşleşiyor mu?
     */
    public function matchesListing(Listing $listing): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $listingType = $listing->type instanceof \BackedEnum
            ? $listing->type->value
            : (string) $listing->type;

        $alertType = $this->type instanceof \BackedEnum
            ? $this->type->value
            : (string) $this->type;

        if ($alertType !== '' && $alertType !== $listingType) {
            return false;
        }

        if ($this->category_id && (int) $this->category_id !== (int) $listing->category_id) {
            return false;
        }

        if ($this->country_code && strtoupper((string) $this->country_code) !== strtoupper((string) $listing->country_code)) {
            return false;
        }

        if ($this->city && self::trLower($this->city) !== self::trLower($listing->city)) {
            return false;
        }

        if ($this->keywords) {
            $keyword = self::trLower($this->keywords);
            $title = self::trLower($listing->title);
            $desc = self::trLower($listing->description);

            if (! str_contains($title, $keyword) && ! str_contains($desc, $keyword)) {
                return false;
            }
        }

        return true;
    }

    private static function trLower(?string $str): string
    {
        if ($str === null || $str === '') {
            return '';
        }

        $clean = str_replace(['İ', 'I', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'], ['i', 'ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], $str);

        return Str::lower($clean);
    }
}

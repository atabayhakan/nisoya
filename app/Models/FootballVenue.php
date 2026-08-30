<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FootballVenue extends Model
{
    use HasFactory;

    public const FEATURE_OPTIONS = [
        'soyunma_odasi' => 'Soyunma Odası',
        'dus' => 'Duş',
        'otopark' => 'Otopark',
        'gece_aydinlatmasi' => 'Gece Aydınlatması',
        'kafe' => 'Kafe / Büfe',
        'krampon_kiralama' => 'Krampon Kiralama',
        'yelek_top' => 'Yelek & Top Temini',
        'tribun' => 'Seyirci Tribünü',
        'kapali_saha' => 'Kapalı Saha',
    ];

    public const PITCH_TYPES = [
        'kapali' => 'Kapalı Saha',
        'acik' => 'Açık Saha',
        'yari_acik' => 'Yarı Açık / Brandalı',
    ];

    public const SURFACE_TYPES = [
        'suni_cim' => 'Suni Çim',
        'dogal_cim' => 'Doğal Çim',
        'parke' => 'Salon / Parke',
        'hali' => 'Klasik Halı',
    ];

    protected $fillable = [
        'created_by_id',
        'name',
        'slug',
        'city',
        'country_code',
        'address',
        'latitude',
        'longitude',
        'phone',
        'website',
        'pitch_type',
        'surface_type',
        'features',
        'opening_hours',
        'price_info',
        'cover_image_path',
        'rating',
        'reviews_count',
        'is_active',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $venue) {
            if (empty($venue->slug)) {
                $base = Str::slug($venue->name);
                $slug = $base;
                $i = 1;
                while (self::query()->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $venue->slug = $slug;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FootballVenueReview::class, 'venue_id');
    }

    public function publishedReviews(): HasMany
    {
        return $this->hasMany(FootballVenueReview::class, 'venue_id')->where('status', 'yayinda');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'venue_id');
    }

    public function recalculateRating(): void
    {
        $reviews = $this->publishedReviews();
        $count = $reviews->count();
        $avg = $count > 0 ? $reviews->avg('rating') : 5.00;

        $this->update([
            'rating' => round($avg, 2),
            'reviews_count' => $count,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCity($query, ?string $city)
    {
        if (empty($city)) {
            return $query;
        }

        return $query->whereRaw('LOWER(city) = ?', [mb_strtolower(trim($city))]);
    }
}

<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Notifications\ListingStatusNotification;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property Carbon|null $featured_until
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'title',
        'slug',
        'description',
        'price',
        'currency',
        'price_unit',
        'country_code',
        'city',
        'latitude',
        'longitude',
        'is_remote',
        'stock',
        'status',
        'is_featured',
        'featured_until',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'type' => ListingType::class,
            'price_unit' => PriceUnit::class,
            'status' => ListingStatus::class,
            'price' => 'decimal:2',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_remote' => 'boolean',
            'is_featured' => 'boolean',
            'featured_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // İlan durumu (moderasyon) değişince sahibini bilgilendir.
        static::updated(function (self $listing) {
            if ($listing->wasChanged('status')) {
                $listing->user?->notify(new ListingStatusNotification(
                    $listing->title,
                    $listing->status->getLabel(),
                    route('listings.show', [$listing->id, $listing->slug]),
                ));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_cover', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'listing_tag');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function featureRequests(): HasMany
    {
        return $this->hasMany(FeatureRequest::class);
    }

    /** İlan şu an gerçekten öne çıkan mı (süre dolmamış). */
    public function isCurrentlyFeatured(): bool
    {
        return $this->is_featured && (is_null($this->featured_until) || $this->featured_until->isFuture());
    }

    /** Yalnızca yayında olan (aktif) ilanlar. */
    public function scopeActive($query)
    {
        return $query->where('status', ListingStatus::Aktif->value);
    }

    /** Activity log: status + featured değişikliklerini logla. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'is_featured', 'featured_until', 'is_remote', 'stock', 'price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "İlan {$eventName}");
    }
}

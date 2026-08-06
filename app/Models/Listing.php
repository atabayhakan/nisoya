<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Notifications\ListingStatusNotification;
use App\Support\Para;
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
 * @property ListingType $type
 * @property PriceUnit|null $price_unit casts() enum'a çeviriyor; docblock'suz
 *                                      kalınca statik analiz kolonu düz `string` sanıyor ve enum
 *                                      metotlarına (suffix() vb.) erişimi hata sayıyor.
 * @property Carbon|null $featured_until
 * @property-read User|null $user
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
        'width_cm',
        'height_cm',
        'status',
        'is_demo',
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
            'is_demo' => 'boolean',
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

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return HasMany<ListingImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    /** @return HasOne<ListingImage, $this> */
    public function coverImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_cover', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'listing_tag');
    }

    /**
     * Emlak ilanının 1:1 detay kaydı (tip=emlak dışında null).
     *
     * @return HasOne<ListingPropertyDetail, $this>
     */
    public function propertyDetail(): HasOne
    {
        return $this->hasOne(ListingPropertyDetail::class);
    }

    /**
     * Vasıta ilanının 1:1 detay kaydı (tip=vasita dışında null).
     *
     * @return HasOne<ListingVehicleDetail, $this>
     */
    public function vehicleDetail(): HasOne
    {
        return $this->hasOne(ListingVehicleDetail::class);
    }

    /**
     * Takvimde "dolu" işaretlenen tarih aralıkları (emlak kısa dönem + kiralık araç).
     *
     * @return HasMany<ListingUnavailableRange, $this>
     */
    public function unavailableRanges(): HasMany
    {
        return $this->hasMany(ListingUnavailableRange::class)->orderBy('starts_on');
    }

    /** Verilen tarih aralığı bu ilanın takviminde tamamen boş mu? */
    public function isAvailableBetween(string $from, string $to): bool
    {
        return ! $this->unavailableRanges()->overlapping($from, $to)->exists();
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

    /**
     * Fiyat, Türkçe biçimde — para birimi HARİÇ (çağıran taraf ekler).
     *
     * Fiyatsız ilan `null` döner; sayfalar orada "Görüşülür" basar. Biçim
     * kuralı ve neden tek yerde durduğu {@see Para} içinde.
     */
    public function bicimliFiyat(): ?string
    {
        return Para::bicimle($this->price);
    }

    /**
     * İlan arşivde mi — yayından kalkmış ama hâlâ görülebilir.
     *
     * Arşiv kipi 2026-08-06'da eklendi: satıcının geçmiş ilanlarını
     * listelemek, o ilanların açılabilmesi demektir (aksi hâlde her kart
     * 404'e giderdi). Arşiv sayfasında iletişim ve favori kapalıdır ve
     * sayfa arama motoruna kapatılır.
     *
     * DİKKAT: `Pasif` bir MODERASYON aracı değildir. Bir ilanı gizlemek
     * gerekiyorsa `Reddedildi` kullanılmalı — Pasif olan ilan bu tarihten
     * sonra herkese açıktır.
     */
    public function arsivdeMi(): bool
    {
        return $this->status === ListingStatus::Pasif;
    }

    /** Yalnızca yayında olan (aktif) ilanlar. */
    public function scopeActive($query)
    {
        return $query->where('status', ListingStatus::Aktif->value);
    }

    /**
     * Öne çıkanları ÜSTTE sıralar — ama yalnızca SÜRESİ GEÇMEMİŞ olanları.
     * Ham is_featured'a göre sıralamak, süresi dolan ilanları günlük
     * expire komutuna kadar ~24 saate dek haksız yere üstte tutuyordu
     * (bkz. isCurrentlyFeatured / denetim). SQLite + MySQL uyumlu.
     */
    public function scopeOrderByFeatured($query)
    {
        return $query->orderByRaw(
            '(is_featured = 1 and (featured_until is null or featured_until > ?)) desc',
            [now()]
        );
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

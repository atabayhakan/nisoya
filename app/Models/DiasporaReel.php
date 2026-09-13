<?php

namespace App\Models;

use App\Support\InstagramMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $account_id
 * @property string $title
 * @property ?string $caption
 * @property string $instagram_url
 * @property ?string $shortcode
 * @property ?string $instagram_username
 * @property ?string $country_code
 * @property ?string $city
 * @property ?string $category
 * @property ?int $safety_score
 * @property string $safety_status
 * @property int $engagement_score
 * @property int $views_count
 * @property int $likes_count
 * @property ?string $thumbnail_url
 * @property ?string $video_url
 * @property bool $is_featured
 * @property int $sort_order
 * @property bool $is_active
 * @property string $status
 * @property ?Country $country
 * @property ?DiasporaAccount $account
 */
class DiasporaReel extends Model
{
    use HasFactory;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ARCHIVED = 'archived';

    public const CATEGORY_GASTRONOMI = 'gastronomi';

    public const CATEGORY_ETKINLIK = 'etkinlik';

    public const CATEGORY_SPOR = 'spor';

    public const CATEGORY_REHBER = 'rehber';

    public const CATEGORY_TOPLULUK = 'topluluk';

    public const CATEGORY_GENEL = 'genel';

    protected $fillable = [
        'account_id',
        'title',
        'caption',
        'instagram_url',
        'shortcode',
        'instagram_username',
        'country_code',
        'city',
        'category',
        'safety_score',
        'safety_status',
        'engagement_score',
        'views_count',
        'likes_count',
        'thumbnail_url',
        'video_url',
        'is_featured',
        'sort_order',
        'is_active',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'safety_score' => 'integer',
            'engagement_score' => 'integer',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $reel): void {
            if ($reel->instagram_url && ! $reel->shortcode) {
                $reel->shortcode = InstagramMedia::extractShortcode($reel->instagram_url);
            }
            if ($reel->instagram_username) {
                $reel->instagram_username = InstagramMedia::normalizeUsername($reel->instagram_username);
            }
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(DiasporaAccount::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', self::STATUS_PUBLISHED);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        return InstagramMedia::embedUrl($this->shortcode ?: $this->instagram_url);
    }

    public function displayLocation(): string
    {
        $parts = array_filter([
            $this->city,
            $this->country?->name_tr,
        ]);

        return implode(', ', $parts);
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GASTRONOMI => '🍽️ Gastronomi & Lezzet',
            self::CATEGORY_ETKINLIK => '🎉 Etkinlik & Festival',
            self::CATEGORY_SPOR => '⚽ Spor & Halı Saha',
            self::CATEGORY_REHBER => '💡 Gurbet Rehberi',
            self::CATEGORY_TOPLULUK => '🤝 Topluluk & Dayanışma',
            self::CATEGORY_GENEL => '🌐 Genel Paylaşım',
        ];
    }
}

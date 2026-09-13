<?php

namespace App\Models;

use App\Support\InstagramMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property ?string $caption
 * @property string $instagram_url
 * @property ?string $shortcode
 * @property ?string $instagram_username
 * @property ?string $country_code
 * @property ?string $city
 * @property ?string $thumbnail_url
 * @property ?string $video_url
 * @property bool $is_featured
 * @property int $sort_order
 * @property bool $is_active
 * @property ?Country $country
 */
class DiasporaReel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'caption',
        'instagram_url',
        'shortcode',
        'instagram_username',
        'country_code',
        'city',
        'thumbnail_url',
        'video_url',
        'is_featured',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
}

<?php

namespace App\Models;

use App\Models\Concerns\NormalizesCityName;
use App\Support\InstagramMedia;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $username
 * @property ?string $title
 * @property ?string $description
 * @property ?string $country_code
 * @property ?string $city
 * @property ?string $profile_pic_url
 * @property bool $is_active
 * @property bool $is_verified
 * @property bool $autopilot
 * @property int $reels_count
 * @property ?CarbonInterface $last_synced_at
 * @property ?Country $country
 * @property Collection<int, DiasporaReel> $reels
 */
class DiasporaAccount extends Model
{
    use HasFactory;
    use NormalizesCityName;

    protected $fillable = [
        'username',
        'title',
        'description',
        'country_code',
        'city',
        'profile_pic_url',
        'is_active',
        'is_verified',
        'autopilot',
        'reels_count',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'autopilot' => 'boolean',
            'reels_count' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            if ($account->username) {
                $account->username = InstagramMedia::normalizeUsername($account->username) ?? $account->username;
            }
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function reels(): HasMany
    {
        return $this->hasMany(DiasporaReel::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAutopilot(Builder $query): Builder
    {
        return $query->where('autopilot', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function getInstagramUrlAttribute(): string
    {
        $clean = ltrim($this->username, '@');

        return "https://www.instagram.com/{$clean}/";
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

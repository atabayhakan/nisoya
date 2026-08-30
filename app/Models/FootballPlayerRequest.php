<?php

namespace App\Models;

use App\Enums\FootballLevel;
use App\Enums\FootballRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property FootballRequestType $type
 * @property FootballLevel|null $level
 * @property array<string>|null $positions
 * @property-read User|null $user
 * @property-read FootballTeam|null $team
 * @property-read FootballMatch|null $match
 */
class FootballPlayerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'match_id',
        'type',
        'city',
        'country_code',
        'match_time',
        'venue_name',
        'needed_count',
        'level',
        'positions',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => FootballRequestType::class,
            'level' => FootballLevel::class,
            'positions' => 'array',
            'match_time' => 'datetime',
            'needed_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<FootballTeam, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }

    /** @return BelongsTo<FootballMatch, $this> */
    public function match(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(FootballPlayerRequestApplication::class, 'request_id');
    }

    public function isLookingForPlayer(): bool
    {
        return $this->type === FootballRequestType::OyuncuAraniyor;
    }

    public function isLookingForMatch(): bool
    {
        return $this->type === FootballRequestType::MacAriyorum;
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

<?php

namespace App\Models;

use App\Enums\FootballLevel;
use App\Enums\FootballPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property FootballLevel $level
 * @property array<string>|null $positions
 */
class FootballPlayerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city',
        'country_code',
        'positions',
        'preferred_foot',
        'level',
        'bio',
        'is_looking_for_team',
        'is_looking_for_match',
        'matches_played',
        'goals',
        'assists',
        'wins',
        'rating',
        'ratings_count',
    ];

    protected function casts(): array
    {
        return [
            'positions' => 'array',
            'level' => FootballLevel::class,
            'is_looking_for_team' => 'boolean',
            'is_looking_for_match' => 'boolean',
            'matches_played' => 'integer',
            'goals' => 'integer',
            'assists' => 'integer',
            'wins' => 'integer',
            'rating' => 'decimal:2',
            'ratings_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * @return list<FootballPosition>
     */
    public function positionEnums(): array
    {
        if (empty($this->positions)) {
            return [];
        }

        $enums = [];
        foreach ($this->positions as $pos) {
            $enum = FootballPosition::tryFrom($pos);
            if ($enum) {
                $enums[] = $enum;
            }
        }

        return $enums;
    }

    public function scopeLookingForTeam($query)
    {
        return $query->where('is_looking_for_team', true);
    }

    public function scopeLookingForMatch($query)
    {
        return $query->where('is_looking_for_match', true);
    }

    public function scopeCity($query, ?string $city)
    {
        if (empty($city)) {
            return $query;
        }

        return $query->whereRaw('LOWER(city) = ?', [mb_strtolower(trim($city))]);
    }
}

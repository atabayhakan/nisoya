<?php

namespace App\Models;

use App\Enums\FootballLevel;
use App\Enums\FootballMemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property FootballLevel $level
 */
class FootballTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'city',
        'country_code',
        'logo_path',
        'primary_kit_color',
        'secondary_kit_color',
        'level',
        'description',
        'is_verified',
        'is_active',
        'matches_count',
        'wins_count',
        'draws_count',
        'losses_count',
        'goals_for',
        'goals_against',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'level' => FootballLevel::class,
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'matches_count' => 'integer',
            'wins_count' => 'integer',
            'draws_count' => 'integer',
            'losses_count' => 'integer',
            'goals_for' => 'integer',
            'goals_against' => 'integer',
            'points' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $team) {
            if (empty($team->slug)) {
                $base = Str::slug($team->name);
                $slug = $base;
                $i = 1;
                while (self::query()->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $team->slug = $slug;
            }
        });
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function members(): HasMany
    {
        return $this->hasMany(FootballTeamMember::class, 'team_id');
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(FootballTeamMember::class, 'team_id')
            ->where('status', FootballMemberStatus::Aktif->value);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }

    public function playerRequests(): HasMany
    {
        return $this->hasMany(FootballPlayerRequest::class, 'team_id');
    }

    public function goalDifference(): int
    {
        return (int) $this->goals_for - (int) $this->goals_against;
    }

    public function isCaptain(User|int|null $user): bool
    {
        if (! $user) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : (int) $user;

        return (int) $this->user_id === $userId;
    }

    public function hasMember(User|int|null $user): bool
    {
        if (! $user) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : (int) $user;

        return $this->activeMembers()->where('user_id', $userId)->exists();
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

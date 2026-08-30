<?php

namespace App\Models;

use App\Enums\FootballMatchStatus;
use App\Enums\FootballResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property FootballMatchStatus $status
 * @property FootballResultStatus $result_status
 * @property Carbon $match_date
 * @property-read FootballTeam|null $homeTeam
 * @property-read FootballTeam|null $awayTeam
 * @property-read FootballVenue|null $venue
 * @property-read Country|null $country
 * @property-read User|null $resultSubmittedBy
 * @property-read User|null $resultVerifiedBy
 * @property-read User|null $mvpPlayer
 */
class FootballMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'venue_id',
        'venue_custom_name',
        'city',
        'country_code',
        'match_date',
        'status',
        'description',
        'home_score',
        'away_score',
        'result_status',
        'result_submitted_by_id',
        'result_verified_by_id',
        'dispute_reason',
        'mvp_player_id',
        'home_scorers',
        'away_scorers',
        'is_featured',
        'news_title',
        'news_summary',
        'news_body',
        'news_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'datetime',
            'news_generated_at' => 'datetime',
            'status' => FootballMatchStatus::class,
            'result_status' => FootballResultStatus::class,
            'home_score' => 'integer',
            'away_score' => 'integer',
            'home_scorers' => 'array',
            'away_scorers' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    /** @return BelongsTo<FootballTeam, $this> */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(FootballTeam::class, 'home_team_id');
    }

    /** @return BelongsTo<FootballTeam, $this> */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(FootballTeam::class, 'away_team_id');
    }

    /** @return BelongsTo<FootballVenue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(FootballVenue::class, 'venue_id');
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return BelongsTo<User, $this> */
    public function resultSubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_submitted_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resultVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_verified_by_id');
    }

    /** @return BelongsTo<User, $this> */
    public function mvpPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mvp_player_id');
    }

    /** @return HasMany<FootballPlayerRequest, $this> */
    public function playerRequests(): HasMany
    {
        return $this->hasMany(FootballPlayerRequest::class, 'match_id');
    }

    public function isVerified(): bool
    {
        return $this->result_status === FootballResultStatus::Dogrulandi;
    }

    public function isPlayed(): bool
    {
        return $this->status === FootballMatchStatus::Oynandi;
    }

    public function venueDisplay(): string
    {
        return $this->venue?->name ?: ($this->venue_custom_name ?: 'Halı Saha');
    }

    public function scopeVerified($query)
    {
        return $query->where('result_status', FootballResultStatus::Dogrulandi->value);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeCity($query, ?string $city)
    {
        if (empty($city)) {
            return $query;
        }

        return $query->whereRaw('LOWER(city) = ?', [mb_strtolower(trim($city))]);
    }
}

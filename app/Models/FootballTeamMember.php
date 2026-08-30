<?php

namespace App\Models;

use App\Enums\FootballMemberStatus;
use App\Enums\FootballPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property FootballMemberStatus $status
 * @property FootballPosition|null $primary_position
 * @property-read FootballTeam|null $team
 * @property-read User|null $user
 */
class FootballTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'status',
        'jersey_number',
        'primary_position',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FootballMemberStatus::class,
            'primary_position' => FootballPosition::class,
            'jersey_number' => 'integer',
            'joined_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<FootballTeam, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isCaptain(): bool
    {
        return $this->role === 'captain';
    }
}

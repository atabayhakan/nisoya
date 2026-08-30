<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FootballPlayerRequestApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'user_id',
        'status',
        'message',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(FootballPlayerRequest::class, 'request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

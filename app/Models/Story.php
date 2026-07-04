<?php

namespace App\Models;

use App\Enums\StoryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Story extends Model
{
    /** NabizService::approvedStories() varsayılan limit — cache anahtarı bununla eşleşmeli. */
    public const APPROVED_CACHE_KEY = 'nabiz_stories_12';

    protected $fillable = [
        'user_id',
        'body',
        'status',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::APPROVED_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::APPROVED_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'status' => StoryStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

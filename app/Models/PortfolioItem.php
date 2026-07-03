<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PortfolioItem extends Model
{
    protected $fillable = [
        'user_id',
        'path_thumb',
        'path_medium',
        'path_large',
        'caption',
        'sort_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Belirli bir varyant için URL döndür (fallback: medium). */
    public function url(string $variant = 'medium'): ?string
    {
        $map = [
            'thumb' => $this->path_thumb,
            'medium' => $this->path_medium,
            'large' => $this->path_large,
        ];

        $path = $map[$variant] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Varyant path'lerini döndür (silme işlemleri için).
     *
     * @return array<int, string>
     */
    public function variantPaths(): array
    {
        return array_values(array_filter([
            $this->path_thumb,
            $this->path_medium,
            $this->path_large,
        ]));
    }
}

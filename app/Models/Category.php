<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    /** Header'daki "Acil" hızlı-erişim butonunun listelediği kategori grubu. */
    public const EMERGENCY_SLUG = 'acil-yardim';

    public const EMERGENCY_CACHE_KEY = 'emergency_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'icon',
        'type',
        'sort_order',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::EMERGENCY_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::EMERGENCY_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YasamKategorisi extends Model
{
    protected $table = 'yasam_kategorileri';

    protected $fillable = ['ad', 'slug', 'ikon', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<YasamKonusu, $this> */
    public function konular(): HasMany
    {
        return $this->hasMany(YasamKonusu::class, 'kategori_id');
    }

    /** @param Builder<YasamKategorisi> $query */
    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

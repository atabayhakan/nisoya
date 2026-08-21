<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YasamKonusu extends Model
{
    protected $table = 'yasam_konulari';

    protected $fillable = ['kategori_id', 'baslik', 'slug', 'kisa_aciklama', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<YasamKategorisi, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(YasamKategorisi::class, 'kategori_id');
    }

    /** @return HasMany<YasamKonuIcerigi, $this> */
    public function icerikler(): HasMany
    {
        return $this->hasMany(YasamKonuIcerigi::class, 'yasam_konusu_id');
    }

    /** @param Builder<YasamKonusu> $query */
    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

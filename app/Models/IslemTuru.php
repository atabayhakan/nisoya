<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Konsolosluk/noter işlem türü şablonu — ÜLKE-BAĞIMSIZ.
 *
 * Vekaletname her ülkede vekaletnamedir; ülkeye/temsilciliğe özgü içerik
 * (evrak listesi, ücret, süre) TemsilcilikIslemi'nde yaşar. Yeni ülke
 * eklerken bu tablo yeniden kurulmaz (tasarım §3).
 */
class IslemTuru extends Model
{
    protected $table = 'islem_turleri';

    protected $fillable = ['ad', 'slug', 'aciklama', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<TemsilcilikIslemi, $this> */
    public function islemler(): HasMany
    {
        return $this->hasMany(TemsilcilikIslemi::class, 'islem_turu_id');
    }

    /** @param Builder<IslemTuru> $query */
    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

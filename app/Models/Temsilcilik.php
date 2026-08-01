<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bir ülkedeki Türk dış temsilciliği (büyükelçilik/başkonsolosluk).
 *
 * URL'deki ikinci segment bu modelin slug'ıdır: /de/koeln → DE + "koeln".
 * Şehirle karıştırılmamalı — Köln Başkonsolosluğu bir kurumdur ve görev
 * bölgesi şehirden geniştir (tasarım K2).
 */
class Temsilcilik extends Model
{
    public const TUR_BUYUKELCILIK = 'buyukelcilik';

    public const TUR_BASKONSOLOSLUK = 'baskonsolosluk';

    protected $table = 'temsilcilikler';

    protected $fillable = [
        'country_code', 'ad', 'slug', 'tur', 'sehir', 'adres',
        'latitude', 'longitude', 'resmi_url', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return HasMany<TemsilcilikIslemi, $this> */
    public function islemler(): HasMany
    {
        return $this->hasMany(TemsilcilikIslemi::class, 'temsilcilik_id');
    }

    /** @param Builder<Temsilcilik> $query */
    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function turEtiketi(): string
    {
        return $this->tur === self::TUR_BUYUKELCILIK ? 'Büyükelçilik' : 'Başkonsolosluk';
    }
}

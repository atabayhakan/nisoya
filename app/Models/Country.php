<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Country extends Model
{
    /** Header'daki "Acil" butonunun ülke seçicisinde kullanılan liste. */
    public const ACTIVE_LIST_CACHE_KEY = 'active_countries';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::ACTIVE_LIST_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::ACTIVE_LIST_CACHE_KEY));
    }

    protected $fillable = [
        'code',
        'name_tr',
        'emoji',
        'default_currency',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_code', 'code')->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'country_code', 'code');
    }

    /** @return HasMany<Temsilcilik, $this> */
    public function temsilcilikler(): HasMany
    {
        return $this->hasMany(Temsilcilik::class, 'country_code', 'code');
    }

    /** @return HasMany<YasamKonuIcerigi, $this> */
    public function yasamIcerikleri(): HasMany
    {
        return $this->hasMany(YasamKonuIcerigi::class, 'country_code', 'code');
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency', 'code');
    }
}

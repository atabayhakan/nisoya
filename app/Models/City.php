<?php

namespace App\Models;

use App\Support\GlobalCommand\CityName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected static function booted(): void
    {
        static::saved(function (self $city): void {
            if ($city->wasRecentlyCreated || $city->wasChanged('name')) {
                $city->aliases()->update(['is_canonical' => false]);
                $city->aliases()->updateOrCreate(['normalized_name' => CityName::normalize($city->name)],
                    ['name' => $city->name, 'is_canonical' => true]);
            }
        });
    }

    /** @return HasMany<CityNameAlias, $this> */
    public function aliases(): HasMany
    {
        return $this->hasMany(CityNameAlias::class);
    }

    protected $fillable = [
        'country_code',
        'name',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}

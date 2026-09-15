<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityNameAlias extends Model
{
    protected $fillable = ['city_id', 'name', 'normalized_name', 'is_canonical'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}

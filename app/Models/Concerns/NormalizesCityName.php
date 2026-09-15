<?php

namespace App\Models\Concerns;

use App\Support\GlobalCommand\CityName;

trait NormalizesCityName
{
    public function setCityAttribute(?string $value): void
    {
        $this->attributes['city'] = $value;
        $this->attributes['city_key'] = CityName::key($value);
    }
}

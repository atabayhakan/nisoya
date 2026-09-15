<?php

namespace App\Support\GlobalCommand;

use App\Models\City;
use App\Models\Country;

class GeoNameResolver
{
    public function detect(string $text, ?string $fallbackCountry = null): array
    {
        $text = CityName::normalize($text);
        $country = Country::where('is_active', true)->whereKey($fallbackCountry)->value('code');
        $matches = [];
        $cities = City::where('is_active', true)->whereHas('country', fn ($q) => $q->where('is_active', true))
            ->when($fallbackCountry !== null, fn ($q) => $q->where('country_code', $country ?? ''))
            ->with('aliases')->lazyById(250);
        foreach ($cities as $city) {
            foreach ($city->aliases->pluck('normalized_name')->push(CityName::normalize($city->name))->unique() as $name) {
                if ($name !== '' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($name, '/').'(?![\p{L}\p{N}])/u', $text)) {
                    $matches[$city->id] = ['country_code' => $city->country_code, 'city' => $city->name];
                    break;
                }
            }
        }
        if (count($matches) === 1) {
            return array_values($matches)[0];
        }
        if ($country !== null) {
            return ['country_code' => $country, 'city' => null];
        }
        $countries = Country::where('is_active', true)->get(['code', 'name_tr'])->filter(function ($candidate) use ($text) {
            $name = CityName::normalize($candidate->name_tr);

            return $name !== '' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($name, '/').'(?![\p{L}\p{N}])/u', $text);
        });

        return ['country_code' => $countries->count() === 1 ? $countries->first()->code : null, 'city' => null];
    }
}

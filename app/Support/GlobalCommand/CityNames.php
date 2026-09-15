<?php

namespace App\Support\GlobalCommand;

use App\Enums\UserStatus;
use App\Models\City;
use App\Models\CityNameAlias;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CityNames
{
    public function keys(City $city): array
    {
        $own = $city->aliases()->pluck('normalized_name');
        $unambiguous = CityNameAlias::whereIn('normalized_name', $own)
            ->whereHas('city', fn ($q) => $q->where('country_code', $city->country_code)->where('is_active', true))
            ->select('normalized_name')->groupBy('normalized_name')->havingRaw('COUNT(DISTINCT city_id) = 1')
            ->pluck('normalized_name');

        return $unambiguous->map(fn ($name) => CityName::key($name))->filter()->sort()->values()->all();
    }

    public function add(int $cityId, string $name, User $actor): CityNameAlias
    {
        abort_unless($actor->isAdmin() && $actor->status === UserStatus::Aktif, 403);
        Validator::make(['name' => $name], ['name' => 'required|string|max:255'])->validate();
        $normalized = CityName::normalize($name);
        if ($normalized === '') {
            throw ValidationException::withMessages(['name' => 'Bir şehir adı girin.']);
        }

        return DB::transaction(function () use ($cityId, $name, $normalized, $actor): CityNameAlias {
            $city = City::where('is_active', true)->findOrFail($cityId);
            // Serialize competing aliases within the same country.
            Country::whereKey($city->country_code)->where('is_active', true)->lockForUpdate()->firstOrFail();
            if (CityNameAlias::where('normalized_name', $normalized)->where('city_id', '!=', $city->id)
                ->whereHas('city', fn ($q) => $q->where('country_code', $city->country_code))->exists()) {
                throw ValidationException::withMessages(['name' => 'Bu ad aynı ülkede başka bir şehre bağlı.']);
            }
            $alias = $city->aliases()->firstOrCreate(['normalized_name' => $normalized], ['name' => trim($name)]);
            if ($alias->wasRecentlyCreated) {
                activity('global-command')->causedBy($actor)->performedOn($alias)->log('city.alias.added');
            }

            return $alias;
        });
    }
}

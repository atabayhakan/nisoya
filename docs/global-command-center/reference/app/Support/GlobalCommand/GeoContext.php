<?php

namespace App\Support\GlobalCommand;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** A reporting lens, never an authorization boundary or a model-wide scope. */
final readonly class GeoContext
{
    public const SESSION_KEY = 'admin.geo.v1';

    private function __construct(
        public string $mode = 'global',
        public ?int $regionId = null,
        public ?string $countryCode = null,
        public ?int $cityId = null,
        public ?string $cityName = null,
        private string $description = 'Tüm dünya',
    ) {}

    public static function global(): self
    {
        return new self;
    }

    public static function fromSelection(array $input): self
    {
        $data = Validator::make($input, [
            'mode' => ['required', Rule::in(['global', 'region', 'country', 'city'])],
            'region_id' => ['nullable', 'integer', 'min:1'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'city_id' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        if ($data['mode'] === 'global') {
            return self::global();
        }

        if ($data['mode'] === 'region') {
            $region = DB::table('geo_regions')->where('id', $data['region_id'] ?? null)
                ->where('is_active', true)->first();
            if (! $region) {
                throw ValidationException::withMessages(['regionId' => 'Aktif bir bölge seçin.']);
            }

            return new self('region', (int) $region->id, description: $region->name_tr);
        }

        $country = Country::query()->where('is_active', true)
            ->whereKey($data['country_code'] ?? null)->first();
        if (! $country) {
            throw ValidationException::withMessages(['countryCode' => 'Aktif bir ülke seçin.']);
        }

        if ($data['mode'] === 'country') {
            return new self('country', countryCode: $country->code, description: $country->name_tr);
        }

        $city = City::query()->where('country_code', $country->code)->where('is_active', true)
            ->whereKey($data['city_id'] ?? null)->first();
        if (! $city) {
            throw ValidationException::withMessages(['cityId' => 'Bu ülkeye ait aktif bir şehir seçin.']);
        }

        // Existing business records store city names. A city_id migration is a later phase.
        return new self('city', countryCode: $country->code, cityId: (int) $city->id,
            cityName: $city->name, description: $country->name_tr.' / '.$city->name);
    }

    public function selection(): array
    {
        return ['mode' => $this->mode, 'region_id' => $this->regionId,
            'country_code' => $this->countryCode, 'city_id' => $this->cityId];
    }

    public function key(): string
    {
        return hash('sha256', json_encode([$this->selection(), $this->cityName], JSON_THROW_ON_ERROR));
    }

    public function label(): string
    {
        return $this->description;
    }

    /** Only use for models with country_code and city columns. */
    public function apply(Builder $query): Builder
    {
        $countryColumn = $query->getModel()->qualifyColumn('country_code');
        if ($this->mode === 'region') {
            $query->whereIn($countryColumn, $this->regionCountries());
        } elseif ($this->countryCode !== null) {
            $query->where($countryColumn, $this->countryCode);
        }
        if ($this->mode === 'city') {
            $query->where($query->getModel()->qualifyColumn('city'), $this->cityName);
        }

        return $query;
    }

    public function countries(): Builder
    {
        $query = Country::query()->where('is_active', true);
        if ($this->mode === 'region') {
            $query->whereIn('code', $this->regionCountries());
        } elseif ($this->countryCode !== null) {
            $query->whereKey($this->countryCode);
        }

        return $query;
    }

    private function regionCountries(): \Illuminate\Database\Query\Builder
    {
        return DB::table('geo_region_country')->select('country_code')->where('geo_region_id', $this->regionId);
    }
}

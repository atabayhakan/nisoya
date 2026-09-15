<?php

namespace App\Livewire\Admin;

use App\Enums\UserStatus;
use App\Filament\Pages\GlobalCommandCenter;
use App\Models\City;
use App\Models\Country;
use App\Support\GlobalCommand\GeoContext;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GeoSwitcher extends Component
{
    public string $mode = 'global';

    public ?string $regionId = null;

    public ?string $countryCode = null;

    public ?string $cityId = null;

    public string $countrySearch = '';

    public string $citySearch = '';

    public function boot(): void
    {
        abort_unless(config('global-command.enabled') && auth()->user()?->isAdmin()
            && auth()->user()->status === UserStatus::Aktif, 403);
    }

    public function mount(): void
    {
        $context = app(GeoContext::class);
        $this->mode = $context->mode;
        $this->regionId = $context->regionId === null ? null : (string) $context->regionId;
        $this->countryCode = $context->countryCode;
        $this->cityId = $context->cityId === null ? null : (string) $context->cityId;
    }

    public function updatedCountryCode(): void
    {
        $this->cityId = null;
        $this->citySearch = '';
    }

    public function apply(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $context = GeoContext::fromSelection([
            'mode' => $this->mode, 'region_id' => $this->regionId ?: null,
            'country_code' => $this->countryCode ?: null, 'city_id' => $this->cityId ?: null,
        ]);
        session()->put(GeoContext::SESSION_KEY, [
            'actor_id' => auth()->id(), 'selection' => $context->selection(),
        ]);
        // Full reload deliberately clears existing table selection and pagination.
        // Never redirect using a client-supplied return URL.
        $this->redirect(GlobalCommandCenter::getUrl(), navigate: false);
    }

    public function resetToGlobal(): void
    {
        $this->mode = 'global';
        $this->apply();
    }

    public function render()
    {
        $search = mb_substr(trim($this->countrySearch), 0, 80);
        $countries = Country::query()->where('is_active', true)
            ->where(function ($query) use ($search): void {
                $query->where('name_tr', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
                if ($this->countryCode) {
                    $query->orWhere('code', $this->countryCode);
                }
            })->orderByRaw('case when code = ? then 0 else 1 end', [$this->countryCode ?? ''])
            ->orderBy('name_tr')->limit(40)->get(['code', 'name_tr']);

        $cities = City::query()->where('is_active', true)->where('country_code', $this->countryCode)
            ->where(function ($query): void {
                $query->where('name', 'like', '%'.mb_substr(trim($this->citySearch), 0, 80).'%');
                if ($this->cityId) {
                    $query->orWhere('id', $this->cityId);
                }
            })->orderByRaw('case when id = ? then 0 else 1 end', [$this->cityId ?: 0])
            ->orderBy('name')->limit(40)->get(['id', 'name']);

        return view('livewire.admin.geo-switcher', [
            'context' => app(GeoContext::class), 'countries' => $countries, 'cities' => $cities,
            'regions' => DB::table('geo_regions')->where('is_active', true)->orderBy('name_tr')->get(['id', 'name_tr']),
        ]);
    }
}

<?php

namespace App\Filament\Pages;

use App\Enums\UserStatus;
use App\Models\City;
use App\Models\CityNameAlias;
use App\Support\GlobalCommand\CityName;
use App\Support\GlobalCommand\CityNames;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use UnitEnum;

class CityNameManager extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'Pazarlama & Büyüme';

    protected static ?string $title = 'Şehir Adları';

    protected static ?string $slug = 'sehir-adlari';

    protected string $view = 'filament.pages.city-name-manager';

    public string $search = '';

    public string $selectedCityId = '';

    public string $aliasName = '';

    public static function canAccess(): bool
    {
        return config('global-command.enabled') && auth()->user()?->isAdmin()
            && auth()->user()->status === UserStatus::Aktif;
    }

    public function boot(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage('aliasesPage');
    }

    public function addAlias(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->validate(['selectedCityId' => ['required', 'integer', 'min:1'], 'aliasName' => ['required', 'string', 'max:255']]);
        app(CityNames::class)->add((int) $this->selectedCityId, $this->aliasName, auth()->user());
        $this->aliasName = '';
        Notification::make()->title('Şehir adı eşleştirildi')->success()->send();
    }

    public function removeAlias(int $id): void
    {
        abort_unless(static::canAccess(), 403);
        DB::transaction(function () use ($id): void {
            $alias = CityNameAlias::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if($alias->is_canonical, 422, 'Şehrin katalog adı bu ekrandan kaldırılamaz.');
            activity('global-command')->causedBy(auth()->user())->performedOn($alias)
                ->withProperties(['city_id' => $alias->city_id, 'name' => $alias->name])->log('city.alias.removed');
            $alias->delete();
        });
        Notification::make()->title('Ek ad kaldırıldı')->success()->send();
    }

    protected function getViewData(): array
    {
        $search = CityName::normalize(mb_substr($this->search, 0, 80));
        $cities = City::with('country')->where('is_active', true)->whereHas('country', fn ($q) => $q->where('is_active', true))
            ->where(fn ($q) => $q->whereHas('aliases', fn ($q) => $q->where('normalized_name', 'like', '%'.$search.'%'))
                ->orWhere('id', (int) $this->selectedCityId))
            ->orderByRaw('case when id = ? then 0 else 1 end', [(int) $this->selectedCityId])->orderBy('name')->limit(40)->get();
        $aliases = CityNameAlias::with('city.country')->where('normalized_name', 'like', '%'.$search.'%')
            ->orderBy('normalized_name')->paginate(12, ['*'], 'aliasesPage');

        return compact('cities', 'aliases');
    }
}

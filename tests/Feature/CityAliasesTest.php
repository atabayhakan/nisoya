<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\CityNameManager;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use App\Support\GlobalCommand\CityName;
use App\Support\GlobalCommand\CityNames;
use App\Support\GlobalCommand\GeoContext;
use App\Support\GlobalCommand\GeoNameResolver;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CityAliasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_city_lens_matches_turkish_case_accents_and_registered_names_only_in_country(): void
    {
        Country::create(['code' => 'KG', 'name_tr' => 'Kırgızistan', 'is_active' => true]);
        $city = City::create(['country_code' => 'KG', 'name' => 'Bişkek', 'is_active' => true]);
        $actor = User::factory()->create(['role' => UserRole::Admin]);
        app(CityNames::class)->add($city->id, 'Bishkek', $actor);
        foreach (['BİŞKEK', ' biskek ', 'Bishkek'] as $name) {
            Listing::withoutEvents(fn () => Listing::factory()->create(['city' => $name, 'country_code' => 'KG']));
        }
        Listing::withoutEvents(fn () => Listing::factory()->create(['city' => 'Bishkek', 'country_code' => 'DE']));
        $context = GeoContext::fromSelection(['mode' => 'city', 'country_code' => 'KG', 'city_id' => $city->id]);
        $this->assertSame(3, $context->apply(Listing::query())->count());
        $this->assertSame('Bişkek', app(GeoNameResolver::class)->detect('Bishkek buluşması', 'KG')['city']);
        $this->assertSame(CityName::key('BİŞKEK'), CityName::key('biskek'));
    }

    public function test_same_country_conflict_is_rejected_and_ambiguous_catalog_is_not_merged(): void
    {
        Country::create(['code' => 'DE', 'name_tr' => 'Almanya', 'is_active' => true]);
        $first = City::create(['country_code' => 'DE', 'name' => 'Neustadt', 'is_active' => true]);
        $second = City::create(['country_code' => 'DE', 'name' => 'Neustadt', 'is_active' => true]);
        $this->assertSame([], app(CityNames::class)->keys($first));
        $this->assertNull(app(GeoNameResolver::class)->detect('Neustadt buluşması', 'DE')['city']);
        $this->expectException(ValidationException::class);
        app(CityNames::class)->add($second->id, 'Neustadt', User::factory()->create(['role' => UserRole::Admin]));
    }

    public function test_alias_change_changes_context_key_and_city_rename_preserves_old_name(): void
    {
        Country::create(['code' => 'GB', 'name_tr' => 'Birleşik Krallık', 'is_active' => true]);
        $city = City::create(['country_code' => 'GB', 'name' => 'Londra', 'is_active' => true]);
        $selection = ['mode' => 'city', 'country_code' => 'GB', 'city_id' => $city->id];
        $old = GeoContext::fromSelection($selection)->key();
        app(CityNames::class)->add($city->id, 'London', User::factory()->create(['role' => UserRole::Admin]));
        $this->assertNotSame($old, GeoContext::fromSelection($selection)->key());
        $city->update(['name' => 'London']);
        $this->assertSame('London', app(GeoNameResolver::class)->detect('Londra buluşması')['city']);
    }

    public function test_admin_can_manage_aliases_but_cannot_remove_canonical_name(): void
    {
        Country::create(['code' => 'GB', 'name_tr' => 'Birleşik Krallık', 'is_active' => true]);
        $city = City::create(['country_code' => 'GB', 'name' => 'Londra', 'is_active' => true]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))->test(CityNameManager::class)
            ->set('selectedCityId', (string) $city->id)->set('aliasName', 'London')->call('addAlias')->assertHasNoErrors()->assertSee('London')
            ->call('removeAlias', $city->aliases()->where('is_canonical', true)->value('id'))->assertStatus(422);
        Livewire::actingAs(User::factory()->create(['role' => UserRole::Uye]))->test(CityNameManager::class)->assertForbidden();
    }
}

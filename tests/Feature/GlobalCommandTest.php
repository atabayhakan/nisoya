<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\DiasporaMediaRadar;
use App\Filament\Widgets\CountryLiquidityWidget;
use App\Http\Middleware\ResolveAdminGeoContext;
use App\Livewire\Admin\GeoSwitcher;
use App\Models\City;
use App\Models\Country;
use App\Models\DiasporaAccount;
use App\Models\DiasporaReel;
use App\Models\Listing;
use App\Models\User;
use App\Support\GlobalCommand\CountryLiquidity;
use App\Support\GlobalCommand\GeoContext;
use App\Support\GlobalCommand\IntelligenceScore;
use App\Support\GlobalCommand\LiquidityScore;
use App\Support\GlobalCommand\RadarMediaUrl;
use App\Support\GlobalCommand\StrictDiasporaSync;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GlobalCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['global-command.enabled' => true]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        foreach (['DE' => 'Almanya', 'KG' => 'Kırgızistan', 'FJ' => 'Fiji'] as $code => $name) {
            Country::create(['code' => $code, 'name_tr' => $name, 'is_active' => true]);
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'status' => 'aktif', 'is_demo' => false, 'country_code' => 'DE']);
    }

    public function test_unrestricted_catalog_country_and_empty_region_are_supported(): void
    {
        $context = GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'FJ']);
        $this->assertSame(['FJ'], $context->countries()->pluck('code')->all());
        $id = DB::table('geo_regions')->insertGetId(['slug' => 'test', 'name_tr' => 'Test', 'kind' => 'operational', 'is_active' => true]);
        $empty = GeoContext::fromSelection(['mode' => 'region', 'region_id' => $id]);
        $this->admin();
        $this->assertSame(0, $empty->apply(User::query())->count());
        DB::table('geo_region_country')->insert(['geo_region_id' => $id, 'country_code' => 'DE']);
        $this->assertSame(1, $empty->apply(User::query())->count());
        $this->assertSame(3, Country::count());
    }

    public function test_city_is_validated_against_country(): void
    {
        $city = City::create(['name' => 'Bişkek', 'country_code' => 'KG', 'is_active' => true]);
        $this->expectException(ValidationException::class);
        GeoContext::fromSelection(['mode' => 'city', 'country_code' => 'DE', 'city_id' => $city->id]);
    }

    public function test_city_lens_filters_existing_name_columns(): void
    {
        $city = City::create(['name' => 'Bişkek', 'country_code' => 'KG', 'is_active' => true]);
        User::factory()->create(['country_code' => 'KG', 'city' => 'Bişkek']);
        User::factory()->create(['country_code' => 'KG', 'city' => 'Oş']);
        $context = GeoContext::fromSelection(['mode' => 'city', 'country_code' => 'KG', 'city_id' => $city->id]);
        $this->assertSame(1, $context->apply(User::query())->count());
        $this->assertSame(2, User::count()); // No global model scope leaks into public queries.
    }

    public function test_session_is_bound_to_actor_and_stale_lens_fails_closed(): void
    {
        $actor = $this->admin();
        $session = app('session.store');
        $session->put(GeoContext::SESSION_KEY, ['actor_id' => $actor->id + 1, 'selection' => ['mode' => 'country', 'country_code' => 'DE']]);
        $request = Request::create('/yonetim');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $actor);
        (new ResolveAdminGeoContext)->handle($request, fn () => response('ok'));
        $this->assertSame('global', app(GeoContext::class)->mode);
        $session->put(GeoContext::SESSION_KEY, ['actor_id' => $actor->id, 'selection' => ['mode' => 'country', 'country_code' => 'DE']]);
        Country::whereKey('DE')->update(['is_active' => false]);
        try {
            (new ResolveAdminGeoContext)->handle($request, fn () => response('must not reach'));
            $this->fail('Invalid lens must stop the request.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertFalse($session->has(GeoContext::SESSION_KEY));
    }

    public function test_request_scoped_binding_resets_for_the_next_lifecycle(): void
    {
        app()->instance(GeoContext::class, GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'DE']));
        app()->forgetScopedInstances();
        $this->assertSame('global', app(GeoContext::class)->mode);
    }

    public function test_liquidity_keeps_zero_countries_and_excludes_demo_records(): void
    {
        $actor = $this->admin();
        User::factory()->create(['country_code' => 'DE', 'is_demo' => true]);
        Listing::withoutEvents(fn () => Listing::factory()->create(['user_id' => $actor->id, 'country_code' => 'DE', 'is_demo' => false]));
        Listing::withoutEvents(fn () => Listing::factory()->create(['user_id' => $actor->id, 'country_code' => 'DE', 'is_demo' => true]));
        $snapshot = app(CountryLiquidity::class)->snapshot(GeoContext::global());
        $rows = collect($snapshot['rows'])->keyBy('code');
        $this->assertCount(3, $rows);
        $this->assertSame(1, $rows['DE']['listings']);
        $this->assertSame(1, $rows['DE']['users']);
        $this->assertSame(0, $rows['FJ']['score']);
        $this->assertSame('cold_start', $rows['FJ']['stage']);
        $single = app(CountryLiquidity::class)->snapshot(GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'FJ']));
        $this->assertCount(1, $single['rows']);
    }

    public function test_scores_are_bounded_and_unknown_is_not_safe(): void
    {
        $this->assertSame(0, LiquidityScore::calculate(0, 0, 0, 0)['score']);
        $this->assertSame(100, LiquidityScore::calculate(100000, 100000, 100000, 100000)['score']);
        $this->assertSame('cold_start', LiquidityScore::calculate(100000, 100000, 100000, 0)['stage']);
        $score = IntelligenceScore::forReel(new DiasporaReel(['safety_score' => null, 'safety_status' => 'safe']));
        $this->assertSame('gray', $score['color']);
        $this->assertSame('AI: İncelenmedi', $score['ai']);
        $this->assertNull(RadarMediaUrl::embed('https://instagram.com.evil.test/reel/ABCDEFG1234/'));
        $this->assertNull(RadarMediaUrl::embed('http://127.0.0.1/reel/ABCDEFG1234/'));
        $this->assertSame('https://www.instagram.com/reel/ABCDEFG1234/embed', RadarMediaUrl::embed('https://www.instagram.com/reel/ABCDEFG1234/'));
    }

    public function test_switcher_saves_only_validated_context_and_rejects_non_admin(): void
    {
        Livewire::actingAs($this->admin())->test(GeoSwitcher::class)
            ->set('mode', 'country')->set('countryCode', 'FJ')->call('apply')->assertHasNoErrors()->assertRedirect('/yonetim/global-command-center');
        $this->assertSame('FJ', session(GeoContext::SESSION_KEY)['selection']['country_code']);
        Livewire::actingAs(User::factory()->create(['role' => UserRole::Uye]))->test(GeoSwitcher::class)->assertForbidden();
    }

    public function test_widgets_and_media_page_render_without_external_calls(): void
    {
        Http::preventStrayRequests();
        $actor = $this->admin();
        DiasporaReel::create(['title' => 'Test reel', 'instagram_url' => 'https://www.instagram.com/reel/ABCDEFG1234/', 'country_code' => 'FJ', 'status' => 'draft', 'is_active' => false]);
        Livewire::actingAs($actor)->test(CountryLiquidityWidget::class)->assertSee('Fiji')->assertSee('Cold-Start');
        Livewire::actingAs($actor)->test(DiasporaMediaRadar::class)->assertSee('Test reel')->assertSee('AI: İncelenmedi')->call('openPreview', DiasporaReel::first()->id)->assertSeeHtml('/embed');
        Http::assertNothingSent();
    }

    public function test_strict_ingest_never_publishes_or_fabricates_metrics_and_is_idempotent(): void
    {
        config(['services.rapidapi.key' => 'test-placeholder']);
        $account = DiasporaAccount::create(['username' => '@test_account', 'country_code' => 'FJ', 'is_active' => true, 'is_verified' => true]);
        Http::fake(['*' => Http::response(['data' => ['items' => [['code' => 'ABCDEFG1234', 'caption' => ['text' => 'Gerçek kaynak metni']]]]])]);
        $this->assertSame(1, app(StrictDiasporaSync::class)->run($account));
        $this->assertSame(0, app(StrictDiasporaSync::class)->run($account));
        $this->assertDatabaseCount('diaspora_reels', 1);
        $reel = DiasporaReel::first();
        $this->assertSame('draft', $reel->status);
        $this->assertFalse($reel->is_active);
        $this->assertNull($reel->views_count);
        $this->assertNull($reel->safety_score);
    }

    public function test_provider_failure_creates_no_reel_and_does_not_mark_success(): void
    {
        config(['services.rapidapi.key' => 'test-placeholder']);
        $account = DiasporaAccount::create(['username' => '@test_account', 'country_code' => 'FJ', 'is_active' => true, 'is_verified' => true]);
        Http::fake(['*' => Http::response([], 429)]);
        try {
            app(StrictDiasporaSync::class)->run($account);
            $this->fail('429 must propagate to the queue retry policy.');
        } catch (RequestException $exception) {
            $this->assertSame(429, $exception->response->status());
        }
        $this->assertDatabaseCount('diaspora_reels', 0);
        $this->assertNull($account->fresh()->last_synced_at);
    }

    public function test_cold_start_tasks_are_idempotent_and_do_not_need_an_admin_session(): void
    {
        $this->artisan('global-command:plan-cold-start')->assertSuccessful();
        $this->artisan('global-command:plan-cold-start')->assertSuccessful();
        $this->assertDatabaseCount('global_growth_tasks', 3);
        $this->assertDatabaseHas('global_growth_tasks', ['country_code' => 'FJ', 'status' => 'suggested']);
    }

    public function test_empty_provider_success_is_distinct_from_malformed_response(): void
    {
        config(['services.rapidapi.key' => 'test-placeholder']);
        $account = DiasporaAccount::create(['username' => '@test_account', 'country_code' => 'FJ', 'is_active' => true, 'is_verified' => true]);
        Http::fake(['*' => Http::sequence()->push(['data' => ['items' => []]])->push(['unexpected' => true])]);
        $this->assertSame(0, app(StrictDiasporaSync::class)->run($account));
        $this->assertNotNull($account->fresh()->last_synced_at);
        $this->expectException(\RuntimeException::class);
        app(StrictDiasporaSync::class)->run($account);
    }

    public function test_radar_rechecks_geo_scope_for_preview_and_verification(): void
    {
        $actor = $this->admin();
        $account = DiasporaAccount::create(['username' => '@fiji_account', 'country_code' => 'FJ', 'is_active' => true, 'is_verified' => false]);
        $reel = DiasporaReel::create(['title' => 'Fiji', 'instagram_url' => 'https://www.instagram.com/reel/ABCDEFG1234/', 'country_code' => 'FJ', 'account_id' => $account->id, 'status' => 'draft']);
        app()->instance(GeoContext::class, GeoContext::fromSelection(['mode' => 'country', 'country_code' => 'DE']));
        foreach (['openPreview' => $reel->id, 'verifyAccount' => $account->id] as $method => $id) {
            try {
                Livewire::actingAs($actor)->test(DiasporaMediaRadar::class)->call($method, $id);
                $this->fail('Scope dışında kayıt bulunmamalı.');
            } catch (ModelNotFoundException $exception) {
                $this->assertContains($exception->getModel(), [DiasporaReel::class, DiasporaAccount::class]);
            }
        }
        $this->assertFalse($account->fresh()->is_verified);
    }
}

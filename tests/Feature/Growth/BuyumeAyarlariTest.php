<?php

namespace Tests\Feature\Growth;

use App\Enums\UserRole;
use App\Filament\Pages\BuyumeAyarlari;
use App\Models\User;
use App\Services\Growth\Discovery\BusinessDiscoverySource;
use App\Services\Growth\Discovery\FixtureDiscoverySource;
use App\Services\Growth\Discovery\GooglePlacesDiscoverySource;
use App\Services\Growth\Discovery\OverpassDiscoverySource;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BuyumeAyarlariTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_only_admin_can_access(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(BuyumeAyarlari::canAccess());

        $this->actingAs(User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]));
        $this->assertFalse(BuyumeAyarlari::canAccess());
    }

    public function test_save_persists_places_key(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(BuyumeAyarlari::class)
            ->set('data.google_places_api_key', 'test-places-key')
            ->call('save');

        $this->assertSame('test-places-key', Settings::get('growth.google_places_api_key'));
    }

    public function test_binding_auto_uses_google_when_key_present_else_fixture(): void
    {
        config(['growth.source' => 'auto', 'growth.google_places.api_key' => 'k']);
        $this->assertInstanceOf(GooglePlacesDiscoverySource::class, app(BusinessDiscoverySource::class));

        config(['growth.source' => 'auto', 'growth.google_places.api_key' => null]);
        $this->assertInstanceOf(FixtureDiscoverySource::class, app(BusinessDiscoverySource::class));
    }

    public function test_binding_respects_explicit_source_selection(): void
    {
        config(['growth.source' => 'overpass']);
        $this->assertInstanceOf(OverpassDiscoverySource::class, app(BusinessDiscoverySource::class));

        config(['growth.source' => 'fixture']);
        $this->assertInstanceOf(FixtureDiscoverySource::class, app(BusinessDiscoverySource::class));
    }

    public function test_save_persists_source_selection(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(BuyumeAyarlari::class)
            ->set('data.source', 'overpass')
            ->call('save');

        $this->assertSame('overpass', Settings::get('growth.source'));
    }

    public function test_probe_reports_success(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => [['id' => 'x']]])]);

        $result = (new GooglePlacesDiscoverySource('k'))->probe();

        $this->assertTrue($result['ok']);
    }

    public function test_probe_reports_failure_with_message(): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 403)]);

        $result = (new GooglePlacesDiscoverySource('bad'))->probe();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('API key not valid', $result['message']);
    }

    public function test_save_persists_advanced_growth_settings(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(BuyumeAyarlari::class)
            ->set('data.auto_create_listings', true)
            ->set('data.use_llm', true)
            ->set('data.min_confidence', '80')
            ->set('data.daily_limit', '250')
            ->set('data.min_rating', '4.0')
            ->set('data.min_reviews', '10')
            ->set('data.whatsapp_signature', 'Test İmza · nisoya.com')
            ->call('save');

        $this->assertSame('1', Settings::get('growth.auto_create_listings'));
        $this->assertSame('1', Settings::get('growth.use_llm'));
        $this->assertSame('80', Settings::get('growth.min_confidence'));
        $this->assertSame('250', Settings::get('growth.daily_limit'));
        $this->assertSame('4.0', Settings::get('growth.min_rating'));
        $this->assertSame('10', Settings::get('growth.min_reviews'));
        $this->assertSame('Test İmza · nisoya.com', Settings::get('growth.whatsapp_signature'));
    }

    public function test_overpass_probe_reports_status(): void
    {
        Http::fake(['overpass-api.de/*' => Http::response('[out:json];', 200)]);

        $result = app(OverpassDiscoverySource::class)->probe();

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('yanıt veriyor', $result['message']);
    }

    public function test_hizli_kesif_baslat_runs_cleanly(): void
    {
        $this->actingAs($this->admin());

        config(['growth.source' => 'fixture']);

        Livewire::test(BuyumeAyarlari::class)
            ->set('kesif_ulke', 'DE')
            ->set('kesif_sehir', 'Berlin')
            ->set('kesif_meslek', 'lokanta')
            ->call('hizliKesifBaslat')
            ->assertHasNoErrors();
    }
}

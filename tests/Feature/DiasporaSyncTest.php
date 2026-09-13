<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DiasporaAccount;
use App\Models\DiasporaReel;
use App\Models\User;
use App\Services\Diaspora\DiasporaSyncEngine;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiasporaSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_sync_engine_saves_drafts_when_autopilot_is_disabled(): void
    {
        $account = DiasporaAccount::create([
            'username' => '@berlinturkleri',
            'title' => 'Berlin Türkleri Topluluğu',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'autopilot' => false,
            'is_active' => true,
        ]);

        $syncEngine = app(DiasporaSyncEngine::class);
        $result = $syncEngine->syncAccount($account);

        $this->assertGreaterThan(0, $result['created']);
        $this->assertSame(0, $result['autopilot_published']);

        $this->assertDatabaseHas('diaspora_reels', [
            'account_id' => $account->id,
            'status' => DiasporaReel::STATUS_DRAFT,
            'is_active' => false,
        ]);

        $account->refresh();
        $this->assertGreaterThan(0, $account->reels_count);
        $this->assertNotNull($account->last_synced_at);
    }

    public function test_sync_engine_publishes_directly_when_autopilot_is_enabled(): void
    {
        $account = DiasporaAccount::create([
            'username' => '@berlinturkleri',
            'title' => 'Berlin Türkleri Topluluğu',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'autopilot' => true,
            'is_active' => true,
        ]);

        $syncEngine = app(DiasporaSyncEngine::class);
        $result = $syncEngine->syncAccount($account);

        $this->assertGreaterThan(0, $result['created']);
        $this->assertSame($result['created'], $result['autopilot_published']);

        $this->assertDatabaseHas('diaspora_reels', [
            'account_id' => $account->id,
            'status' => DiasporaReel::STATUS_PUBLISHED,
            'is_active' => true,
        ]);
    }

    public function test_sync_engine_prevents_duplicate_reels(): void
    {
        $account = DiasporaAccount::create([
            'username' => '@biskek_turkleri',
            'title' => 'Bişkek Türk Topluluğu',
            'country_code' => 'KG',
            'city' => 'Bişkek',
            'autopilot' => false,
            'is_active' => true,
        ]);

        $syncEngine = app(DiasporaSyncEngine::class);
        $firstRun = $syncEngine->syncAccount($account);
        $this->assertGreaterThan(0, $firstRun['created']);

        $secondRun = $syncEngine->syncAccount($account);
        $this->assertSame(0, $secondRun['created']);
        $this->assertGreaterThan(0, $secondRun['skipped']);
    }

    public function test_admin_can_access_diaspora_accounts_management(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        DiasporaAccount::create([
            'username' => '@amsterdamturkleri',
            'title' => 'Amsterdam Türkleri',
            'country_code' => 'NL',
            'city' => 'Amsterdam',
        ]);

        $this->actingAs($admin)
            ->get('/yonetim/diaspora-hesaplari')
            ->assertOk()
            ->assertSee('İzlenen Diaspora Hesapları')
            ->assertSee('@amsterdamturkleri');
    }
}

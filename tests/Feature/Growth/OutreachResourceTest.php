<?php

namespace Tests\Feature\Growth;

use App\Enums\UserRole;
use App\Filament\Resources\OutreachTargets\Pages\ListOutreachTargets;
use App\Models\OutreachTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OutreachResourceTest extends TestCase
{
    use RefreshDatabase;

    private function target(array $overrides = []): OutreachTarget
    {
        return OutreachTarget::create(array_merge([
            'name' => 'Anadolu Kebap House',
            'country' => 'US',
            'city' => 'New York',
            'source' => 'fixture',
            'external_id' => 'fx-us-1',
            'detection_band' => 'turkish',
            'detection_confidence' => 98,
            'detection_method' => 'deterministic',
            'marketing_status' => 'allowed',
            'needs_review' => false,
            'status' => 'kesif',
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_admin_can_view_discovery_pool(): void
    {
        $this->target();

        $this->actingAs($this->admin())
            ->get('/yonetim/outreach-targets')
            ->assertOk()
            ->assertSee('Anadolu Kebap House');
    }

    public function test_admin_can_open_edit_page(): void
    {
        $target = $this->target();

        $this->actingAs($this->admin())
            ->get('/yonetim/outreach-targets/'.$target->id.'/edit')
            ->assertOk();
    }

    public function test_moderator_cannot_access_growth_pool(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        // RestrictsToAdmins: keşif havuzu admin'e özel — moderatöre kapalı.
        $this->actingAs($moderator)
            ->get('/yonetim/outreach-targets')
            ->assertForbidden();
    }

    public function test_panel_button_runs_discovery(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListOutreachTargets::class)
            ->call('runDiscovery', 'US', 3);

        // Panelden tetiklenen keşif havuzu doldurdu.
        $this->assertGreaterThan(0, OutreachTarget::where('country', 'US')->count());
        $this->assertNotNull(OutreachTarget::where('name', 'Anadolu Kebap House')->first());
    }
}

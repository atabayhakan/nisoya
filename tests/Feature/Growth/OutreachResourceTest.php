<?php

namespace Tests\Feature\Growth;

use App\Enums\UserRole;
use App\Filament\Resources\OutreachTargets\Pages\ListOutreachTargets;
use App\Filament\Widgets\KesifIlerlemeWidget;
use App\Jobs\EnrichTargetJob;
use App\Jobs\RunDiscoveryJob;
use App\Models\OutreachTarget;
use App\Models\User;
use App\Services\Growth\DiscoveryRunner;
use App\Services\Growth\EnrichmentRunner;
use App\Support\Growth\KesifIlerlemesi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

    public function test_panel_button_queues_discovery_jobs(): void
    {
        Queue::fake();
        $this->actingAs($this->admin());

        Livewire::test(ListOutreachTargets::class)
            ->call('runDiscovery', 'US', 3);

        // ABD: 5 şehir × 3 meslek = 15 iş kuyruklanır (senkron değil → 504 yok).
        Queue::assertPushed(RunDiscoveryJob::class, 15);
    }

    public function test_discovery_job_persists_targets(): void
    {
        (new RunDiscoveryJob('US', 'New York', ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant']))
            ->handle(app(DiscoveryRunner::class));

        // Fixture kaynağı (auto, anahtar yok) şehir/meslekten bağımsız US döndürür.
        $this->assertNotNull(OutreachTarget::where('name', 'Anadolu Kebap House')->first());
    }

    public function test_enrich_button_queues_jobs_for_allowed_pending_only(): void
    {
        Queue::fake();
        $this->target(['external_id' => 'e1', 'website' => 'https://a.test']);                                              // gönderilebilir + site + e-posta yok → kuyruk
        $this->target(['external_id' => 'e2', 'website' => 'https://b.test', 'country' => 'DE', 'marketing_status' => 'region_blocked']); // engelli → GDPR, atla
        $this->target(['external_id' => 'e3', 'website' => 'https://c.test', 'contact_email' => 'zaten@var.com']);          // zaten var → atla
        $this->target(['external_id' => 'e4']);                                                                             // site yok → atla

        $this->actingAs($this->admin());
        Livewire::test(ListOutreachTargets::class)->call('runEnrichment');

        Queue::assertPushed(EnrichTargetJob::class, 1);
    }

    /**
     * Canlıda bulunan hata (2026-07-31): keşif yeni sonuçlar bulmuştu ama
     * SONUÇ TABLOSU (widget'tan ayrı bir Livewire bileşeni) eski toplamı
     * göstermeye devam ediyordu — widget'ın pollaması yalnız kendini
     * yeniliyordu. Bitince widget tek seferlik bir olay yayınlamalı.
     */
    public function test_kesif_tamamlaninca_tablo_yenileme_olayi_bir_kez_yayinlanir(): void
    {
        $admin = $this->admin();
        $lot = KesifIlerlemesi::baslat($admin->id, 1);
        KesifIlerlemesi::tamamlaniyor($lot);

        Livewire::actingAs($admin)
            ->test(KesifIlerlemeWidget::class)
            ->assertDispatched('kesif-tamamlandi');

        // İkinci render (bir sonraki poll): TEKRAR yayınlanmamalı.
        Livewire::actingAs($admin)
            ->test(KesifIlerlemeWidget::class)
            ->assertNotDispatched('kesif-tamamlandi');
    }

    public function test_kesif_devam_ederken_olay_yayinlanmaz(): void
    {
        $admin = $this->admin();
        $lot = KesifIlerlemesi::baslat($admin->id, 3);
        KesifIlerlemesi::tamamlaniyor($lot);

        Livewire::actingAs($admin)
            ->test(KesifIlerlemeWidget::class)
            ->assertNotDispatched('kesif-tamamlandi');
    }

    public function test_enrich_target_job_persists_email(): void
    {
        Http::fake(['*' => Http::response('<a href="mailto:info@site.com">x</a>')]);
        $target = $this->target(['external_id' => 'e1', 'website' => 'https://site.test']);

        (new EnrichTargetJob($target->id))->handle(app(EnrichmentRunner::class));

        $this->assertSame('info@site.com', $target->fresh()->contact_email);
    }
}

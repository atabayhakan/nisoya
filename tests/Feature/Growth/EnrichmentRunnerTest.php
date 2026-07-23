<?php

namespace Tests\Feature\Growth;

use App\Models\OutreachTarget;
use App\Services\Growth\DiscoveryRunner;
use App\Services\Growth\EnrichmentRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnrichmentRunnerTest extends TestCase
{
    use RefreshDatabase;

    private function target(array $overrides = []): OutreachTarget
    {
        static $n = 0;
        $n++;

        return OutreachTarget::create(array_merge([
            'name' => 'Kebap '.$n,
            'country' => 'US',
            'source' => 'fixture',
            'external_id' => 'e'.$n,
            'detection_band' => 'turkish',
            'marketing_status' => 'allowed',
            'website' => 'https://site.test',
        ], $overrides));
    }

    public function test_enriches_allowed_targets(): void
    {
        Http::fake(['*' => Http::response('<a href="mailto:info@site.com">x</a>')]);
        $target = $this->target();

        $stats = app(EnrichmentRunner::class)->run();

        $this->assertSame('info@site.com', $target->fresh()->contact_email);
        $this->assertSame(1, $stats['enriched']);
    }

    public function test_skips_region_blocked_targets_for_gdpr(): void
    {
        Http::fake(['*' => Http::response('<a href="mailto:info@site.com">x</a>')]);
        $blocked = $this->target(['country' => 'DE', 'marketing_status' => 'region_blocked']);

        $stats = app(EnrichmentRunner::class)->run();

        $this->assertNull($blocked->fresh()->contact_email, 'AB adayı zenginleştirilmemeli (GDPR)');
        $this->assertSame(1, $stats['skipped_blocked']);
        $this->assertSame(0, $stats['enriched']);
    }

    public function test_ignores_targets_without_website_or_already_enriched(): void
    {
        Http::fake(['*' => Http::response('<a href="mailto:info@site.com">x</a>')]);
        $this->target(['website' => null]);                         // site yok
        $this->target(['contact_email' => 'zaten@var.com']);        // zaten zenginleştirilmiş

        $stats = app(EnrichmentRunner::class)->run();

        $this->assertSame(0, $stats['candidates']);
    }

    public function test_end_to_end_discover_then_enrich(): void
    {
        Http::fake(['*' => Http::response('<a href="mailto:info@anadolu.com">x</a>')]);

        app(DiscoveryRunner::class)->runForCountry('US');
        $stats = app(EnrichmentRunner::class)->run('US');

        $this->assertGreaterThan(0, $stats['enriched']);
        $this->assertSame('info@anadolu.com', OutreachTarget::where('name', 'Anadolu Kebap House')->first()?->contact_email);
    }
}

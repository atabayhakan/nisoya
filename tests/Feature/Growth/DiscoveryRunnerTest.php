<?php

namespace Tests\Feature\Growth;

use App\Models\OutreachTarget;
use App\Services\Growth\DiscoveryRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovers_and_persists_turkish_targets(): void
    {
        // Anahtar yok → fixture kaynağı bağlanır.
        $stats = app(DiscoveryRunner::class)->runForCountry('US');

        $this->assertSame('fixture', $stats['source']);
        $this->assertGreaterThan(0, $stats['saved']);

        $kebap = OutreachTarget::where('name', 'Anadolu Kebap House')->first();
        $this->assertNotNull($kebap);
        $this->assertSame('turkish', $kebap->detection_band);
        $this->assertSame('allowed', $kebap->marketing_status);
    }

    public function test_non_turkish_results_are_not_stored(): void
    {
        app(DiscoveryRunner::class)->runForCountry('US');

        // "Sunrise Bakery" hiçbir Türk işareti taşımaz → havuza girmez.
        $this->assertNull(OutreachTarget::where('name', 'Sunrise Bakery')->first());
    }

    public function test_ambiguous_result_is_flagged_for_review(): void
    {
        app(DiscoveryRunner::class)->runForCountry('US');

        // "Kaya" tek başına Türk soyadı da Japonca da olabilir → sınırda.
        $kaya = OutreachTarget::where('name', 'Kaya Sushi Bar')->first();
        $this->assertNotNull($kaya);
        $this->assertSame('ambiguous', $kaya->detection_band);
        $this->assertTrue($kaya->needs_review);
    }

    public function test_rerun_is_idempotent(): void
    {
        $runner = app(DiscoveryRunner::class);
        $runner->runForCountry('KZ');
        $count = OutreachTarget::count();

        $runner->runForCountry('KZ');

        $this->assertSame($count, OutreachTarget::count());
    }

    public function test_eu_discovery_is_stored_but_marketing_blocked(): void
    {
        // AB keşifte var (pazar zekâsı) ama gönderim engelli — kararın çekirdeği.
        app(DiscoveryRunner::class)->runForCountry('DE');

        $target = OutreachTarget::where('name', 'Berlin Anadolu Kebap')->first();
        $this->assertNotNull($target, 'AB işletmesi keşifte saklanmalı');
        $this->assertSame('turkish', $target->detection_band);
        $this->assertSame('region_blocked', $target->marketing_status);

        // Dolayısıyla "gönderilebilir" havuzunda görünmez.
        $this->assertSame(0, OutreachTarget::query()->sendable()->where('country', 'DE')->count());
    }
}

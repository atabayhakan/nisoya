<?php

namespace Tests\Feature\Growth;

use App\Support\Growth\RegionPolicy;
use Tests\TestCase;

class RegionPolicyTest extends TestCase
{
    public function test_allowlisted_country_is_allowed(): void
    {
        config(['growth.sending_allowlist' => ['US', 'KZ'], 'growth.restricted_countries' => ['RU']]);

        $this->assertSame(RegionPolicy::ALLOWED, RegionPolicy::marketingStatus('US'));
        $this->assertTrue(RegionPolicy::isSendable('kz')); // büyük/küçük harf duyarsız
    }

    public function test_eu_and_turkey_are_always_blocked(): void
    {
        config(['growth.sending_allowlist' => []]); // allowlist kapalı olsa bile

        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus('DE'));
        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus('FR'));
        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus('TR'));
    }

    public function test_restricted_country_is_blocked(): void
    {
        config(['growth.sending_allowlist' => [], 'growth.restricted_countries' => ['RU']]);

        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus('RU'));
    }

    public function test_off_allowlist_or_unknown_is_blocked(): void
    {
        config(['growth.sending_allowlist' => ['US']]);

        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus('BR'));
        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus(null));
        $this->assertSame(RegionPolicy::BLOCKED, RegionPolicy::marketingStatus(''));
    }
}

<?php

namespace Tests\Unit;

use App\Support\CategoryIcon;
use PHPUnit\Framework\TestCase;

class CategoryIconTest extends TestCase
{
    public function test_maps_known_emoji_to_heroicon_slug(): void
    {
        $this->assertSame('academic-cap', CategoryIcon::heroicon('📚'));
        $this->assertSame('wrench-screwdriver', CategoryIcon::heroicon('🔧'));
        $this->assertSame('lifebuoy', CategoryIcon::heroicon('🚨'));
    }

    public function test_falls_back_to_generic_icon_for_unknown_emoji(): void
    {
        $this->assertSame('squares-2x2', CategoryIcon::heroicon('🦄'));
    }

    public function test_falls_back_to_generic_icon_for_null(): void
    {
        $this->assertSame('squares-2x2', CategoryIcon::heroicon(null));
    }
}

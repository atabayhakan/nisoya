<?php

namespace Tests\Unit\Growth;

use App\Services\Growth\QueryPermutationEngine;
use PHPUnit\Framework\TestCase;

class QueryPermutationEngineTest extends TestCase
{
    public function test_builds_multilingual_queries_with_turkish_markers(): void
    {
        $queries = (new QueryPermutationEngine)->build(
            ['Almaty'],
            [['tr' => 'elektrikçi', 'en' => 'electrician', 'local' => 'электрик']],
        );

        // Üç dil varyantı da üretilir.
        $this->assertContains('электрик Almaty', $queries);
        $this->assertContains('electrician Almaty', $queries);
        $this->assertContains('elektrikçi Almaty', $queries);

        // "Türk/Turkish" enjekteli varyantlar da.
        $this->assertContains('Türk elektrikçi Almaty', $queries);
        $this->assertContains('Turkish electrician Almaty', $queries);
    }

    public function test_marker_injection_can_be_disabled(): void
    {
        $queries = (new QueryPermutationEngine)->build(
            ['Bishkek'],
            [['en' => 'barber']],
            withMarkers: false,
        );

        $this->assertSame(['barber Bishkek'], $queries);
    }

    public function test_skips_missing_terms_and_dedupes(): void
    {
        $queries = (new QueryPermutationEngine)->build(
            ['Bangkok'],
            [
                ['en' => 'barber'],          // yalnızca İngilizce
                ['en' => 'barber'],          // yinelenen → tekilleştirilir
            ],
            withMarkers: false,
        );

        $this->assertSame(['barber Bangkok'], $queries);
    }

    public function test_cross_product_covers_every_city_and_trade(): void
    {
        $queries = (new QueryPermutationEngine)->build(
            ['Almaty', 'Astana'],
            [['en' => 'barber'], ['en' => 'electrician']],
            withMarkers: false,
        );

        $this->assertCount(4, $queries);
        $this->assertContains('barber Almaty', $queries);
        $this->assertContains('electrician Astana', $queries);
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\ParallelTesting;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        // ParaTest ile paralel çalışırken tüm worker'lar aynı fiziksel
        // storage/logs/laravel.log dosyasını paylaşıyor — biri exception
        // loglarken diğeri aynı anda dosya içeriğini assertStringContainsString
        // ile kontrol ederse yarış durumu oluşup birbirinin log satırlarını
        // okuyabiliyorlardı. Her worker'a kendi log dosyasını ver.
        if ($token = ParallelTesting::token()) {
            config(['logging.channels.single.path' => storage_path("logs/laravel-test-{$token}.log")]);
        }
    }
}

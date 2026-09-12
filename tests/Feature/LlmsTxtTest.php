<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmsTxtTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_llms_txt_rotasi_gecerli_markdown_ve_basliklari_doner(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertStringContainsString('# Nisoya', $response->getContent());
        $this->assertStringContainsString('## Kapsanan Başlıca Ülkeler', $response->getContent());
        $this->assertStringContainsString('## İlan ve Hizmet Kategorileri', $response->getContent());
    }

    public function test_llms_full_txt_rotasi_calisir(): void
    {
        $response = $this->get('/llms-full.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertStringContainsString('## Kapsamlı Dizin Sürümü', $response->getContent());
    }
}

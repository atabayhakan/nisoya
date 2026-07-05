<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    public function test_default_brand_renders_no_override(): void
    {
        $html = (string) view('components.brand-theme')->render();

        $this->assertSame('', trim($html));
    }

    public function test_custom_brand_overrides_emerald_css_variables(): void
    {
        Settings::setMany(['gorunum.marka_rengi' => 'blue']);

        $html = (string) view('components.brand-theme')->render();

        $this->assertStringContainsString('--color-emerald-600: var(--color-blue-600);', $html);
        $this->assertStringContainsString('--color-emerald-50: var(--color-blue-50);', $html);
    }

    public function test_invalid_brand_value_falls_back_to_emerald_default(): void
    {
        Settings::setMany(['gorunum.marka_rengi' => 'kırmızı-imzasız-degeri']);

        $html = (string) view('components.brand-theme')->render();

        $this->assertSame('', trim($html));
    }

    public function test_brand_color_hex_helper_matches_config(): void
    {
        $this->assertSame('#059669', brandColorHex());

        Settings::setMany(['gorunum.marka_rengi' => 'rose']);
        $this->assertSame('#e11d48', brandColorHex());
    }

    public function test_home_page_reflects_selected_brand_color(): void
    {
        Settings::setMany(['gorunum.marka_rengi' => 'violet']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--color-emerald-600: var(--color-violet-600);', false);
    }
}

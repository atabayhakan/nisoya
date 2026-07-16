<?php

namespace Tests\Feature;

use App\Providers\Filament\AdminPanelProvider;
use App\Support\Settings;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Faz İ5: admin panelinin (/yonetim) marka rengi artık sabit "Emerald"
 * değil, genel site ayarına (gorunum.marka_rengi) bağlı.
 *
 * Not: gerçek HTTP isteğiyle uçtan uca test edilmiyor — Filament panel
 * kaydını tek bir PHP süreci içinde önbelleğe alıyor (ilk resolve'dan
 * sonra sabitleniyor), bu yüzden aynı test sürecinde ayarı değiştirip
 * ikinci bir HTTP isteğiyle farkı görmek güvenilir değil. Bunun yerine
 * asıl mantık (resolveBrandColor) reflection ile doğrudan test ediliyor
 * — gerçek tarayıcıda (php artisan serve, ayrı istekler) elle doğrulandı.
 */
class AdminBrandColorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    protected function resolve(): array
    {
        $provider = new AdminPanelProvider(app());
        $method = new ReflectionMethod($provider, 'resolveBrandColor');
        $method->setAccessible(true);

        return $method->invoke($provider);
    }

    public function test_defaults_to_emerald_when_no_setting_saved(): void
    {
        $this->assertSame(Color::Emerald, $this->resolve());
    }

    public function test_follows_chosen_brand_color(): void
    {
        Settings::setMany(['gorunum.marka_rengi' => 'violet']);

        $this->assertSame(Color::Violet, $this->resolve());
    }

    public function test_falls_back_to_emerald_for_invalid_key(): void
    {
        Settings::setMany(['gorunum.marka_rengi' => 'nope-not-real']);

        $this->assertSame(Color::Emerald, $this->resolve());
    }
}

<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\ExifPrivacyWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SystemHealthWidget;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Settings;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\BasePage;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('yonetim')
            ->brandName('Nisoya Yönetim')
            ->login()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => $this->resolveBrandColor(),
            ])
            ->darkMode(true) // Sistem teması ile otomatik senkronize; kullanıcı override edebilir.
            ->defaultThemeMode(ThemeMode::System)
            // Grup sırası kullanım sıklığına göre: önce günlük içerik
            // (Pazaryeri, İş İlanları, Topluluk), sonra ara sıra dokunulan
            // yapılandırma (Site Yönetimi, Tasarım, Ayarlar), en sonda
            // sadece denetim amaçlı Sistem. Bu, Resource'ların kendi
            // navigationSort'larından bağımsız olarak grup sırasını sabitler.
            ->navigationGroups([
                NavigationGroup::make('Pazaryeri'),
                NavigationGroup::make('İş İlanları'),
                NavigationGroup::make('Topluluk'),
                NavigationGroup::make('İletişim'),
                NavigationGroup::make('Site Yönetimi'),
                NavigationGroup::make('Tasarım'),
                NavigationGroup::make('Ayarlar'),
                NavigationGroup::make('Sistem'),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Siteyi Görüntüle')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url('/')
                    ->openUrlInNewTab(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                SystemHealthWidget::class,
                ExifPrivacyWidget::class,
                StatsOverview::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Muhafazakâr CSP admin panelde de geçerli olsun (Filament kendi
                // middleware yığınını kullandığı için web grubuna eklenen
                // SecurityHeaders buraya uğramaz).
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // Standart Resource Edit/Create sayfalarında Kaydet/İptal butonları
        // ekranın altına sabitlenir — uzun formlarda kaydetmek için sayfanın
        // sonuna kadar kaydırmak gerekmez. (Elle yazılmış özel sayfalar,
        // ör. IcerikAyarlari, bu alt yapıyı kullanmadığı için ayrıca kendi
        // Blade görünümünde yapışkan bir alt çubukla çözülür.)
        BasePage::stickyFormActions();
    }

    /**
     * Faz İ5 — admin panelinin marka rengini genel site ayarına bağlar
     * (Site Yönetimi → İçerik → Görünüm). Sabit "Emerald" yerine admin
     * hangi rengi seçtiyse panel de o rengi kullanır. Migration öncesi
     * (fresh install/CI) tabloya dokunmadan güvenle "Emerald"a düşer.
     */
    protected function resolveBrandColor(): array
    {
        $key = 'emerald';

        if (Schema::hasTable('site_settings')) {
            $key = Settings::get('gorunum.marka_rengi', 'emerald');
        }

        if (! array_key_exists($key, config('brand_colors', []))) {
            $key = 'emerald';
        }

        return match ($key) {
            'blue' => Color::Blue,
            'rose' => Color::Rose,
            'amber' => Color::Amber,
            'violet' => Color::Violet,
            'teal' => Color::Teal,
            'indigo' => Color::Indigo,
            'orange' => Color::Orange,
            default => Color::Emerald,
        };
    }
}

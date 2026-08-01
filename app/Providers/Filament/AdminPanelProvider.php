<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\BekleyenIslerWidget;
use App\Filament\Widgets\ExifPrivacyWidget;
use App\Filament\Widgets\IlanHareketleriWidget;
use App\Filament\Widgets\KategoriDagilimiWidget;
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
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
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
            // Grup sırası kullanım sıklığına göre sabitlenir (Resource'ların
            // kendi navigationSort'larından bağımsız). Bu adlar, tüm
            // Resource/Page'lerin bildirdiği KANONİK 8 grup adıyla birebir
            // eşleşmelidir — aksi hâlde eşleşmeyen grup sidebar'ın en sonuna
            // düşer (bkz. 2026-07-22 denetimi #7 ve AdminPanelTest'teki
            // kanonik grup testi).
            //
            // 2026-07-30 yeniden gruplama: "Sistem & Araçlar" 17 kaleme şişmişti
            // ve içindeki SEO/Büyüme/Kâhya kalemleri "sistem işi" değildi. İki
            // yeni başlık açıldı: "Pazarlama & Büyüme" (siteyi büyüten işler)
            // ve "Kâhya & Yapay Zekâ" (asistan + YZ ayarları). "Topluluk &
            // Etkinlikler" da "Topluluk & İletişim" oldu — panelde etkinlik
            // diye bir şey yok, iletişim kutusu var.
            ->navigationGroups([
                NavigationGroup::make('Pazaryeri & Ticaret'),
                NavigationGroup::make('İş & Kariyer Portalı'),
                NavigationGroup::make('Ülke Rehberi'),
                NavigationGroup::make('Topluluk & İletişim'),
                NavigationGroup::make('Kullanıcılar & Güvenlik'),
                NavigationGroup::make('İçerik & Tasarım (CMS)'),
                NavigationGroup::make('Pazarlama & Büyüme'),
                NavigationGroup::make('Kâhya & Yapay Zekâ'),
                NavigationGroup::make('Sistem & Araçlar'),
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
            // Sıra: önce "bugün ne yapmalıyım" (bekleyen işler), sonra sayılar,
            // sonra eğilim grafikleri, en sonda sistem/hesap kartları.
            // getSort() değerleri: BekleyenIsler -4, StatsOverview -3,
            // IlanHareketleri -2, KategoriDagilimi -1.
            ->widgets([
                BekleyenIslerWidget::class,
                SystemHealthWidget::class,
                ExifPrivacyWidget::class,
                StatsOverview::class,
                IlanHareketleriWidget::class,
                KategoriDagilimiWidget::class,
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
            ])
            /*
             * Kâhya balonu — panelin HER sayfasında sağ altta duran sohbet
             * düğmesi. Sahip "etiketler nerede?" diye sormak için Kâhya
             * sayfasına gitmek zorunda kalmasın; olduğu ekrandan sorsun.
             *
             * Yalnız admin görür (sohbetten EYLEM tetiklenir — moderatöre
             * açmak yetki yükseltmesi olurdu; bkz. KahyaSohbet::canAccess) ve
             * Kâhya ile Konuş sayfasında gizlenir: aynı sohbetin iki kopyası
             * aynı ekranda kafa karıştırır.
             */
            ->renderHook(
                PanelsRenderHook::BODY_END,
                function (): string {
                    if (! (auth()->user()?->isAdmin() ?? false)) {
                        return '';
                    }

                    if (request()->routeIs('filament.admin.pages.kahya-sohbet')) {
                        return '';
                    }

                    return Blade::render("@livewire('kahya-balonu')");
                },
            );
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
            // Vitrin teması aktifken panel de Vitrin birincil rengini (Deniz
            // mavisi) kullanır — handoff gereği site ile admin tutarlı kalır.
            // Kayıtlı ayara bakılır (oturum önizlemesi DEĞİL: panel rengi
            // provider kaydında, session'dan önce çözülür).
            if (Settings::get('gorunum.tema', 'klasik') === 'vitrin') {
                return Color::hex('#3E63F0');
            }

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

<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\City;
use App\Models\ListingImage;
use App\Models\User;
use App\Observers\ListingImageObserver;
use App\Observers\UserObserver;
use App\Services\PerformanceService;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton: PerformanceMetricsMiddleware ve QueryLogMiddleware aynı
        // istek içinde aynı PerformanceService örneğini paylaşmalı — aksi
        // halde start()/record() çağrıları farklı örneklerde çalışır ve
        // performans logu istek başına iki kez (biri yanlış sorgu sayısıyla)
        // yazılır.
        $this->app->singleton(PerformanceService::class);
    }

    public function boot(): void
    {
        // Kullanıcı banlandığında aktif ilanları otomatik pasif yap.
        User::observe(\App\Observers\UserObserver::class);

        // Görsel kaydı silindiğinde thumb/medium/large dosyalarını da temizle.
        ListingImage::observe(ListingImageObserver::class);

        // Şehir önerilerini (ülkeye göre) ilgili formlara paylaş.
        View::composer([
            'auth.kayit',
            'panel.listings.create',
            'panel.listings.edit',
            'panel.profile.edit',
        ], function ($view) {
            $view->with('citiesByCountry', City::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['country_code', 'name'])
                ->groupBy('country_code')
                ->map(fn ($group) => $group->pluck('name')->all()));
        });

        // Header'daki "Acil" hızlı-erişim butonu: her sayfada görünür,
        // kategoriler tablosu henüz yoksa (migrasyon öncesi/test) sessizce
        // boş koleksiyon döner. Kategori nadiren değiştiği için kalıcı
        // cache'lenir (bkz. Category::booted() — kayıt/silmede otomatik temizlenir).
        // Not: Eloquent modeli değil, düz dizi cache'lenir (bkz. Page::navPages()
        // ile aynı desen) — 'database' cache sürücüsü, APP_KEY sızıntısına karşı
        // gadget-chain saldırılarını önlemek için nesne unserialize'ını
        // varsayılan olarak engelliyor (config/cache.php: serializable_classes=false).
        View::composer('components.layouts.app', function ($view) {
            $items = Schema::hasTable('categories')
                ? Cache::rememberForever(Category::EMERGENCY_CACHE_KEY, fn () => Category::query()
                    ->where('slug', Category::EMERGENCY_SLUG)
                    ->first()
                    ?->children()
                    ->where('is_active', true)
                    ->get(['id', 'name', 'slug', 'icon'])
                    ->map(fn (Category $cat) => $cat->only(['id', 'name', 'slug', 'icon']))
                    ->all() ?? [])
                : [];

            $view->with('emergencyCategories', collect($items)->map(fn (array $item) => (object) $item));
        });

        // Reklam & bağış ayarlarını DB'den (varsa) env'in üzerine yaz.
        // Öncelik: DB (.env'den bağımsız admin panelden yönetim) > env > boş.
        // Tablo migrate edilmeden (test/console) çağrılmamalı.
        if (Schema::hasTable('site_settings')) {
            $this->mergeRuntimeConfig();
        }
    }

    /**
     * AdSense/Analytics/donation ayarlarını DB → env sırasıyla runtime'da çöz.
     * Böylece admin panelden girilen değerler .env'den öncelikli olur.
     */
    protected function mergeRuntimeConfig(): void
    {
        // Yayıncı ID: DB > env
        $adsensePublisher = Settings::get('reklam.adsense_publisher') ?: env('ADSENSE_PUBLISHER_ID');
        $adsenseAutoAdsCode = Settings::get('reklam.adsense_auto_ads_kod') ?: env('ADSENSE_AUTO_ADS_CODE');
        $analyticsId = Settings::get('reklam.analytics_measurement_id') ?: env('ANALYTICS_MEASUREMENT_ID');
        $analyticsCustomCode = Settings::get('reklam.analytics_ozel_kod');
        $paypal = Settings::get('bagis.paypal_me') ?: env('DONATION_PAYPAL_ME');
        $iban = Settings::get('bagis.iban') ?: env('DONATION_IBAN');
        $ibanOwner = Settings::get('bagis.iban_sahibi') ?: env('DONATION_IBAN_OWNER');
        $headerCustomCode = Settings::get('header.ozel_kod');
        $footerCustomCode = Settings::get('footer.ozel_kod');

        // Config'i override et (env'den bağımsız)
        Config::set('services.adsense.publisher_id', $adsensePublisher ?: null);
        Config::set('services.adsense.auto_ads_code', $adsenseAutoAdsCode ?: null);
        Config::set('services.analytics.measurement_id', $analyticsId ?: null);
        Config::set('services.analytics.custom_code', $analyticsCustomCode ?: null);
        Config::set('services.donation.paypal_me', $paypal ?: null);
        Config::set('services.donation.iban', $iban ?: null);
        Config::set('services.donation.iban_owner', $ibanOwner ?: null);
        Config::set('services.custom_head_code', $headerCustomCode ?: null);
        Config::set('services.custom_footer_code', $footerCustomCode ?: null);

        // Yayıncı ID mevcutsa AdSense'i etkin say (env'den bağımsız)
        if ($adsensePublisher) {
            Config::set('services.adsense.enabled', true);
        }
        if ($analyticsId) {
            Config::set('services.analytics.enabled', true);
        }
    }
}

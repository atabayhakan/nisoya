<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\ListingImage;
use App\Models\NavigationLink;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Observers\ListingImageObserver;
use App\Observers\PortfolioItemObserver;
use App\Observers\UserObserver;
use App\Services\Ai\AiManager;
use App\Services\PerformanceService;
use App\Services\VisitorLocationService;
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

        // Yapay zeka katmanı (provider-agnostic). AiProvider tip-ipucu,
        // config/ai.php'de seçili sağlayıcıya (Claude/OpenAI/Gemini) çözülür —
        // özellik kodu sağlayıcıyı bilmez. Bkz. App\Services\Ai\AiManager.
        $this->app->singleton(AiManager::class);
        $this->app->bind(AiProvider::class, fn ($app) => $app->make(AiManager::class)->provider());
    }

    public function boot(): void
    {
        // Kullanıcı banlandığında aktif ilanları otomatik pasif yap.
        User::observe(UserObserver::class);

        // Görsel kaydı silindiğinde thumb/medium/large dosyalarını da temizle.
        ListingImage::observe(ListingImageObserver::class);

        // Portfolyo görseli silindiğinde thumb/medium/large dosyalarını da temizle.
        PortfolioItem::observe(PortfolioItemObserver::class);

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

            $countries = Schema::hasTable('countries')
                ? Cache::rememberForever(Country::ACTIVE_LIST_CACHE_KEY, fn () => Country::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['code', 'name_tr', 'emoji'])
                    ->map(fn (Country $country) => $country->only(['code', 'name_tr', 'emoji']))
                    ->all())
                : [];

            // "Acil Yardım"da varsayılan ülke: profildeki kayıtlı ülke değil,
            // o an fiilen bulunulan ülke önceliklidir (ör. seyahatteyken
            // profil bilgisi yanıltıcı olur) — bkz. VisitorLocationService.
            // Sadece Acil Yardım'ın listelediği ülkelerden biriyse kullanılır.
            $visitorCountry = app(VisitorLocationService::class)->resolve(request());
            $emergencyDefaultCountry = auth()->user()?->country_code ?? '';
            if ($visitorCountry && collect($countries)->contains('code', $visitorCountry->code)) {
                $emergencyDefaultCountry = $visitorCountry->code;
            }

            $view->with('emergencyCategories', collect($items)->map(fn (array $item) => (object) $item));
            $view->with('emergencyCountries', collect($countries)->map(fn (array $item) => (object) $item));
            $view->with('emergencyDefaultCountry', $emergencyDefaultCountry);
            $view->with('visitorCountry', $visitorCountry);

            // Header menü linkleri (bkz. App\Models\NavigationLink) — admin
            // panelden ekleyip/çıkarabildiği, sürükle-bırakla sıraladığı liste.
            $navLinks = Schema::hasTable('navigation_links')
                ? NavigationLink::activeCached()
                : collect();
            $view->with('navLinks', $navLinks);

            // Masaüstü header Faz H1 (mega menü): group_key dolu linkler tek
            // bir "Keşfet" açılırında toplanır, boş olanlar tekil link olarak
            // kalır. Mobil şerit henüz bölünmüyor (bkz. Faz H3 planı).
            $view->with('navLinksMega', $navLinks->where('group_key', NavigationLink::GROUP_KESFET)->values());
            $view->with('navLinksSingle', $navLinks->whereNull('group_key')->values());
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

        $this->mergeAiConfig();
    }

    /**
     * Yapay zeka ayarlarını (admin panel → Yapay Zeka) config('ai.*') üzerine
     * runtime'da yaz. Öncelik: DB > env > kod varsayılanı. config:cache
     * gerektirmez — admin panelden anahtar girilince özellik ANINDA aktifleşir.
     */
    protected function mergeAiConfig(): void
    {
        $provider = Settings::get('ai.saglayici');
        if ($provider) {
            Config::set('ai.default', $provider);
        }

        // Seçili sağlayıcının anahtar/modelini yalnızca DB'de değer varsa ez
        // (boşsa env/config varsayılanı korunur).
        $active = config('ai.default');

        $apiKey = Settings::get('ai.api_anahtari');
        if ($apiKey) {
            Config::set("ai.providers.{$active}.api_key", $apiKey);
        }

        $model = Settings::get('ai.model');
        if ($model) {
            Config::set("ai.providers.{$active}.model", $model);
        }

        $quickListing = Settings::get('ai.hizli_ilan_aktif');
        if ($quickListing !== null && $quickListing !== '') {
            Config::set('ai.features.quick_listing', $quickListing === '1');
        }

        $moderation = Settings::get('ai.moderasyon_aktif');
        if ($moderation !== null && $moderation !== '') {
            Config::set('ai.features.image_moderation', $moderation === '1');
        }
    }
}

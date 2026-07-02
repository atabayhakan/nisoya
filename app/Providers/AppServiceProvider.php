<?php

namespace App\Providers;

use App\Models\City;
use App\Models\User;
use App\Observers\UserObserver;
use App\Support\Settings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Kullanıcı banlandığında aktif ilanları otomatik pasif yap.
        User::observe(\App\Observers\UserObserver::class);

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
        $analyticsId = Settings::get('reklam.analytics_measurement_id') ?: env('ANALYTICS_MEASUREMENT_ID');
        $paypal = Settings::get('bagis.paypal_me') ?: env('DONATION_PAYPAL_ME');
        $iban = Settings::get('bagis.iban') ?: env('DONATION_IBAN');
        $ibanOwner = Settings::get('bagis.iban_sahibi') ?: env('DONATION_IBAN_OWNER');

        // Config'i override et (env'den bağımsız)
        Config::set('services.adsense.publisher_id', $adsensePublisher ?: null);
        Config::set('services.analytics.measurement_id', $analyticsId ?: null);
        Config::set('services.donation.paypal_me', $paypal ?: null);
        Config::set('services.donation.iban', $iban ?: null);
        Config::set('services.donation.iban_owner', $ibanOwner ?: null);

        // Yayıncı ID mevcutsa AdSense'i etkin say (env'den bağımsız)
        if ($adsensePublisher) {
            Config::set('services.adsense.enabled', true);
        }
        if ($analyticsId) {
            Config::set('services.analytics.enabled', true);
        }
    }
}

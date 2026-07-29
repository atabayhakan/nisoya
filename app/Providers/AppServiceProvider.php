<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\ListingImage;
use App\Models\NavigationLink;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\ListingImageObserver;
use App\Observers\PortfolioItemObserver;
use App\Observers\UserObserver;
use App\Services\Ai\AiManager;
use App\Services\Growth\Discovery\BusinessDiscoverySource;
use App\Services\Growth\Discovery\FixtureDiscoverySource;
use App\Services\Growth\Discovery\GooglePlacesDiscoverySource;
use App\Services\Growth\Discovery\OverpassDiscoverySource;
use App\Services\PerformanceService;
use App\Services\VisitorLocationService;
use App\Support\Settings;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
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

        // Büyüme Ajanı keşif kaynağı — admin panelden seçilir (config('growth.source')):
        //  overpass → OpenStreetMap (ÜCRETSİZ), google → Google Places (anahtar),
        //  fixture → demo, auto → anahtar varsa Google yoksa fixture (güvenli
        //  varsayılan; testler bunu kullanır). Runner yalnızca arayüzü konuşur.
        $this->app->bind(BusinessDiscoverySource::class, function ($app) {
            $google = fn () => new GooglePlacesDiscoverySource(config('growth.google_places.api_key'));

            return match (config('growth.source', 'auto')) {
                'overpass' => $app->make(OverpassDiscoverySource::class),
                'google' => $google()->isConfigured() ? $google() : $app->make(OverpassDiscoverySource::class),
                'fixture' => new FixtureDiscoverySource,
                default => $google()->isConfigured() ? $google() : new FixtureDiscoverySource,
            };
        });
    }

    public function boot(): void
    {
        // Yönetici (Admin) süper-kullanıcıdır: tüm policy/gate kontrollerini
        // geçer. Bu olmadan sahiplik-temelli policy'ler (ör. ListingPolicy)
        // admin'in başkasının ilanını panelden moderasyonunu 403 ile engelliyordu.
        // null döndürmek "karar verme, normal policy'ye devam et" demektir —
        // yani moderatör/üye için varsayılan yetkilendirme akışı korunur.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // GÜVENLİK (2026-07-27, destek sistemi keşfinde bulundu): giden
        // e-postalarda MARKDOWN ENJEKSİYONU. Blade `{{ }}` yalnız `<` `>`
        // kaçırır; `[` `]` `(` `)` `*` dokunulmadan kalır. Yani iletişim
        // formuna yazılan `[Hesabını doğrula](https://kotu-site.example)`
        // metni, yöneticiye giden bildirim mailinde GERÇEK TIKLANABİLİR
        // BİR LİNK olarak render ediliyordu (NewContactMessageNotification
        // ad/e-posta/mesaj alanlarını ham geçiriyor). withSecuredEncoding()
        // markdown özel karakterlerini kaçırır — tek satırda TÜM mailleri
        // (mevcut + yeni destek yanıtları) korur.
        Markdown::withSecuredEncoding();

        // Kullanıcı banlandığında aktif ilanları otomatik pasif yap.
        User::observe(UserObserver::class);

        // Görsel kaydı silindiğinde thumb/medium/large dosyalarını da temizle.
        ListingImage::observe(ListingImageObserver::class);

        // Portfolyo görseli silindiğinde thumb/medium/large dosyalarını da temizle.
        PortfolioItem::observe(PortfolioItemObserver::class);

        // Şirket silindiğinde logo/kapak + galeri görsellerini diskten temizle.
        Company::observe(CompanyObserver::class);

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
        // env() DEĞİL config(): deploy/deploy.sh:96 `config:cache` çalıştırıyor
        // ve önbelleklenmiş config yüklendiğinde $_ENV doldurulmaz — config
        // dosyalarının DIŞINDAKİ env() çağrıları canlıda sessizce null döner.
        // Altı yedek değer (AdSense yayıncı ID'si, Auto Ads kodu, Analytics
        // ölçüm ID'si, PayPal.me, IBAN, IBAN sahibi) bu yüzden üretimde hiç
        // devreye girmiyordu: panelde bir kayıt yoksa bağış modalı ve reklam
        // yuvaları boş kalıyordu, ama yerelde (config:cache'siz) çalıştığı
        // için fark edilmiyordu.
        //
        // Anahtarların hepsi zaten config/services.php'de tanımlı; oradan
        // okumak hem önbellekli hem önbelleksiz ortamda aynı sonucu verir.
        // DB > config sırası korunuyor.
        $adsensePublisher = Settings::get('reklam.adsense_publisher') ?: config('services.adsense.publisher_id');
        $adsenseAutoAdsCode = Settings::get('reklam.adsense_auto_ads_kod') ?: config('services.adsense.auto_ads_code');
        $analyticsId = Settings::get('reklam.analytics_measurement_id') ?: config('services.analytics.measurement_id');
        $analyticsCustomCode = Settings::get('reklam.analytics_ozel_kod');
        $paypal = Settings::get('bagis.paypal_me') ?: config('services.donation.paypal_me');
        $iban = Settings::get('bagis.iban') ?: config('services.donation.iban');
        $ibanOwner = Settings::get('bagis.iban_sahibi') ?: config('services.donation.iban_owner');
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
        $this->mergeMailConfig();
        $this->mergeGrowthConfig();
        $this->mergeKahyaConfig();
    }

    /**
     * Büyüme Ajanı ayarlarını (admin panel → Büyüme Ajanı) config('growth.*')
     * üzerine runtime'da yaz. Öncelik: DB > env. Google Places anahtarı panelden
     * girilince gerçek keşif ANINDA aktifleşir (yoksa fixture kaynağı) —
     * config:cache/SSH gerekmez. BusinessDiscoverySource bağlaması bu config'i
     * tembel (resolve anında) okuduğu için değeri buradan alır.
     */
    protected function mergeGrowthConfig(): void
    {
        $placesKey = Settings::get('growth.google_places_api_key');
        if ($placesKey) {
            Config::set('growth.google_places.api_key', $placesKey);
        }

        $source = Settings::get('growth.source');
        if ($source) {
            Config::set('growth.source', $source);
        }
    }

    /**
     * E-posta (SMTP) ayarlarını (admin panel → Mail Ayarları) config('mail.*')
     * üzerine runtime'da yaz. Öncelik: DB > env > kod varsayılanı. Böylece
     * e-posta sağlayıcısı değişince .env düzenlemeye/SSH'a gerek kalmaz.
     * Yalnızca DB'de bir SMTP sunucusu (host) girilmişse devreye girer; aksi
     * halde env varsayılanları korunur (Faz 1 · G3).
     */
    protected function mergeMailConfig(): void
    {
        $host = Settings::get('mail.host');
        if (! $host) {
            return; // DB'de ayar yok → env/config korunur
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);

        $port = Settings::get('mail.port');
        if ($port) {
            Config::set('mail.mailers.smtp.port', (int) $port);
        }

        // Kullanıcı adı/parola boş olabilir (kimlik doğrulamasız sunucular) →
        // null olarak yazılır ki eski env değeri sızmasın.
        Config::set('mail.mailers.smtp.username', Settings::get('mail.username') ?: null);
        Config::set('mail.mailers.smtp.password', Settings::get('mail.password') ?: null);

        // Symfony şeması: SSL (465) → smtps (örtük TLS); TLS (587) → smtp (STARTTLS).
        $encryption = Settings::get('mail.encryption');
        Config::set('mail.mailers.smtp.scheme', match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        });

        $fromAddress = Settings::get('mail.from_address');
        if ($fromAddress) {
            Config::set('mail.from.address', $fromAddress);
        }

        $fromName = Settings::get('mail.from_name');
        if ($fromName) {
            Config::set('mail.from.name', $fromName);
        }
    }

    /**
     * Yapay zeka ayarlarını (admin panel → Yapay Zeka) config('ai.*') üzerine
     * runtime'da yaz. Öncelik: DB > env > kod varsayılanı. config:cache
     * gerektirmez — admin panelden anahtar girilince özellik ANINDA aktifleşir.
     */
    /**
     * Kâhya ayarlarını (rapor saati) runtime'da config'e bindir.
     *
     * Rapor saati zamanlayıcı tarafından okunuyor (routes/console.php), yani
     * panelden değiştirilince bir sonraki gün geçerli olur — cron tanımına
     * dokunmak ya da config:cache çalıştırmak gerekmez.
     */
    protected function mergeKahyaConfig(): void
    {
        $saat = Settings::get('kahya.rapor_saati');

        // Biçim DOĞRULANIR: dailyAt() geçersiz bir değerde sessizce çalışmaz,
        // ve zamanlayıcı hatası da sessiz bir hatadır. Bu sistemin bütün amacı
        // sessiz hataları görünür kılmak; kendisi sessizce ölmemeli.
        if ($saat && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $saat)) {
            Config::set('kahya.rapor_saati', $saat);
        }
    }

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

        // Ana anahtar (Faz 1): '0' ise sağlayıcı/anahtar girili ve alt özellikler
        // açık olsa bile TÜM yapay zekayı kapat. Sağlayıcı çökerse ya da maliyeti
        // durdurmak için sahibin tek düğmesi. En sonda ezer ki üstteki bireysel
        // ayarları geçersiz kılsın.
        if (Settings::get('ai.aktif') === '0') {
            Config::set('ai.features.quick_listing', false);
            Config::set('ai.features.image_moderation', false);
        }
    }
}

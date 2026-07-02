<?php

use App\Http\Controllers\BrowseController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SavedSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Keşif (herkese açık)
Route::get('/ilanlar', [BrowseController::class, 'index'])->name('listings.index');
Route::get('/harita', [MapController::class, 'index'])->name('listings.map');
Route::get('/ilanlar/kategori/{category:slug}', [BrowseController::class, 'category'])->name('listings.category');
Route::get('/uye/{user:username}', [ProfileController::class, 'show'])->name('profiles.show');

// Herkese açık ilan detayı
Route::get('/ilan/{listing}/{slug?}', [ListingController::class, 'show'])->name('listings.show');

// Statik sayfalar (işlevsel olanlar kodda kalır; kurumsal metinler yönetilebilir sayfalara taşındı)
Route::get('/nasil-calisir', [PagesController::class, 'nasilCalisir'])->name('pages.how');
Route::get('/iletisim', [PagesController::class, 'iletisim'])->name('pages.contact');

// Çerez tercihleri (kodda sabit; gizlilik sayfası Faz B'de CMS'e taşındı — bkz. StaticPagesSeeder)
Route::view('/cerez-tercihleri', 'pages.cerez-tercihleri')->name('pages.cookie-preferences');

// SEO  (robots.txt → public/robots.txt statik dosyası, nginx doğrudan sunar)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Sağlık kontrolü (uptime monitor'ler için basit JSON)
Route::get('/health', [HealthController::class, 'basic'])->name('health.basic');
// Detaylı health check (admin erişimli) — güvenlik gerektirir
Route::middleware(['auth', 'active.user', 'admin.role'])->prefix('yonetim')->group(function () {
    Route::get('/health/detailed', [HealthController::class, 'detailed'])->name('health.detailed');

    // EXIF harita API'si
    Route::prefix('harita')->name('exif-map.')->group(function () {
        Route::get('/gorseller', [\App\Http\Controllers\ExifMapController::class, 'images'])->name('images');
        Route::get('/cluster', [\App\Http\Controllers\ExifMapController::class, 'clusters'])->name('clusters');
        Route::get('/istatistik', [\App\Http\Controllers\ExifMapController::class, 'stats'])->name('stats');
    });
});

// PWA offline yedek sayfası
Route::view('/offline', 'offline')->name('offline');

// Üye paneli (giriş + e-posta doğrulaması gerekli)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/panel', 'panel.dashboard')->name('dashboard');

    // İlan yönetimi
    Route::get('/panel/ilanlarim', [ListingController::class, 'index'])->name('panel.listings.index');
    Route::get('/panel/ilan/yeni', [ListingController::class, 'create'])->name('panel.listings.create');
    Route::post('/panel/ilan', [ListingController::class, 'store'])
        ->middleware(['honeypot', 'throttle:listing-create'])
        ->name('panel.listings.store');
    Route::get('/panel/ilan/{listing}/duzenle', [ListingController::class, 'edit'])->name('panel.listings.edit');
    Route::match(['put', 'patch'], '/panel/ilan/{listing}', [ListingController::class, 'update'])->name('panel.listings.update');
    Route::delete('/panel/ilan/{listing}', [ListingController::class, 'destroy'])->name('panel.listings.destroy');
    Route::post('/panel/ilan/{listing}/one-cikar', [FeatureController::class, 'store'])->name('panel.listings.feature')->middleware('throttle:listing-feature');

    // Bildirimler
    Route::get('/panel/bildirimler', [NotificationController::class, 'index'])->name('panel.notifications.index');

    // Davet / referans
    Route::get('/panel/davet', [InviteController::class, 'index'])->name('panel.invite');

    // Kayıtlı aramalar
    Route::get('/panel/aramalarim', [SavedSearchController::class, 'index'])->name('panel.saved-searches.index');
    Route::post('/panel/arama-kaydet', [SavedSearchController::class, 'store'])->name('saved-searches.store')->middleware('throttle:search-save');
    Route::delete('/panel/aramalarim/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');

    // Favoriler
    Route::get('/panel/favorilerim', [FavoriteController::class, 'index'])->name('panel.favorites.index');
    Route::post('/favori/{listing}', [FavoriteController::class, 'toggle'])->name('favorites.toggle')->middleware('throttle:favorite-toggle');

    // Mesajlaşma
    Route::get('/panel/mesajlar', [MessageController::class, 'index'])->name('panel.messages.index');
    Route::get('/panel/mesajlar/{conversation}', [MessageController::class, 'show'])->name('panel.messages.show');
    Route::get('/panel/mesajlar/{conversation}/akis', [MessageController::class, 'stream'])->name('panel.messages.stream');
    Route::post('/panel/mesajlar/{conversation}', [MessageController::class, 'store'])
        ->middleware(['honeypot', 'throttle:message-send'])
        ->name('panel.messages.store');
    Route::post('/ilan/{listing}/mesaj', [MessageController::class, 'start'])
        ->middleware(['honeypot', 'throttle:message-start'])
        ->name('messages.start');

    // Değerlendirme & şikayet
    Route::post('/uye/{user:username}/degerlendir', [ReviewController::class, 'store'])
        ->middleware(['honeypot', 'throttle:review-store'])
        ->name('reviews.store');
    Route::post('/ilan/{listing}/sikayet', [ReportController::class, 'store'])
        ->middleware(['honeypot', 'throttle:report-store'])
        ->name('reports.store');

    // Profil ayarları
    Route::get('/panel/profil', [ProfileSettingsController::class, 'edit'])->name('panel.profile.edit');
    Route::put('/panel/profil', [ProfileSettingsController::class, 'update'])->name('panel.profile.update');
    Route::put('/panel/profil/sifre', [ProfileSettingsController::class, 'password'])->name('panel.profile.password');

    // KVKK: Veri silme + veri dışa aktarma
    Route::delete('/panel/profil', [ProfileSettingsController::class, 'destroy'])->name('panel.profile.destroy');
    Route::get('/panel/profil/verilerim', [ProfileSettingsController::class, 'export'])->name('panel.profile.export');

    // İki faktörlü kimlik doğrulama (2FA / TOTP)
    Route::get('/panel/profil/iki-faktor', [\App\Http\Controllers\TwoFactorController::class, 'show'])->name('panel.profile.2fa');
    Route::post('/panel/profil/iki-faktor/kur', [\App\Http\Controllers\TwoFactorController::class, 'setup'])->name('panel.profile.2fa.setup');
    Route::post('/panel/profil/iki-faktor/onayla', [\App\Http\Controllers\TwoFactorController::class, 'confirm'])->name('panel.profile.2fa.confirm');
    Route::post('/panel/profil/iki-faktor/kapat', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('panel.profile.2fa.disable');
});

require __DIR__.'/auth.php';

// Yönetilebilir içerik sayfaları (catch-all — DİĞER TÜM ROTALARDAN SONRA olmalı)
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('pages.dynamic');

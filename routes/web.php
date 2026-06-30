<?php

use App\Http\Controllers\BrowseController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Keşif (herkese açık)
Route::get('/ilanlar', [BrowseController::class, 'index'])->name('listings.index');
Route::get('/harita', [MapController::class, 'index'])->name('listings.map');
Route::get('/ilanlar/kategori/{category:slug}', [BrowseController::class, 'category'])->name('listings.category');
Route::get('/uye/{user:username}', [ProfileController::class, 'show'])->name('profiles.show');

// Herkese açık ilan detayı
Route::get('/ilan/{listing}/{slug?}', [ListingController::class, 'show'])->name('listings.show');

// Statik sayfalar
Route::get('/nasil-calisir', [PagesController::class, 'nasilCalisir'])->name('pages.how');
Route::get('/hakkimizda', [PagesController::class, 'hakkimizda'])->name('pages.about');
Route::get('/iletisim', [PagesController::class, 'iletisim'])->name('pages.contact');
Route::get('/kosullar', [PagesController::class, 'kosullar'])->name('pages.terms');
Route::get('/gizlilik', [PagesController::class, 'gizlilik'])->name('pages.privacy');
Route::get('/sss', [PagesController::class, 'sss'])->name('pages.faq');

// SEO  (robots.txt → public/robots.txt statik dosyası, nginx doğrudan sunar)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Üye paneli (giriş + e-posta doğrulaması gerekli)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/panel', 'panel.dashboard')->name('dashboard');

    // İlan yönetimi
    Route::get('/panel/ilanlarim', [ListingController::class, 'index'])->name('panel.listings.index');
    Route::get('/panel/ilan/yeni', [ListingController::class, 'create'])->name('panel.listings.create');
    Route::post('/panel/ilan', [ListingController::class, 'store'])->name('panel.listings.store');
    Route::get('/panel/ilan/{listing}/duzenle', [ListingController::class, 'edit'])->name('panel.listings.edit');
    Route::match(['put', 'patch'], '/panel/ilan/{listing}', [ListingController::class, 'update'])->name('panel.listings.update');
    Route::delete('/panel/ilan/{listing}', [ListingController::class, 'destroy'])->name('panel.listings.destroy');
    Route::post('/panel/ilan/{listing}/one-cikar', [FeatureController::class, 'store'])->name('panel.listings.feature');

    // Favoriler
    Route::get('/panel/favorilerim', [FavoriteController::class, 'index'])->name('panel.favorites.index');
    Route::post('/favori/{listing}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // Mesajlaşma
    Route::get('/panel/mesajlar', [MessageController::class, 'index'])->name('panel.messages.index');
    Route::get('/panel/mesajlar/{conversation}', [MessageController::class, 'show'])->name('panel.messages.show');
    Route::get('/panel/mesajlar/{conversation}/akis', [MessageController::class, 'stream'])->name('panel.messages.stream');
    Route::post('/panel/mesajlar/{conversation}', [MessageController::class, 'store'])->name('panel.messages.store');
    Route::post('/ilan/{listing}/mesaj', [MessageController::class, 'start'])->name('messages.start');

    // Değerlendirme & şikayet
    Route::post('/uye/{user:username}/degerlendir', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/ilan/{listing}/sikayet', [ReportController::class, 'store'])->name('reports.store');

    // Profil ayarları
    Route::get('/panel/profil', [ProfileSettingsController::class, 'edit'])->name('panel.profile.edit');
    Route::put('/panel/profil', [ProfileSettingsController::class, 'update'])->name('panel.profile.update');
    Route::put('/panel/profil/sifre', [ProfileSettingsController::class, 'password'])->name('panel.profile.password');
});

require __DIR__.'/auth.php';

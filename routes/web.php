<?php

use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyGalleryController;
use App\Http\Controllers\CompanyReviewController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventInvitationController;
use App\Http\Controllers\EventMediaController;
use App\Http\Controllers\ExifMapController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\HappyMomentsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobBookmarkController;
use App\Http\Controllers\JobBrowseController;
use App\Http\Controllers\JobFeatureController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\JobSavedSearchController;
use App\Http\Controllers\Kahya\CikisController;
use App\Http\Controllers\Kahya\SesGeriBildirimController;
use App\Http\Controllers\ListingAvailabilityController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NabizController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PaylasimKartiController;
use App\Http\Controllers\PaymentLinkController;
use App\Http\Controllers\PortfolioItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\PropertyBrowseController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\QuickListingController;
use App\Http\Controllers\QuickSearchController;
use App\Http\Controllers\RehberController;
use App\Http\Controllers\RehberGeriBildirimController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\TemaOzellestiriciController;
use App\Http\Controllers\TemsiliGorselController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\VehicleBrowseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Keşif (herkese açık)
Route::get('/ilanlar', [BrowseController::class, 'index'])->name('listings.index');
Route::get('/emlak', [PropertyBrowseController::class, 'index'])->middleware('module:emlak')->name('properties.index');
Route::get('/vasita', [VehicleBrowseController::class, 'index'])->middleware('module:vasita')->name('vehicles.index');
Route::get('/harita', [MapController::class, 'index'])->name('listings.map');
Route::get('/ilanlar/kategori/{category:slug}', [BrowseController::class, 'category'])->name('listings.category');
Route::get('/uye/{user:username}', [ProfileController::class, 'show'])->name('profiles.show');
Route::get('/nabiz', [NabizController::class, 'index'])->name('nabiz');
Route::get('/mutlu-anlar', [HappyMomentsController::class, 'index'])->name('happy-moments');

// Herkese açık ilan detayı
// DİKKAT: kart rotası detaydan ÖNCE gelmeli — {slug?} "kart.png"i de yakalar
// ve kart isteği ilan sayfasına 301'lenirdi.
Route::get('/ilan/{listing}/kart.png', PaylasimKartiController::class)->name('listings.card');
Route::get('/ilan/{listing}/{slug?}', [ListingController::class, 'show'])->name('listings.show');

// Davetiye (herkese açık — misafirler hesap açmadan LCV verir).
// Modül kapalıysa tüm davetiye yüzeyi 404 (bkz. App\Support\Modules · G4).
Route::middleware('module:davetiye')->group(function () {
    Route::get('/davet/{token}', [EventInvitationController::class, 'show'])->name('davet.show');
    Route::get('/davet/{token}/takvim.ics', [EventInvitationController::class, 'ics'])->name('davet.ics');
    Route::post('/davet/{token}/lcv', [EventInvitationController::class, 'rsvp'])
        ->middleware(['honeypot', 'throttle:rsvp'])
        ->name('davet.rsvp');
    Route::post('/davet/{token}/medya', [EventMediaController::class, 'store'])
        ->middleware(['honeypot', 'throttle:event-upload'])
        ->name('davet.media.store');
    Route::delete('/davet/{token}/medya/{media}', [EventMediaController::class, 'destroy'])
        ->middleware('throttle:event-upload')
        ->name('davet.media.destroy');
});

// İş ilanları (herkese açık keşif + detay + şirket sayfası + adaylar).
// Modül kapalıysa tüm iş ilanları yüzeyi 404.
Route::middleware('module:is_ilanlari')->group(function () {
    Route::get('/isler', [JobBrowseController::class, 'index'])->name('jobs.index');
    Route::get('/is/{job}/{slug?}', [JobListingController::class, 'show'])->name('jobs.show');
    Route::get('/sirket/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::get('/adaylar', [CandidateController::class, 'index'])->name('candidates.index');
});

// Hızlı arama (header komut paleti / Cmd+K, Faz H2) — herkese açık, salt-okunur JSON
Route::get('/arama/hizli', [QuickSearchController::class, 'index'])
    ->middleware('throttle:quick-search')
    ->name('search.quick');

// Statik sayfalar (işlevsel olanlar kodda kalır; kurumsal metinler yönetilebilir sayfalara taşındı)
Route::get('/nasil-calisir', [PagesController::class, 'nasilCalisir'])->name('pages.how');
Route::get('/iletisim', [PagesController::class, 'iletisim'])->name('pages.contact');
Route::post('/iletisim', [ContactMessageController::class, 'store'])
    ->middleware(['honeypot', 'throttle:contact-store'])
    ->name('contact.store');

/*
 * ERİŞİM POSTASI: LİSTEDEN ÇIKIŞ + SES GERİ BİLDİRİMİ (2026-08-07)
 *
 * Üçü de herkese açık ve oturumsuz — muhatap üye değil, bizden bir posta almış
 * bir yabancı ya da doğrudan Amazon SNS. İkinci ve üçüncü rota CSRF'ten muaf
 * (bkz. bootstrap/app.php): isteği gönderen taraf bizim sayfamız değil,
 * alıcının posta istemcisi ya da AWS — jeton taşıyamazlar.
 *
 * Çok segmentli yollar bilinçli: dosyanın sonundaki tek-segmentli catch-all
 * (`/{slug}`) ve ülke rotası (`/{ulke}`) bunları gölgeleyemesin.
 */
Route::get('/e-posta/cikis/{jeton}', [CikisController::class, 'goster'])
    ->where('jeton', '[A-Za-z0-9]{16,64}')
    ->middleware('throttle:eposta-cikis')
    ->name('kahya.cikis');
Route::post('/e-posta/cikis/{jeton}', [CikisController::class, 'cik'])
    ->where('jeton', '[A-Za-z0-9]{16,64}')
    ->middleware('throttle:eposta-cikis')
    ->name('kahya.cikis.uygula');
Route::post('/webhook/ses-geri-bildirim', SesGeriBildirimController::class)
    ->middleware('throttle:ses-geri-bildirim')
    ->name('kahya.ses.geri-bildirim');

// Çerez tercihleri (kodda sabit; gizlilik sayfası Faz B'de CMS'e taşındı — bkz. StaticPagesSeeder)
Route::view('/cerez-tercihleri', 'pages.cerez-tercihleri')->name('pages.cookie-preferences');

// SEO  (robots.txt → public/robots.txt statik dosyası, nginx doğrudan sunar)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// PWA manifest'i — DİNAMİK: theme_color marka rengini/temayı izler.
// public/ altında aynı adlı statik dosya BIRAKILMAMALI, yoksa nginx onu
// sunar ve bu rota hiç çalışmaz (dosya bu yüzden silindi).
Route::get('/manifest.webmanifest', ManifestController::class)->name('manifest');

// Sağlık kontrolü (uptime monitor'ler için basit JSON)
Route::middleware('throttle:health-basic')
    ->get('/health', [HealthController::class, 'basic'])->name('health.basic');
// Detaylı health check (admin erişimli) — güvenlik gerektirir
Route::middleware(['auth', 'active.user', 'admin.role'])->prefix('yonetim')->group(function () {
    Route::middleware('throttle:health-detailed')
        ->get('/health/detailed', [HealthController::class, 'detailed'])->name('health.detailed');

    // EXIF harita API'si
    Route::prefix('harita')->name('exif-map.')->middleware('throttle:exif-map')->group(function () {
        Route::get('/gorseller', [ExifMapController::class, 'images'])->name('images');
        Route::get('/cluster', [ExifMapController::class, 'clusters'])->name('clusters');
        Route::get('/istatistik', [ExifMapController::class, 'stats'])->name('stats');
    });

    // Yedek indirme (dosyalar web'e kapalı storage/app/backups altında)
    Route::get('/yedek/{name}/indir', BackupDownloadController::class)
        ->where('name', '[A-Za-z0-9._-]+')
        ->name('admin.backup.download');
});

// PWA offline yedek sayfası
Route::view('/offline', 'offline')->name('offline');

// Üye paneli (giriş + e-posta doğrulaması gerekli)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/panel', PanelController::class)->name('dashboard');

    // İlan yönetimi
    Route::get('/panel/ilanlarim', [ListingController::class, 'index'])->name('panel.listings.index');
    Route::get('/panel/ilan/yeni', [ListingController::class, 'create'])->name('panel.listings.create');

    // Kamera-önce hızlı ilan (Faz M3)
    Route::get('/panel/ilan/hizli', [QuickListingController::class, 'create'])->name('panel.listings.quick');
    Route::post('/panel/ilan/analiz', [QuickListingController::class, 'analyze'])
        ->middleware('throttle:quick-listing-analyze')
        ->name('panel.listings.analyze');
    // Serbest metinden taslak: birkaç kelime ya da WhatsApp'tan yapıştırılan
    // ilan metni. `ai-listing-draft` sınırı dakikalık VE günlük — her çağrı
    // dışarıya para harcıyor (bkz. bootstrap/app.php).
    Route::post('/panel/ilan/metin-analiz', [QuickListingController::class, 'analyzeText'])
        ->middleware('throttle:ai-listing-draft')
        ->name('panel.listings.analyze-text');
    Route::post('/panel/ilan', [ListingController::class, 'store'])
        ->middleware(['honeypot', 'throttle:listing-create'])
        ->name('panel.listings.store');
    Route::get('/panel/ilan/{listing}/duzenle', [ListingController::class, 'edit'])->name('panel.listings.edit');
    Route::match(['put', 'patch'], '/panel/ilan/{listing}', [ListingController::class, 'update'])->name('panel.listings.update');
    Route::post('/panel/ilan/{listing}/yayinla', [ListingController::class, 'yayinla'])
        ->middleware('throttle:listing-create')
        ->name('panel.listings.publish');
    // Üyenin kendi yayın kararı: kaldır/geri aç. Silmenin geri alınabilir
    // alternatifi — bkz. ListingController::yayindanKaldir.
    Route::post('/panel/ilan/{listing}/yayindan-kaldir', [ListingController::class, 'yayindanKaldir'])
        ->middleware('throttle:listing-visibility')
        ->name('panel.listings.unpublish');
    Route::post('/panel/ilan/{listing}/geri-yayinla', [ListingController::class, 'geriYayinla'])
        ->middleware('throttle:listing-visibility')
        ->name('panel.listings.republish');
    Route::delete('/panel/ilan/{listing}', [ListingController::class, 'destroy'])->name('panel.listings.destroy');
    Route::patch('/panel/ilan/{listing}/gorsel/{image}/hizala', [ListingController::class, 'alignImage'])->name('panel.listings.images.align');
    // Temsilî görsel — YALNIZ görseli olmayan hizmet ilanlarında (kapı
    // TemsiliGorselUretici::uygunMu içinde). Görsel üretimi para harcadığı
    // için ayrı ve dar bir hız sınırı var.
    Route::post('/panel/ilan/{listing}/temsili-gorsel', [TemsiliGorselController::class, 'store'])
        ->middleware('throttle:temsili-gorsel')
        ->name('panel.listings.representative-image');
    Route::post('/panel/ilan/{listing}/one-cikar', [FeatureController::class, 'store'])->name('panel.listings.feature')->middleware('throttle:listing-feature');
    Route::post('/panel/ilan/{listing}/takvim', [ListingAvailabilityController::class, 'store'])->name('panel.listings.availability.store');
    Route::delete('/panel/ilan/{listing}/takvim/{range}', [ListingAvailabilityController::class, 'destroy'])->name('panel.listings.availability.destroy');

    // Bildirimler
    Route::get('/panel/bildirimler', [NotificationController::class, 'index'])->name('panel.notifications.index');

    // Web push aboneliği (Faz M1.3)
    Route::post('/panel/push-abonelik', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/panel/push-abonelik', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    // Davet / referans
    Route::get('/panel/davet', [InviteController::class, 'index'])->name('panel.invite');

    // Davetiyeler (etkinlik + LCV yönetimi)
    Route::get('/panel/etkinlikler', [EventController::class, 'index'])->name('panel.events.index');
    Route::get('/panel/etkinlik/yeni', [EventController::class, 'create'])
        ->middleware('module:davetiye')
        ->name('panel.events.create');
    Route::post('/panel/etkinlik', [EventController::class, 'store'])
        ->middleware(['honeypot', 'throttle:event-create', 'module:davetiye'])
        ->name('panel.events.store');
    Route::get('/panel/etkinlik/{event}', [EventController::class, 'show'])->name('panel.events.show');
    Route::get('/panel/etkinlik/{event}/duzenle', [EventController::class, 'edit'])->name('panel.events.edit');
    Route::match(['put', 'patch'], '/panel/etkinlik/{event}', [EventController::class, 'update'])->name('panel.events.update');
    Route::delete('/panel/etkinlik/{event}', [EventController::class, 'destroy'])->name('panel.events.destroy');
    Route::get('/panel/etkinlik/{event}/qr', [EventController::class, 'qr'])->name('panel.events.qr');
    Route::get('/panel/etkinlik/{event}/album.zip', [EventController::class, 'downloadAlbum'])->name('panel.events.album');
    Route::post('/panel/etkinlik/{event}/medya/{media}/onayla', [EventController::class, 'approveMedia'])->name('panel.events.media.approve');
    Route::delete('/panel/etkinlik/{event}/medya/{media}', [EventController::class, 'destroyMedia'])->name('panel.events.media.destroy');
    Route::post('/panel/etkinlik/{event}/misafir/{guest}/engelle', [EventController::class, 'toggleGuestBlock'])->name('panel.events.guests.block');

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
    Route::post('/panel/mesajlar/{conversation}/yaziyor', [MessageController::class, 'typing'])
        ->middleware('throttle:message-typing')
        ->name('panel.messages.typing');
    Route::post('/panel/mesajlar/{conversation}', [MessageController::class, 'store'])
        ->middleware(['honeypot', 'throttle:message-send'])
        ->name('panel.messages.store');
    Route::delete('/panel/mesajlar/{conversation}/mesaj/{message}', [MessageController::class, 'recall'])
        ->name('panel.messages.recall');

    // Anlaşmalar (sohbet içinden — Nisoya para akışını görmez, sadece niyet defteri)
    Route::post('/panel/mesajlar/{conversation}/anlasma', [DealController::class, 'propose'])
        ->middleware('throttle:deal-action')->name('panel.deals.propose');
    Route::post('/panel/anlasma/{deal}/kabul', [DealController::class, 'accept'])
        ->middleware('throttle:deal-action')->name('panel.deals.accept');
    Route::post('/panel/anlasma/{deal}/tamamla', [DealController::class, 'complete'])
        ->middleware('throttle:deal-action')->name('panel.deals.complete');
    Route::post('/panel/anlasma/{deal}/iptal', [DealController::class, 'cancel'])
        ->middleware('throttle:deal-action')->name('panel.deals.cancel');
    Route::post('/panel/anlasma/{deal}/sorun', [DealController::class, 'dispute'])
        ->middleware('throttle:deal-action')->name('panel.deals.dispute');

    Route::post('/ilan/{listing}/mesaj', [MessageController::class, 'start'])
        ->middleware(['honeypot', 'throttle:message-start'])
        ->name('messages.start');

    // Profilden doğrudan mesaj — aktif ilanı olmayan yeteneğe ulaşmanın TEK yolu
    // (ilan üzerinden mesaj o kişiye kapalıydı). Aynı honeypot + oran sınırı.
    Route::post('/profil/{user:username}/mesaj', [MessageController::class, 'startWithUser'])
        ->middleware(['honeypot', 'throttle:message-start'])
        ->name('messages.startWithUser');

    // Değerlendirme & şikayet
    Route::post('/uye/{user:username}/degerlendir', [ReviewController::class, 'store'])
        ->middleware(['honeypot', 'throttle:review-store'])
        ->name('reviews.store');
    Route::post('/ilan/{listing}/sikayet', [ReportController::class, 'store'])
        ->middleware(['honeypot', 'throttle:report-store'])
        ->name('reports.store');
    Route::post('/uye/{user:username}/dolandiricilik-bildir', [ReportController::class, 'reportFraud'])
        ->middleware(['honeypot', 'throttle:report-store'])
        ->name('users.report-fraud');

    // Profil ayarları
    Route::get('/panel/profil', [ProfileSettingsController::class, 'edit'])->name('panel.profile.edit');
    Route::put('/panel/profil', [ProfileSettingsController::class, 'update'])->name('panel.profile.update');
    Route::put('/panel/profil/sifre', [ProfileSettingsController::class, 'password'])->name('panel.profile.password');
    Route::patch('/panel/profil/avatar-hizala', [ProfileSettingsController::class, 'alignAvatar'])->name('panel.profile.avatar-align')->middleware('throttle:avatar-align');

    // Ödeme linki/QR kodu (Nisoya para akışını görmez — sadece satıcının kendi ödeme sayfasına yönlendirir)
    Route::post('/panel/profil/odeme-linki', [PaymentLinkController::class, 'store'])->name('panel.payment-links.store');
    Route::delete('/panel/profil/odeme-linki/{paymentLink}', [PaymentLinkController::class, 'destroy'])->name('panel.payment-links.destroy');

    // Portfolyo (geçmiş iş örnekleri)
    Route::post('/panel/profil/portfolyo', [PortfolioItemController::class, 'store'])->name('panel.portfolio.store')->middleware('throttle:portfolio-store');
    Route::delete('/panel/profil/portfolyo/{portfolioItem}', [PortfolioItemController::class, 'destroy'])->name('panel.portfolio.destroy');

    // Gurbet Günlüğü (Nisoya Nabzı — anonim hikaye duvarı)
    Route::post('/nabiz/hikaye', [StoryController::class, 'store'])
        ->middleware(['honeypot', 'throttle:story-store'])
        ->name('nabiz.stories.store');

    // --- İş ilanları modülü (panel) ---
    // Şirket profili
    Route::get('/panel/sirket', [CompanyController::class, 'edit'])->name('panel.company.edit');
    Route::put('/panel/sirket', [CompanyController::class, 'update'])->name('panel.company.update');
    Route::post('/panel/sirket/galeri', [CompanyGalleryController::class, 'store'])->name('panel.company.gallery.store')->middleware('throttle:company-gallery-store');
    Route::delete('/panel/sirket/galeri/{companyGalleryImage}', [CompanyGalleryController::class, 'destroy'])->name('panel.company.gallery.destroy');

    // İşverenin iş ilanları
    Route::get('/panel/is-ilanlarim', [JobListingController::class, 'index'])->name('panel.jobs.index');
    Route::get('/panel/is-ilani/yeni', [JobListingController::class, 'create'])
        ->middleware('module:is_ilanlari')
        ->name('panel.jobs.create');
    Route::post('/panel/is-ilani', [JobListingController::class, 'store'])
        ->middleware(['honeypot', 'throttle:job-create', 'module:is_ilanlari'])
        ->name('panel.jobs.store');
    Route::get('/panel/is-ilani/{job}/duzenle', [JobListingController::class, 'edit'])->name('panel.jobs.edit');
    Route::match(['put', 'patch'], '/panel/is-ilani/{job}', [JobListingController::class, 'update'])->name('panel.jobs.update');
    Route::patch('/panel/is-ilani/{job}/durum', [JobListingController::class, 'updateStatus'])->name('panel.jobs.status');
    Route::delete('/panel/is-ilani/{job}', [JobListingController::class, 'destroy'])->name('panel.jobs.destroy');
    Route::get('/panel/is-ilani/{job}/basvurular', [JobApplicationController::class, 'applicants'])->name('panel.jobs.applicants');
    Route::post('/panel/is-ilani/{job}/one-cikar', [JobFeatureController::class, 'store'])
        ->name('panel.jobs.feature')
        ->middleware('throttle:job-listing-feature');

    // Başvurular (aday + işveren)
    Route::post('/is/{job}/basvur', [JobApplicationController::class, 'store'])
        ->middleware(['honeypot', 'throttle:job-apply'])
        ->name('jobs.apply');
    Route::get('/panel/basvurularim', [JobApplicationController::class, 'mine'])->name('panel.applications.mine');
    Route::patch('/panel/basvuru/{application}/durum', [JobApplicationController::class, 'updateStatus'])
        ->middleware('throttle:application-status')
        ->name('panel.applications.status');
    Route::get('/panel/basvuru/{application}/cv', [JobApplicationController::class, 'downloadCv'])->name('panel.applications.cv');

    // Canlı tema özelleştirici (yalnız admin — yetki controller'da da doğrulanır).
    Route::post('/panel/gorunum', [TemaOzellestiriciController::class, 'kaydet'])
        ->middleware('throttle:tema-ozellestirici')
        ->name('panel.gorunum.kaydet');
    Route::post('/panel/gorunum/sifirla', [TemaOzellestiriciController::class, 'sifirla'])
        ->middleware('throttle:tema-ozellestirici')
        ->name('panel.gorunum.sifirla');

    // İş ilanı yer imleri (aday)
    Route::get('/panel/is-yer-imlerim', [JobBookmarkController::class, 'index'])->name('panel.job-bookmarks.index');
    Route::post('/is-yer-imi/{job}', [JobBookmarkController::class, 'toggle'])->name('job-bookmarks.toggle')->middleware('throttle:job-bookmark-toggle');

    // Şirket değerlendirmeleri (aday, sadece başvurmuşsa)
    Route::post('/sirket/{company}/degerlendir', [CompanyReviewController::class, 'store'])
        ->middleware(['honeypot', 'throttle:company-review-store'])
        ->name('company-reviews.store');

    // İş ilanı uyarıları (kayıtlı arama)
    Route::get('/panel/is-aramalarim', [JobSavedSearchController::class, 'index'])->name('panel.job-saved-searches.index');
    Route::post('/panel/is-arama-kaydet', [JobSavedSearchController::class, 'store'])->name('job-saved-searches.store')->middleware('throttle:job-search-save');
    Route::delete('/panel/is-aramalarim/{jobSavedSearch}', [JobSavedSearchController::class, 'destroy'])->name('job-saved-searches.destroy');

    // KVKK: Veri silme + veri dışa aktarma
    Route::delete('/panel/profil', [ProfileSettingsController::class, 'destroy'])->name('panel.profile.destroy');
    // Export tüm kişisel verinin ağır bir dökümünü üretir — kullanıcı başına
    // 6/dk ile sınırla (scrape/kötüye kullanım koruması).
    Route::get('/panel/profil/verilerim', [ProfileSettingsController::class, 'export'])
        ->middleware('throttle:6,1')->name('panel.profile.export');

    // İki faktörlü kimlik doğrulama (2FA / TOTP)
    Route::get('/panel/profil/iki-faktor', [TwoFactorController::class, 'show'])->name('panel.profile.2fa');
    Route::post('/panel/profil/iki-faktor/kur', [TwoFactorController::class, 'setup'])->name('panel.profile.2fa.setup');
    // onayla/kapat 6 haneli TOTP kodu doğrular — brute-force'a karşı throttle.
    Route::post('/panel/profil/iki-faktor/onayla', [TwoFactorController::class, 'confirm'])
        ->middleware('throttle:6,1')->name('panel.profile.2fa.confirm');
    Route::post('/panel/profil/iki-faktor/kapat', [TwoFactorController::class, 'disable'])
        ->middleware('throttle:6,1')->name('panel.profile.2fa.disable');

    // Passkey kayıt/yönetim uçları artık laravel/passkeys paketinden
    // (GET /user/passkeys/options + POST /user/passkeys + DELETE
    // /user/passkeys/{passkey}) — bkz. config/passkeys.php.
});

require __DIR__.'/auth.php';

/*
 * Ülke-Adaptif Rehber (F1) — /de, /de/koeln, /de/koeln/vekaletname.
 *
 * KONUM ÖNEMLİ: catch-all /{slug}'dan hemen ÖNCE. {ulke} deseni [a-z]{2}
 * olduğu için tek segmentli 2 harfli yollar (ör. /de) buraya, diğer her
 * tek segment catch-all'a düşer. 2 harfli CMS slug'ı PageForm'da yasak
 * (K5) — yani bu rota hiçbir CMS sayfasını gölgeleyemez. Geçersiz/pasif
 * ülke kodu controller'da 404 olur.
 */
Route::middleware('module:rehber')->group(function () {
    Route::post('/rehber/geri-bildirim/{islemKaydi}', [RehberGeriBildirimController::class, 'store'])
        ->middleware(['honeypot', 'throttle:rehber-geri-bildirim'])
        ->name('rehber.geribildirim');

    Route::get('/{ulke}', [RehberController::class, 'ulke'])
        ->where('ulke', '[a-z]{2}')
        ->name('rehber.ulke');
    Route::get('/{ulke}/{temsilcilik}', [RehberController::class, 'temsilcilik'])
        ->where(['ulke' => '[a-z]{2}', 'temsilcilik' => '[a-z0-9\-]+'])
        ->name('rehber.temsilcilik');
    Route::get('/{ulke}/{temsilcilik}/{islem}', [RehberController::class, 'islem'])
        ->where(['ulke' => '[a-z]{2}', 'temsilcilik' => '[a-z0-9\-]+', 'islem' => '[a-z0-9\-]+'])
        ->name('rehber.islem');
});

// Yönetilebilir içerik sayfaları (catch-all — DİĞER TÜM ROTALARDAN SONRA olmalı)
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('pages.dynamic');

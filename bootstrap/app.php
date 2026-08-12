<?php

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureUserCanAccessAdminPanel;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HoneypotMiddleware;
use App\Http\Middleware\PerformanceMetricsMiddleware;
use App\Http\Middleware\QueryLogMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TemaViewYollari;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Tema motoru (Vitrin): StartSession core web grubunda olduğu
            // için append edilen bu middleware her zaman ondan sonra koşar
            // (admin tema-önizleme oturumu okunabilir).
            TemaViewYollari::class,
            EnsureUserIsActive::class,
            PerformanceMetricsMiddleware::class,
            QueryLogMiddleware::class,
            SecurityHeaders::class,
        ]);

        /*
         * CSRF MUAFİYETİ — YALNIZ BU İKİSİ (2026-08-07).
         *
         * İkisinde de isteği gönderen taraf bizim bir sayfamız değil, o yüzden
         * jeton taşıyamaz:
         *   · Tek-tık listeden çıkış (RFC 8058) — POST'u alıcının posta
         *     istemcisi (Gmail/Outlook) atar.
         *   · SES geri bildirimi — POST'u Amazon SNS atar.
         *
         * İkisinin de kendi yetkilendirmesi var ve CSRF'in yerini o tutuyor:
         * çıkışta tahmin edilemez, tek mesaja bağlı jeton; SES'te SNS imza
         * doğrulaması + konu (TopicArn) eşleşmesi. Listeye üçüncü bir yol
         * eklemeden önce "bu isteğin kimliğini ne kanıtlıyor?" sorusunun
         * cevabı olmalı.
         */
        $middleware->validateCsrfTokens(except: [
            'e-posta/cikis/*',
            'webhook/ses-geri-bildirim',
        ]);

        $middleware->alias([
            'active.user' => EnsureUserIsActive::class,
            'honeypot' => HoneypotMiddleware::class,
            'admin.role' => EnsureUserCanAccessAdminPanel::class,
            'module' => EnsureModuleEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Giriş yapmış AMA yönetim paneline erişemeyen normal üye /yonetim'e
        // giderse çıplak "403 Forbidden" yerine dostça mesajla kendi paneline
        // yönlendirilir. ÇIKIŞ yapılmaz (kullanıcı sadece yanlış URL'e gitti;
        // logout oturumu bozar + kötüye-kullanım vektörü olurdu). Guest'e
        // dokunulmaz (Filament login'e yönlendirir); yetkili zaten 403 almaz.
        $exceptions->render(function (Throwable $e, Request $request) {
            $isForbidden = $e instanceof AuthorizationException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 403);

            if ($isForbidden
                && $request->is('yonetim*')
                // API uçları (health/exif harita) JSON döndürür — onlar 403
                // kalsın; yalnızca panel SAYFALARINA giden normal üye yönlendirilir.
                && ! $request->is('yonetim/health/*', 'yonetim/harita/*')
                && ! $request->expectsJson()
                && $request->user()
                && ! ($request->user()->role?->canAccessAdminPanel() ?? false)) {
                return redirect()->route('dashboard')
                    ->with('status', 'Yönetim paneli yalnızca yöneticiler içindir.');
            }

            return null; // diğer tüm durumlar normal işlensin
        });
    })
    ->booted(function () {
        // Hassas işlemler için rate limit policy'leri
        RateLimiter::for('listing-create', fn (Request $request) => Limit::perMinute(12)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('listing-feature', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        /*
         * AI ile ilan taslağı (metin ve fotoğraf). Diğer sınırlardan farkı:
         * burada her istek DIŞARIYA PARA HARCIYOR. Nisoya'nın geliri bağış +
         * reklam, komisyon yok — yani kötüye kullanım doğrudan sahibin cebinden
         * çıkar.
         *
         * Bu yüzden İKİ sınır birden: dakikalık sınır otomatik döngüyü keser,
         * GÜNLÜK sınır ise yavaş ama sürekli bir sömürüyü keser. Yalnız
         * dakikalık koysaydık, dakikada 5 istekle günde 7200 çağrı yapılabilirdi.
         *
         * Günde 30: gerçek bir kullanıcı bir oturumda birkaç ilan açar; 30
         * denemeye ulaşan kişi ya kötüye kullanıyor ya da özellik onun için
         * çalışmıyor demektir (ikisinde de durmak doğru).
         */
        RateLimiter::for('ai-listing-draft', fn (Request $request) => [
            Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
            Limit::perDay(30)->by($request->user()?->id ?: $request->ip()),
        ]);

        /*
         * Temsilî görsel: metin analizinden PAHALI (görsel üretimi) ve bir
         * ilana bir kez gerekiyor. Günlük sınır kasten düşük — 5 hizmet ilanı
         * açan bir kullanıcı bile buna çarpmaz, otomatik döngü hemen çarpar.
         */
        RateLimiter::for('temsili-gorsel', fn (Request $request) => [
            Limit::perMinute(2)->by($request->user()?->id ?: $request->ip()),
            Limit::perDay(10)->by($request->user()?->id ?: $request->ip()),
        ]);

        /*
         * İlan çevirisi: metin üretimi, görselden ucuz ama bedava değil. Bir
         * ilana bir kez gerekiyor; metni değiştirip yeniden çevirtmek makul
         * ama gün içinde onlarca kez değil.
         */
        RateLimiter::for('ilan-cevirisi', fn (Request $request) => [
            Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()),
            Limit::perDay(30)->by($request->user()?->id ?: $request->ip()),
        ]);

        // Listeden çıkış: gerçek kişi bir kez basar. Cömert ama sınırsız değil —
        // jeton kaba kuvvetle aranamasın.
        RateLimiter::for('eposta-cikis', fn (Request $request) => Limit::perMinute(20)->by($request->ip())
        );

        // SES geri bildirimi: uç herkese açık ve doğrulama her istekte imza
        // hesaplıyor. SNS gerçek yükte dakikada bu kadarına yaklaşmaz; tavan
        // sahte isteklerin doğrulama maliyetini sömürmesini engellemek için.
        RateLimiter::for('ses-geri-bildirim', fn (Request $request) => Limit::perMinute(60)->by($request->ip())
        );

        // İlanı yayından kaldır / geri yayınla — bir tıklık, ucuz bir UPDATE.
        // Birkaç ilanını sırayla kapatan üyeye cömert, döngüye sınırlı.
        RateLimiter::for('listing-visibility', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-listing-feature', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('search-save', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-search-save', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('favorite-toggle', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-bookmark-toggle', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        // Tema özelleştirici: sahip deneyerek ilerler, ama her deneme
        // kaydedilmez (önizleme istemcide). Cömert ama sınırsız değil.
        RateLimiter::for('tema-ozellestirici', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
        );

        // Kanban panosunda durum değişikliği bir sürükleme kadar ucuz; toplu
        // triyaj yapan işvereni engellemeyecek ama otomatik bir döngünün
        // kuyruğu doldurmasına izin vermeyecek bir tavan.
        RateLimiter::for('application-status', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('portfolio-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        // Rehber "güncel mi?" geri bildirimi anonim ve oturumsuz — gerçek bir
        // kullanıcı aynı sayfada en fazla birkaç kez bildirir; seri gönderim bot izi.
        RateLimiter::for('rehber-geri-bildirim', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('company-gallery-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('message-send', fn (Request $request) => Limit::perMinute(40)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('message-start', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        // "Yazıyor..." sinyali (Faz M4) — istemci ~2.5 sn'de bir pingler;
        // cömert ama sınırlı: kullanıcı başına 40/dk.
        RateLimiter::for('message-typing', fn (Request $request) => Limit::perMinute(40)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('event-create', fn (Request $request) => Limit::perMinute(6)->by($request->user()?->id ?: $request->ip())
        );

        // Anlaşma aksiyonları (teklif/kabul/tamamla/iptal/sorun) — kullanıcı başına 20/dk.
        RateLimiter::for('deal-action', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );

        // LCV hesapsız verildiği için IP bazlı (aynı ağdan birden çok misafir olabilir — cömert ama sınırlı)
        RateLimiter::for('rsvp', fn (Request $request) => Limit::perMinute(15)->by($request->ip())
        );

        // Anı akışı yüklemeleri de hesapsız — IP bazlı (istek başına 10 dosya zaten sınırlı)
        RateLimiter::for('event-upload', fn (Request $request) => Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('review-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('company-review-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('report-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('story-store', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        // İletişim formu misafirlere de açık (login gerekmez) — sadece IP bazlı,
        // daha sıkı bir limit (spam riski diğer login-gerektiren formlara göre daha yüksek).
        RateLimiter::for('contact-store', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('job-create', fn (Request $request) => Limit::perMinute(12)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('job-apply', fn (Request $request) => Limit::perMinute(15)->by($request->user()?->id ?: $request->ip())
        );

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->input('email').$request->ip())
        );

        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(6)->by($request->user()?->id ?: $request->ip())
        );

        // Reverse geocoding (Nominatim 1 req/s rate limit'i):
        // Admin başına dakikada max 60 işlem — yeterli 1000+ görsel için.
        RateLimiter::for('reverse-geocode', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        // === Admin API & health endpoint rate limit'leri (adım 9) ===
        // Public /health (uptime monitor'ler için) — IP başına 60/dk.
        // UptimeRobot her 5 dakikada bir ping atar → 12 istek/saat → limit rahat.
        RateLimiter::for('health-basic', fn (Request $request) => Limit::perMinute(60)->by($request->ip())
        );

        // Admin /health/detailed — hassas sistem bilgisi, admin başına 30/dk.
        RateLimiter::for('health-detailed', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
        );

        // Admin EXIF harita API'leri (gorseller/cluster/istatistik) — 60/dk.
        // Çok büyük dataset için rate limit gerekli (1000+ marker'ın scrape'i).
        RateLimiter::for('exif-map', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        // Header hızlı arama (Cmd+K komut paleti, Faz H2) — herkese açık,
        // debounce'lu canlı sorgu; scrape/kötüye kullanıma karşı 60/dk.
        RateLimiter::for('quick-search', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
        );

        // Kamera-önce hızlı ilan analizi (Faz M3) — her analiz bir Claude
        // vision çağrısı (maliyetli); kullanıcı başına 10/dk.
        RateLimiter::for('quick-listing-analyze', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
        );

        // Avatar kaydır+zum hizalama (2026-07-17) — her istek GD ile tam bir
        // decode+crop+webp-encode yapıyor (eskiden sadece 2 integer yazan
        // odak-noktası uçundan çok daha ağır); kullanıcı başına 20/dk normal
        // düzenleme oturumuna cömert, script'lenmiş kötüye kullanıma sınırlı.
        RateLimiter::for('avatar-align', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
        );
    })
    ->create();

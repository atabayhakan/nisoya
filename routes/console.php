<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Süresi dolan öne çıkan ilanları her gün normale döndür
Schedule::command('listings:expire-featured')->daily()->withoutOverlapping();
Schedule::command('job-listings:expire-featured')->daily()->withoutOverlapping();

// Kayıtlı aramalara uyan yeni ilanlar için günlük uyarı e-postaları
Schedule::command('alerts:saved-searches')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('job-alerts:saved-searches')->dailyAt('09:00')->withoutOverlapping();

// Header'daki ülke bayrağı için MaxMind GeoLite2 veritabanını güncel tut
// (MaxMind veriyi haftalık yayınlar; lisans anahtarı yoksa sessizce atlanır).
Schedule::command('geoip:update')->weekly()->withoutOverlapping();

// Etkinlik medyası saklama politikası: 11. ayda ev sahibini uyar, 12. ayda sil
Schedule::command('events:purge-media')->dailyAt('03:30')->withoutOverlapping();

// Günlük tam yedek (veritabanı + medya) + eski yedekleri temizle.
// Sahibin geliştirici olmadan siteyi kurtarabilmesi için güvenlik ağı
// (bkz. Admin → Sistem → Yedekleme). Saat config/backup.php'den ayarlanabilir.
Schedule::command('backup:run')->dailyAt(config('backup.daily_time', '04:00'))->withoutOverlapping();

// Zamanı gelen ileri tarihli sayfaların footer menü önbelleğini tazele
// (sayfanın kendisi zaten otomatik görünür — bkz. Faz 4 zamanlanmış yayın).
Schedule::command('content:publish-due')->everyFifteenMinutes()->withoutOverlapping();

// Günlük Kâhya raporu — yöneticiye "bugün şunlar oldu" e-postası.
//
// SAAT 07:30 SEÇİMİ RASTGELE DEĞİL: 03:30'daki medya temizliğinden ve
// 04:00'taki yedekten SONRA çalışır ki rapor "son yedek" durumunu doğru
// göstersin. Saat panelden değiştirilebilsin diye config'ten okunuyor
// (backup.daily_time ile aynı desen).
//
// withoutOverlapping: rapor uzun sürerse ertesi günkü koşu üst üste binmesin.
Schedule::command('kahya:gunluk-rapor')
    ->dailyAt(config('kahya.rapor_saati', '07:30'))
    ->withoutOverlapping();

// F5 — haftalık öğrenme koşusu: sahibin geçen haftaki kararlarından ders
// damıtır. Pazartesi 06:00: haftanın sinyalleri tamamlanmış, günün raporundan
// (07:30) ÖNCE — yeni dersler aynı sabahki sohbetlerde hemen geçerli olsun.
Schedule::command('kahya:ders-cikar')
    ->weeklyOn(1, '06:00')
    ->withoutOverlapping();

// Büyüme Ajanı keşif işlerini (RunDiscoveryJob, 'database' kuyruğu) her dakika
// işle. Ayrı bir queue worker/supervisor gerektirmez — scheduler kuyruğu
// boşaltır. --stop-when-empty: iş yoksa hemen çıkar; --max-time: dakikayı aşma;
// withoutOverlapping: aynı anda iki işleyici olmasın (çift işleme job kilidiyle
// zaten önlenir, bu ek güvence).
Schedule::command('queue:work database --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()->withoutOverlapping();

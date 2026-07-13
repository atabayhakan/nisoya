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

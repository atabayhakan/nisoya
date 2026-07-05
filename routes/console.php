<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Süresi dolan öne çıkan ilanları her gün normale döndür
Schedule::command('listings:expire-featured')->daily();
Schedule::command('job-listings:expire-featured')->daily();

// Kayıtlı aramalara uyan yeni ilanlar için günlük uyarı e-postaları
Schedule::command('alerts:saved-searches')->dailyAt('09:00');
Schedule::command('job-alerts:saved-searches')->dailyAt('09:00');

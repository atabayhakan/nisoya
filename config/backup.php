<?php

/*
|--------------------------------------------------------------------------
| Yedekleme ayarları (BackupService)
|--------------------------------------------------------------------------
| Site sahibinin geliştirici olmadan yönetebilmesi için basit tutuldu.
| Değerler .env'den override edilebilir; hiçbiri zorunlu değil.
|
| Bkz. app/Services/BackupService.php · Admin → Sistem → Yedekleme.
*/

return [

    // Sunucudaki mysqldump ikilisinin yolu. Genelde PATH'te olduğu için
    // sadece "mysqldump" yeter; farklı bir konumdaysa tam yol verilebilir.
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),

    // Kaç günden eski yedekler otomatik temizlensin (en yeni yedek her zaman
    // korunur). 0/negatif verilirse temizlik yapılmaz.
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 7),

    // Günlük otomatik yedeğin çalışacağı saat (sunucu saati, HH:MM).
    'daily_time' => env('BACKUP_DAILY_TIME', '04:00'),
];

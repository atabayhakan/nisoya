<?php

namespace App\Support;

/**
 * `php artisan config:cache` ŞU AN mı koşuyor?
 *
 * ---------------------------------------------------------------------------
 * NEDEN BÖYLE BİR KONTROL GEREKİYOR
 *
 * `config:cache` yapılandırmayı diske yazmadan önce uygulamayı BOOT EDER.
 * Yani servis sağlayıcıların `boot()` içinde `Config::set()` ile yazdığı
 * ÇALIŞMA ANI değerleri de `bootstrap/cache/config.php` dosyasına gömülür.
 *
 * Nisoya'da bu bir sızıntı üretiyordu: `Settings::SIRLI_ANAHTARLAR`
 * listesindeki sırlar veritabanında ŞİFRELİ durur, ama `AppServiceProvider`
 * onları boot'ta çözüp config'e yazdığı için önbellek dosyasına DÜZ METİN
 * olarak düşüyorlardı.
 *
 * 2026-08-10'da canlıda ölçüldü: SES SMTP parolası DB'de 256 hane şifreliyken
 * `bootstrap/cache/config.php` içinde 44 hane açık metindi. Dosya izinleri
 * `-rw-r--r--`. Üstelik ayarı veritabanından silmek yetmiyordu — gömülü kopya
 * yerinde kalıyor ve mailer "silahlı" görünmeye devam ediyordu.
 *
 * ---------------------------------------------------------------------------
 * ÇÖZÜM NEDEN "YAZMA", "SONRADAN TEMİZLE" DEĞİL
 *
 * Sağlayıcı her istekte boot olur; sırları önbelleğe yazmanın hiçbir faydası
 * yok. Değer çalışma anında zaten DB'den tazeleniyor. Dolayısıyla önbellek
 * kurulurken sır yazmayı ATLAMAK davranışı hiç değiştirmez, yalnız diskteki
 * kopyayı ortadan kaldırır.
 *
 * ---------------------------------------------------------------------------
 * `optimize` DE SAYILIR
 *
 * `php artisan optimize` içeriden `callSilently('config:cache')` çağırır —
 * AYNI süreçte. O yüzden `$_SERVER['argv'][1]` 'config:cache' değil 'optimize'
 * olur. İkisini birden yakalamazsak `optimize` yolundan sızıntı geri gelir.
 * (Laravel'in kendi `runningConsoleCommand()`'ı da argv[1]'e bakar; burada
 * doğrudan okumamızın tek sebebi test edilebilirlik.)
 */
final class YapilandirmaOnbellegi
{
    /** Önbellek kuran komutlar — ikisi de aynı süreçte config:cache çalıştırır. */
    private const KOMUTLAR = ['config:cache', 'optimize'];

    public static function aliniyorMu(): bool
    {
        if (! app()->runningInConsole()) {
            return false;
        }

        return in_array($_SERVER['argv'][1] ?? null, self::KOMUTLAR, true);
    }
}

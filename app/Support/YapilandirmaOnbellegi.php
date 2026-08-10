<?php

namespace App\Support;

use Symfony\Component\Console\Input\ArgvInput;

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
 * AYNI süreçte. O yüzden komut adı 'config:cache' değil 'optimize' olur.
 * İkisini birden yakalamazsak `optimize` yolundan sızıntı geri gelir.
 *
 * ---------------------------------------------------------------------------
 * KOMUT ADI `argv[1]` DEĞİLDİR — DÜŞMANCA İNCELEME BUNU YAKALADI (2026-08-10)
 *
 * İlk sürüm doğrudan `$_SERVER['argv'][1]`'e bakıyordu. Dört bağımsız inceleyici
 * aynı deliği ölçtü: Symfony seçenekleri komut adından ÖNCE kabul eder, yani
 *
 *     php artisan --env=production config:cache
 *     php artisan -n config:cache
 *     php artisan -q optimize
 *
 * çağrılarında argv[1] bir SEÇENEKTİR. Kapı false döner, dört sır da düz metin
 * dosyaya yazılır — üstelik çıkış kodu 0 ve ekranda "Configuration cached
 * successfully". Yani güvenlik güvencesi, insanın yazması son derece olağan bir
 * bayrak yüzünden SESSİZCE buharlaşıyordu.
 *
 * Çare: komut adını tahmin etmeyi bırak, Symfony'nin kendi ayrıştırıcısına sor.
 * `ArgvInput::getFirstArgument()` seçenekleri (hem `--env=x` hem `--env x`
 * biçimini) atlayıp ilk gerçek argümanı döndürür — bu iş için yazılmış metot.
 *
 * İkinci ağ olarak argv'nin TAMAMINA da bakılır. Yanlış pozitif riski yok
 * sayılır (bir komuta birebir "config:cache" değerinde argüman verilmesi
 * gerekirdi) ve yanlış pozitifin bedeli sızıntının olmaması yönündedir.
 *
 * ---------------------------------------------------------------------------
 * BİLİNEN SINIR
 *
 * Süreç içinden `Artisan::call('config:cache')` çağrılırsa argv dış komutu
 * gösterir ve kapı kapanmaz. Depoda böyle bir çağrı YOK; olmadığını
 * `SirlarOnbellegeYazilmazTest::test_kod_icinden_config_cache_cagrilmiyor`
 * mühürlüyor — biri eklerse test kırmızıya döner ve bu notu okur.
 */
final class YapilandirmaOnbellegi
{
    /** Önbellek kuran komutlar — ikisi de aynı süreçte config:cache çalıştırır. */
    public const KOMUTLAR = ['config:cache', 'optimize'];

    public static function aliniyorMu(): bool
    {
        if (! app()->runningInConsole()) {
            return false;
        }

        $argv = $_SERVER['argv'] ?? [];

        /*
         * Yalnız `artisan` çağrıları. Yapılandırmayı diske yazan tek yol budur;
         * dahası bu daraltma ölçülmüş bir yanlış pozitifi kapatır: `phpunit
         * --filter optimize` çağrısında argv 'optimize' jetonunu taşır ve
         * aşağıdaki ikinci ağ tetiklenirdi — o testler boyunca veritabanı
         * katmanı sessizce atlanır, sebebi görünmeyen kırıklar çıkardı.
         */
        if (basename((string) ($argv[0] ?? '')) !== 'artisan') {
            return false;
        }

        // 1) Doğru yol: Symfony komut adını kendisi çözsün (seçenekleri atlar).
        if (in_array((new ArgvInput($argv))->getFirstArgument(), self::KOMUTLAR, true)) {
            return true;
        }

        // 2) İkinci ağ: jeton nerede geçerse geçsin yakala (getFirstArgument'ın
        //    beklenmedik bir biçimde yanıldığı hâller için).
        return (bool) array_intersect($argv, self::KOMUTLAR);
    }
}

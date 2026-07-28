<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * `env()` kullanım kuralının bekçisi (2026-07-29).
 *
 * BULUNAN HATA: AppServiceProvider'da altı `env()` çağrısı vardı ve hepsi
 * ÜRETİMDE sessizce null dönüyordu. deploy/deploy.sh `config:cache`
 * çalıştırıyor; önbelleklenmiş config yüklendiğinde Laravel .env dosyasını
 * hiç okumaz, dolayısıyla config dosyalarının DIŞINDAKİ env() çağrıları
 * null verir.
 *
 * Etkilenenler: AdSense yayıncı ID'si, Auto Ads kodu, Analytics ölçüm ID'si,
 * PayPal.me bağlantısı, IBAN ve IBAN sahibi. Panelde kayıt yoksa bağış modalı
 * ve reklam yuvaları boş kalıyordu.
 *
 * NEDEN HİÇ FARK EDİLMEDİ: yerelde ve testlerde `config:cache` çalışmaz, bu
 * yüzden env() beklendiği gibi çalışır. Hata YALNIZCA üretimde ortaya çıkar
 * ve hiçbir hata mesajı üretmez — değerler yalnızca "boş" görünür.
 *
 * Kural: env() SADECE config/ altında çağrılır. Uygulama kodu config()
 * kullanır. Bu test kuralı makineyle dayatır.
 */
class EnvKullanimiTest extends TestCase
{
    /** Kuralın uygulandığı dizinler (config/ bilinçli olarak DIŞARIDA). */
    private const TARANAN_DIZINLER = ['app', 'routes', 'database', 'bootstrap'];

    public function test_config_disinda_env_cagrisi_yok(): void
    {
        $kok = dirname(__DIR__, 2);
        $ihlaller = [];

        foreach (self::TARANAN_DIZINLER as $dizin) {
            $yol = $kok.DIRECTORY_SEPARATOR.$dizin;

            if (! is_dir($yol)) {
                continue;
            }

            $gezgin = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($yol));

            foreach ($gezgin as $dosya) {
                if (! str_ends_with((string) $dosya, '.php')) {
                    continue;
                }

                $satirlar = file((string) $dosya) ?: [];

                foreach ($satirlar as $no => $satir) {
                    // Yorum satırlarını atla: kuraldan yorumda söz etmek
                    // ("env() DEĞİL config()") onu kullanmak değildir.
                    $kirpik = ltrim($satir);

                    if (str_starts_with($kirpik, '//') || str_starts_with($kirpik, '*') || str_starts_with($kirpik, '/*')) {
                        continue;
                    }

                    if (preg_match('/(?<![\w>$])env\s*\(/', $satir)) {
                        $ihlaller[] = sprintf(
                            '%s:%d → %s',
                            str_replace($kok.DIRECTORY_SEPARATOR, '', (string) $dosya),
                            $no + 1,
                            trim($satir)
                        );
                    }
                }
            }
        }

        $this->assertSame([], $ihlaller, sprintf(
            "config/ dışında env() çağrısı bulundu. Bunlar üretimde `config:cache` sonrası SESSİZCE null döner.\n".
            "Değeri bir config dosyasına taşıyın ve config('...') ile okuyun.\n\n%s",
            implode("\n", $ihlaller)
        ));
    }
}

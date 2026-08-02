<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Kuyruk değişmezi bekçisi (açık işler envanteri: "--tries tutarsızlığı").
 *
 * DEĞİŞMEZ: kuyruk bağlantısının retry_after'ı, worker'ın --timeout'undan
 * BÜYÜK olmalı. Küçük kalırsa kuyruk, hâlâ ÇALIŞAN işi "takıldı" sanıp
 * ikinci kez salıverir ve e-posta bildirimleri çift gider. Bu tam da bir
 * config-sürüklenmesi hatasıdır: iki değer iki ayrı dosyada yaşar
 * (config/queue.php ↔ deploy/supervisor-nisoya-worker.conf) ve birini
 * değiştiren diğerini hatırlamak zorunda kalmamalı — bekçi hatırlar.
 *
 * Ayrıca dev betiği ile üretim worker'ının --tries'ı aynı tutulur: kuyruk
 * davranışını yerelde farklı deneyip canlıda başka yaşamak, "bende
 * çalışıyordu" sınıfı hataların kaynağıdır.
 */
class KuyrukDegismeziTest extends TestCase
{
    /** Şablondaki worker komutunun --timeout'u (yoksa Laravel varsayılanı 60). */
    private function sablonTimeout(): int
    {
        $konf = file_get_contents(base_path('deploy/supervisor-nisoya-worker.conf'));
        $this->assertIsString($konf);

        preg_match('/queue:work[^\n]*/', $konf, $komut);
        $this->assertNotEmpty($komut, 'Şablonda queue:work komutu bulunamadı.');

        return preg_match('/--timeout=(\d+)/', $komut[0], $e) === 1 ? (int) $e[1] : 60;
    }

    public function test_retry_after_worker_timeoutundan_buyuk(): void
    {
        $timeout = $this->sablonTimeout();

        foreach (['database', 'redis'] as $baglanti) {
            $retryAfter = (int) config("queue.connections.{$baglanti}.retry_after");

            $this->assertGreaterThan(
                $timeout,
                $retryAfter,
                "queue.connections.{$baglanti}.retry_after ({$retryAfter}) worker --timeout'undan ({$timeout}) büyük olmalı — aksi çift gönderim üretir."
            );
        }
    }

    public function test_dev_betigi_ve_uretim_workeri_ayni_tries_ile_kosar(): void
    {
        $composer = file_get_contents(base_path('composer.json'));
        $sablon = file_get_contents(base_path('deploy/supervisor-nisoya-worker.conf'));

        preg_match('/queue:listen[^"]*--tries=(\d+)/', (string) $composer, $dev);
        preg_match('/queue:work[^\n]*--tries=(\d+)/', (string) $sablon, $uretim);

        $this->assertNotEmpty($dev, 'composer.json dev betiğinde queue:listen --tries bulunamadı.');
        $this->assertNotEmpty($uretim, 'Worker şablonunda --tries bulunamadı.');
        $this->assertSame($uretim[1], $dev[1], 'Dev betiği ile üretim worker\'ının --tries değeri ayrıştı.');
    }
}

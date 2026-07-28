<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * `scripts/audit.php` güvenlik kapısının bekçisi (2026-07-29).
 *
 * BULUNAN HATA: kapı hiçbir zaman hiçbir şeyi engellememişti. Composer'ın
 * JSON'unda `advisories` paket adına göre gruplanmış bir sözlüktür ve her
 * değeri bir advisory LİSTESİDİR:
 *
 *   {"advisories": {"guzzlehttp/guzzle": [{...}, {...}, {...}]}}
 *
 * Betik bunu düz bir advisory listesi sanıp `$advisory['severity']` okuyordu;
 * okuduğu şey bir liste olduğu için severity daima boş kalıyor ve sayaçlar
 * hep 0 çıkıyordu. Enjekte edilmiş sahte bir `critical` advisory bile CI'ı
 * yeşil geçiyordu.
 *
 * Bu tür bir hata "test yeşil" ile yakalanamaz — kapının KIRMIZI olması
 * gereken durumu ayrıca kanıtlamak gerekir. Aşağıdaki testler tam olarak
 * bunu yapar: gerçek composer şemasında kritik/yüksek girdiler için çıkış
 * kodunun 1, orta/düşük için 0 olduğunu doğrular.
 */
class AuditKapisiTest extends TestCase
{
    private string $gecici;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gecici = sys_get_temp_dir().'/nisoya-audit-'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->gecici)) {
            unlink($this->gecici);
        }

        parent::tearDown();
    }

    /**
     * Betiği çalıştırır.
     *
     * @param  array<string, mixed>  $advisories
     * @return array{kod: int, cikti: string}
     */
    private function calistir(array $advisories): array
    {
        file_put_contents($this->gecici, json_encode([
            'advisories' => $advisories,
            'abandoned' => [],
        ]));

        $betik = dirname(__DIR__, 2).'/scripts/audit.php';

        $cikti = [];
        $kod = 0;
        exec(sprintf('%s %s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($betik), escapeshellarg($this->gecici)), $cikti, $kod);

        return ['kod' => $kod, 'cikti' => implode("\n", $cikti)];
    }

    /**
     * Composer'ın gerçek şeması: paket => advisory listesi.
     *
     * @return array<string, mixed>
     */
    private function paket(string $ad, string $seviye, int $adet = 1): array
    {
        $liste = [];

        for ($i = 0; $i < $adet; $i++) {
            $liste[] = [
                'advisoryId' => 'PKSA-test-'.$i,
                'packageName' => $ad,
                'severity' => $seviye,
                'title' => "Test advisory {$i}",
            ];
        }

        return [$ad => $liste];
    }

    public function test_kritik_advisory_kapiyi_kirar(): void
    {
        $sonuc = $this->calistir($this->paket('sahte/paket', 'critical'));

        $this->assertSame(1, $sonuc['kod'], "Kritik advisory CI'ı kırmalıydı. Çıktı:\n".$sonuc['cikti']);
        $this->assertStringContainsString('critical=1', $sonuc['cikti']);
    }

    public function test_yuksek_advisory_kapiyi_kirar(): void
    {
        $sonuc = $this->calistir($this->paket('sahte/paket', 'high'));

        $this->assertSame(1, $sonuc['kod'], "Yüksek advisory CI'ı kırmalıydı. Çıktı:\n".$sonuc['cikti']);
        $this->assertStringContainsString('high=1', $sonuc['cikti']);
    }

    /**
     * Asıl regresyon testi: aynı pakette birden çok advisory.
     *
     * Guzzle'ın gerçek durumu buydu (3 medium). Eski betik listeyi tek bir
     * advisory sanıp hepsini kaçırıyordu.
     */
    public function test_ayni_pakette_coklu_advisory_tek_tek_sayilir(): void
    {
        $sonuc = $this->calistir($this->paket('guzzlehttp/guzzle', 'medium', 3));

        $this->assertSame(0, $sonuc['kod'], 'Orta seviye kapıyı kırmamalı');
        $this->assertStringContainsString('medium=3', $sonuc['cikti'], "Üç advisory'nin üçü de sayılmalıydı. Çıktı:\n".$sonuc['cikti']);
    }

    public function test_orta_seviye_engellemez_ama_gorunur_uyari_verir(): void
    {
        $sonuc = $this->calistir($this->paket('sahte/paket', 'medium'));

        $this->assertSame(0, $sonuc['kod'], 'Orta seviye kapıyı kırmamalı');
        $this->assertStringContainsString('::warning::', $sonuc['cikti'], 'Orta seviye görünür bir uyarı üretmeliydi — sessiz kalırsa borç birikir');
    }

    public function test_advisory_yoksa_temiz_gecer(): void
    {
        $sonuc = $this->calistir([]);

        $this->assertSame(0, $sonuc['kod']);
        $this->assertStringContainsString('Güvenlik açığı yok', $sonuc['cikti']);
    }

    /**
     * Kritik, orta seviyelerin arasına saklanamaz.
     */
    public function test_kritik_ortalarin_arasinda_kaybolmaz(): void
    {
        $sonuc = $this->calistir(array_merge(
            $this->paket('guzzlehttp/guzzle', 'medium', 3),
            $this->paket('tehlikeli/paket', 'critical')
        ));

        $this->assertSame(1, $sonuc['kod'], "Kritik advisory orta seviyelerin arasında kayboldu. Çıktı:\n".$sonuc['cikti']);
        $this->assertStringContainsString('critical=1', $sonuc['cikti']);
        $this->assertStringContainsString('medium=3', $sonuc['cikti']);
    }

    /**
     * Severity alanı okunamıyorsa bu SESSİZ kalmamalı — betiğin ilk hatası
     * tam olarak severity'nin sessizce boş kalmasıydı.
     */
    public function test_okunamayan_seviye_uyari_uretir(): void
    {
        $sonuc = $this->calistir(['sahte/paket' => [['advisoryId' => 'X', 'title' => 'seviyesiz']]]);

        $this->assertStringContainsString('bilinmeyen=1', $sonuc['cikti']);
        $this->assertStringContainsString('::warning::', $sonuc['cikti']);
    }
}

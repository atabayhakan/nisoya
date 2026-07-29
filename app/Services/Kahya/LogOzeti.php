<?php

namespace App\Services\Kahya;

use Illuminate\Support\Carbon;

/**
 * Son 24 saatin hata günlüğü özeti — "ne bozuk" bölümünün kaynağı.
 *
 * NEDEN ÖZET, NEDEN HAM LOG DEĞİL: log dosyası binlerce satır olabilir ve
 * aynı hata yüzlerce kez tekrar eder. Rapora ham satır koymak raporu okunmaz
 * yapar. Burada imzaya göre gruplanır: aynı hatanın 400 kez olması TEK satır
 * ama "400 kez" bilgisiyle — ki asıl sinyal odur.
 *
 * ---------------------------------------------------------------------------
 * GİZLİLİK — BU SINIFIN EN ÖNEMLİ KISMI
 *
 * Laravel'in `QueryException` mesajı SQL'i, BAĞLANMIŞ DEĞERLERİ ve bağlantı
 * ayrıntılarını (veritabanı adı, sürücü) içerir. Bağlanmış değerler kullanıcı
 * verisidir: e-posta adresi, telefon, oturum jetonu, parola sıfırlama anahtarı
 * hepsi bir sorgu parametresi olarak geçebilir.
 *
 * Bu özet e-posta ile gönderiliyor ve ileride MCP üzerinden okunacak.
 * Dolayısıyla mesaj METNİ ASLA taşınmaz — yalnız istisna sınıfı, dosya, satır
 * ve tekrar sayısı taşınır. "Hangi hata, nerede, kaç kez" sorusunu yanıtlamak
 * için bu yeter; "hangi kullanıcının verisiyle" sorusu raporun işi değildir,
 * sahip sunucuda loga bakar.
 * ---------------------------------------------------------------------------
 *
 * TEST LOGLARI ELENİR: `laravel-test-*.log` paralel test koşularının çıktısıdır,
 * üretim hatası değildir. Yerelde onlarcası birikir ve raporu boğar.
 */
class LogOzeti
{
    /** Rapora giren seviyeler — WARNING ve altı gürültüdür. */
    private const SEVIYELER = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    /**
     * @return array{taranan_dosya: int, toplam: int, imzalar: list<array{seviye: string, sinif: string, yer: string, adet: int}>, uyari: ?string}
     */
    public function ozetle(int $saat = 24): array
    {
        $dizin = storage_path('logs');

        if (! is_dir($dizin)) {
            return ['taranan_dosya' => 0, 'toplam' => 0, 'imzalar' => [], 'uyari' => 'Log dizini yok'];
        }

        $esik = now()->subHours($saat);
        $dosyalar = array_filter(
            glob($dizin.'/*.log') ?: [],
            // Test çıktıları üretim hatası değildir.
            fn (string $y) => ! str_contains(basename($y), 'laravel-test-')
        );

        $imzalar = [];
        $toplam = 0;

        foreach ($dosyalar as $dosya) {
            // Dosyayı belleğe tamamen almadan satır satır oku: üretimde log
            // yüzlerce MB olabilir ve bu komut kuyrukta çalışır.
            $tutamac = @fopen($dosya, 'r');

            if ($tutamac === false) {
                continue;
            }

            while (($satir = fgets($tutamac)) !== false) {
                // Laravel biçimi: [2026-07-29 07:14:32] production.ERROR: ...
                if (! preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\]\s+\S+\.(\w+):/', $satir, $m)) {
                    continue;
                }

                $seviye = strtoupper($m[2]);

                if (! in_array($seviye, self::SEVIYELER, true)) {
                    continue;
                }

                try {
                    if (Carbon::parse($m[1])->lt($esik)) {
                        continue;
                    }
                } catch (\Throwable) {
                    continue;
                }

                $toplam++;

                // İMZA: yalnız sınıf + dosya:satır. Mesaj metni TAŞINMAZ
                // (docblock'taki gizlilik gerekçesi).
                $sinif = preg_match('/\b([A-Za-z_\\\\]+(?:Exception|Error))\b/', $satir, $s) ? $s[1] : 'bilinmeyen';
                $yer = preg_match('#([\w/\\\\.-]+\.php):(\d+)#', $satir, $y) ? basename($y[1]).':'.$y[2] : '—';

                $anahtar = $seviye.'|'.$sinif.'|'.$yer;
                $imzalar[$anahtar] = ($imzalar[$anahtar] ?? 0) + 1;
            }

            fclose($tutamac);
        }

        arsort($imzalar);

        $liste = [];
        foreach (array_slice($imzalar, 0, 5, true) as $anahtar => $adet) {
            [$seviye, $sinif, $yer] = explode('|', $anahtar);
            $liste[] = ['seviye' => $seviye, 'sinif' => $sinif, 'yer' => $yer, 'adet' => $adet];
        }

        return [
            'taranan_dosya' => count($dosyalar),
            'toplam' => $toplam,
            'imzalar' => $liste,
            'uyari' => $dosyalar === [] ? 'Üretim log dosyası bulunamadı — loglama kapalı olabilir' : null,
        ];
    }
}

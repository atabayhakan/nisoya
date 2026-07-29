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
     * @param  ?int  $saat  null ise `config('kahya.log_penceresi_saat')`
     * @param  ?string  $dizin  Varsayılan storage/logs. Testler kendi geçici
     *                          dizinlerini verir — yoksa ortamdaki gerçek
     *                          loglar iddiaları boğar ve test kırılgan olur.
     *                          BU PARAMETRE MCP YÜZEYİNE AÇILMAZ: keyfi dizin
     *                          okutmak yol geçişi (path traversal) demektir.
     * @return array{taranan_dosya: int, toplam: int, imzalar: list<array{seviye: string, sinif: string, yer: string, adet: int}>, uyari: ?string}
     */
    public function ozetle(?int $saat = null, ?string $dizin = null): array
    {
        $saat = max(1, $saat ?? (int) config('kahya.log_penceresi_saat', 24));
        $dizin ??= storage_path('logs');

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

                $yer = preg_match('#([\w/\\\\.-]+\.php):(\d+)#', $satir, $y) ? basename($y[1]).':'.$y[2] : '—';

                // İMZA: istisna sınıfı varsa o kullanılır.
                if (preg_match('/\b([A-Za-z_\\\\]+(?:Exception|Error))\b/', $satir, $s)) {
                    $sinif = $s[1];
                } else {
                    // İstisna yoksa bu, uygulamanın kendi Log::error() çağrısıdır.
                    // "bilinmeyen" demek eyleme dönüştürülemez bir sinyaldir
                    // (yerelde 96 kayıt böyle gruplanıyordu). Bu kod tabanında
                    // log mesajları GELİŞTİRİCİ TARAFINDAN YAZILMIŞ SABİT
                    // metinlerdir ("Görsel işleme başarısız oldu" gibi);
                    // kullanıcı verisi mesajda değil, ARKASINDAKİ context
                    // JSON'undadır.
                    //
                    // Bu yüzden yalnız context'ten ÖNCEKİ kısım alınır ve
                    // üstüne bir güvenlik ağı geçirilir: e-posta benzeri ve
                    // uzun sayısal diziler yine de temizlenir. Sabit metin
                    // varsayımı bir gün bozulursa (biri mesaja değişken
                    // gömerse) ağ tutar.
                    $sinif = $this->guvenliMesaj($satir);
                }

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

    /**
     * İstisna sınıfı olmayan log satırından GÜVENLİ bir imza üretir.
     *
     * Alınan: `production.ERROR:` ile ilk `{` / `[` arasındaki metin — yani
     * geliştiricinin yazdığı sabit mesaj. Alınmayan: context dizisi, ki
     * kullanıcı verisi oradadır.
     *
     * ÜSTÜNE GÜVENLİK AĞI: e-posta benzeri diziler ve 4+ haneli sayılar yine
     * de maskelenir. Amaç "sabit metin" varsayımının bir gün bozulmasına karşı
     * korunmak — biri mesaja değişken gömerse ağ tutar, çünkü bu özet e-posta
     * ile gidiyor ve ileride MCP üzerinden okunacak.
     */
    private function guvenliMesaj(string $satir): string
    {
        // "…ERROR: " sonrası, ilk context ayracına kadar.
        if (! preg_match('/\.\w+:\s*(.*?)(?:\s*[\{\[]|$)/', $satir, $m)) {
            return 'bilinmeyen';
        }

        $mesaj = trim($m[1]);

        if ($mesaj === '') {
            return 'bilinmeyen';
        }

        // Güvenlik ağı — varsayım bozulursa veriyi yine de maskele.
        $mesaj = preg_replace('/[\w.+-]+@[\w.-]+/', '[e-posta]', $mesaj) ?? $mesaj;
        $mesaj = preg_replace('/\b\d{4,}\b/', '[sayı]', $mesaj) ?? $mesaj;

        return mb_substr($mesaj, 0, 70);
    }
}

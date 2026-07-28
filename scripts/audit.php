<?php

/**
 * `composer audit --format=json` çıktısını okuyup CI kapısını çalıştırır.
 * Lokal kullanım: php scripts/audit.php composer-audit.json
 *
 * ------------------------------------------------------------------------
 * BU BETİK SESSİZCE BOZUKTU — aşağıdaki yapı o hatadan geliyor.
 *
 * Composer'ın JSON'unda `advisories` bir PAKET SÖZLÜĞÜDÜR ve her değeri o
 * pakete ait advisory'lerin LİSTESİDİR:
 *
 *   {"advisories": {"guzzlehttp/guzzle": [{...severity...}, {...}, {...}]}}
 *
 * Eski sürüm bunu düz bir advisory listesi sanıp `$advisory['severity']`
 * okuyordu; okuduğu şey aslında bir LİSTE olduğu için severity daima boş
 * kalıyor, sayaçlar hep 0 çıkıyordu. Sonuç: kapı yalnız "medium'u saymıyor"
 * değildi — KRİTİK bir advisory'yi bile yeşil geçiriyordu. Enjekte edilmiş
 * sahte bir `critical` kaydıyla doğrulandı; o senaryo artık
 * tests/Unit/AuditKapisiTest.php içinde donduruldu.
 *
 * Bu yüzden iki şey birden yapılıyor:
 *   1) İç içe yapı doğru geziliyor (tek nesne gelirse de kırılmıyor).
 *   2) medium/low artık GÖRÜNÜR uyarı üretiyor. Kapıyı kırmıyorlar — ama
 *      sayılmadıkları sürece borç sessizce birikiyordu: guzzle'ın üç medium
 *      advisory'si aylarca CI'da hiç görünmedi.
 * ------------------------------------------------------------------------
 */
$path = $argv[1] ?? null;
if (! $path || ! file_exists($path)) {
    fwrite(STDERR, "Kullanım: php scripts/audit.php <composer-audit.json>\n");
    exit(2);
}

$data = json_decode(file_get_contents($path), true);
if (! is_array($data) || ! isset($data['advisories'])) {
    fwrite(STDERR, "Geçersiz JSON veya advisories bulunamadı\n");
    exit(2);
}

/**
 * Paket sözlüğünü düz advisory listesine indirger.
 *
 * @return list<array<string, mixed>>
 */
$duzlestir = static function (mixed $advisories): array {
    if (! is_array($advisories)) {
        return [];
    }

    $sonuc = [];

    foreach ($advisories as $paketAdvisoryleri) {
        if (! is_array($paketAdvisoryleri)) {
            continue;
        }

        // Beklenen: paket => [advisory, advisory, ...]
        // Savunma: composer tek advisory'yi doğrudan verirse (severity/
        // advisoryId anahtarı varsa) onu tek elemanlı liste say.
        if (array_key_exists('severity', $paketAdvisoryleri) || array_key_exists('advisoryId', $paketAdvisoryleri)) {
            $sonuc[] = $paketAdvisoryleri;

            continue;
        }

        foreach ($paketAdvisoryleri as $advisory) {
            if (is_array($advisory)) {
                $sonuc[] = $advisory;
            }
        }
    }

    return $sonuc;
};

$hepsi = $duzlestir($data['advisories']);

$sayac = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'bilinmeyen' => 0];
$satirlar = [];

foreach ($hepsi as $advisory) {
    $seviye = strtolower((string) ($advisory['severity'] ?? ''));

    if (! array_key_exists($seviye, $sayac)) {
        $seviye = 'bilinmeyen';
    }

    $sayac[$seviye]++;

    $satirlar[] = sprintf(
        '  [%s] %s — %s',
        strtoupper($seviye),
        (string) ($advisory['packageName'] ?? $advisory['cve'] ?? '?'),
        (string) ($advisory['title'] ?? 'başlık yok')
    );
}

echo sprintf(
    'critical=%d high=%d medium=%d low=%d bilinmeyen=%d (toplam %d)',
    $sayac['critical'],
    $sayac['high'],
    $sayac['medium'],
    $sayac['low'],
    $sayac['bilinmeyen'],
    count($hepsi)
).PHP_EOL;

if ($satirlar !== []) {
    echo implode(PHP_EOL, $satirlar).PHP_EOL;
}

// Bilinmeyen seviye kapıyı kırmaz ama SESSİZ de kalmaz: severity okunamıyorsa
// ayrıştırma yine bozulmuş olabilir — bu betiğin ilk hatası tam olarak buydu.
if ($sayac['bilinmeyen'] > 0) {
    fwrite(STDERR, "::warning::{$sayac['bilinmeyen']} advisory'nin seviyesi okunamadı — JSON şeması değişmiş olabilir\n");
}

if ($sayac['critical'] + $sayac['high'] > 0) {
    fwrite(STDERR, sprintf(
        "::error::%d kritik + %d yüksek seviye güvenlik açığı tespit edildi\n",
        $sayac['critical'],
        $sayac['high']
    ));
    exit(1);
}

if ($sayac['medium'] + $sayac['low'] > 0) {
    // Kapıyı kırmaz: medium/low için acil yükseltme dayatmak gerçek bir
    // aciliyet olmadan sürüm baskısı üretir. Ama görünür olmalı.
    fwrite(STDERR, sprintf(
        "::warning::%d orta + %d düşük seviye advisory var — engellemiyor, takip edilmeli\n",
        $sayac['medium'],
        $sayac['low']
    ));
    echo '⚠ Yüksek/kritik yok; orta/düşük seviye advisory takipte'.PHP_EOL;
    exit(0);
}

echo '✓ Güvenlik açığı yok'.PHP_EOL;
exit(0);

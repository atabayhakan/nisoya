<?php

/**
 * composer audit JSON çıktısından high+ seviye güvenlik açıklarını say.
 * CI workflow'unda kullanılır. Lokal: php audit.php composer-audit.json
 */
$path = $argv[1] ?? null;
if (! $path || ! file_exists($path)) {
    fwrite(STDERR, "Kullanım: php audit.php <composer-audit.json>\n");
    exit(2);
}

$data = json_decode(file_get_contents($path), true);
if (! is_array($data) || ! isset($data['advisories'])) {
    fwrite(STDERR, "Geçersiz JSON veya advisories bulunamadı\n");
    exit(2);
}

$highCount = 0;
$criticalCount = 0;
foreach ($data['advisories'] as $advisory) {
    $severity = strtolower($advisory['severity'] ?? '');
    if ($severity === 'high') {
        $highCount++;
    }
    if ($severity === 'critical') {
        $criticalCount++;
    }
}

echo "high=$highCount critical=$criticalCount".PHP_EOL;

if ($criticalCount > 0) {
    fwrite(STDERR, "::error::$criticalCount kritik seviye güvenlik açığı tespit edildi\n");
    exit(1);
}

if ($highCount > 0) {
    fwrite(STDERR, "::error::$highCount yüksek seviye güvenlik açığı tespit edildi\n");
    exit(1);
}

echo '✓ Yüksek/kritik seviye güvenlik açığı yok'.PHP_EOL;
exit(0);

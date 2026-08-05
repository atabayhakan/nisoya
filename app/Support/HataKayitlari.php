<?php

namespace App\Support;

/**
 * Son hata kayıtlarını okur (yönetim panelindeki "Son Hatalar" ekranı için).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * 2026-08-05'te El Kitabı canlıda 500 verdi. Sebebi bulmak için sahibe ÜÇ KEZ
 * sunucuda komut çalıştırttım (SSH erişimim yok). Hatanın kendisi bir
 * satırlıktı; onu görmek yarım saat aldı.
 *
 * Bu ekran o bağımlılığı kaldırır: sahip hatayı panelden görür, kimseye
 * sormadan.
 *
 * ---------------------------------------------------------------------------
 * NE GÖSTERİLİR, NE GÖSTERİLMEZ
 *
 * Zaman · seviye · istisna sınıfı · mesaj · dosya:satır gösterilir.
 * TAM YIĞIN İZİ VE BAĞLAM YÜKÜ GÖSTERİLMEZ: log satırları kullanıcı verisi
 * (e-posta, mesaj metni, form girdisi) içerebilir ve bir hata ekranının
 * bunları sayfaya dökmesi gereksiz bir sızıntı yüzeyidir. Teşhis için
 * dosya:satır zaten yeterli — bugünkü hatada da yeterliydi.
 *
 * ---------------------------------------------------------------------------
 * LOG DOSYASI YOKSA
 *
 * Bu, "hata yok" demek DEĞİLDİR — "kayıt tutulmuyor olabilir" demektir ve
 * ikisi apayrı. Ekran bu ayrımı açıkça yapar; sessizce "temiz" demek,
 * olmayan bir güvence verirdi.
 */
class HataKayitlari
{
    /** Laravel log satırının başı: [2026-08-05 19:02:09] production.ERROR: ... */
    private const SATIR_BASI = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\][^\]]*?(\w+)\.(\w+): /m';

    private const ILGILI_SEVIYELER = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    private const MESAJ_SINIRI = 300;

    /**
     * Klasör dışarıdan verilebilir.
     *
     * Test edilebilirlik için: ilk yazışta `storage_path('logs')` sabitti ve
     * testler geliştirme makinesindeki GERÇEK logları okuyup 25 kayıt buldu.
     * Kendi verisini kuramayan bir test, ne olduğunu değil ne olduğunu sandığını
     * ölçer.
     */
    public function __construct(private readonly ?string $klasor = null) {}

    /** @return list<string> Log dosyaları, en yeni önce. */
    public function dosyalar(): array
    {
        $klasor = $this->klasor ?? storage_path('logs');

        if (! is_dir($klasor)) {
            return [];
        }

        $dosyalar = glob($klasor.'/*.log') ?: [];
        usort($dosyalar, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        return $dosyalar;
    }

    /** Hiç log dosyası yok mu? (≠ "hata yok" — bkz. sınıf açıklaması) */
    public function kayitTutuluyorMu(): bool
    {
        return $this->dosyalar() !== [];
    }

    /**
     * Son hatalar, en yeni önce.
     *
     * DÜZ DİZİ döner, Collection değil: Collection'ın şablon tipi DEĞİŞMEZ
     * (invariant), yani daraltılmış bir generic'i katmanlar arasında taşımak
     * PHPStan'de çözümsüz bir varyans hatası üretiyor. Burada Collection'ın
     * sağladığı hiçbir şeye ihtiyaç yok — şablon zaten üzerinde geziniyor.
     *
     * @return list<array{zaman: string, seviye: string, sinif: string, mesaj: string, yer: string|null}>
     */
    public function sonHatalar(int $adet = 25): array
    {
        $kayitlar = [];

        foreach ($this->dosyalar() as $dosya) {
            foreach ($this->dosyadanOku($dosya) as $kayit) {
                $kayitlar[] = $kayit;

                if (count($kayitlar) >= $adet * 4) {
                    break 2; // Bol okunur, sonra sıralanıp kırpılır.
                }
            }
        }

        usort($kayitlar, fn (array $a, array $b) => strcmp($b['zaman'], $a['zaman']));

        return array_slice($kayitlar, 0, $adet);
    }

    /**
     * Tek dosyayı ayrıştırır.
     *
     * Dosyanın YALNIZ SON KISMI okunur: bir log dosyası megabaytlarca olabilir
     * ve tamamını belleğe almak, teşhis ekranının kendisini bir bellek hatasına
     * çevirir — hata ekranının çökmesi, hatanın kendisinden kötüdür.
     *
     * @return list<array{zaman: string, seviye: string, sinif: string, mesaj: string, yer: ?string}>
     */
    private function dosyadanOku(string $dosya): array
    {
        $icerik = $this->sonBaytlar($dosya);

        if ($icerik === '') {
            return [];
        }

        preg_match_all(self::SATIR_BASI, $icerik, $basliklar, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $kayitlar = [];

        foreach ($basliklar as $i => $baslik) {
            $seviye = strtoupper($baslik[3][0]);

            if (! in_array($seviye, self::ILGILI_SEVIYELER, true)) {
                continue;
            }

            $baslangic = $baslik[0][1] + strlen($baslik[0][0]);
            $bitis = isset($basliklar[$i + 1]) ? $basliklar[$i + 1][0][1] : strlen($icerik);
            $govde = substr($icerik, $baslangic, $bitis - $baslangic);

            $kayitlar[] = [
                'zaman' => $baslik[1][0],
                'seviye' => $seviye,
                'sinif' => $this->sinifBul($govde),
                'mesaj' => $this->mesajBul($govde),
                'yer' => $this->yerBul($govde),
            ];
        }

        return array_reverse($kayitlar); // en yeni önce
    }

    /** Dosyanın son ~256 KB'ı — büyük loglarda belleği korur. */
    private function sonBaytlar(string $dosya, int $limit = 262144): string
    {
        $boyut = @filesize($dosya);

        if ($boyut === false) {
            return '';
        }

        $tutamac = @fopen($dosya, 'rb');

        if ($tutamac === false) {
            return '';
        }

        if ($boyut > $limit) {
            fseek($tutamac, -$limit, SEEK_END);
            fgets($tutamac); // yarım kalan ilk satırı at
        }

        $icerik = stream_get_contents($tutamac);
        fclose($tutamac);

        return $icerik === false ? '' : $icerik;
    }

    private function sinifBul(string $govde): string
    {
        // {"exception":"[object] (TypeError(code: 0): ...
        if (preg_match('/\(([A-Za-z0-9_\\\\]+)\(code: /', $govde, $m)) {
            return class_basename($m[1]);
        }

        return '—';
    }

    private function mesajBul(string $govde): string
    {
        $ilkSatir = trim(strtok($govde, "\n") ?: '');
        // Bağlam JSON'unu at: kullanıcı verisi içerebilir.
        $ilkSatir = (string) preg_replace('/\s*\{"(exception|userId|email)".*$/s', '', $ilkSatir);

        return mb_strimwidth($ilkSatir, 0, self::MESAJ_SINIRI, '…');
    }

    private function yerBul(string $govde): ?string
    {
        if (preg_match('#at (/[^\s:]+|[A-Z]:\\\\[^\s:]+):(\d+)#', $govde, $m)) {
            return basename($m[1]).':'.$m[2];
        }

        return null;
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Permissions-Policy başlığı, sitenin KENDİ özelliklerini kapatmasın.
 *
 * ---------------------------------------------------------------------------
 * CANLIDA BULUNDU (2026-08-13)
 *
 * Başlık `geolocation=(), microphone=(), camera=()` idi. Boş parantez "hiç
 * kimseye izin yok" demek — kendi kaynağımıza bile. Tarayıcıya soruldu
 * (document.featurePolicy.allowsFeature) ve üçü de `false` döndü.
 *
 * Oysa:
 *   - Sesle ilan (SpeechRecognition) MİKROFON ister. Düğme görünüyordu ama
 *     basınca hiçbir şey olmuyordu — çalışamayacağı ekranda hiç yazmıyordu.
 *   - Acil paneli ve mesaj ekranı KONUM ister; sessizce hep yedeğe düşüyordu.
 *
 * ---------------------------------------------------------------------------
 * BU TEST NEDEN DİZEYE DEĞİL KULLANIMA BAKIYOR
 *
 * "Başlık şu olsun" diye sabitleseydik, özellik kaldırıldığında testi
 * güncellemek gerekirdi ve gereksiz izin açık kalırdı. Burada KODUN GERÇEKTE
 * NE KULLANDIĞI taranıyor: mikrofonu kullanan bir yer varsa mikrofon `self`
 * olmalı, yoksa şart değil. Böylece izinler kullanımla birlikte hareket eder.
 */
class TarayiciIzinPolitikasiTest extends TestCase
{
    private function politika(): string
    {
        $yol = base_path('deploy/nginx-nisoya.conf');
        $this->assertFileExists($yol);

        $icerik = (string) file_get_contents($yol);

        // Yorum satırları hariç, gerçekten gönderilen başlık.
        $satirlar = array_filter(
            explode("\n", $icerik),
            fn (string $s): bool => str_starts_with(trim($s), 'add_header Permissions-Policy')
        );

        $this->assertCount(1, $satirlar, 'Permissions-Policy satırı tek olmalı (yoksa hangisinin geçerli olduğu belirsiz).');

        preg_match('/"([^"]+)"/', (string) reset($satirlar), $m);

        return $m[1] ?? '';
    }

    /** Bir özellik kendi kaynağımıza açık mı? */
    private function kendimizeAcikMi(string $politika, string $ozellik): bool
    {
        if (! preg_match('/'.preg_quote($ozellik, '/').'=\(([^)]*)\)/', $politika, $m)) {
            // Hiç yazılmamışsa tarayıcı varsayılanı geçerli — kısıt yok.
            return true;
        }

        return str_contains($m[1], 'self');
    }

    /** Depoda bu tarayıcı yeteneğini kullanan bir yer var mı? */
    private function kodKullaniyorMu(array $desenler): bool
    {
        foreach (['resources/js', 'resources/views'] as $dizin) {
            $yol = base_path($dizin);
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($yol));

            foreach ($it as $dosya) {
                if (! $dosya->isFile()) {
                    continue;
                }

                $icerik = (string) file_get_contents($dosya->getPathname());

                foreach ($desenler as $desen) {
                    if (str_contains($icerik, $desen)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function test_mikrofon_kullanan_kod_varsa_izin_de_var(): void
    {
        $politika = $this->politika();

        if (! $this->kodKullaniyorMu(['SpeechRecognition', 'getUserMedia'])) {
            $this->markTestSkipped('Mikrofon kullanan kod yok — izin gerekmiyor.');
        }

        $this->assertTrue(
            $this->kendimizeAcikMi($politika, 'microphone'),
            "Kod mikrofon kullanıyor ama Permissions-Policy kapatıyor: {$politika}\n".
            'Sesle ilan düğmesi görünür ama çalışmaz.'
        );
    }

    public function test_konum_kullanan_kod_varsa_izin_de_var(): void
    {
        $politika = $this->politika();

        if (! $this->kodKullaniyorMu(['navigator.geolocation'])) {
            $this->markTestSkipped('Konum kullanan kod yok — izin gerekmiyor.');
        }

        $this->assertTrue(
            $this->kendimizeAcikMi($politika, 'geolocation'),
            "Kod konum kullanıyor ama Permissions-Policy kapatıyor: {$politika}\n".
            'Acil paneli şehri bulamaz, sessizce yedeğe düşer.'
        );
    }

    public function test_kullanilmayan_yetenek_acik_birakilmiyor(): void
    {
        /*
         * Ters yön: en az yetki. Kamerayı getUserMedia ile kullanan yer yok
         * (hızlı ilan `<input type="file" capture>` kullanıyor ve o bu
         * politikaya tabi değil), o hâlde kamera açık olmamalı.
         */
        $politika = $this->politika();

        if ($this->kodKullaniyorMu(['getUserMedia'])) {
            $this->markTestSkipped('Kamera/mikrofon akışı kullanılıyor — bu kural geçerli değil.');
        }

        $this->assertFalse(
            $this->kendimizeAcikMi($politika, 'camera'),
            'Kamera akışı kullanılmıyor ama izin açık bırakılmış — gereksiz yetki.'
        );
    }
}

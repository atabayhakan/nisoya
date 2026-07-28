<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Koyu mod kapsamının bekçisi (2026-07-29).
 *
 * BULUNAN HATA: 15 blade dosyasında `dark:` sınıfı HİÇ yoktu. Gövde
 * `dark:bg-stone-950` bastığı için koyu zemin üzerine koyu metin çiziliyordu.
 * Canlı ölçüm: `/giris` başlığı 1.00:1 — yani tamamen görünmez. Koyu mod OS
 * tercihiyle otomatik devrede (theme-init.blade.php), kullanıcı hiçbir şeye
 * basmıyor; kayıt/giriş formunu okuyamayan ziyaretçi doğrudan kayıp.
 *
 * NEDEN BU KURAL BÖYLE DAR: "bg-white'ın dark: karşılığı olmalı" gibi bir
 * kural YANLIŞ POZİTİF üretir — renkli bir bandın üzerindeki beyaz buton
 * (nabiz.blade.php:114, vitrin cta) iki modda da doğrudur. Aynı şekilde
 * `text-stone-800` beyaz bir butonun içinde doğrudur. Tek tek sınıf eşleşmesi
 * bu yüzden denenip BIRAKILDI.
 *
 * Onun yerine gerçekte olan hata sınıfı donduruluyor: bir sayfa nötr renk
 * paletini kullanıyor ama koyu mod hiç DÜŞÜNÜLMEMİŞ (dosyada tek bir `dark:`
 * yok). Bu kaba ama neredeyse sıfır yanlış pozitifli bir sinyaldir ve tam
 * olarak yaşanan hatayı yakalar.
 */
class KoyuModKapsamiTest extends TestCase
{
    /**
     * Kapsam dışı — her biri gerekçeli.
     *
     * qr: kendi <html>'ini kuran YAZDIRMA sayfası. theme-init içermiyor, yani
     *     `dark` sınıfı oraya hiç ulaşmıyor; üstelik QR kodun taranabilmesi
     *     için zemin beyaz KALMALI.
     */
    private const MUAF = [
        'panel/events/qr.blade.php',
    ];

    /** Bu köklerin altındaki her şey ziyaretçiye görünür yüzeydir. */
    private const KAPSAM = ['pages', 'auth', 'partials', 'components'];

    /**
     * @return list<array{string, string}>
     */
    public static function goruntuler(): array
    {
        $kok = dirname(__DIR__, 2).'/resources/views';
        $sonuc = [];

        foreach (self::KAPSAM as $dizin) {
            $yol = $kok.'/'.$dizin;

            if (! is_dir($yol)) {
                continue;
            }

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($yol)) as $dosya) {
                $p = str_replace('\\', '/', (string) $dosya);

                if (! str_ends_with($p, '.blade.php')) {
                    continue;
                }

                if (str_contains($p, '/filament/')) {
                    continue;
                }

                $kisa = substr($p, strpos($p, '/resources/views/') + strlen('/resources/views/'));

                if (in_array($kisa, self::MUAF, true)) {
                    continue;
                }

                $sonuc[$kisa] = [$kisa, $p];
            }
        }

        ksort($sonuc);

        return array_values($sonuc);
    }

    #[DataProvider('goruntuler')]
    public function test_notr_palet_kullanan_gorunum_koyu_modu_dusunur(string $kisa, string $tamYol): void
    {
        $icerik = file_get_contents($tamYol);

        // Nötr palet = koyu modda ters çevrilmesi gereken renkler.
        $notrSayisi = preg_match_all('/\b(text|bg|border|divide|ring)-(stone|gray|slate|neutral|zinc)-\d{2,3}\b|\bbg-white\b/', $icerik);

        // Eşik 3: bir-iki nötr sınıf taşıyan küçük parçalar (ikon sarmalayıcı,
        // ayraç) koyu mod kararı vermek zorunda değil.
        if ($notrSayisi < 3) {
            $this->assertTrue(true);

            return;
        }

        $this->assertStringContainsString('dark:', $icerik, sprintf(
            "%s nötr renk paletini %d yerde kullanıyor ama tek bir `dark:` sınıfı yok.\n".
            "Gövde `dark:bg-stone-950` bastığı için koyu modda koyu zemin üzerine koyu metin çizilir.\n".
            'Koyu mod OS tercihiyle otomatik açılır — kullanıcı hiçbir şeye basmadan bu ekranı görür.',
            $kisa,
            $notrSayisi
        ));
    }

    /**
     * Form alanı koyu kenarlık alıyorsa koyu ZEMİN de almalı.
     *
     * Çoğu input'un hiç `bg-` sınıfı yoktur (tarayıcı varsayılanı beyaz), bu
     * yüzden "açık sınıfın koyu karşılığını ekle" mantığı onları GÖREMEZ:
     * ortada eşleştirilecek açık sınıf yoktur. Sonuç koyu zemin üzerinde
     * bembeyaz kutudur. Bu tam olarak iki kez yaşandı.
     */
    public function test_koyu_kenarlikli_form_alani_koyu_zemin_de_alir(): void
    {
        $ihlaller = [];

        foreach (self::goruntuler() as [$kisa, $tamYol]) {
            // Blade ifadeleri ÖNCE temizlenir: `{{ old('x', auth()->user()->y) }}`
            // içindeki `->` okunun `>` karakteri etiket sonu sanılıyordu ve
            // ilk göç betiği bu yüzden iki input'u atlamıştı.
            $icerik = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', '', file_get_contents($tamYol)) ?? '';

            preg_match_all('/<(input|textarea|select)\b[^>]*>/s', $icerik, $m);

            foreach ($m[0] as $etiket) {
                // color/range kendi görsel dilini taşır: renk seçicinin zemini
                // SEÇİLEN RENKTİR, ona stone-800 vermek kontrolü bozar.
                if (preg_match('/type="(checkbox|radio|hidden|submit|button|file|color|range)"/', $etiket)) {
                    continue;
                }

                if (! str_contains($etiket, 'dark:border-')) {
                    continue;
                }

                if (! str_contains($etiket, 'dark:bg-')) {
                    $ihlaller[] = $kisa.' → '.substr(preg_replace('/\s+/', ' ', $etiket) ?? '', 0, 90);
                }
            }
        }

        $this->assertSame([], $ihlaller, sprintf(
            "Koyu kenarlık alıp koyu zemin almayan form alanı(lar):\n%s\n\n".
            'Konvansiyon: dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100',
            implode("\n", $ihlaller)
        ));
    }
}

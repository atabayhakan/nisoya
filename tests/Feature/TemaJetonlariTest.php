<?php

namespace Tests\Feature;

use App\Support\TemaJetonlari;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Görünüm ayarları kayıt defterinin bekçisi (2026-07-28).
 *
 * BULUNAN HATA: admin panelindeki görünüm kontrollerinin tamamı — marka rengi,
 * ana renk, font, köşe, cam, animasyon — Vitrin teması aktifken hiçbir şey
 * yapmıyordu. Klasik iskelet <x-brand-theme /> + <x-tasarim-theme /> basıyor,
 * Vitrin iskeleti ise yalnız <x-vitrin-theme /> basıyordu ve o bileşen hiçbir
 * ayar okumuyordu. Hiçbir uyarı yoktu: sahip düğmeleri çeviriyor, site
 * değişmiyordu.
 *
 * Bu dosya o hata sınıfını makineyle imkânsız kılar. Kayıt defterindeki her
 * beyan üç aşamada doğrulanır:
 *   1. beyan edilen "okuyan" dosya gerçekten var mı,
 *   2. o dosya ayar anahtarını gerçekten OKUYOR mu,
 *   3. o bileşen, beyan edilen temanın iskeletinde gerçekten BASILIYOR mu.
 *
 * Üçüncü adım kritiktir: ayarı okuyan bir bileşen yazmak yetmez, o bileşen
 * ilgili temanın düzenine eklenmezse ayar yine ölüdür — orijinal hata tam
 * olarak buydu.
 */
class TemaJetonlariTest extends TestCase
{
    public function test_her_jeton_beyan_ettigi_dosya_tarafindan_gercekten_okunur(): void
    {
        foreach (TemaJetonlari::JETONLAR as $anahtar => $jeton) {
            $yol = base_path($jeton['okuyan']);

            $this->assertFileExists($yol, "Kayıt defteri var olmayan bir dosyayı okuyucu ilan ediyor: {$jeton['okuyan']}");

            $this->assertStringContainsString(
                $anahtar,
                File::get($yol),
                "'{$anahtar}' ayarı {$jeton['okuyan']} tarafından okunduğu BEYAN EDİLİYOR ama dosyada geçmiyor — ".
                'yani bu kontrol ölü. Ya okumayı ekle ya kayıt defterinden çıkar.'
            );
        }
    }

    public function test_bilesen_okuyucuya_gercekten_baglidir(): void
    {
        // Zincirin orta halkası: ayarı okuyan yer ile temanın bastığı bileşen
        // farklı dosyalar olabilir (Vitrin'de öyle). O zaman bileşenin okuyucuya
        // bağlandığını da kanıtlamak gerekir, yoksa "okunuyor" ve "basılıyor"
        // ayrı ayrı doğru olup zincir yine kopuk kalabilir.
        foreach (TemaJetonlari::JETONLAR as $anahtar => $jeton) {
            $bilesenIcerik = File::get(base_path($jeton['bilesen_dosyasi']));

            if ($jeton['okuyan'] === $jeton['bilesen_dosyasi']) {
                continue; // aynı dosya — ilk test zaten kanıtladı
            }

            $okuyucuSinif = pathinfo($jeton['okuyan'], PATHINFO_FILENAME);

            $this->assertStringContainsString(
                $okuyucuSinif,
                $bilesenIcerik,
                "'{$anahtar}' ayarını {$jeton['okuyan']} okuyor ama {$jeton['bilesen_dosyasi']} ona hiç ".
                'başvurmuyor — zincir kopuk, ayar yine ölü.'
            );
        }
    }

    public function test_her_jetonun_bileseni_beyan_ettigi_temanin_iskeletinde_basilir(): void
    {
        foreach (TemaJetonlari::JETONLAR as $anahtar => $jeton) {
            foreach ($jeton['temalar'] as $tema) {
                $iskelet = File::get(base_path(TemaJetonlari::ISKELETLER[$tema]));

                $this->assertStringContainsString(
                    '<'.$jeton['bilesen'],
                    $iskelet,
                    "'{$anahtar}' ayarının '{$tema}' temasında geçerli olduğu beyan ediliyor, ama o temanın ".
                    'iskeleti onu okuyan bileşeni HİÇ BASMIYOR. Ayarı okuyan bir bileşen yazmak yetmez; '.
                    'düzene eklenmezse ayar yine ölüdür (2026-07-28 bulgusunun tam kök nedeni).'
                );
            }
        }
    }

    public function test_her_temanin_en_az_bir_calisan_kontrolu_vardir(): void
    {
        // Özelleştirici bir temada tek bir işleyen kontrol bile sunamıyorsa,
        // o tema için ölü bir panel demektir — kabul edilemez.
        foreach (array_keys(TemaJetonlari::ISKELETLER) as $tema) {
            $this->assertNotEmpty(
                TemaJetonlari::temaninJetonlari($tema),
                "'{$tema}' temasında hiçbir görünüm kontrolü çalışmıyor."
            );
        }
    }

    public function test_vitrin_aksanlari_acik_ve_koyu_seti_birlikte_tasir(): void
    {
        foreach (TemaJetonlari::vitrinAksanAdlari() as $ad) {
            $aksan = TemaJetonlari::vitrinAksani($ad);

            $this->assertNotEmpty($aksan['acik'], "{$ad}: açık set yok");
            $this->assertNotEmpty($aksan['koyu'], "{$ad}: koyu set yok");

            // Koyu moddaki buton metni koyudur (klasikten miras dark:text-stone-900
            // → #131c2f). Bu yüzden koyu-600 zemin, açık-600'den DAHA AÇIK olmak
            // zorunda; aksi hâlde koyu modda butonlar okunmaz olur.
            $this->assertTrue(
                $this->parlaklik($aksan['koyu'][600]) > $this->parlaklik($aksan['acik'][600]),
                "{$ad}: koyu moddaki vurgu (#{$aksan['koyu'][600]}) açık moddakinden daha koyu — ".
                'koyu modda buton metni koyu olduğu için zemin açık olmalı.'
            );
        }
    }

    public function test_varsayilan_aksan_vitrinin_orijinal_paletini_korur(): void
    {
        // Bu değerler tema motorunun mührüdür: TemaTest literal olarak
        // '--color-emerald-600: #3E63F0' bekliyor.
        $deniz = TemaJetonlari::vitrinAksani('deniz');

        $this->assertSame('#3E63F0', $deniz['acik'][600]);
        $this->assertSame('#6b8afd', $deniz['koyu'][600]);
        $this->assertSame('#10203c', $deniz['acik'][950]);
    }

    public function test_gecersiz_aksan_varsayilana_duser(): void
    {
        $this->assertSame('#3E63F0', TemaJetonlari::vitrinAksani('uydurma-renk')['acik'][600]);
    }

    public function test_sunulan_her_yazi_tipi_gercekten_self_host_edilir(): void
    {
        // BULUNAN HATA: panelde 'inter' ve 'outfit' seçenekleri duruyordu ama
        // o aileler hiçbir yerden yüklenmiyordu — sahip seçiyor, site sessizce
        // sistem sans'ına düşüyordu. Üstelik "obsidian"/"nordic" hazır ayarları
        // o ölü değerleri kendiliğinden yazıyordu.
        $vite = File::get(base_path('vite.config.js'));

        foreach (TemaJetonlari::FONTLAR as $anahtar => $font) {
            $this->assertStringContainsString(
                "bunny('{$font['aile']}'",
                $vite,
                "'{$anahtar}' seçeneği '{$font['aile']}' ailesini vaat ediyor ama vite.config.js onu ".
                'self-host etmiyor — seçilse de sistem fontuna düşer, yani ölü bir kontrol.'
            );

            // CSS listesi de o aileyle başlamalı, yoksa vaat edilen font hiç denenmez.
            $this->assertStringContainsString($font['aile'], $font['css']);
        }
    }

    public function test_hazir_ayarlar_olu_yazi_tipi_yazmaz(): void
    {
        $sayfa = File::get(base_path('app/Filament/Pages/TasarimAyarlari.php'));

        preg_match_all("/'gorunum\.font_family'\s*=>\s*'([^']+)'/", $sayfa, $m);
        $this->assertNotEmpty($m[1], 'Hazır ayarlarda font_family bulunamadı — test kendi hedefini kaybetmiş.');

        foreach ($m[1] as $deger) {
            $this->assertArrayHasKey(
                $deger,
                TemaJetonlari::FONTLAR,
                "Hazır ayarlardan biri '{$deger}' yazıyor ama bu geçerli bir yazı tipi değil."
            );
        }
    }

    public function test_gecerlilik_sorgusu_temaya_gore_ayirir(): void
    {
        $this->assertTrue(TemaJetonlari::gecerliMi('gorunum.vitrin_aksan', 'vitrin'));
        $this->assertFalse(TemaJetonlari::gecerliMi('gorunum.vitrin_aksan', 'klasik'));
        $this->assertTrue(TemaJetonlari::gecerliMi('gorunum.font_family', 'klasik'));
        $this->assertFalse(TemaJetonlari::gecerliMi('gorunum.font_family', 'vitrin'));
    }

    /** Basit göreli parlaklık (sIRGB), yalnız sıralama karşılaştırması için. */
    private function parlaklik(string $hex): float
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%2x%2x%2x');

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}

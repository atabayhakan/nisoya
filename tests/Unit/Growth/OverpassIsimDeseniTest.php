<?php

namespace Tests\Unit\Growth;

use App\Support\Growth\TurkishLexicon;
use PHPUnit\Framework\TestCase;

/**
 * Overpass sorgusuna gömülen isim deseni (2026-08-06).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Overpass metin araması yapamaz; bir alandaki TÜM işletmeleri getirir.
 * ÖLÇÜLDÜ: desensiz New York + New Jersey'den 80 lokanta çekildi ve Türk çıkan
 * SIFIR oldu — bölgede binlerce lokanta var, 40'ı rastgele geliyordu. Desenle
 * aynı bölgeden 28 Türk + 9 sınırda aday geldi.
 *
 * Desen bozulursa keşif sessizce eski hâline döner: sorgu yine çalışır, yine
 * "0 Türk" yazar. Bu yüzden burada mühürlü.
 */
class OverpassIsimDeseniTest extends TestCase
{
    public function test_gucli_kultur_isaretlerini_icerir(): void
    {
        $desen = TurkishLexicon::overpassIsimDeseni();

        // "kebab" ve "lahmacun" gibi işaretler deseni taşıyan omurgadır.
        $this->assertStringContainsString('kebab', $desen);
        $this->assertStringContainsString('lahma', $desen);
        $this->assertStringContainsString('t[uü]rk', $desen);
    }

    public function test_diakritikli_yazimlari_da_yakalar(): void
    {
        /*
         * Sözlük ASCII-katlanmış tutulur ama OSM adları katlanmamıştır:
         * gerçek kayıtlarda "Döner Haus", "Köfte Piyaz", "Kotti Berliner
         * Döner Kebab" var. Katlanmamış hâli yakalamazsak bunlar kaçar.
         */
        $desen = TurkishLexicon::overpassIsimDeseni();

        $this->assertMatchesRegularExpression('/'.$desen.'/iu', 'Döner Haus');
        $this->assertMatchesRegularExpression('/'.$desen.'/iu', 'Köfte Piyaz');
        $this->assertMatchesRegularExpression('/'.$desen.'/iu', 'İstanbul Kebap Salonu');
    }

    public function test_ad_ve_soyadlar_desene_girmez(): void
    {
        /*
         * KRİTİK: Overpass regex'i ALT-DİZE eşler. "ali" deseni
         * "It-ali-an Restaurant"ı, "can" ise "Ameri-can Diner"ı getirirdi —
         * samanlığı daraltmak yerine geri genişletirdi.
         *
         * İş bölümü: sorgu ucuza daraltır, kararı TurkishBusinessDetector
         * verir (ad/soyadları o kullanıyor).
         */
        $desen = TurkishLexicon::overpassIsimDeseni();

        foreach (['mehmet', 'mustafa', 'yilmaz', 'kaya'] as $ad) {
            $this->assertStringNotContainsString($ad, $desen, "Ad/soyad desende olmamalı: {$ad}");
        }

        $this->assertDoesNotMatchRegularExpression('/'.$desen.'/iu', 'Italian Restaurant');
        $this->assertDoesNotMatchRegularExpression('/'.$desen.'/iu', 'American Diner');
    }

    public function test_desen_gecerli_bir_regex(): void
    {
        // Bozuk desen Overpass'ta sessiz bir sorgu hatasına dönüşürdü.
        $this->assertIsInt(preg_match('/'.TurkishLexicon::overpassIsimDeseni().'/iu', 'deneme'));
        $this->assertSame(PREG_NO_ERROR, preg_last_error());
    }
}

<?php

namespace Tests\Unit;

use App\Support\Growth\RegionPolicy;
use App\Support\Growth\UlkeTespiti;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Keşif havuzundaki ülke tespiti. (2026-08-08)
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * `outreach_targets.country` sorgunun ülkesini taşıyordu: "KZ'de ara" denince
 * Kartal/İstanbul'daki terzi de KZ yazılıyordu. Bu kozmetik değil — hukuki:
 * `RegionPolicy` ticari e-postayı AB-27 + TR'ye kapatıyor ve kararı bu alandan
 * okuyor, yani yanlış alan kapıyı yanlış yere koyuyor.
 *
 * RİSK ASİMETRİK, TESTLER DE ÖYLE:
 *   yanlış pozitif (ABD'deki kaydı TR sanmak)   → kayıp fırsat, geri alınır
 *   ters yön       (TR'deki kaydı allowed yapmak) → HUKUKİ RİSK
 * Bu yüzden en sondaki test bir DEĞİŞMEZ mühürlüyor: tespit hiçbir kaydın
 * kapısını AÇMAMALI.
 *
 * Örneklerin hepsi üretim havuzundan alındı (# ile kayıt kimliği yazılı).
 *
 * Tests\TestCase (düz PHPUnit değil): son testteki RegionPolicy `config()`
 * okuyor — gönderim beyaz listesi yapılandırmadan gelir, uygulama açılmadan
 * "Target class [config] does not exist" ile patlar.
 */
class UlkeTespitiTest extends TestCase
{
    /** @return array<string, array{0: string, 1: ?string, 2: ?string}> */
    public static function adresler(): array
    {
        return [
            // --- Türkiye: 5 hane + İlçe/İl ---------------------------------
            '#77 Kartal/İstanbul' => ['34876 Kartal/İstanbul', 'http://www.turkterzi.com/', 'TR'],
            '#129 Nilüfer/Bursa (İ noktalı)' => ['16285 Ni̇lüfer/Bursa', 'https://www.turkauto.com/', 'TR'],
            '#250 Muratpaşa/Antalya' => ['07070 Muratpaşa/Antalya', null, 'TR'],
            '#252 Tokat Merkez/Tokat' => ['60100 Tokat Merkez/Tokat', null, 'TR'],
            '#93 sitesiz TR adresi' => ['34768 Ümraniye/İstanbul', null, 'TR'],

            // --- ABD: eyalet + ZIP -----------------------------------------
            '#US NJ' => ['NJ 07110', null, 'US'],
            'US PA (kayıtta KG yazıyordu)' => ['PA 19053', 'https://www.istanbulfurniture.com/', 'US'],
            'US bare New York' => ['New York', null, 'US'],

            // --- Başka posta kalıpları -------------------------------------
            '#101 Avustralya' => ['Dapto NSW 2530', 'http://loganturkelectrical.com.au/', 'AU'],
            '#130 Almanya (kayıtta KZ yazıyordu)' => ['57462 Olpe', 'http://www.turk-car-service.de/', 'DE'],

            // --- Şehir adı: ADRES ALAN ADINI YENER -------------------------
            // Bu üçü kural şehir listesine bağlanmadan ÖNCE yanlış çıkıyordu.
            '#157 Astana + .org.tr sitesi' => ['Astana 020000', 'https://www.yee.org.tr/', 'KZ'],
            '#217 Toshkent + .com.tr sitesi' => ['Toshkent', 'http://www.ortadoguholding.com.tr/', 'UZ'],
            '#172 Shymkent' => ['Shymkent 160000', 'http://koksaray.foodpicasso.com/', 'KZ'],
            'Bishkek' => ['Bishkek 720016', null, 'KG'],
            'Berlin' => ['10967 Berlin', null, 'DE'],

            // --- AB / BK: hepsi gönderim beyaz listesi DIŞINDA -------------
            // Bunlar kural yazılmadan önce "KG" olarak duruyordu, yani AB'deki
            // işletmeler gönderilebilir görünüyordu.
            '#82 Consett (İngiltere)' => ['Consett DH8 5QG', null, 'GB'],
            '#78 Castlewellan (K. İrlanda)' => ['Castlewellan BT31 9DW', null, 'GB'],
            '#163 Margate' => ['Margate CT9 2PN', null, 'GB'],
            '#79 Eircode (İrlanda)' => ['N91 EC80', null, 'IE'],
            '#11 Vir (Hırvatistan)' => ['Vir', 'http://linktr.ee/birsey', 'HR'],
            '#14 Kaunas (Litvanya)' => ['44292 Kaunas', null, 'LT'],
            '#90 Prienai (Litvanya)' => ['59126 Prienai', null, 'LT'],

            // --- Katalog dışı ABD şehirleri --------------------------------
            // Posta kodu yok; "eyalet + ZIP" kalıbı tutmuyor. Tanınmasalardı
            // "ülkesi bilinmiyor" sayılıp kapıları kapanırdı — oysa apaçık ABD.
            '#477 Brooklyn' => ['Brooklyn', 'https://www.aksarayrestaurant.com/', 'US'],
            '#505 Paterson' => ['Paterson', null, 'US'],
            '#512 Beverly Hills' => ['Beverly Hills', null, 'US'],
            '#498 East Rutherford' => ['East Rutherford', null, 'US'],

            // --- Kiril / yerel yazımlar ------------------------------------
            '#58 Бишкек' => ['Бишкек', null, 'KG'],
            '#113 Алматы' => ['Алматы 050000', null, 'KZ'],
            '#178 Шымкент' => ['Шымкент', null, 'KZ'],
            '#70 Bişkek' => ['Bişkek 720010', null, 'KG'],

            // --- Karışması KOLAY olanlar, karışmamalı ----------------------
            // Kanada posta kodu BK kalıbına benzer ama tutmamalı (#319).
            'Kanada ON L2N 4P7 → BK sanılmamalı' => ['ON L2N 4P7', null, null],
            // ABD "NY 10017" BK kalıbına düşmemeli; eyalet+ZIP önce çalışır.
            '#34 NY 10017 + .gov.tr sitesi (konsolosluk)' => ['NY 10017', 'https://newyork-cg.mfa.gov.tr/Mission', 'US'],

            // --- Kanıt yok → null ------------------------------------------
            'boş adres, platform sitesi' => ['', 'https://www.instagram.com/birsey', null],
            'tanınmayan yer, ülke bildirmeyen site' => ['Sokak 5', 'http://www.entreprisesturk.com/', null],
            // Mathaf artık tanınıyor (Doha semti) — kayıtta "UZ" yazıyordu.
            '#247 Mathaf (Katar)' => ['Mathaf', 'http://www.entreprisesturk.com/', 'QA'],
        ];
    }

    #[DataProvider('adresler')]
    public function test_adres_ve_alan_adindan_ulke(string $adres, ?string $site, ?string $beklenen): void
    {
        $this->assertSame($beklenen, UlkeTespiti::tespit($adres, $site));
    }

    public function test_alan_adi_yalnizca_adres_susunca_konusur(): void
    {
        // Adres bir şey söylemiyorsa uzantı devreye girer…
        $this->assertSame('TR', UlkeTespiti::tespit('', 'https://ornek.com.tr/'));
        $this->assertSame('AZ', UlkeTespiti::tespit(null, 'https://ustacilinger.az/'));

        // …ama söylüyorsa adres kazanır. GDPR işletmenin FİZİKSEL yerini sorar.
        $this->assertSame('US', UlkeTespiti::tespit('NJ 07110', 'https://ornek.com.tr/'));
    }

    public function test_genel_uzantilar_hicbir_sey_soylemez(): void
    {
        // .com/.net/.org ülke bildirmez — bunları ülke sanmak sessiz bir hatadır.
        $this->assertNull(UlkeTespiti::tespit('', 'https://ornek.com/'));
        $this->assertNull(UlkeTespiti::tespit('', 'https://ornek.net/'));
        $this->assertNull(UlkeTespiti::tespit('', 'https://ornek.org/'));
    }

    public function test_uzun_uzanti_kisa_olandan_once_denenir(): void
    {
        // `.com.tr` "`.tr` ile bitiyor" diye değil, kendisi olduğu için TR;
        // sıralama bozulursa ".com.au" da yanlışlıkla ".au" sanılır.
        $this->assertSame('TR', UlkeTespiti::tldUlke('ornek.com.tr'));
        $this->assertSame('AU', UlkeTespiti::tldUlke('ornek.com.au'));
        $this->assertSame('GB', UlkeTespiti::tldUlke('ornek.co.uk'));
    }

    public function test_sehir_adi_sozcuk_siniriyla_aranir(): void
    {
        // "Osh" üç harf — çıplak alt-dize araması onu her yerde bulurdu.
        $this->assertSame('KG', UlkeTespiti::sehirdenUlke('Osh'));
        $this->assertNull(UlkeTespiti::sehirdenUlke('Oshkosh, Wisconsin'));
    }

    /**
     * DEĞİŞMEZ: tespit hiçbir kaydın gönderim kapısını AÇMAMALI.
     *
     * Düzeltmenin amacı yanlış AÇIK kalmış kapıları kapatmak. Ters yönde tek
     * bir örnek bile hukuki risktir; bu yüzden ayrı ve açık bir test.
     */
    public function test_tespit_kapiyi_acmaz_ornekleri(): void
    {
        $kayitlar = [
            ['34876 Kartal/İstanbul', 'http://www.turkterzi.com/', 'KZ'],
            ['57462 Olpe', 'http://www.turk-car-service.de/', 'KZ'],
            ['Toshkent', 'http://www.ortadoguholding.com.tr/', 'UZ'],
            ['Astana 020000', 'https://www.yee.org.tr/', 'KZ'],
        ];

        foreach ($kayitlar as [$adres, $site, $eskiUlke]) {
            $yeni = UlkeTespiti::tespit($adres, $site);
            if ($yeni === null) {
                continue;   // dokunulmuyor
            }

            $eskiIzin = RegionPolicy::marketingStatus($eskiUlke);
            $yeniIzin = RegionPolicy::marketingStatus($yeni);

            $this->assertFalse(
                $eskiIzin !== RegionPolicy::ALLOWED && $yeniIzin === RegionPolicy::ALLOWED,
                "Kapı AÇILDI: {$adres} ({$eskiUlke} → {$yeni})",
            );
        }
    }
}

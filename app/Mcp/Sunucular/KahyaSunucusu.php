<?php

namespace App\Mcp\Sunucular;

use App\Mcp\Araclar\EksikAlanlar;
use App\Mcp\Araclar\HataKayitlari;
use App\Mcp\Araclar\KahyaAraci;
use App\Mcp\Araclar\MedyaDogrula;
use App\Mcp\Araclar\Nabiz;
use App\Mcp\Araclar\SistemSagligi;
use App\Mcp\Araclar\SonRapor;
use App\Mcp\Araclar\TamTeshis;
use App\Support\SaltOkunurBekci;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

/**
 * Kâhya'nın MCP sunucusu — Faz E.
 *
 * ---------------------------------------------------------------------------
 * ASIL KAZANIM "CLAUDE CANLIYI GÖREBİLSİN" DEĞİL
 *
 * SSH erişimi zaten var; `php artisan tinker` ile canlı veritabanına her şey
 * sorulabiliyordu. Bu sunucunun getirdiği şey ERİŞİM değil, ERİŞİMİN ŞEKLİ:
 *
 *   · SABİT bir yüzey — ne sorulabileceği yedi araçla sınırlı, her biri
 *     ne döndürdüğünü yazılı olarak söylüyor.
 *   · YAZAMAZ — serbest SQL yerine, veritabanı katmanında zorlanan salt-okunur
 *     bir kip (bkz. {@see SaltOkunurBekci}). Elle yazılan bir
 *     tinker komutu yanlışlıkla veri değiştirebilir; bu araçlar değiştiremez.
 *   · SIZDIRMAZ — log mesajı, ayar değeri ve istisna metni bilerek dışarıda
 *     bırakıldı; her aracın docblock'unda nedeni yazılı.
 *   · HERHANGİ BİR İSTEMCİDEN — kabuk erişimi olan bir oturuma bağlı değil.
 *
 * ---------------------------------------------------------------------------
 * KÂHYA YAZMAZ — ve bu bir niyet değil, bir mekanizma
 *
 * Her araç {@see KahyaAraci} tabanından türer ve gövdesi
 * salt-okunur kip içinde çalışır. Yazma denemesi PDO'ya ulaşmadan istisnayla
 * durur. Değişiklikler yönetim panelinden, insan tıklamasıyla yapılır.
 *
 * `kahya:gunluk-rapor` komutu BİLEREK araç yapılmadı: e-posta gönderir ve
 * deftere yazar, yani salt-okuma değildir. Rapor göndermek için paneldeki
 * "Şimdi rapor gönder" düğmesi var — insan tıklaması gerektiren yer orası.
 */
#[Name('nisoya-kahya')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Nisoya (nisoya.com) — yurtdışındaki Türkler için ücretsiz Türkçe pazaryeri.
Kâhya, bu sitenin durumunu okuyan SALT-OKUNUR teşhis sunucusudur.

## Nereden başlanır
`kahya-nabiz` — ucuz, tek çağrıda "sitede ne durumda" sorusunu yanıtlar.
Altyapı şüphesi varsa `kahya-sistem-sagligi`. Sahibin sabah gördüğü raporu
okumak için `kahya-son-rapor` (bedava, yeniden hesaplamaz).

## Maliyet farkı gerçektir
`kahya-nabiz`, `kahya-son-rapor`, `kahya-eksik-alanlar` ucuzdur; serbestçe
çağır. `kahya-tam-teshis`, `kahya-medya-dogrula` ve `kahya-hata-kayitlari`
onlarca sorgu, yüzlerce dosya kontrolü ve log dosyalarının baştan sona
okunması demektir — gerektiğinde, limitleri düşük tutarak çağır.

## Bu sunucu HİÇBİR ŞEY DEĞİŞTİREMEZ
Yazma işlemleri veritabanı katmanında engellenir. Bir düzeltme gerekiyorsa
onu yapma; NE yapılması gerektiğini ve NEREDE (hangi yönetim ekranında)
yapılacağını söyle.

## Verilerde bilerek olmayan şeyler
- Log mesajlarının metni (sorgu mesajları kullanıcı verisi içerir; yalnız
  istisna sınıfı + dosya:satır + tekrar sayısı döner)
- Ayar DEĞERLERİ (aynı tabloda SMTP parolası ve API anahtarları var; yalnız
  "dolu mu, boş mu" bilgisi döner)
- Kullanıcıların kişisel verileri (e-posta, telefon, mesaj içeriği)

Bunlar eksiklik değil, tasarım. Gerekirse sahibi yönetim paneline yönlendir.

## Sayı okurken dikkat
`envanter.ilan` yalnız AKTİF ilanları sayar; `son_24_saat.yeni_ilan` durum
filtresi uygulamaz (taslak ve moderasyondakiler dâhil). İkisi aynı şeyi
ölçmez. `envanter` satırı ilan sayısını satıcı sayısıyla birlikte verir —
"12 ilan" iyi görünür, "12 ilan / 1 satıcı" gerçeği söyler.
MARKDOWN)]
class KahyaSunucusu extends Server
{
    /**
     * KAYITLI ARAÇ LİSTESİ. `KahyaMcpTest` bu listedeki her sınıfın
     * `KahyaAraci` tabanından türediğini doğrular — taban atlanırsa
     * salt-okunurluk, sızıntı koruması ve yapılandırılmış çıktı garantilerinin
     * üçü birden düşer.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        Nabiz::class,
        SistemSagligi::class,
        SonRapor::class,
        EksikAlanlar::class,
        TamTeshis::class,
        MedyaDogrula::class,
        HataKayitlari::class,
    ];
}

<?php

namespace App\Mcp\Sunucular;

use App\Mcp\Araclar\Demo\DemoDurum;
use App\Mcp\Araclar\Demo\DemoSil;
use App\Mcp\Araclar\Demo\DemoUret;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

/**
 * Örnek (demo) veri sunucusu — Faz A/B.
 *
 * ---------------------------------------------------------------------------
 * KÂHYA'DAN AYRI BİR SUNUCU, ve bu ayrılık asıl tasarım kararı
 *
 * {@see KahyaSunucusu} veritabanı katmanında yazamaz ve bunu testler
 * kanıtlıyor. Yazma araçlarını oraya eklemek o kanıtı çöpe atardı:
 * salt-okunurluk "çoğu zaman açık bir bayrak" hâline gelir, o bayrağın bir gün
 * yanlış kalması an meselesi olurdu.
 *
 * Bu yüzden iki sunucu var. Kâhya teşhis koyar ve hiçbir şey değiştiremez;
 * burası örnek veri üretir ve yalnız kendi ürettiğini silebilir.
 *
 * ---------------------------------------------------------------------------
 * KAPI PANELDEN AÇILIR
 *
 * `demo-uret` ve `demo-sil`, `demo.mcp_acik` ayarı açık değilse `tools/list`
 * içinde HİÇ GÖRÜNMEZ (`shouldRegister()`). Kapıyı bir insan açar ve bir kez
 * açar — her çağrıda onay istemek, sahibin "ajana söyleyince yapsın"
 * isteğini boşa çıkarırdı.
 *
 * `demo-durum` her zaman kayıtlıdır ve kapalıyken bunu söyler. Sessizce
 * kaybolan bir yetenek, hata ayıklanamaz bir yetenektir.
 */
#[Name('nisoya-demo')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Nisoya için ÖRNEK (demo) veri üretir ve geri alır.

## Önce durum
`demo-durum` — kapı açık mı, hangi partiler var, defter dışı artık var mı.
`demo-uret` ve `demo-sil` görünmüyorsa kapı kapalıdır; sahibi yönetim
panelindeki "Örnek Veri" sayfasından açmalı.

## Üretilen veri GERÇEK DEĞİLDİR ve öyle davranılmalı
Üyeler `@demo.invalid` adresli, ilan başlıkları `[ÖRNEK]` önekli, görseller
apaçık yer tutucu. Kâhya teşhisi bu kayıtları hiçbir koşulda saymaz —
"gerçek envanter" sayısı demo veriyle şişirilemez, bu bilerek böyle.

## Varsayılan GİZLİ
`gorunur` verilmezse ilanlar taslak doğar ve sitede görünmez; üretim
sitesinde hiçbir şey değişmez. `gorunur=true` ilanları yayınlar ama
kartlarda ve detay sayfasında "ÖRNEK" işareti çıkar ve o ilanlara mesaj
gönderilemez. Görünür kip istenmedikçe kullanma.

## Her şey geri alınabilir
Üretilen her kayıt ve diske yazılan her dosya deftere yazılır. `demo-sil`
YALNIZ defterde yazanları siler; gerçek veriye dokunamaz. Bir üretimden
sonra parti kimliğini kullanıcıya söyle ki geri alabilsin.
MARKDOWN)]
class DemoSunucusu extends Server
{
    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        DemoDurum::class,
        DemoUret::class,
        DemoSil::class,
    ];
}

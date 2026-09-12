<?php

declare(strict_types=1);

namespace App\Mcp\Sunucular;

use App\Mcp\Araclar\Yonetim\BekleyenIlanlar;
use App\Mcp\Araclar\Yonetim\CmsOzeti;
use App\Mcp\Araclar\Yonetim\DuyuruYonet;
use App\Mcp\Araclar\Yonetim\HeroYonet;
use App\Mcp\Araclar\Yonetim\HizliKesif;
use App\Mcp\Araclar\Yonetim\IlanArama;
use App\Mcp\Araclar\Yonetim\IlanDetay;
use App\Mcp\Araclar\Yonetim\IlanDurumuGuncelle;
use App\Mcp\Araclar\Yonetim\OzetMetrikler;
use App\Mcp\Araclar\Yonetim\SahipsizIsletmeler;
use App\Mcp\Araclar\Yonetim\SayfaYonet;
use App\Mcp\Araclar\Yonetim\SssYonet;
use App\Mcp\Araclar\Yonetim\WhatsAppDavet;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

/**
 * Nisoya Yönetim MCP Sunucusu (Nisoya Management Server).
 *
 * Yapay zekâ asistanlarının (Claude, ChatGPT, Antigravity, Cursor vb.) Nisoya
 * platformu üzerinde güvenli ilan yönetimi, esnaf keşfi, moderasyon, analitik
 * ve CMS (içerik, vitrin, duyuru, SSS, sayfa) yönetimini gerçekleştirmesini sağlar.
 */
#[Name('nisoya-yonetim')]
#[Version('1.1.0')]
#[Instructions(<<<'MARKDOWN'
Nisoya (nisoya.com) — Yurtdışındaki Türkler ve diaspora toplulukları için geliştirilmiş ücretsiz Türkçe ilan ve pazaryeri platformudur.

Bu MCP sunucusu, Nisoya'nın resmi yönetim, büyüme ve CMS arayüzüdür.

## Kullanabileceğiniz Temel Yetenekler:
1. **İlan Arama & İnceleme (`nisoya_ilan_ara`, `nisoya_ilan_detay`):**
   Pazaryerindeki ilanları filtreleyin veya detaylı inceleyin.
2. **Moderasyon & Onay (`nisoya_bekleyen_ilanlar`, `nisoya_ilan_durum_guncelle`):**
   Onay bekleyen ilanları listeleyin ve uygunluğuna göre onaylayın ('aktif') ya da reddedin ('reddedildi').
3. **Büyüme & Esnaf Keşfi (`nisoya_kesif_baslat`, `nisoya_sahipsiz_isletmeler`):**
   Almanya, Fransa, Avusturya gibi ülkelerde belirli meslek kollarındaki Türk işletmelerini haritadan tarayın.
4. **WhatsApp İletişimi (`nisoya_whatsapp_davet_onizle`):**
   Keşfedilen esnafa vitrinini sahiplenmesi için kişiselleştirilmiş Türkçe davet bağlantısı üretin.
5. **Platform Durumu (`nisoya_ozet_metrikler`):**
   Aktif ilan, üye ve sistem sağlık durumunu tek raporda görün.
6. **CMS & Tasarım Yönetimi (`nisoya_cms_ozet`, `nisoya_duyuru_yonet`, `nisoya_hero_yonet`, `nisoya_sss_yonet`, `nisoya_sayfa_yonet`):**
   - Platformun tasarım, tema ve içerik özetini alın (`nisoya_cms_ozet`).
   - Sitenin üst duyuru bandını okuyun veya anında güncelleyin (`nisoya_duyuru_yonet`).
   - Vitrin ana başlık, rozet, vurgu ve butonlarını güncelleyin (`nisoya_hero_yonet`).
   - Sıkça sorulan soruları (SSS) listeleyin, ekleyin veya güncelleyin (`nisoya_sss_yonet`).
   - Kurumsal sayfaları inceleyin veya yeni sayfalar oluşturun (`nisoya_sayfa_yonet`).

## Güvenlik Kuralları:
- İlan veya CMS içeriğini değiştirmeden önce kullanıcıya yapacağınız eylemi açıkça bildirin.
- Reddedilen ilanlarda net bir gerekçe belirtin.
- Hassas sistem ve veritabanı sırları bu arayüzden dışarı verilmez.
MARKDOWN)]
class NisoyaYonetimSunucusu extends Server
{
    /**
     * Kayıtlı yönetim araçları listesi (13 Hazır Yönetim Aracı).
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        IlanArama::class,
        BekleyenIlanlar::class,
        IlanDetay::class,
        IlanDurumuGuncelle::class,
        HizliKesif::class,
        SahipsizIsletmeler::class,
        WhatsAppDavet::class,
        OzetMetrikler::class,
        CmsOzeti::class,
        DuyuruYonet::class,
        HeroYonet::class,
        SssYonet::class,
        SayfaYonet::class,
    ];
}

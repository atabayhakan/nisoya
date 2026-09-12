<?php

declare(strict_types=1);

namespace App\Mcp\Sunucular;

use App\Mcp\Araclar\Yonetim\AnlasmaYonet;
use App\Mcp\Araclar\Yonetim\BekleyenIlanlar;
use App\Mcp\Araclar\Yonetim\CmsOzeti;
use App\Mcp\Araclar\Yonetim\DuyuruYonet;
use App\Mcp\Araclar\Yonetim\EpostaVeSablonYonet;
use App\Mcp\Araclar\Yonetim\HeroYonet;
use App\Mcp\Araclar\Yonetim\HizliKesif;
use App\Mcp\Araclar\Yonetim\IlanArama;
use App\Mcp\Araclar\Yonetim\IlanDetay;
use App\Mcp\Araclar\Yonetim\IlanDurumuGuncelle;
use App\Mcp\Araclar\Yonetim\KategoriYonet;
use App\Mcp\Araclar\Yonetim\KesifHavuzuYonet;
use App\Mcp\Araclar\Yonetim\KonsoloslukRehberYonet;
use App\Mcp\Araclar\Yonetim\OneCikarmaYonet;
use App\Mcp\Araclar\Yonetim\OzetMetrikler;
use App\Mcp\Araclar\Yonetim\ReklamVeAlanYonet;
use App\Mcp\Araclar\Yonetim\SahipsizIsletmeler;
use App\Mcp\Araclar\Yonetim\SayfaYonet;
use App\Mcp\Araclar\Yonetim\SeoVeGeoYonet;
use App\Mcp\Araclar\Yonetim\SistemSaglikVeHatalar;
use App\Mcp\Araclar\Yonetim\SistemYapilandirmaYonet;
use App\Mcp\Araclar\Yonetim\SssYonet;
use App\Mcp\Araclar\Yonetim\TemsilcilikYonet;
use App\Mcp\Araclar\Yonetim\WhatsAppDavet;
use App\Mcp\Araclar\Yonetim\YasamRehberiYonet;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

/**
 * Nisoya Yönetim MCP Sunucusu (Nisoya Management Server).
 *
 * Yapay zekâ asistanlarının (Claude, ChatGPT, Antigravity, Cursor vb.) Nisoya
 * platformu üzerinde güvenli ilan yönetimi, esnaf keşfi, moderasyon, analitik,
 * CMS, pazaryeri, ülke rehberi, SEO/GEO ve sistem operasyonlarını gerçekleştirmesini sağlar.
 */
#[Name('nisoya-yonetim')]
#[Version('1.5.0')]
#[Instructions(<<<'MARKDOWN'
Nisoya (nisoya.com) — Yurtdışındaki Türkler ve diaspora toplulukları için geliştirilmiş ücretsiz Türkçe ilan ve pazaryeri platformudur.

Bu MCP sunucusu, Nisoya'nın resmi yönetim, büyüme, CMS, pazaryeri ticaret, ülke rehberi ve SEO/GEO arayüzüdür.

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
7. **Pazaryeri, Kategori & Ticaret Yönetimi (`nisoya_kategori_yonet`, `nisoya_anlasma_yonet`, `nisoya_one_cikarma_yonet`):**
   - Kategorileri listeleyin, yeni kategori ekleyin veya güncelleyin (`nisoya_kategori_yonet`).
   - Alıcı-satıcı anlaşmalarını inceleyin ve ihtilaf/sorunlu durumları çözüme kavuşturun (`nisoya_anlasma_yonet`).
   - İlan öne çıkarma (featured) taleplerini listeleyin, onaylayın veya reddedin (`nisoya_one_cikarma_yonet`).
8. **Ülke Rehberi & Konsolosluk Yönetimi (`nisoya_temsilcilik_yonet`, `nisoya_konsolosluk_rehber_yonet`, `nisoya_yasam_rehberi_yonet`):**
   - Dış temsilcilikleri (başkonsolosluk/büyükelçilik) listele, adres/iletişim bilgilerini güncelle (`nisoya_temsilcilik_yonet`).
   - Konsolosluk işlem rehberlerini (evrak, harç, süre, notlar) incele, yayına al, kullanıcı geri bildirimlerini listele (`nisoya_konsolosluk_rehber_yonet`).
   - Yaşam rehberi kategori/konu ağacını ve ülke içeriklerini listele/güncelle, topluluk önerilerini karara bağla (`nisoya_yasam_rehberi_yonet`).
9. **Sistem Sağlığı, E-posta & Yapılandırma (`nisoya_sistem_saglik_ve_hatalar`, `nisoya_eposta_ve_sablon_yonet`, `nisoya_sistem_yapilandirma_yonet`):**
   - Platform sağlık durumunu, kurtarma dayanıklılık skorunu ve hata loglarını denetleyin; yapay zekâ ile hata teşhisi yapın (`nisoya_sistem_saglik_ve_hatalar`).
   - E-posta bildirim metinlerini listeleyin, yer-tutucuları koruyarak AI ile optimize edin veya güncelleyin (`nisoya_eposta_ve_sablon_yonet`).
   - Dikey modülleri (emlak/vasıta/davetiye/iş) açıp kapatın, ülkeleri ve para birimlerini yönetin (`nisoya_sistem_yapilandirma_yonet`).
10. **Pazarlama, SEO & Büyüme (`nisoya_seo_ve_geo_yonet`, `nisoya_kesif_havuzu_yonet`, `nisoya_reklam_ve_alan_yonet`):**
    - SEO ve 2026 Generative Engine Optimization (GEO) sağlığını denetleyin, /llms.txt standardı oluşturun (`nisoya_seo_ve_geo_yonet`).
    - Keşif havuzundaki adayları inceleyin, kültürel analiz yapın, vitrin açın ve davet linki hazırlayın (`nisoya_kesif_havuzu_yonet`).
    - Reklam ve banner alanlarını yönetin, yüksek dönüşümlü AI kampanya blokları ekleyin (`nisoya_reklam_ve_alan_yonet`).

## Güvenlik Kuralları:
- İlan, kategori veya anlaşma durumunu değiştirmeden önce kullanıcıya yapacağınız eylemi açıkça bildirin.
- Reddedilen ilanlarda veya öne çıkarma taleplerinde net bir gerekçe belirtin.
- Hassas sistem ve veritabanı sırları bu arayüzden dışarı verilmez.
MARKDOWN)]
class NisoyaYonetimSunucusu extends Server
{
    public int $defaultPaginationLength = 50;

    /**
     * Kayıtlı yönetim araçları listesi (25 Hazır Yönetim Aracı - v1.5.0).
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
        KategoriYonet::class,
        AnlasmaYonet::class,
        OneCikarmaYonet::class,
        TemsilcilikYonet::class,
        KonsoloslukRehberYonet::class,
        YasamRehberiYonet::class,
        SistemSaglikVeHatalar::class,
        EpostaVeSablonYonet::class,
        SistemYapilandirmaYonet::class,
        SeoVeGeoYonet::class,
        KesifHavuzuYonet::class,
        ReklamVeAlanYonet::class,
    ];
}

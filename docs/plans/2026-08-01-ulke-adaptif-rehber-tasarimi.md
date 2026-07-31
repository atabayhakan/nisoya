# Ülke-Adaptif Rehber — Tasarım ve Uygulama Planı

**Tarih:** 2026-08-01
**Durum:** Sahibin ürün kararları (aşağıda §0) + 7 ajanlı kod tabanı analizi birleştirildi. Uygulama başlamadı — Faz 1 onayı bekleniyor.
**Kapsam:** Nisoya'nın "yabancı bir sistemde yol bulmak" eksenine oturan yeni katmanı: ülke-adaptif deneyim + konsolosluk/noter işlem rehberi + (sonraki fazlarda) yerel dizinler.
**Devraldığı belge:** [2026-07-27 büyüme fikirleri uygulama planı](2026-07-27-buyume-fikirleri-uygulama-plani.md)'nın B3/B4 maddeleri bu plana devşirildi (o belgeye not düşüldü).

---

## 0. Sahibin ürün kararları (değişmez çerçeve, 2026-08-01)

1. **Temel ilke:** Kullanıcı hangi ülkeden üye olursa site o ülkeye göre şekillenir. **Ülke = ikamet ülkesi.**
2. **Konumlandırma:** Yurtdışında yaşayan Türkler (Türkiye içi hedef değil). Ürünün asıl konusu **yabancı bir sistemde yol bulmak** — "Türklere yönelik her şey" değil. Bu filtre neyin içeride/dışarıda olduğunu belirler.
3. **Modüller (içeride):** ülke/şehir bazlı yerel işletme dizini · Türkçe konuşan doktor dizini (en yüksek değerli veri) · konsolosluk işlemleri (vekaletname, pasaport, tescil, askerlik, Mavi Kart, apostil) · noterlik işlemleri + "yerel noter mi konsolosluk mu?" karşılaştırması · 24 saat açık merkezler (çilingir, hastane, eczane) · okul/üniversite rozeti (kurumsal e-posta doğrulamalı, logo yerine jenerik ikon) · "Başım belada" → avukat yönlendirme (**hukuki çerçeve netleşmeden açılmayacak**). **Ertelendi:** VIP araç hizmeti (çözülmüş problem, farklılaşma zayıf).
4. **İlk sürüm:** Almanya + noter/konsolosluk evrak süreçleri. ~13 temsilcilik × ~15 işlem türü. *Tek konuda %100 dolu, 10 konuda %20 dolu olmaktan iyidir.*
5. **Veri modeli:** Ülke → Temsilcilik/Şehir → İşlem. Her işlem kaydında: evrak listesi, süre, ücret, resmi kaynak linki, son güncelleme tarihi, "bu bilgi güncel mi?" geri bildirim butonu.
6. **Gelir (sıralı):** (1) yeminli tercüme aracılığı — gün birden satılabilir, trafik gerektirmez → (2) evrak takip hizmeti → (3) listeleme ücreti (tercüman/noter/avukat/doktor) → (4) reklam/premium. *İçerik müşteriyi getirir, gelir işlemden gelir.*
7. **Hukuki sınırlar:** Almanya'da avukat yönlendirmesinden komisyon yasak (BRAO §49b); hukuki hizmet RDG'ye tabi. Prosedür anlatmak serbest, "sizin durumunuzda şunu yapın" demek değil. Resmî randevu sistemlerini kazıma/otomatik randevu YOK — yerine kullanıcı bildirimine dayalı "randevu ~X hafta" göstergesi. Her sayfada: *bilgilendirme amaçlıdır, resmî kaynaktan teyit ediniz.*
8. **Teknik:** SSR şart (içerik SEO ile büyüyecek) · URL yapısı ülke/şehir bazlı (`/de/koeln/vekaletname`) · SEO trafiği 4-8 ayda oturur, plan buna göre.

---

## 1. Kod tabanı gerçekliği (2026-08-01 analiz özeti)

**Hazır olanlar:**
- `countries` tablosu (PK = CHAR(2) ISO kod, 22 diaspora ülkesi seed'li, `is_active`+`sort_order`, cache'li aktif liste) — ülke katmanı için yeni tablo GEREKMEZ. Türkiye bilinçli olarak listede yok.
- `users.country_code` kayıttan beri **zorunlu** (form düzeyinde; kolon nullable — eski üyelerde NULL olabilir, fallback şart).
- `VisitorLocationService`: MaxMind GeoLite2 ile IP→ülke, session önbellekli, `?test_country=XX` debug — misafir kişiselleştirmesi için hazır.
- Saf Blade SSR (Inertia/SPA yok) — SEO şartı zaten sağlanıyor. SEO meta tek bileşenden (`x-layout-head-meta`), XSS-güvenli `x-json-ld` mevcut.
- Modül deseni tek kapılı: `App\Support\Modules::KEYS` + `module:<key>` middleware (kapalıysa 404) + dinamik admin Modüller sayfası + `ModulesTest` şablonu.
- Nav/ana sayfa yüzeyleri panelden: `navigation_links` (mega menü), `HomeSections`, `home_highlights`, Zone sistemi.
- İçerik tohumlama deseni: `OgrenciRehberiSeeder` (elle çalıştırılan, taslak doğuran, `firstOrCreate`'li).

**Tuzaklar:**
- `routes/web.php:344` catch-all `/{slug}` (tek segment) — ülke rotaları bundan ÖNCE tanımlanmalı; 2 harfli ülke kodları CMS slug'ı olarak rezerve edilmeli (Page validasyonuna kural).
- `cities` tablosu rehber için kullanılamaz: slug yok, ülke başına 2 tohum kayıt, Türkçe egzonimler, unique kısıtı yok. Ayrıca listings/users'daki `city` serbest metin — şehir eşleşmesine güvenilmez.
- Sitemap her istekte cache'siz üretiliyor — ~200 rehber URL'si eklenince `Cache::remember` şart.
- Site içi arama (`QuickSearchController`) Page/rehber içeriğini taramıyor — "vekaletname" araması rehberi bulamaz, entegrasyon gerekli.
- Ana sayfa iki temada aynı `HomeController` verisiyle çalışır — ülke-adaptif veri controller'dan geçmeli (composer değişkenleri home partial'larına ulaşmıyor).
- Keşif Havuzu (`outreach_targets`) verisi: `google_places` kaynaklılar herkese açık dizinde YAYINLANAMAZ (Places ToS "alternatif dizin" yasağı — uyarı kodda da yazılı); `openstreetmap` kaynaklılar ODbL atıfıyla yayınlanabilir; `fixture` tamamen dışlanır. GDPR amaç-sınırlaması: veri "iç pazar zekâsı" için toplandı, yayına taşımak amaç değişikliğidir → claim/kaldırma-talebi modeli şart.

---

## 2. Karar satırları (F0 — bu belgeyle kayda geçti)

| # | Karar | Gerekçe |
|---|-------|---------|
| K1 | **Ülke önceliği: üye için ikamet (`users.country_code`) > GeoIP; misafir için GeoIP; her rehber yüzeyinde elle ülke değiştirici.** | Sahibin "ülke = ikamet ülkesi" kararının teknik karşılığı. Acil butonundaki "fiilî konum > profil" önceliğinin bilinçli TERSİ: tatildeki kullanıcıya tatil ülkesinin konsolosluğu gösterilmemeli. NULL ülkeli eski üye → ülke seçim çağrısı, varsayılan dayatılmaz. |
| K2 | **`/de/koeln/vekaletname`'deki ikinci segment TEMSİLCİLİK slug'ıdır, şehir değil.** `cities` tablosuna dokunulmaz. | Köln başkonsolosluğu bir kurum, şehir değil; cities rehber için elverişsiz (bkz. §1). Slug temsilcilik varlığında yaşar. |
| K3 | **Yeminli tercüme geliri lead-yönlendirme olarak başlar** (form → destek bileti kuyruğu). Komisyonlu aracılık, docs/02'nin "platform içi ödeme yok" kararının resmî revizesiyle birlikte Stripe Connect fazına ertelendi. | Gün birden satılabilirlik lead ile de sağlanır; ödeme kararı tek satırla delinmemeli. |
| K4 | **Yayın politikası ≠ gönderim politikası.** `marketing_status=region_blocked` e-posta kapısıdır, dizin görünürlüğüne uygulanmaz (Almanya dizini tam da o bölgede yaşayacak). Dizine ithalat kaynak-temelli: yalnız `openstreetmap` + atıf; `google_places`/`fixture` asla; kişi alanları (owner_name/contact_email) hiçbir koşulda yayına taşınmaz. | Hukuki ayrım iki ayrı düzlemde: ToS/lisans (kaynak) ve GDPR (amaç + kişisel veri). |
| K5 | **2 harfli ülke kodları CMS sayfa slug'ı olarak rezerve** — Page oluşturma validasyonuna kural eklenir, yalnız rota sırasına güvenilmez. | `/de` ülke sayfası 'de' slug'lı bir CMS sayfasını sonsuza dek gölgeler. |
| K6 | **`/de` dil değil ÜLKE anlamı taşır; içerik Türkçe kalır, hreflang/locale altyapısı kurulmaz.** | Site tek dilli (tr); og:locale tr_TR doğru kalır. |
| K7 | **Rehber içeriği resmî kaynaktan (konsolosluk.gov.tr vb.) KENDİ İFADEMİZLE özetlenir**; resmî kaynak linki her sayfada birincil CTA; doğrulanmamış işlem kaydı yayınlanmaz (taslak statüsü); `verified_at` 90 günü aşan yayındaki kayıtlar Kâhya günlük raporuna "bayat rehber" uyarısı olarak düşer. | İtibar riski: yanlış evrak listesiyle konsolosluğa gidip geri çevrilen kullanıcı için "güven" markası ters teper. Bakım süreci tasarımın parçası, sonradan eklenecek süs değil. |

---

## 3. Veri modeli (yeni tablolar)

```
temsilcilikler            — country_code CHAR(2) (countries.code'a, desen gereği kısıtsız index),
                            ad, slug, tur (buyukelcilik|baskonsolosluk), sehir, adres,
                            lat/lng nullable, resmi_url, is_active, sort_order
                            UNIQUE(country_code, slug)

islem_turleri             — ad, slug, aciklama, sort_order, is_active
                            ÜLKE-BAĞIMSIZ şablon (~15 tür: vekaletname, pasaport, askerlik,
                            Mavi Kart, apostil, doğum/evlilik tescili...) — yeni ülke eklerken
                            tür seti yeniden kurulmaz, yalnız içerik girilir

temsilcilik_islemleri     — temsilcilik_id FK, islem_turu_id FK, evraklar JSON,
                            sure_metni, ucret_metni, resmi_kaynak_url, notlar,
                            dogrulanma_tarihi, status (taslak|yayinda)
                            UNIQUE(temsilcilik_id, islem_turu_id)

rehber_geri_bildirimleri  — temsilcilik_islemi_id FK, tur (guncel_degil|hata|oneri),
                            metin nullable, created_at
                            Honeypot + yeni RateLimiter; Filament'te rozet sayaçlı kuyruk
```

**URL şeması** (catch-all'dan önce, `{ulke}` aktif ülke kodlarına `whereIn` kısıtlı):
`/{ulke}` ülke rehber sayfası → `/{ulke}/{temsilcilik}` temsilcilik sayfası → `/{ulke}/{temsilcilik}/{islem}` işlem detayı.

---

## 4. Fazlar

### F0 — Kararlar + takvimli iş (bu belge + 1 iş)
- ✅ K1-K7 karar satırları kayda geçti; eski plan devşirildi.
- **GB öğrenci rehberi**: içerik `OgrenciRehberiSeeder` ile HAZIR (taslak doğurur). Kalan iş: canlıda seeder'ı çalıştırmak + sahibin panelden okuyup yayınlaması. **2026-08-15 (TUSU) öncesi** — modülü BEKLEMEZ, Page CMS ile bağımsız çıkar.

### F1 — Çekirdek rehber modülü, yalnız Almanya (asıl gövde)
- `Modules::KEYS+LABELS`'a `rehber` (admin toggle + middleware + ModulesTest otomatik kapsar).
- §3'teki 4 migration + modeller + `RehberController` (ülke/temsilcilik/işlem sayfaları).
- Rotalar catch-all'dan önce; K5 Page-slug rezervasyon validasyonu.
- Filament Resource'lar (İş İlanları deseni; yeni "Rehber" nav grubu) — temsilcilik/işlem/geri bildirim yönetimi.
- DE tohum seeder'ı (OgrenciRehberiSeeder deseni, elle çalıştırılan): 13 temsilcilik + ~15 işlem türü gerçek; işlem İÇERİKLERİ taslak statüsünde doğar, sahip doğrulayıp yayınlar (K7).
- SEO: sayfa başına `x-layout-head-meta` + `x-json-ld` (GovernmentService/FAQPage/BreadcrumbList); sitemap girişleri (Modules-sarılı) + sitemap'e İLK `Cache::remember`.
- "Güncel mi?" geri bildirim POST'u (honeypot + rate limit) + Filament kuyruğu.
- Kâhya entegrasyonu: günlük rapora "bayat rehber" (verified_at > 90 gün) uyarısı.
- `RehberTest` (ModulesTest iskeleti + rota/yayın-kapısı/geri-bildirim testleri).

### F2 — Yüzey + kişiselleştirme
- Nav: panelden mega menü kartı (sıfır kod) + footer linki iki tema layout'una.
- `HomeSections`'a `rehber` bölümü + `HomeController`'a K1 öncelikli ülke verisi (iki-tema ortak sözleşmesi) + null-ülke fallback + elle ülke değiştirici.
- `QuickSearchController`'a rehber işlem araması ("vekaletname" Cmd+K'da bulunmalı).
- `home_highlights` lansman kartı (panelden, sıfır kod).

### F3 — Gelir (lead)
- İşlem sayfalarına `rehber_islem_alt` Zone anahtarı (panelden CTA/reklam).
- Yeminli tercüme lead formu → mevcut destek bileti kuyruğuna (K3).
- Pazaryerine arz köprüsü: işlem sayfasında "bu şehirde tercüman mısın? Ücretsiz listelen" boş-durum çağrısı (envanter kapısı kararıyla uyumlu: arz çağrısı, talep vaadi değil).

### F4 — Yerel işletme dizini (AYRI karar kapısı — sahip onayı olmadan başlamaz)
- `directory_businesses` yayın tablosu (slug, adres, lat/lng, telefon, claimed_by, verified_at, source_attribution).
- İthalat K4 kurallarıyla: yalnız OSM + "© OpenStreetMap contributors" atıfı + claim/kaldırma-talebi akışı; yayın eşiği `band=turkish AND confidence>=75 AND needs_review=0 AND status=onayli`.
- `GrowthCatalog`'a DE'nin ~13 temsilcilik şehri (bugün yalnız Berlin+Köln) + Overpass'ın zaten çektiği lat/lng'yi kaydetme düzeltmesi.
- `google_places` kayıtları iç aday listesi kalır → "kaydını sahiplen" davetiyle işletme beyanına dönüşünce yayınlanır (ToS-temiz + büyüme kanalı).

### F5 — Dikey dizinler (her biri KENDİ mini-analiziyle)
Doktor dizini (doktor adı = KİŞİSEL VERİ; AB'de hukuki dayanak analizi yapılmadan açılmaz — "bilgi dizini + sorumluluk reddi" çerçevesi) · noterlik karşılaştırması · 24 saat açık merkezler (Acil butonunun doğal genişlemesi) · okul/üniversite rozeti. Bugünkü analiz bu modülleri taşıyacak derinlikte değil — plana "analizi yapılacak" olarak girdiler, "yapılacak" olarak değil.

---

## 5. Riskler ve korkuluklar

- **Kapsam patlaması:** tasarım 6+ modül sayıyor; depodaki her başarılı iş dar-kapsamlı tek PR'dı. Faz disiplini hayatta kalma aracı — F1 bitmeden F4/F5 açılmaz.
- **Bakım borcu:** ~200 işlem sayfası eskir. Korkuluklar: K7 (taslak-önce + verified_at + Kâhya uyarısı + geri bildirim butonu) ve her sayfada sorumluluk reddi + resmî kaynak birincil CTA.
- **SEO ince içerik:** boş/kopya sayfa yayınlanmaz (mevcut sitemap'in boş-kategori dışlama felsefesi rehbere de uygulanır); sayfalar kademeli yayına girer.
- **Strateji:** pazaryeri boş (3 ilan) — rehber trafiği tek başına dönüşüm üretmez; F3'teki arz köprüsü bu yüzden planın parçası (izole bilgi sitesi olmamak için).
- **GeoIP yanılgısı:** VPN/kurumsal ağ yanlış ülke çözer — K1'deki elle değiştirici her yüzeyde zorunlu.

## 6. Sahibe açık sorular

1. **F1'e başlama onayı** — yukarıdaki kapsamla Almanya çekirdeğini yazmaya başlayayım mı?
2. **GB öğrenci sayfası:** canlıda `php artisan db:seed --class=OgrenciRehberiSeeder --force` çalıştırıp taslağı oluşturayım mı? (Yayınlama kararı panelde sende kalır.)
3. **Temsilcilik listesi doğrulaması:** Almanya'daki 13 temsilcilik listesini (Berlin BE + Köln, Hamburg, München, Stuttgart, Frankfurt, Hannover, Essen, Düsseldorf, Nürnberg, Karlsruhe, Mainz, Münster BK'ları) resmî kaynaktan teyit edip seeder'a öyle koyacağım — itiraz/ekleme var mı?
4. **F4 (işletme dizini) karar kapısı** — F1-F3 bitince ayrıca soracağım; şimdiden bir yön belirtmek istersen not ederim.

# NİSOYA — Derinlemesine Analiz ve Mimari Plan

> **Nisoya** = "**N**e **İŞ** **O**lursa **YA**parım" — esprili ama gerçek bir ihtiyaca dayanan bir isim.
> Yurtdışında yaşayan Türklerin **sadece kendi aralarında** kullanacağı; yeteneklerini/hizmetlerini paraya dönüştürdükleri ve evde ürettikleri ürünleri sergiledikleri bir pazaryeri.

Belge tarihi: 2026-06-30
Durum: Faz 0 — Analiz

---

## 0. Yönetici Özeti

Elimizdeki örnek (Jobzilla), WordPress + WP Job Manager üzerine kurulu **iki taraflı bir iş ilanı pazaryeri**. Biz bu kodu kullanmayacağız; ama olgunlaşmış bir pazaryerinin hangi parçalardan oluştuğunu net biçimde gösteriyor: ilan/varlık modeli, çift rol (arz/talep), kullanıcı paneli, mesajlaşma, arama-filtreleme, harita, yer imleri, uyarılar, öne çıkarma/ücretlendirme.

Nisoya'nın Jobzilla'dan **temel farkı**, ilişkinin yönüdür:

- **Jobzilla'da:** İşveren ilan verir → aday başvurur. İlan = "iş".
- **Nisoya'da:** Kişi **kendini/hizmetini/ürününü** ilan eder → müşteri iletişime geçer/satın alır. İlan = "yetenek, hizmet veya ürün".

Yani Nisoya, Jobzilla'nın "CV (resume)" tarafına benzer ama onu **ana ürün** haline getirir ve üstüne bir **ürün vitrini (Etsy mantığı)** ekler. Sonuç: **Fiverr + Etsy + yerel ilan panosu** karması, yurtdışı Türk diasporasına odaklı.

Bu belge; örneğin tam dökümünü, Nisoya'ya kavramsal dönüşümü, önerilen veri modelini, teknoloji yığınını, özellik yol haritasını ve karar verilmesi gereken noktaları içerir.

---

## 1. Jobzilla Analizi (Örnek Platform)

### 1.1 Ne tür bir sistem?
Klasik bir **job board** (iş ilanı sitesi). İki taraf var:
- **İşveren (Employer):** Şirket profili oluşturur, iş ilanı yayınlar, başvuruları yönetir, aday CV'lerini arar.
- **Aday (Candidate):** CV oluşturur, ilanlara başvurur, iş uyarısı kurar, ilanları kaydeder.

### 1.2 Teknik mimari (ve neden bizim için uygun değil)
- **Çekirdek:** WordPress teması (`jobzilla.zip`, 20 MB) + WP Job Manager eklenti ekosistemi + WooCommerce (ücretli ilanlar) + Elementor (sayfa kurucu).
- **Eklentiler:** resumes, applications, bookmarks, alerts, locations, tags, company-reviews, wc-paid-listings, dz-user-message (mesajlaşma), dz-social-connect (sosyal login), dzcore (8 MB tema çekirdeği).
- **Neden kullanmıyoruz:** Kullanıcının açık talebi. WordPress/WooCommerce; ağır, eklenti bağımlı, güncelleme/güvenlik yükü yüksek, özelleştirmesi katmanlı ve kırılgan. Sıfırdan, ihtiyacımıza birebir oturan, hafif bir sistem hedefliyoruz. **Jobzilla'dan kodu değil, ürün fikrini ve veri modelini alıyoruz.**

### 1.3 Veri modeli (varlıklar, alanlar, taksonomiler)
Demo veri dökümünden çıkarılan gerçek model:

| Varlık | Adet (demo) | Açıklama |
|---|---|---|
| `job_listing` | 55 | İş ilanı (ana içerik) |
| `resume` | 53 | Aday CV'si |
| `company` | 17 | İşveren şirket profili |
| `job_application` | 23 | Başvuru kaydı |
| `job_alert` | 11 | Kayıtlı iş uyarısı |
| `product` / `shop_order` | 6 / 47 | Ücretli ilan paketleri (WooCommerce) |

**Taksonomiler (kategori/etiket sistemleri):** `job_listing_category` (iş kategorisi), `job_listing_region` (bölge), `job_listing_type` (tam zamanlı/yarı zamanlı vb.), `job_listing_tag`, `resume_category`, `resume_skill` (yetenek — 33 adet), `resume_region`, `company_category`.

**İş ilanı alanları:** maaş (+ para birimi + birim: saatlik/aylık), lokasyon, deneyim, nitelik, cinsiyet tercihi, uzaktan çalışma, başvuru son tarihi, bağlı şirket, öne çıkan mı, doldu mu.

**CV/aday alanları:** ad, ünvan, e-posta, telefon, foto, video, eğitim, deneyim, lokasyon (+ enlem/boylam), beklenen maaş, CV dosyası, yetenekler.

**Şirket alanları:** isim, logo, adres, telefon, e-posta, web, sosyal medya, kuruluş yılı, ofis galerisi, tanıtım videosu, slogan.

> **Nisoya için çıkarım:** "resume + skills" yapısı, bizim **hizmet sunan kişi profilimizin** neredeyse hazır şablonu. "job_listing" alanları (fiyat + para birimi + birim, lokasyon, uzaktan) bizim **hizmet ilanımıza** birebir uyarlanabilir. "product" tarafı ise bizim **ürün vitrinimizin** çekirdeği.

### 1.4 Kullanıcı rolleri ve akışları
İki rol, her birine ait ayrı panel sayfa seti var:
- **İşveren paneli:** ilan ekle, ilanlarım, şirketlerim, şirket ekle, başvurular, mesajlar, profil, yer imleri.
- **Aday paneli:** CV ekle, adaylar, mesajlar, profil, yer imleri, iş uyarıları, geçmiş başvurular.

### 1.5 Özellik envanteri
1. Kayıt / giriş / şifre sıfırlama (+ sosyal login)
2. Rol bazlı kullanıcı paneli (dashboard)
3. İlan/CV oluşturma formları (alan tipleri: metin, açılır liste, çoklu seçim, dosya, tarih, editör)
4. Gelişmiş arama + filtreleme (kategori, bölge, tip, anahtar kelime)
5. **Harita bazlı arama** (coğrafi konum)
6. **Kullanıcılar arası mesajlaşma**
7. Yer imleri (favoriler)
8. E-posta uyarıları / abonelik (Mailchimp)
9. Şirket değerlendirmeleri (yorum/puan)
10. **Öne çıkan ilan / ücretli paketler** (gelir modeli)
11. Çok dilli altyapı (WPML)
12. Anasayfa varyasyonları, blog, SSS, referanslar

### 1.6 Sayfa/ekran haritası
`login`, `register`, `lost-password`, `user-dashboard`, `my-profile`, `add-job`, `manage-jobs`, `submit-resume`, `candidates`, `manage-companies`, `submit-company`, `messages`, `bookmark-resumes`, `job-alerts`, `past-applications`, `jobs-list` (filtreli liste), `top-map` (harita), tekil detay sayfaları (`single-job_listing`, `single-resume`, `single-company`), `terms-conditions`.

---

## 2. Jobzilla → Nisoya Kavramsal Dönüşüm

### 2.1 Temel zihinsel değişim
| Jobzilla | Nisoya |
|---|---|
| İşveren ilan verir | **Kişi kendini/hizmetini ilan eder** |
| Aday başvurur | **Müşteri iletişime geçer / sipariş verir** |
| "İş" merkezli | "**Yetenek + Hizmet + Ürün**" merkezli |
| Şirket profili | **Esnaf/birey profili** (vitrin) |
| CV = yan içerik | **Profil/vitrin = ana içerik** |
| Maaş | **Hizmet/ürün fiyatı** (çok para birimli) |

### 2.2 Nisoya'nın iki ana ilan tipi
1. **Hizmet / Yetenek İlanı** (Fiverr/yerel ilan mantığı)
   - Örn: "İngilizce ders veririm", "Düğün fotoğrafçısıyım", "Nakliyatta yardım ederim", "Web sitesi yaparım", "Saç-bakım hizmeti".
   - Alanlar: başlık, kategori, açıklama, fiyat (+ para birimi + birim: saatlik/iş başına/paket), konum (ülke/şehir) + **uzaktan/online mı**, görseller, sunan kişinin profili, iletişim/mesaj.
2. **Ev Yapımı Ürün İlanı** (Etsy mantığı)
   - Örn: "Ev yapımı baklava", "El örgüsü bebek hırkası", "Reçel/turşu", "El yapımı takı".
   - Alanlar: ürün adı, kategori, açıklama, fiyat (+ para birimi), stok/üretim süresi, görseller (galeri), teslimat/kargo bilgisi (hangi ülkelere), satıcı profili.

> Not: Üçüncü bir hafif tip olarak **"İş arıyorum" ilanı** da düşünülebilir (kişi hizmet sunmuyor ama "şu işi arıyorum" diyor). MVP'de opsiyonel; ileride eklenebilir.

### 2.3 Neyi alıyoruz / neyi atıyoruz
**Alıyoruz (uyarlayarak):**
- Çift profil + ilan + detay sayfası mimarisi
- Kategori/yetenek taksonomisi
- Kullanıcı paneli (ilanlarım, mesajlar, favoriler, profil)
- Arama + filtreleme + (opsiyonel) harita
- Mesajlaşma
- Yer imleri / favoriler
- Öne çıkan ilan (gelir modeli)
- Fiyat + **çok para birimi** alt yapısı (Jobzilla'da maaş para birimi zaten var)

**Atıyoruz / değiştiriyoruz:**
- WordPress/WooCommerce/Elementor (komple) → sıfırdan kod
- "Başvuru (application)" akışı → yerine "**mesaj/sipariş talebi**"
- Şirket/CV ikiliği → tek **birleşik profil** (herkes hem satıcı hem alıcı olabilir)
- Türk Lirası ve Türkiye odağı → **çok ülke, çok para birimi**
- WPML çok dilli → tek dil (Türkçe), basit

---

## 3. Nisoya'ya Özgü Gereksinimler (Jobzilla'da olmayan)

1. **Tek dil, net kitle:** Arayüz tamamen Türkçe. Kullanıcılar: yurtdışındaki Türkler.
2. **Çok ülke / çok para birimi:** EUR, USD, GBP, CHF, SEK, CAD, AUD... Kullanıcı kendi ülkesini ve para birimini seçer. Fiyatlar girildiği para biriminde gösterilir. (TL **yok**.) Opsiyonel: yaklaşık çeviri gösterimi.
3. **Konum modeli ülke-öncelikli:** Önce ülke, sonra şehir. "Bana yakın" filtresi (aynı ülke/şehirdeki Türkler). Diaspora yoğun şehirler önemli (Berlin, Londra, Amsterdam, Paris, Stockholm...).
4. **Güven & topluluk:** Yabancı bir ülkede "kendi insanından" hizmet almanın güven değeri yüksek. Bu yüzden: profil doğrulama, değerlendirme/puan, yorum sistemi öncelikli.
5. **Hibrit içerik:** Hizmet ilanı + fiziksel ürün ilanı aynı platformda, ayrı akışlarla.
6. **Hafiflik & maliyet:** Hostinger üzerinde dönecek; başlangıçta düşük trafik, düşük maliyet. Aşırı mühendislikten kaçınılmalı.
7. **Para hareketi kararı:** Platform üzerinden ödeme alınacak mı, yoksa sadece **ilan + iletişim** (ödeme tarafların arasında) mı? Bu, yasal/teknik olarak en kritik karar (bkz. Bölüm 8).

---

## 4. Önerilen Veri Modeli (Nisoya)

Sıfırdan kuracağımız için ilişkisel ve sade bir model öneriyorum.

**users** — id, ad_soyad, e-posta, şifre_hash, telefon, foto, ülke, şehir, tercih_para_birimi, hakkında (bio), doğrulanmış_mı, kayıt_tarihi, rol (üye/moderatör/admin).

**listings** (ilanlar — hizmet ve ürün ortak tablo, `type` ile ayrışır) — id, user_id, **type** (`hizmet` | `urun`), başlık, slug, açıklama, kategori_id, fiyat, para_birimi, fiyat_birimi (saatlik/paket/adet vb.), ülke, şehir, uzaktan_mı (hizmet için), stok/teslim_süresi (ürün için), durum (aktif/pasif/beklemede), öne_çıkan_mı, görüntülenme, oluşturma/güncelleme tarihi.

**listing_images** — id, listing_id, dosya_yolu, sıra.

**categories** — id, parent_id, ad, slug, tip (hizmet/ürün/ikisi), ikon. (Ağaç yapı.)

**listing_tags / tags** — yetenek/etiket araması için (Jobzilla'daki resume_skill karşılığı).

**conversations** + **messages** — kullanıcılar arası mesajlaşma (conversation: iki kullanıcı + ilgili ilan; message: gönderen, metin, tarih, okundu mu).

**favorites** — user_id, listing_id (yer imleri).

**reviews** — id, listing_id (veya target_user_id), yazan_user_id, puan (1-5), yorum, tarih.

**reports** — kötüye kullanım/şikayet bildirimi (moderasyon için).

**(opsiyonel, gelir için) featured_orders / payments** — öne çıkarma satın alımları.

> Tasarım notu: Hizmet ve ürünü tek `listings` tablosunda `type` ile tutmak MVP'yi hızlandırır; tip-özel alanlar için ya nullable kolonlar ya da küçük bir `listing_meta` tablosu kullanılır.

---

## 5. Önerilen Teknoloji Yığını (sıfırdan, Hostinger uyumlu)

Seçim, **Hostinger plan tipine** bağlı (bkz. Bölüm 8 — Karar 1):

### Seçenek A — Paylaşımlı/Web Hosting (PHP + MySQL) — **MVP için önerilen**
- **Backend:** PHP 8.2+ (Laravel framework) — Hostinger paylaşımlı planlarda yerel destek, kurulum kolay, maliyet düşük.
- **Veritabanı:** MySQL / MariaDB (Hostinger standart).
- **Frontend:** Laravel Blade + Tailwind CSS + hafif JS (Alpine.js/HTMX). Sunucu taraflı render → SEO ve hız iyi.
- **Avantaj:** Hostinger ile en uyumlu, en ucuz, tek geliştiriciyle en hızlı ilerleme.

### Seçenek B — VPS (modern JS yığını)
- **Backend/Frontend:** Next.js (React) veya Node.js + ayrı frontend.
- **Veritabanı:** PostgreSQL veya MySQL.
- **Avantaj:** Daha modern DX, gerçek zamanlı mesajlaşmaya uygun. **Dezavantaj:** VPS gerekir (daha pahalı, sysadmin yükü).

**Önerim:** MVP'yi **Seçenek A (Laravel + MySQL + Blade/Tailwind)** ile yapmak. Hostinger'ın paylaşımlı planında bile çalışır, en düşük maliyet ve en hızlı sonuç. Platform büyürse VPS'e/Seçenek B'ye geçiş mümkün.

**Ortak ihtiyaçlar:** Görsel yükleme/optimizasyon, e-posta gönderimi (Hostinger SMTP veya harici), önbellekleme, yedekleme.

---

## 6. Özellik Yol Haritası (Fazlar)

### Faz 1 — MVP (çekirdek pazaryeri)
- Kayıt / giriş / şifre sıfırlama
- Profil (birleşik: herkes ilan verebilir + iletişim kurabilir)
- İlan oluştur/düzenle (hizmet **ve** ürün tipi)
- İlan listeleme + arama + filtre (kategori, ülke/şehir, fiyat, anahtar kelime)
- İlan detay sayfası + satıcı profili
- Kullanıcı paneli: ilanlarım, favorilerim, profil düzenle
- Çok para birimi gösterimi
- Temel mesajlaşma (ilan üzerinden iletişim)
- Türkçe arayüz, responsive tasarım
- Temel moderasyon (admin: ilan/kullanıcı yönetimi, şikayet)

### Faz 2 — Güven & topluluk
- Değerlendirme/puan + yorumlar
- Profil doğrulama (e-posta/telefon, opsiyonel kimlik)
- Bildirimler (e-posta + site içi)
- Gelişmiş mesajlaşma (okundu, bildirim)
- "Bana yakın" / konum bazlı keşif, opsiyonel harita

### Faz 3 — Gelir & büyüme
- Öne çıkan ilan / vitrin paketleri (ödeme entegrasyonu)
- İlan kotaları / üyelik seviyeleri
- Kategori bazlı vitrinler, kampanyalar
- (Karar verilirse) platform içi ödeme/escrow

### Faz 4 — Ölçek
- Mobil uygulama (PWA veya native)
- İlan istatistikleri, satıcı paneli analizleri
- SEO/içerik, davet/referans sistemi

---

## 7. Para Kazanma Modeli (platformun geliri)

Jobzilla'daki "ücretli ilan/öne çıkarma" mantığı bize de uygun. Seçenekler:
1. **Öne çıkan ilan ücreti** (ilan başı/süreli) — en sade.
2. **Üyelik seviyeleri** (ücretsiz: X ilan; premium: sınırsız + öne çıkma).
3. **İşlem komisyonu** — yalnızca platform içi ödeme yapılırsa anlamlı (yasal yük getirir).
4. **Reklam / sponsorlu kategori** — ileride.

MVP'de gelir baskısı koymadan, Faz 3'te 1 ve 2'yi devreye almak mantıklı.

---

## 8. Kritik Karar Noktaları

Bunlar ilerlemeden önce netleşmesi gereken, mimariyi doğrudan etkileyen kararlar:

1. **Hostinger plan tipi:** Paylaşımlı/Web Hosting mi, VPS mi? → Teknoloji yığınını (Seçenek A vs B) belirler.
2. **Ödeme modeli:** Platform sadece **ilan + iletişim panosu** mu (ödeme taraflar arasında, en basit ve en az yasal yük), yoksa **platform içi ödeme/komisyon** mu? → MVP kapsamını ve yasal gereklilikleri kökten değiştirir.
3. **İlan tipleri MVP'de:** Hem hizmet hem ürün aynı anda mı, yoksa önce sadece **hizmet/yetenek** ile mi başlayalım (daha hızlı MVP)?
4. **Konum/harita:** MVP'de basit ülke/şehir filtresi yeterli mi, harita Faz 2'ye mi bıraksak? (Önerim: Faz 2.)
5. **Marka/tasarım:** Hazır bir tasarım yönü var mı (logo, renk, his)? Yoksa sade-modern bir yön mü belirleyelim?
6. **Kapsam/sahiplik:** Bu projeyi baştan sona kod olarak ben mi geliştireyim (adım adım), yoksa önce detaylı teknik tasarım/şartname mi istiyorsun?

---

## Ekler
- Örnek platform analizi için kaynak: `jobzilla_v1.8.zip` (geçici olarak çıkarıldı, scratchpad).
- Bu belge canlı bir dokümandır; kararlar netleştikçe güncellenecektir.

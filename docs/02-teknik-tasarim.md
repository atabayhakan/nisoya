# NİSOYA — Teknik Tasarım ve Şartname (v1)

Belge tarihi: 2026-06-30
Durum: Faz 0.5 — Teknik Tasarım
Önceki belge: [01-analiz-jobzilla-ve-nisoya.md](01-analiz-jobzilla-ve-nisoya.md)

---

## 0. Bu Belgenin Dayandığı Kararlar

| Karar | Seçim |
|---|---|
| Barındırma | **Hostinger VPS** (tam kontrol) |
| Ödeme modeli | **Sadece ilan + iletişim** (ödeme taraflar arasında; platform içi ödeme/komisyon YOK) |
| MVP kapsamı | **Önce hizmet/yetenek ilanları** (ürün vitrini Faz 2; veri modeli baştan uyumlu) |
| Dil | **Türkçe** (tek dil) |
| Para birimi | **Çok para birimli, TL YOK** |
| Kitle | Yurtdışındaki Türkler, kendi aralarında |

**Açık kalan tek temel karar:** Kesin yazılım yığını (Bölüm 1'de öneri + alternatif). Belgenin geri kalanı (veritabanı, sayfa akışları, kategoriler, model) yığından büyük ölçüde bağımsızdır; yığın değişse bile bu tasarım geçerli kalır.

---

## 1. Teknoloji Yığını

VPS olduğu için seçim serbest. İki sağlam yol var:

### Öneri (A): Laravel ekosistemi — *bu profil için tavsiyem*
- **Çatı:** Laravel 11 (PHP 8.3)
- **Veritabanı:** PostgreSQL (veya MySQL/MariaDB — ikisi de olur)
- **Arayüz:** Livewire + Blade + Tailwind CSS (reaktif his, ağır JS olmadan)
- **Admin/Moderasyon paneli:** **Filament** — neredeyse hazır, üretim kalitesinde yönetim paneli (kullanıcı/ilan/şikayet yönetimi). Bir pazaryeri için moderasyon şart; bu büyük zaman kazandırır.
- **Kimlik doğrulama:** Laravel Breeze/Fortify (e-posta + şifre, doğrulama, sıfırlama hazır)
- **Neden:** Tek, bütünleşik çatı; auth/mail/kuyruk/ORM/migration/validasyon hepsi yerleşik. Tek kişilik geliştirmede en hızlı ve en az "parça birleştirme". SEO için sunucu render doğal. Hostinger VPS'te kurulum çok standart.

### Alternatif (B): Next.js ekosistemi
- Next.js 15 (App Router, React, TypeScript) + PostgreSQL + Prisma + Tailwind + Auth.js
- **Ne zaman tercih:** İleride React Native ile kod paylaşan mobil uygulama, veya çok "uygulama gibi" SPA his isteniyorsa. Bedeli: auth/admin/ORM'i kendin birleştirirsin, daha fazla parça.

> **Tavsiyem A.** Niş bir topluluk pazaryeri için SEO + güçlü hazır admin + sade-bütünleşik kod + hıza öncelik veriyorum. Mobil/React odaklı büyümek istersen B'ye dönebiliriz; aşağıdaki veritabanı ve sayfa tasarımı her iki durumda da aynı kalır.

### VPS Dağıtım Mimarisi (Hostinger)
- **Nginx** (ters proxy + statik + SSL sonlandırma)
- **PHP-FPM** (A) / **Node + PM2** (B)
- **PostgreSQL** (veya MySQL)
- **Redis** — önbellek + kuyruk (e-posta/bildirim arka plan işleri)
- **Supervisor** — kuyruk işçisi (queue worker) sürekli çalışsın
- **Certbot / Let's Encrypt** — ücretsiz SSL
- **Görsel depolama:** Başlangıçta VPS yerel disk + sunucu tarafı yeniden boyutlama/WebP; büyüyünce object storage'a (S3 uyumlu) taşınabilir
- **E-posta:** Hostinger SMTP veya harici (doğrulama + bildirim e-postaları)
- **Yedekleme:** Günlük DB dump + görsel klasörü yedeği (cron)

---

## 2. Roller & İzinler

| Rol | Yetki |
|---|---|
| **Ziyaretçi** | İlanları görüntüler, arar, filtreler. Mesaj/favori/ilan için kayıt gerekir. |
| **Üye** | Hem **satıcı** (ilan verir) hem **alıcı** (mesajlaşır, favoriler, değerlendirir). Tek birleşik rol. |
| **Moderatör** | İlan/kullanıcı/şikayet yönetimi (admin panel, sınırlı). |
| **Admin** | Tam yetki: kategoriler, kullanıcılar, ayarlar, öne çıkarma, raporlar. |

> Jobzilla'daki "işveren vs aday" ikiliğini birleştiriyoruz: Nisoya'da herkes hem hizmet sunabilir hem alabilir.

---

## 3. Bilgi Mimarisi & Site Haritası

### 3.1 Genel (public) sayfalar
- `/` — Anasayfa (arama kutusu, öne çıkan ilanlar, kategoriler, nasıl çalışır, popüler şehirler)
- `/ilanlar` — Tüm ilanlar (arama + filtre + sıralama)
- `/ilanlar/kategori/{slug}` — Kategoriye göre liste
- `/ilan/{id}-{slug}` — İlan detay sayfası
- `/uye/{kullaniciadi}` — Üye/satıcı profil vitrini (ilanları + değerlendirmeleri)
- `/kategoriler` — Tüm kategoriler
- `/nasil-calisir`, `/hakkimizda`, `/iletisim`, `/sss`
- `/kosullar`, `/gizlilik` — Kullanım koşulları & gizlilik (GDPR)

### 3.2 Kimlik sayfaları
- `/giris`, `/kayit`, `/sifremi-unuttum`, `/sifre-sifirla/{token}`, `/eposta-dogrula`

### 3.3 Üye paneli (`/panel/...`, giriş gerekli)
- `/panel` — Özet (ilan sayısı, mesajlar, görüntülenme)
- `/panel/ilanlarim` — İlanlarım (düzenle/pasifleştir/sil)
- `/panel/ilan/yeni` — Yeni ilan oluştur (hizmet)
- `/panel/ilan/{id}/duzenle` — İlan düzenle
- `/panel/mesajlar` — Mesaj kutusu (konuşma listesi + sohbet)
- `/panel/favorilerim` — Kaydedilen ilanlar
- `/panel/degerlendirmelerim` — Aldığım/yaptığım değerlendirmeler
- `/panel/profil` — Profil düzenle (foto, bio, ülke/şehir, iletişim tercihleri)
- `/panel/ayarlar` — Şifre değiştir, bildirim tercihleri, hesap silme

### 3.4 Admin paneli (`/yonetim/...`, Filament)
- Gösterge paneli, Kullanıcılar, İlanlar (onay/moderasyon), Kategoriler, Şikayetler, Değerlendirmeler, Para birimleri/Ülkeler, Site ayarları, (Faz 3) Öne çıkarma/ödemeler.

---

## 4. Ekran Ekran Sayfa Akışları (MVP)

**Anasayfa** — Amaç: keşif + güven. İçerik: büyük arama kutusu (anahtar kelime + ülke/şehir seçimi), kategori kılavuzu (ikonlu), öne çıkan/yeni ilanlar, "nasıl çalışır" 3 adım, popüler diaspora şehirleri, CTA "İlanını ücretsiz ver". Aksiyon: arama → `/ilanlar`.

**İlan listeleme (`/ilanlar`)** — Sol/üst filtre paneli: kategori, ülke, şehir, fiyat aralığı + para birimi, uzaktan/online mı, sıralama (yeni/fiyat/popüler). Orta: ilan kartları (görsel, başlık, fiyat+pb, konum, satıcı, puan). Sayfalama. Boş durum mesajı.

**İlan detay (`/ilan/{id}-{slug}`)** — Görsel galeri, başlık, fiyat (+birim+pb), kategori, konum/uzaktan rozeti, açıklama, satıcı kartı (foto, isim, puan, üyelik tarihi, "profili gör"), **"Mesaj gönder"** butonu (giriş gerekli), favorile, paylaş, şikayet et. Altında: satıcının diğer ilanları, değerlendirmeler. Görüntülenme sayacı artar.

**Satıcı profili (`/uye/{kullaniciadi}`)** — Foto, isim, bio, ülke/şehir, üyelik tarihi, doğrulama rozetleri, ortalama puan, ilanları (grid), aldığı değerlendirmeler.

**Kayıt (`/kayit`)** — ad soyad, e-posta, şifre, ülke, şehir, tercih para birimi, koşulları kabul. Sonrası: e-posta doğrulama maili. (Opsiyonel: sosyal login Faz 2.)

**Giriş / Şifre sıfırlama** — Standart akış (Breeze/Fortify hazır).

**Panel — Yeni ilan (`/panel/ilan/yeni`)** — Form: tip (MVP: hizmet, sabit), başlık, kategori (ağaç seçim), açıklama (zengin metin), fiyat + para birimi + fiyat birimi (saatlik/iş başına/paket/görüşülür), konum (ülke/şehir) + "uzaktan/online verilebilir" anahtarı, görseller (çoklu yükleme, ilki kapak), etiketler. Kaydet → moderasyon kuyruğuna (ayara göre otomatik yayın veya onay bekler).

**Panel — İlanlarım** — Liste: durum (aktif/pasif/beklemede/reddedildi), görüntülenme, hızlı aksiyonlar (düzenle, pasifleştir, sil, öne çıkar [Faz 3]).

**Panel — Mesajlar** — Sol: konuşma listesi (karşı taraf + son mesaj + okunmamış rozeti). Sağ: sohbet penceresi, ilgili ilan başlığı, mesaj yaz/gönder. (MVP: sayfa yenileme veya kısa polling; gerçek zamanlı Faz 2.)

**Panel — Favoriler / Değerlendirmeler / Profil / Ayarlar** — Bölüm 3.3'teki amaçlar.

**Admin** — Filament ile otomatik CRUD + moderasyon aksiyonları (ilan onayla/reddet, kullanıcı askıya al, şikayet kapat).

---

## 5. Veritabanı Şeması (MVP)

> Notasyon: PK = birincil anahtar, FK = yabancı anahtar. Laravel migration mantığıyla; PostgreSQL/MySQL fark etmez. Zaman damgaları (`created_at`, `updated_at`) tüm tablolarda var sayılır.

### users
| Kolon | Tip | Not |
|---|---|---|
| id | bigint PK | |
| name | varchar | ad soyad |
| username | varchar unique | profil URL'i için |
| email | varchar unique | |
| email_verified_at | timestamp null | |
| password | varchar | hash |
| phone | varchar null | |
| avatar_path | varchar null | |
| bio | text null | |
| country_code | char(2) | ISO ülke (DE, GB, NL...) |
| city | varchar null | |
| preferred_currency | char(3) | EUR, USD, GBP... (TL hariç) |
| role | enum(uye, moderator, admin) | varsayılan uye |
| is_verified | boolean | profil doğrulama rozeti |
| status | enum(aktif, askida, silinmis) | |
| last_seen_at | timestamp null | |

### categories
| Kolon | Tip | Not |
|---|---|---|
| id | bigint PK | |
| parent_id | bigint FK null | ağaç yapı |
| name | varchar | |
| slug | varchar unique | |
| icon | varchar null | ikon adı |
| type | enum(hizmet, urun, ikisi) | MVP'de hizmet |
| sort_order | int | |
| is_active | boolean | |

### listings
| Kolon | Tip | Not |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | sahibi |
| type | enum(hizmet, urun) | MVP: hizmet (ürün Faz 2) |
| title | varchar | |
| slug | varchar | |
| description | text | |
| category_id | bigint FK | |
| price | decimal(12,2) null | null = "görüşülür" |
| currency | char(3) | EUR/USD/GBP... |
| price_unit | enum(saatlik, gunluk, is_basina, paket, adet, gorusulur) | |
| country_code | char(2) | |
| city | varchar null | |
| is_remote | boolean | uzaktan/online verilebilir |
| stock | int null | (ürün için, Faz 2) |
| status | enum(taslak, beklemede, aktif, pasif, reddedildi) | |
| is_featured | boolean | Faz 3 |
| featured_until | timestamp null | Faz 3 |
| views_count | int | |
| **İndeksler** | | (category_id), (country_code, city), (status), (type), full-text(title, description) |

### listing_images
| id PK | listing_id FK | path | sort_order | is_cover |

### tags  /  listing_tag (pivot)
- tags: id, name, slug
- listing_tag: listing_id FK, tag_id FK (çoka-çok). Yetenek/etiket araması için.

### conversations
| id PK | listing_id FK null | user_one_id FK | user_two_id FK | last_message_at | (unique: listing_id+user_one+user_two) |

### messages
| id PK | conversation_id FK | sender_id FK | body | read_at (null) | created_at |

### favorites
| id PK | user_id FK | listing_id FK | (unique: user_id+listing_id) |

### reviews
| id PK | listing_id FK null | reviewee_id FK (değerlendirilen) | reviewer_id FK | rating tinyint(1-5) | comment text | status enum(yayinda, gizli) | (unique: reviewer+reviewee per listing) |

### reports (şikayet/moderasyon)
| id PK | reporter_id FK | reportable_type (listing/user/review) | reportable_id | reason | note | status enum(acik, incelendi, kapandi) |

### Referans tabloları
- **currencies**: code (PK, char3), name, symbol, is_active. (Başlangıç: EUR, USD, GBP, CHF, SEK, NOK, DKK, CAD, AUD, PLN... — TL **yok**.)
- **countries**: code (PK, char2), name_tr, default_currency, is_active. (Diaspora yoğun ülkeler öncelik.)
- (cities: MVP'de serbest metin; ileride referans tabloya çevrilebilir.)

### Çatı tabloları (otomatik)
- password_reset_tokens, sessions, jobs (kuyruk), notifications, personal_access_tokens (gerekirse API).

### Faz 3 (şimdi değil)
- **plans / subscriptions / payments / featured_orders** — öne çıkarma & üyelik gelir modeli.

---

## 6. Kategori Taksonomisi (Hizmet — Diaspora Odaklı Başlangıç)

Üst kategoriler (alt kategori örnekleriyle):
1. **Eğitim & Ders** — yabancı dil, Türkçe (çocuklara), müzik, okul desteği/özel ders, sınav hazırlık, yazılım/kodlama eğitimi
2. **Ev & Tamir** — nakliyat/taşınma, tadilat/boya, montaj, tesisat/elektrik, temizlik, bahçe
3. **Yemek & İkram** — ev yemeği, pasta/tatlı, catering, özel gün menüleri
4. **Güzellik & Bakım** — kuaför, makyaj, cilt/tırnak, gelin hazırlık
5. **Çocuk & Aile** — bebek bakıcılığı, yaşlı bakımı, ev içi yardım
6. **Etkinlik & Medya** — fotoğraf/video, DJ/müzik, organizasyon, davetiye
7. **Dijital & Tasarım** — web sitesi, grafik/logo, sosyal medya yönetimi, içerik/metin
8. **Çeviri & Resmi İşler** — tercüme, evrak/başvuru desteği, danışmanlık (*lisans gerektiren alanlarda uyarı*)
9. **Oto & Ulaşım** — havalimanı transferi, şoförlük, taşıma
10. **Diğer Hizmetler** — terzi, tamir, kişisel asistan vb.

> Not: Sağlık/hukuk/mali müşavirlik gibi **lisans gerektiren** alanlarda platform "tavsiye/danışmanlık" çerçevesinde kalmalı; koşullarda sorumluluk reddi bulunmalı.

(Ürün kategorileri — Faz 2: el yapımı yiyecek, örgü/dikiş, takı/aksesuar, ev dekor, sanat vb.)

---

## 7. Para Birimi & Konum Modeli

- **Para birimi:** Her ilan kendi para biriminde saklanır ve gösterilir (sembolüyle). Kullanıcının `preferred_currency`'si varsayılan seçim olur. **TL hiçbir yerde yok.** (Opsiyonel Faz 2: "≈ X EUR" yaklaşık çeviri, günlük kur ile — bilgi amaçlı.)
- **Konum:** `country_code` (ISO-2) + serbest `city`. Filtrede önce ülke, sonra şehir. "Aynı şehirdekiler / aynı ülkedekiler" keşfi. Harita Faz 2 (opsiyonel, gerekirse).

---

## 8. Kimlik Doğrulama, Güvenlik, Yasal

- E-posta + şifre (hash). E-posta doğrulama zorunlu (ilan vermek için).
- Oturum güvenliği, CSRF, rate limiting (giriş & mesaj & ilan), spam koruması (honeypot/captcha kayıt + ilan).
- Yetkilendirme: kullanıcı yalnızca kendi ilanını/profilini düzenler (policy/gate).
- Görsel yükleme doğrulama (tip/boyut), zararlı içerik taraması.
- **GDPR (AB kullanıcıları):** açık rıza, gizlilik politikası, veri indirme/silme hakkı, çerez bildirimi. (Kitle AB ağırlıklı olduğu için önemli.)
- Koşullarda: platformun aracı olduğu, ödeme/iş kalitesinden sorumlu olmadığı, lisanslı meslekler uyarısı.

---

## 9. Mesajlaşma Tasarımı

- `conversations` (iki kullanıcı + ilgili ilan) + `messages`. İlan detayındaki "Mesaj gönder" konuşma açar/var olana ekler.
- MVP: panelde mesaj kutusu, okunmamış rozeti, e-posta bildirimi ("yeni mesajın var"). Gönderim sayfa aksiyonu/kısa polling.
- Faz 2: gerçek zamanlı (WebSocket — Laravel Reverb/Echo veya Next tarafında socket).
- Kötüye kullanım: mesajda rate limit, şikayet, engelleme.

---

## 10. Arama & Filtreleme

- Anahtar kelime: başlık + açıklama üzerinde full-text (PostgreSQL tsvector / MySQL FULLTEXT). Büyüyünce Meilisearch/Typesense opsiyonel.
- Filtreler: kategori, ülke, şehir, fiyat aralığı (+pb), uzaktan, etiket.
- Sıralama: en yeni, fiyat artan/azalan, en popüler (görüntülenme), en yüksek puan.

---

## 11. Bildirimler

- Site içi + e-posta: yeni mesaj, ilan onaylandı/reddedildi, yeni değerlendirme.
- Tercih yönetimi (`/panel/ayarlar`). Arka planda kuyruk ile gönderim.

---

## 12. Moderasyon & Admin (Filament — Öneri A)

- İlan moderasyonu: yeni ilanlar "beklemede" → onay/ret (veya güvenilir kullanıcılarda otomatik yayın; ayarlanabilir).
- Şikayet kuyruğu (reports), kullanıcı askıya alma, değerlendirme gizleme.
- Kategori & referans (para birimi/ülke) yönetimi, site ayarları.
- Temel metrikler: kullanıcı, ilan, mesaj sayıları; günlük büyüme.

---

## 13. Tasarım / UI Yönü (kısa)

- Sade, modern, güven veren; bol beyaz alan, net tipografi, ikonlu kategoriler, kart tabanlı listeler.
- Mobil öncelikli (responsive) — kitle çoğunlukla telefondan girecek.
- Türkçe mikro-metinler sıcak ve samimi (markanın esprili ruhuna uygun ama profesyonel).
- Renk/logo yönü ayrı bir adımda netleşecek (Bölüm 15).

---

## 14. MVP İş Kırılımı (Build Sırası)

**M0 — Temel kurulum:** VPS hazırlığı, proje iskeleti (seçilen yığın), DB bağlantısı, Tailwind, temel layout (header/footer), Türkçe yerelleştirme.

**M1 — Kimlik:** kayıt, e-posta doğrulama, giriş, şifre sıfırlama, profil iskeleti.

**M2 — Veri modeli & admin:** migration'lar (Bölüm 5), seed (kategoriler, ülkeler, para birimleri), admin paneli (kullanıcı/kategori/ilan yönetimi).

**M3 — İlanlar (çekirdek):** ilan oluştur/düzenle/sil (görsel yükleme), ilan detay, ilanlarım, moderasyon durumları.

**M4 — Keşif:** ilan listeleme + arama + filtre + sıralama + sayfalama, kategori sayfaları, satıcı profili.

**M5 — Etkileşim:** mesajlaşma, favoriler, değerlendirme/puan, şikayet.

**M6 — Cilalama:** anasayfa, statik sayfalar (nasıl çalışır, koşullar, gizlilik), bildirim e-postaları, SEO (meta, sitemap), responsive QA.

**M7 — Yayın:** domain bağlama, SSL, yedekleme, izleme; yumuşak açılış.

**Sonraki (Faz 2+):** ürün vitrini, gerçek zamanlı mesaj, harita, sosyal login, öne çıkarma/üyelik (gelir).

---

## 15. Açık Sorular / Sonraki Kararlar

1. **Yazılım yığını onayı:** Öneri A (Laravel + Livewire + Filament) ile devam edelim mi, yoksa B (Next.js) mi?
2. **Marka/tasarım:** Logo, renk paleti, genel his — hazır bir fikir var mı, yoksa birlikte mi belirleyelim?
3. **İlan onayı:** Yeni ilanlar otomatik mi yayınlansın, yoksa admin onayından mı geçsin (başlangıçta)?
4. **Alan adı:** Domain adı belli mi (nisoya.com vb.)? VPS'e bağlama adımını ona göre planlarız.
5. **VPS erişimi:** Kuruluma geçtiğimizde sunucuya nasıl erişeceğiz (SSH bilgileri, ya da önce yerelde geliştirip sonra mı dağıtacağız)?

---

*Bu belge canlı dokümandır; kararlar netleştikçe güncellenecek. Bir sonraki adım: yığın onayı → M0 kurulum.*

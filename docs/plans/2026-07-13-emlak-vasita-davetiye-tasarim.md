# Emlak + Vasıta + Davetiye Genişleme Tasarımı

**Tarih:** 2026-07-13 · **Durum:** Onaylandı (beyin fırtınası oturumu sonucu)

Nisoya'nın üç büyük genişlemesinin tasarım belgesi: (1) Emlak ilanları + kısa dönem
(Airbnb tarzı) kiralama, (2) araç satış + kiralama ilanları, (3) davetiye modülü
(düğün/sünnet/iftar/özel günler) + etkinlik sonrası anı akışı.

## Onaylanan temel kararlar

| Karar | Seçim |
|---|---|
| Rezervasyon derinliği | Takvim + mesajla anlaşma. Online ödeme YOK — "ödeme aranızda" modeli korunur. |
| Veri mimarisi | Hibrit: ilanlar `Listing` kaydı kalır + dikeye özel 1:1 detay tablosu. |
| Davetiye gizliliği | Varsayılan özel (tokenli link), ev sahibi isterse albümü herkese açar. |
| Medya depolama | VPS + sıkı limitler; `event-media` disk soyutlaması ile ileride Cloudflare R2'ye tek config'le geçiş. |
| Sıralama | Emlak → Vasıta → Davetiye. |

## 1. Temel mimari: dikey ilan altyapısı

- `ListingType` enum'una iki yeni tip: **`emlak`**, **`vasita`**. Tip, ilan sihirbazındaki
  ek adımı ve hangi detay tablosuna yazılacağını belirler.
- Yeni 1:1 detay tabloları:
  - `listing_property_details`: oda sayısı (3+1), brüt m², bulunduğu kat, eşyalı mı,
    depozito, müsait tarih; kısa dönem için gecelik konuk kapasitesi, minimum konaklama.
  - `listing_vehicle_details`: marka, model, yıl, km, yakıt (elektrik dahil), vites,
    kasa tipi, renk; kiralık için min. kiralama günü, depozito, günlük km sınırı.
- İlan `Listing` kaydı olduğu için **favoriler, mesajlaşma, değerlendirmeler,
  şikayet/moderasyon, öne çıkarma, kayıtlı aramalar, görsel hattı (EXIF temizliği)
  sıfır ek işle çalışır** — ayrı modüle göre işin ~1/3'ü.
- Kategori ağacına iki yeni kök:
  - **Emlak** → Kiralık Konut · Satılık Konut · Kısa Dönem / Tatil Kiralık · Oda & Ev Arkadaşı
  - **Vasıta** → Satılık Araç · Kiralık Araç
- **Ortak müsaitlik takvimi**: `listing_unavailable_ranges` (ilan, başlangıç, bitiş) —
  iki dikey de kullanır. Ziyaretçi takvimden tarih seçip mevcut mesajlaşma üzerinden
  **yapılandırılmış talep** gönderir ("15–22 Ağustos, 2 kişi").
- İlan verme sihirbazına tipe göre tek ek adım girer; akışın kalanı aynı.

## 2. Emlak dikeyi

- **Sayfalar:** `/emlak` vitrini (ülke/şehir + kategori sekmeleri + öne çıkanlar);
  filtreler: fiyat aralığı, oda, m² aralığı, eşyalı, kısa dönemde tarih + kişi sayısı.
  İlan detayında özellik tablosu + müsaitlik takvimi + "Bu tarihler için mesaj gönder".
- **Kısa dönem davranışı:** dolu aralıklar takvimde gri; seçilen aralık çakışma
  kontrolünden geçer; talep mesajlaşmaya kart olarak düşer (tarih, kişi, gecelik × gece
  = tahmini toplam); ev sahibi anlaşınca kartın üzerindeki "Tarihleri kapat" aksiyonuyla
  aralığı dolu işaretler.
- **Diasporaya özgü:** "Yeni gelenlere uygun" rozet seti (Anmeldung yapılabilir,
  SCHUFA istenmez, kefilsiz — ülkeye göre etiketler); Oda & Ev Arkadaşı kategorisi
  öğrenci/yeni göçen vurgusu.
- **Güvenlik:** ilan detayında sabit uyarı ("Evi görmeden asla ödeme yapmayın");
  yeni hesapların ilk ilanı admin onaylı (mevcut `ListingStatus` akışı).
- **SEO:** `RealEstateListing` şeması + şehir bazlı açılış sayfaları
  (`/emlak/almanya/berlin-kiralik`) — organik trafiğin ana kaynağı.

## 3. Vasıta dikeyi

- Emlak deseninin yeniden kullanımı; marka/model/yıl/km/fiyat filtreleri; kiralıkta
  aynı takvim bileşeni.
- **Diasporaya özgü:** "Kesin dönüş nedeniyle acele satılık" rozeti; kiralıkta
  "havalimanı teslimi var" işareti (sıla yolu / tatil senaryosu); elektrikli araç
  filtresi öne çıkar.
- **2027 vizyonu (bu fazda DEĞİL, mimaride yeri hazır):** VIN ile yıl/model/donanım
  otomatik doldurma; fotoğraftan yapay zekayla sahte ilan ön filtresi; "fiyat asistanı"
  (aynı model/yıl/km medyan fiyat önerisi).
- Güvenlik: kapora dolandırıcılığı uyarısı + km/hasar beyanı sorumluluk şerhi.

## 4. Davetiye modülü: çekirdek

- **Varlıklar:** `Event` → `Invitation` (tema/tasarım) → `EventGuest` (davetli + LCV).
- **Etkinlik türleri** (her türün kendi tema seti): Düğün · Nişan · Kına · Sünnet ·
  Doğum/Baby Shower · Doğum Günü · İftar Daveti · Mevlid · Vaftiz & Noel/Paskalya
  (Hristiyan Türkler) · Mezuniyet · Diğer.
- **Akış:** üye etkinlik oluşturur (tür, tarih/saat, mekan + harita) → tema seçer
  (şablonlar Blade/CSS ile yerli, harici servis yok) → sistem tahmin edilemez tokenli
  link üretir (`/davet/a8x3k2...`) → link WhatsApp'ta paylaşılır → **misafir hesap
  açmadan** LCV verir (isim, evet/hayır/belki, kişi sayısı, not) → ev sahibi panelinde
  canlı sayaç.
- **2027 dokunuşları:** "Takvime ekle" (ICS), geri sayım, masalara konan **QR kod**
  (okutunca anı akışına yükleme ekranı), çok dilli davetiye (tek tıkla ikinci dil).
- **Stratejik döngü:** etkinlik oluşturana içeriden pazaryeri önerisi ("Düğün
  fotoğrafçısı mı lazım?") → hizmet ilanlarına köprü. Her paylaşılan link yeni
  ziyaretçi getirir + hizmet pazarına talep pompalar.

## 5. Anı akışı (fotoğraf/video)

- Aynı davet linki etkinlik gününden itibaren ortak albüme dönüşür; misafirler
  hesapsız, LCV'deki isimle yükler; kronolojik akış + emoji tepkileri.
- **Yükleme kuralları (50GB diski koruyan tasarım):**
  - Fotoğraflar mevcut görsel hattından geçer (küçültme, varyantlar, EXIF/GPS
    temizliği, kuyruk) — altyapı hazır.
  - Video: etkinlik başına adet + boyut/süre limiti (ör. 20 video, 100MB/90sn);
    sunucuda dönüştürme YOK (1 CPU) — tarayıcıda oynayan MP4/WebM doğrudan kabul.
  - Etkinlik başına toplam kota (ör. 2GB); ayrı `event-media` Laravel disk'i.
- **Saklama:** etkinlikten 12 ay sonra medya otomatik silinir; 1 ay önce e-posta
  uyarısı + "albümü ZIP indir".
- **Moderasyon:** ev sahibi moderatör (her şeyi silebilir); isteğe bağlı "yüklemeler
  önce onayımdan geçsin" modu (kalabalık etkinliklerde önerilir); her yükleme misafir
  token'ına bağlı — sorunlu misafirin tümü tek tıkla kaldırılır + link engellenir;
  şikayet sistemi bağlanır.
- **Herkese açma:** ev sahibi albümü yayınlarsa `/mutlu-anlar` vitrininde görünür —
  en güçlü organik reklam.

## 6. Fazlama, kesişenler, riskler

**Fazlar** (her faz kendi başına canlıya çıkar):

| Faz | İçerik |
|---|---|
| E1 — Emlak | Dikey altyapı (tip + detay tablosu + takvim), `/emlak` vitrini, filtreler, sihirbaz adımı, yapılandırılmış talep, SEO şemaları |
| E2 — Vasıta | Desenin yeniden kullanımı: satılık + kiralık araç, marka/model filtreleri |
| D1 — Davetiye çekirdeği | Etkinlik + tema + LCV + tokenli link, ev sahibi paneli |
| D2 — Anı akışı | Misafir yüklemeleri, moderasyon, kota + 12 ay saklama |
| D3 — Parlatma | QR kod, ICS, çok dilli davetiye, `/mutlu-anlar` vitrini, ZIP indirme |

**Kesişen kazançlar:** her dikey = yeni SEO sayfaları = AdSense envanteri (zone
sistemine yeni anchor'lar: `emlak_liste_ust`, `davet_akis_alt`...); davetiye →
hizmet pazarına talep köprüsü.

**Riskler ve panzehirler:**

1. **Kapsam şişmesi** — faz bitmeden sıradakine başlanmaz.
2. **Boş vitrin soğukluğu** — lansman dönemi "öne çıkarma ücretsiz" + mevcut üyelere e-posta.
3. **Dolandırıcılık** (emlak/araç en riskli) — sabit uyarı kutuları, yeni hesabın ilk ilanına admin onayı.
4. **KVKK** — misafir isimleri + çocuk fotoğrafları: aydınlatma metni güncellenir,
   EXIF/GPS temizliği hazır, silme hakkı ev sahibi panelinde.
5. **1 CPU sunucu** — video dönüştürme yok, kotalar var, R2'ye geçiş tek config.

# Yaşam Rehberi — Tasarım ve Uygulama Planı

**Tarih:** 2026-08-21
**Durum:** Sahiple brainstorming ile tasarım onaylandı (5 bölüm, hepsi teyit edildi). F0 (altyapı) inşa ediliyor.
**Kapsam:** Yurt dışında yaşayan Türklerin gündelik-hayat sorularına (resmî işlemler DIŞINDA) çözüm olacak, hibrit AI+topluluk üretimli bir bilgi katmanı.
**İlişkili belge:** [Ülke-Adaptif Rehber tasarımı](2026-08-01-ulke-adaptif-rehber-tasarimi.md) — bu plan onun K1-K7 iskeletini yeniden kullanır, ayrı bir veri modeliyle.

---

## 0. Sahibin kararları (brainstorming'den, 2026-08-21)

1. **İsim ve konumlandırma:** "Yaşam Rehberi" — uykudaki `PilotYasamRehberiSeeder`'ın adı; fikir zaten oradaydı, şimdi onaylandı. Ülke Rehberi'yle AYRI yüzey: biri resmî/tek-doğru-cevaplı (vekaletname), diğeri pratik/ipucu niteliğinde (ev nasıl kiralanır).
2. **Kapsam — 8 kategori (hepsi onaylandı):** Bankacılık & Finans, Barınma, Sağlık & Sigorta, İş & Kariyer, Eğitim, Ulaşım, Gündelik bürokrasi, Kültür & Uyum.
3. **Ülke hedefi:** Büyük diaspora ülkelerinin **hepsi** — ama tek seferde değil, kanıtlanmış partiler hâlinde (bkz. §7 Fazlar). "Hepsi birden" seeder denemesinin 210 taslaktan yalnız 30'unun yayınlanabildiği dersi unutulmadı.
4. **Üretim modeli — hibrit:** AI ilk taslağı resmî/güvenilir kaynaktan yazar + bağımsız ajan doğrular (Ülke Rehberi'nin 8+13 ajanlı deseni) → taslak → sahip son onayı verir → yayında. Yayınlandıktan SONRA üyeler yapılandırılmış düzeltme ÖNERİSİ gönderebilir (serbest wiki değil, öneri kuyruğa düşer, sahip onaylar).

---

## 1. Ülke Rehberi'nden miras alınanlar (yeniden inşa edilmeyecek)

- Ülke önceliği: üye ikameti > GeoIP, elle değiştirici (K1'in aynısı).
- `/{ulke}` kökü ve 2 harfli kod rezervasyonu (K5-K6) — catch-all kapısı zaten kurulu.
- Taslak-önce yayın kapısı mantığı (K7) — kaynak+doğrulanma tarihi yoksa yayınlanamaz.
- Bayatlık uyarısı zinciri: `scopeBayat()` → `BekleyenIsler` → Kâhya günlük raporu — yeni içerik türü buraya BAĞLANIR, yeniden yazılmaz.
- Sitemap cache + JSON-LD + Cmd+K taslak-sızdırmama deseni.
- Filament nav grubu: "Ülke Rehberi" — yeni kaynaklar buraya eklenir, ayrı grup açılmaz.

---

## 2. Karar satırları

| # | Karar | Gerekçe |
|---|-------|---------|
| K1 | **Kapsam resmî işlemler DEĞİL** — 8 gündelik-hayat kategorisi (§0.2). | Ülke Rehberi'yle kullanıcı niyeti farklı; karıştırmak ikisini de sulandırır. |
| K2 | **Veri modeli 2 tablo (Ülke Rehberi'nin 3'üne karşı)** — "temsilcilik" gibi ayrı fiziksel varlık yok, doğrudan `countries`. | Basitlik; gereksiz üçüncü katman eklenmez. |
| K3 | **URL: `/{ulke}/yasam/{kategori}/{konu-slug}`** — Ülke Rehberi'nin `/{ulke}/{temsilcilik}/{islem}` ile aynı kökü paylaşır, ayrı üst-seviye rota ailesi açılmaz. | Mevcut catch-all kapısı ve K5-K6 kararları yeniden kullanılır. |
| K4 | **Üretim hibrit: AI-araştır+bağımsız-doğrula → taslak → sahip onayı → yayın → topluluk-öneri-kuyruğu.** | Kanıtlanmış Ülke Rehberi deseni + yayın-sonrası taze tutma mekanizması. |
| K5 | **Topluluk önerisi asla otomatik uygulanmaz** — `yasam_konu_onerileri` kuyruğunda bekler, sahip onaylar. | Gerçek Bilgi Kuralı: yayındaki hiçbir şey doğrulanmadan değişmez. |
| K6 | **Boş hücre sessizce yok — "hazırlanıyor" yazılmaz.** | Bişkek bilgi-notu dersi: boş vaat, dolu vaatten kötü. |
| K7 | **Rollout partiler hâlinde: kategori × birkaç ülke.** İlk parti: Bankacılık & Finans × 5-6 büyük diaspora ülkesi. | "Hepsi birden" tek seferde 210-boş-taslak hatasını tekrarlar. |
| K8 | **Pazaryeri köprüsü:** uygun konularda sayfa altında ilgili ilan kategorisine link. | Nisoya'nın asıl darboğazı arz; bilgi sayfasından ilana köprü bedavaya gelen kazanç. |

---

## 3. Veri modeli

```
yasam_kategorileri        — ad, slug, ikon, sort_order, is_active
                            8 sabit kayıtla seed'lenir, panelden yönetilebilir

yasam_konulari             — kategori_id FK, baslik, slug, kisa_aciklama, sort_order, is_active
                             ÜLKE-BAĞIMSIZ şablon (örn. Bankacılık→"SSN'siz hesap açma")

yasam_konu_icerikleri      — yasam_konusu_id FK, country_code FK(countries),
                             icerik (JSON — DÜZ blok listesi {tip: baslik|paragraf|madde, metin}[]
                             — iç içe repeater yok, ardışık "madde"lar Blade'de
                             tek <ul>'a toplanır (panelden düzenlemesi kolay);
                             Ülke Rehberi'nin `evraklar` alanıyla AYNI desen:
                             serbest markdown değil, yapılandırılmış blok listesi.
                             Sebep: bu depoda markdown yalnız Kâhya sohbet
                             balonu için var (KisitliMarkdown, kalın/italik/link),
                             gövde içerik için hiç kullanılmıyor; yeni bağımlılık
                             eklemeden aynı render güvenliğini korur),
                             kaynak_url, kaynak_aciklama,
                             dogrulanma_tarihi, status(taslak|yayinda),
                             yazan_tur(ai|topluluk|sahip)
                             UNIQUE(yasam_konusu_id, country_code)

yasam_konu_onerileri       — yasam_konu_icerigi_id FK, user_id FK,
                             onerilen_metin, kaynak_url nullable,
                             durum(bekliyor|onaylandi|reddedildi), created_at
```

**URL şeması** (catch-all'dan önce, `{ulke}` aktif ülke kodlarına kısıtlı):
`/{ulke}/yasam` kategori listesi → `/{ulke}/yasam/{kategori}` konu listesi → `/{ulke}/yasam/{kategori}/{konu}` içerik detayı.

---

## 4. Üretim hattı

İş parçası birimi = **bir kategori × birkaç ülke**, tek seferde tüm matris değil. Her parti için Workflow ile:

1. Araştırma ajanları resmî/güvenilir kaynaktan Türkçe taslak yazar, kaynak+tarih not eder.
2. Bağımsız doğrulama ajanı iddiayı sıfırdan çapraz kontrol eder (Chicago doğum-tescili dersi: eksik/yanlış genelleme düşürülür).
3. Sonuç taslak olarak `yasam_konu_icerikleri`ne yazılır, otomatik yayınlanmaz.
4. Sahip son-onay turu yapar (panelden, taslak rozetli liste).

Kaynak bulunamayan hücre üretilmez (K6).

---

## 5. Site yüzeyi

- Ülke sayfasında (`/{ulke}`) mevcut resmî-işlemler bloğunun yanına Yaşam Rehberi bloğu — yalnız o ülkede yayında içerik varsa görünür.
- Ana sayfa: ayrı bölüm açılmaz, mevcut "rehber" `HomeSections` bölümü genişletilir.
- Cmd+K hızlı arama: mevcut entegrasyona dahil edilir, taslak sızmaz.
- Filament: `Yaşam Kategorileri` / `Yaşam Konuları` / `Yaşam Konu İçerikleri` / `Yaşam Konu Önerileri` kaynakları, mevcut "Ülke Rehberi" nav grubuna eklenir.

---

## 6. Hata durumları ve testler

**Kenar durumları:** içeriksiz ülke → blok yok · kaynaksız konu → üretilmez · kötü niyetli/yanlış öneri → otomatik uygulanmaz + üye başına günlük öneri sınırı · 90+ gün → Kâhya uyarısı (mevcut mekanizma).

**Testler** (Ülke Rehberi bekçi deseni):
- Yayın kapısı: taslak → 404, yayınlanan → 200+içerik
- Topluluk önerisi: gönder → kuyrukta bekliyor → onayla → içerik + doğrulanma tarihi güncellendi
- Cmd+K: taslak sızmıyor
- Pazaryeri köprüsü: ilgili kategori varsa link var, yoksa yok
- Bayatlık: 90+ gün → Kâhya raporunda uyarı

---

## 7. Fazlar

- **F0 — Altyapı (ŞİMDİ İNŞA EDİLİYOR):** 4 migration + modeller + `YasamRehberiController` + rotalar + Filament kaynakları + ülke sayfası bloğu (minimal görünüm) + testler.
- **F1 — İlk içerik partisi:** Bankacılık & Finans × Almanya/Hollanda/Fransa/Belçika/Avusturya (+ isteğe bağlı ABD). Workflow ile araştır+doğrula, sahip son onayı.
- **F2 — Tam yüzey entegrasyonu:** ana sayfa bölümü genişletme + Cmd+K + pazaryeri köprüsü (F1'in gerçek içeriği üzerinden anlamlı test edilir).
- **F3 — Topluluk öneri mekanizması:** öneri formu + Filament kuyruğu + onay akışı.
- **F4+:** sıradaki kategori/ülke partileri, sahibin F1 kalite değerlendirmesine göre sıralanır.

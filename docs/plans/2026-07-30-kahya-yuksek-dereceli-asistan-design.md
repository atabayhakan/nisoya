# Kâhya 2.0 — Yüksek Dereceli Asistan Tasarımı

Tarih: 2026-07-30 · Durum: ONAYLANDI (sahip), uygulama F0'dan başlıyor
Kaynak: sahibin vizyon genişletmesi ("Kâhya yüksek dereceli bir asistan olsun, AI
teknolojisinin tüm nimetlerini göster, güvenlik diye kendimizi kısıtlamayalım") +
brainstorm oturumu (5 bölüm, her biri onaylandı).

## 0) Tasarım frekansı (tüm kararların anası)

- **İç eylemde cesaret:** Sistem her gün yedekleniyor; veritabanına yazan eylemlerde
  risk serbest. Korkuluk = işlem + denetim izi + geri-al + günlük yedek dörtlüsü,
  onay kapısı DEĞİL.
- **Dışa çıkan hamlede TEK onay kapısı:** Gönderilmiş e-posta, harcanmış para, dış
  itibar (spam damgası, ban) yedekten geri yüklenemez. Ajan hamleyi kendisi tasarlar,
  sahibe sorar, onaydan sonra uygular.
- **Yeni teknoloji ve harcama serbest:** Sunucuya gereken kurulur (sunucuda yalnız
  Nisoya var); değer katan dış servise MCP/API ile bağlanılır, kredi alınır. Bütçe
  alarmı konur ama bütçe korkusuyla özellik kesilmez.

## 1) Verilmiş kararlar

| Karar | Seçim |
|---|---|
| Ajan çekirdeği | **Hibrit:** önce `laravel/ai` (Laravel içinde, tek kod tabanı); Laravel'in yapamadığı yetenek gerekirse o iş için dar kapsamlı Node yardımcısı |
| Görünürlük sorunu | Arayıp-bulma araçları (çok-turlu araç döngüsü) — statik bağlam gömme DEĞİL |
| Hafıza türü | İkisi birden: elle "hatırla" + otomatik örüntü öğrenme (F5) |
| Hafıza içeriği | Dörtlü: kural + gerçek + ders (düzeltmelerden) + serbest not |
| Hafıza yönetimi | Sohbetten ekleme + Filament panel ekranından yönetim |

## 2) Mimari — üç katman

### 2.1 Ajan çekirdeği
`KahyaSohbeti::karariAl()` (tek çağrı, JSON çıktı) emekli. Yerine `laravel/ai` ajan
sınıfı: native tool-calling döngüsü — araç çağır, sonucu gör, tekrar düşün, gerekirse
başka araç, sonunda cevap. Yan fayda: `response_format:json_object` hilesi ve onun
"json kelimesi" / "geçersiz JSON" hata sınıfı kökten biter. Uzun işler kuyruğa düşer,
sohbeti kilitlemez; bitince Kâhya özetle döner.

### 2.2 Araç halkaları

**Okuma halkası (serbest, sınırsız tur):** `tablo-sorgula` (parametreli güvenli sorgu,
SaltOkunurBekci arkasında — yapısal olarak yazamaz), `panel-haritasi`, `site-teshisi`,
`hafiza-oku` (F1). Mevcut salt-okunur MCP araçları buraya taşınır.

**İç-yazma halkası (cesur):** Bugünkü 10 eylem araca dönüşür, disiplinlerini KORUR
(denetim defteri `kahya_eylemleri` + geri-alma izi). Yeni ilke: eskiden "katalogda
olmayan yapılamaz"dı; şimdi **"denetim izi bırakmayan yapılamaz"** — kapsam genişledi,
izlenebilirlik kaldı. Onay kapısı iç işlerden KALKAR; yalnız toplu-yıkıcı işler
(ör. 20+ kayıt silme) "emin misin?" sorar. Zamanla yeni araçlar: ilan düzenle/pasife
çek, kullanıcı yönet, duyuru bandı, içerik sayfası…

**Dış-eylem halkası (tek onay kapısı):** e-posta gönder, sosyal içerik, eşik üstü
kredi harcama. Hepsi `bekleyen_hamleler` kuyruğuna düşer: Kâhya hamle + gerekçe yazar
→ panelde/balonda hamle kartı → Onayla/Düzenle/Vazgeç → uygulanır.

### 2.3 Hafıza + öğrenme + görev defteri

**`kahya_hafiza`:** tür (`kural`/`gercek`/`ders`/`not`), metin, kaynak
(`sahip`/`kahya-cikarimi`), aktif mi, kullanım sayacı. Ekleme: sohbette "hatırla" →
`hafiza-yaz` aracı (iç-yazma, onay yok, geri alınabilir). Yönetim:
`/yonetim/kahya-hafiza` Filament ekranı. Kullanım: aktif kayıtlar yönergeye
"## Hatırladıkların" bölümü olarak girer; 50+ olursa yalnız kurallar + ilgililer,
gerisi `hafiza-oku` ile aranır.

**Öğrenme döngüsü:** haftalık `kahya:ders-cikar` — onay/ret/geri-alma geçmişi + hamle
kartı kararları + sohbet düzeltmelerinden `ders` tipi kayıt damıtır. Kaynağı
`kahya-cikarimi` işaretli: panelde ayrı renk, tek tıkla silinir. Onay yok, görünürlük tam.

**`kahya_gorevleri`:** haftalarca süren misyonların evi — hedef, adım planı, durum,
sıradaki adım, ilerleme notları. Kâhya günlük koşuda açık görevlere bakar: iç işse
kendisi yapar, dış işse hamle kartı sunar. Günlük rapora "görevlerde durum" bölümü
eklenir — misyonlar sessizce ölmez.

## 3) Dış servisler (ihtiyaç sırasına göre)

| Servis | Ne verir | Halka |
|---|---|---|
| Web araması (Tavily/Brave/Exa, kredili) | Anlık araştırma: kanallar, rakipler, fırsatlar | Okuma |
| Google Places API | Türk işletme keşfi ({şehir}×{meslek}×{dil} permütasyonu, docs/06) | Okuma |
| Amazon SES + ayrı gönderim alt alanı | Onaylı erişim kampanyaları. Ana alana ASLA dokunmaz | **Dış (onaylı)** |
| Google Search Console | SEO döngüsü kendini ölçer | Okuma |

**İlk büyük misyon — "Gerçek kullanıcı bul":** docs/06-tanitim-agenti-plani.md görev
defterine ilk kayıt olur; Kâhya operatördür (keşif → skorlama → zenginleştirme →
taslak → hamle kartı → ısıtmalı gönderim → ölçüm). Hedef sırası: ABD → Orta Asya →
GD Asya. docs/03'teki birikmiş fikirler (TUSU, ATAA, Dubai Rehberi…) aday hamle olarak
taşınır — o dosya rapor değil, iş kuyruğu olur.

**Bütçe:** `kahya_harcamalar` defteri — her API/LLM çağrısının tahmini maliyeti.
Panelde aylık sayaç; eşik (sahip belirler, ör. $30/ay) aşılınca Kâhya durmaz ama dış
kredi harcayan araçlar "onay iste" moduna düşer + haber verir.

## 4) Fazlar (her biri ayrı PR, tek başına değer üretir)

- **F0 — Beyin nakli:** `laravel/ai` + ajan sınıfı + okuma halkası (`tablo-sorgula`
  dahil) + 10 eylemin araca dönüşümü + iç onay kapısının kalkması + **LLM harcama
  sayacı ilk günden**. Kategori-id görünürlük sorunu burada kökten çözülür.
- **F1 — Hafıza:** `kahya_hafiza` + `hatirla`/`hafiza-oku` + Filament ekranı + enjeksiyon.
- **F2 — Görev defteri + hamle kartları:** `kahya_gorevleri` + `bekleyen_hamleler` +
  günlük rapora görev durumu.
- **F3 — Dış gözler:** web araması + Places + Search Console + bütçe eşiği/panel sayacı.
- **F4 — Dış eller:** SES + gönderim alanı + ısıtma/suppression → büyüme misyonu canlanır.
- **F5 — Öğrenme:** haftalık `kahya:ders-cikar` (ham malzeme birikince anlamlı).

## 5) Test disiplini

- Her araç ayrı test (mevcut eylem testleri taşınır); döngü sahte sağlayıcıyla sınanır.
- **Her yeni istem deploy öncesi GERÇEK sağlayıcıyla yerelde denenir** ("json kelimesi"
  mezar taşı dersi — sahte sağlayıcı HTTP kısıtlarını göremez).
- `tablo-sorgula` SaltOkunurBekci arkasında; okuma halkası yapısal olarak yazamaz.

## 6) Dürüst riskler

1. Mesaj başına maliyet 3-10x — sayaç ilk günden görünür kılar.
2. Ucuz modeller araç seçiminde zayıf — sohbet modeli olarak güçlü model (Claude
   Sonnet sınıfı) önerilir; `kahya.sohbet_modeli` ayarı zaten var.
3. Araç sayısı arttıkça yanlış araç seçimi olasılığı artar — "her araç kendini Türkçe
   anlatır" disiplini korunur.
4. `laravel/ai` genç paket, API'si değişebilir — AiManager soyutlaması tampon.

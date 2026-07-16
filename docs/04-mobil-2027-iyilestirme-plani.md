# Mobil 2027 Ufku ve İyileştirme Planı

Bu doküman, cep telefonu teknolojisinin 2027'ye doğru nereye evrileceği
öngörüsüyle Nisoya'nın mobil deneyimine eklenebilecek somut geliştirmeleri
listeler. Spekülatif bir "hayal" değil, bugünden görülebilen (2024-2026'da
zaten şipping olmuş) trendlerin 1-2 yıl içinde olgunlaşmış hâlidir.

> Teknik "nasıl yapılacak" detayı: [05-mobil-teknik-uygulama.md](05-mobil-teknik-uygulama.md)

Mevcut durum tespiti (bu plan buradan başlar):
- Alt sekme çubuğu var (`resources/views/components/mobile-tab-bar.blade.php`) —
  Ana Sayfa / Keşfet / İlan Ver / Mesajlar / Panelim, native-app hissi (Faz H3).
- Komut paleti (Cmd+K) ve mega menü var (Faz H1-H2).
- `safe-area-inset` kullanılıyor (çentik/dinamik ada uyumlu).
- **PWA altyapısı yok**: `manifest.json` yok, service worker yok, web push yok.
- **WebAuthn/passkey yok** — sadece klasik parola girişi.
- JSON-LD ve OG etiketleri mevcut (SEO/paylaşım için temel var, genişletilebilir).

---

## 1. 2027'de cep telefonu — gerçekçi öngörü

1. **OS-seviyesi asistan/ajan entegrasyonu** — Apple Intelligence (App Intents),
   Gemini (Android App Actions), Galaxy AI zaten 2024-25'te başladı; 2027'de
   asistanlar uygulama/site içeriğini "eylem" olarak görüp kullanıcı adına
   arama/işlem yapabilecek olgunluğa ulaşır.
2. **Passkey/biyometrik giriş norm hâline gelir** — parola girişi azınlıkta
   kalır, cihazlar arası senkron (iCloud Keychain, Google Password Manager)
   yaygınlaşır.
3. **PWA'lar native app farkını kapatır** — iOS Safari push (2023'ten beri
   var), background sync, ana ekrana ekleme akıcılaşır; app store'suz
   "gerçek uygulama" hissi ücretsiz platformlar için kritik bir fırsat olur.
4. **Kamera + NPU gücü ile görsel arama/otomatik içerik** sıradanlaşır —
   telefonla çekilen bir nesneden başlık/kategori/fiyat önerisi çıkarmak
   ucuz ve hızlı hâle gelir.
5. **Tarayıcıdan AR ("odanda dene")** — WebXR / iOS Quick Look, ek uygulama
   kurulumu gerektirmeden mobilya/beyaz eşya gibi büyük ürünlerde
   yaygınlaşır.
6. **Katlanabilir telefonlar ve farklı en-boy oranları** çoğalır — tablet-vari
   genişlikte akıcı düzen ihtiyacı artar (responsive zaten temel çözüm).
7. **RCS + zengin paylaşım kartları** SMS'in yerini alır; link paylaşımında
   önizleme kalitesi daha çok önem kazanır.
8. **Cüzdan-native ödeme** — Tap to Pay (iPhone'da kart okuyucusuz ödeme
   alma), dijital kimlik/yaş doğrulama cüzdanları yaygınlaşır.
9. **Bağlantı kalitesi hâlâ eşit değildir** — e-SIM/roaming yaygınlaşsa da
   gurbetçi kullanıcılar seyahat/Körfez gibi değişken bağlantı senaryolarında
   kalır; offline-first tasarımın değeri düşmez.
10. **Bildirim yorgunluğu** — kullanıcılar Odak modlarıyla bildirimi
    kısıyor; az ama değerli, zamanlaması isabetli bildirim öne çıkar.

## 2. Nisoya'nın profiliyle kesişim — neden bu, neden şimdi

- **Ücretsiz platform, reklam bütçesi yok** → PWA + web push, app store'a
  bağımlı olmadan ücretsiz bir geri-kazanım (retention) kanalıdır.
- **Az tekno-meraklı / göçmen kullanıcı kitlesi** → parola yerine
  biyometrik/passkey, yazı yazmak yerine kamerayla ilan vermek giriş
  engelini büyük ölçüde azaltır.
- **Ev eşyası pazaryeri** → kamera-önce ilan akışı ve AR önizleme doğrudan
  arz tarafını büyütür (ilan verme sürtünmesi en büyük engel).
- **Hizmet havuzu (elektrikçi, temizlik vb.)** → yerinde ödeme (Tap to Pay),
  gerçek zamanlı mesajlaşma güven ve dönüşümü artırır.
- **Coğrafi dağınıklık (Avrupa/ABD/Körfez)** → saat dilimine duyarlı
  bildirim zamanlaması, değişken bağlantıya dayanıklı offline taslak.

## 3. Fazlı plan (öneri: "Faz M" — Mobil serisi)

### Faz M1 — PWA temeli
*Efor: düşük-orta · Önce bu yapılmalı, sonraki fazların çoğu buna dayanır.*
- `public/manifest.json` (isim, ikonlar, `display: standalone`, tema rengi
  — zaten admin panelinde ayarlanabilen marka rengiyle uyumlu).
- Basit bir service worker: statik asset cache + offline fallback sayfası.
- `beforeinstallprompt` yakalanıp mobil sekme çubuğunda doğal bir "Uygulama
  olarak yükle" ipucu gösterilmesi (agresif pop-up değil).
- Web Push (VAPID) altyapısı — Laravel tarafında `web-push` paketi.

### Faz M2 — Passkey / biyometrik giriş
*Efor: orta.*
- WebAuthn desteği mevcut parola girişine **ek seçenek** olarak eklenir
  (örn. `laragear/webauthn`), parola akışı kaldırılmaz.
- Özellikle yaşlı/az tekno-meraklı kullanıcı için tekrar giriş sürtünmesini
  neredeyse sıfırlar (Face ID/parmak izi ile).

### Faz M3 — Kamera-önce ilan oluşturma
*Efor: orta-yüksek · Etki: yüksek (arz tarafı büyümesi).*
- "Fotoğraf çek → başlık/kategori/fiyat önerisi" hızlı-ilan modu, mevcut
  ilan formunun üstüne opsiyonel bir kısayol olarak eklenir.
- Ucuz bir görüntü sınıflandırma çağrısı (örn. Gemini Flash/GPT-4o mini)
  yeterli; ücretsiz platform bütçesine uygun düşük maliyetli seçim önemli.

### Faz M4 — Gerçek zamanlı & zengin mesajlaşma
*Efor: orta.*
- Yazıyor... göstergesi, sohbette fotoğraf/konum paylaşımı.
- Paylaşım kartı kalitesi kontrolü (WhatsApp/RCS önizlemesi — OG etiketleri
  zaten var, görsel/başlık kalitesi gözden geçirilir).

### Faz M5 — AR "odanda gör" (deneysel)
*Efor: yüksek · Kapsam: büyük mobilya/beyaz eşya kategorisi ile sınırlı başla.*
- iOS Quick Look / WebXR ile ilan fotoğrafından basit AR önizleme.
- Düşük efor alternatifi: AR olmadan sadece "boyut karşılaştırma" görseli
  (referans nesneyle ölçek kıyaslaması).

### Faz M6 — Akıllı bildirim & asistan entegrasyonu
*Efor: orta · Ufuk: 12-24 ay.*
- Mevcut JSON-LD yapısının genişletilmesi — OS asistanlarının (Siri,
  Gemini) site içeriğini daha iyi "anlaması" için.
- Bildirim sıklığı kullanıcı kontrolüne bırakılır; günlük özet tek
  bildirimde toplanabilir (spam hissi vermemesi marka tonu gereği önemli).

## 4. Öncelik sırası

| Vade | Fazlar |
|---|---|
| Hemen (3-6 ay) | M1 (PWA temeli), M2 (Passkey) |
| Orta vade (6-12 ay) | M3 (kamera-önce ilan), M4 (zengin mesajlaşma) |
| İzle / deneysel (12-24 ay, 2027 ufku) | M5 (AR), M6 (asistan entegrasyonu) |

M1 önce gelmeli çünkü push bildirim ve ana-ekrana-ekleme gibi sonraki
fazların temel taşı; M3 en yüksek doğrudan etkiye sahip (ilan verme
sürtünmesini azaltarak arz tarafını büyütür).

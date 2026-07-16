# Mobil Fazları — Teknik Uygulama Planı

[04-mobil-2027-iyilestirme-plani.md](04-mobil-2027-iyilestirme-plani.md)'deki
stratejik fazların (M1-M6) "nasıl yapılacağı". Yığın varsayımları:
Laravel 13 / PHP 8.3, Blade + Alpine.js, Tailwind 4 + Vite 8, Filament 5
admin, kuyruk işçisi üretimde çalışıyor, HTTPS var (nisoya.com).

---

> **Durum güncellemesi (2026-07-17):** M1.1 ve M1.2'nin zaten canlıda olduğu
> görüldü (statik `public/manifest.webmanifest`, navigasyon-only `public/sw.js`,
> ikonlar, `/offline` sayfası — plan yazılırken `manifest.json` arandığı için
> gözden kaçmıştı). **M1.3 (web push) ve M1.4 (yükleme ipucu) bu tarihte
> uygulandı** — ayrıntılar ilgili bölümlerin sonunda.

## Faz M1 — PWA temeli

### M1.1 Manifest (dinamik)
Marka rengi admin panelinden değiştirilebildiği için (`SiteSetting`),
statik `public/manifest.json` yerine **route üzerinden üretilen** manifest:

- `routes/web.php` → `GET /manifest.webmanifest` → küçük bir controller
  (veya closure) `SiteSetting`'den marka rengini okuyup JSON döner,
  `Cache-Control: public, max-age=3600`.
- İçerik: `name: "Nisoya"`, `short_name`, `start_url: "/"`,
  `display: "standalone"`, `theme_color` (marka rengi),
  `background_color`, ikonlar.
- İkonlar: mevcut logodan 192×192, 512×512 ve **maskable** varyant
  (`purpose: "maskable"`) üretilir → `public/icons/`. Intervention/Image
  zaten kurulu, tek seferlik artisan komutuyla üretilebilir
  (`php artisan nisoya:pwa-icons` gibi).
- `app.blade.php` + `guest.blade.php` `<head>`'ine:
  `<link rel="manifest" href="/manifest.webmanifest">` ve iOS için
  `<link rel="apple-touch-icon" ...>` + `<meta name="apple-mobile-web-app-capable">`.

### M1.2 Service worker
Vite hash'li dosya adı ürettiği için SW **Vite dışında**, elle yazılmış
`public/sw.js` olarak durur (kök scope şart):

- Strateji: HTML istekleri **network-first** (düşerse cache, o da yoksa
  önceden cache'lenmiş `/cevrimdisi` sayfası); statik asset'ler
  (build/, icons/, fonts) **stale-while-revalidate**.
- `/cevrimdisi`: basit Blade sayfası, "İnternet bağlantısı yok" + marka
  görseli (boş durum illüstrasyonları zaten var, aynı dil).
- Kayıt: `resources/js/app.js` sonuna
  `navigator.serviceWorker.register('/sw.js')` (feature-detect ile).
- Sürümleme: `sw.js` içinde `const VERSION = 'v1'` cache adı; deploy
  checklist'ine "SW sürümünü artır" maddesi eklenir (aksi hâlde eski
  cache temizlenmez).
- **Dikkat:** Filament admin (`/admin`) SW scope'u dışında tutulmalı —
  fetch handler'da `/admin` ve `/livewire` isteklerine hiç dokunma
  (passthrough), yoksa admin panelde tuhaf cache sorunları çıkar.

### M1.3 Web Push
- Paketler: `composer require laravel-notification-channels/webpush`
  (altta `minishlink/web-push`).
- `php artisan webpush:vapid` → VAPID anahtarları `.env`'e
  (deploy notu: üretim `.env`'ine de eklenecek).
- Migration: paket `push_subscriptions` tablosunu getirir; `User`'a
  `HasPushSubscriptions` trait'i.
- Frontend: `sw.js`'e `push` + `notificationclick` event handler'ları;
  Alpine ile abonelik akışı — **kullanıcı jesti** ile tetiklenir
  (örn. Mesajlar sayfasında "Yeni mesaj bildirimi al" düğmesi),
  sayfa açılışında asla izin isteme (marka tonu + tarayıcılar bunu
  cezalandırıyor).
- Abonelik kaydı: `POST /panel/push-abonelik` → subscription JSON'ı
  `user->updatePushSubscription(...)`.
- İlk bildirim türleri: yeni mesaj (mevcut `Message` oluşturma noktasına
  notification), kayıtlı arama eşleşmesi (`SavedSearch`/`JobSavedSearch`
  zaten var — günlük kuyruk job'ı). Kuyruk işçisi üretimde mevcut,
  ekstra altyapı gerekmez.
- **iOS gerçeği:** iOS'ta push yalnızca site ana ekrana eklenmişse
  çalışır → M1.4'teki yükleme ipucu bu yüzden önemli.

### M1.4 "Uygulama olarak yükle" ipucu
- Android/Chrome: `beforeinstallprompt` yakalanır (Alpine store'da
  saklanır), Keşfet alt sayfasının (mobile-tab-bar.blade.php'deki sheet)
  altına "📲 Nisoya'yı uygulama olarak yükle" satırı; tıklanınca
  `prompt()`.
- iOS: event yok → Safari + iOS tespit edilirse aynı satır, tıklanınca
  "Paylaş → Ana Ekrana Ekle" adımlarını gösteren küçük bir sheet.
- Bir kez kapatılırsa `localStorage` ile 30 gün susturulur.

**Bitti sayılır:** Lighthouse PWA denetimi geçer; Android'de yükleme
istemi çıkar; uçak modunda `/cevrimdisi` görünür; test push'ı cihaza düşer.

**Uygulandı (2026-07-17):**
- M1.3: `laravel-notification-channels/webpush` v11 kuruldu;
  `push_subscriptions` migration'ı çalıştı; VAPID anahtarları yerel `.env`'de.
  `User` → `HasPushSubscriptions`; `NewMessageNotification`'a `WebPushChannel`
  eklendi (VAPID tanımlı değilse kanal hiç eklenmez — üretim güvenli).
  Uçlar: `POST/DELETE /panel/push-abonelik`
  (`PushSubscriptionController` — bootstrap'taki `shouldRenderJsonWhen`
  yalnızca `api/*`'ı kapsadığı için manuel Validator + açık JSON 422).
  `public/sw.js`'e `push` + `notificationclick` handler'ları; Alpine
  `pushToggle` bileşeni + `<x-push-toggle />` (Mesajlar ve Bildirimler
  sayfa başlıklarında). İzin YALNIZCA düğmeyle istenir.
- M1.4: Alpine `$store.pwa` (`beforeinstallprompt` yakalama, iOS tespiti,
  30 gün localStorage susturma); Keşfet alt sayfasında yükleme kartı —
  Android'de native istem, iOS'ta "Ana Ekrana Ekle" talimatı.
- Testler: `PwaTest`'e 4 push testi eklendi; tam takım 522 test yeşil.
- **Üretim deploy notları:** `composer install` (yeni paket),
  `php artisan migrate` (push_subscriptions), üretim `.env`'ine
  `php artisan webpush:vapid` ile YENİ anahtar üret (yereldekini kopyalama),
  `npm run build`. Kuyruk işçisi zaten çalışıyor — ek altyapı yok.
- iOS gerçek cihaz doğrulaması (ana ekrana ekle → push izni) canlıda
  yapılmalı; yerelde yalnızca Android/masaüstü Chrome doğrulanabilir.

---

## Faz M2 — Passkey / biyometrik giriş

- Paket: `composer require laragear/webauthn` (Laravel 13 uyumlu).
  Migration → `webauthn_credentials` tablosu; `User`'a
  `WebAuthnAuthentication` trait'i.
- Rotalar: attestation (kayıt) `POST /webauthn/register/(options|verify)`
  — auth middleware arkasında; assertion (giriş)
  `POST /webauthn/login/(options|verify)` — misafir.
- Kayıt UX: Panel → Hesap ayarlarına "Yüz tanıma / parmak izi ile giriş"
  kartı; "Bu cihazı ekle" düğmesi → tarayıcı WebAuthn diyaloğu →
  başarıda cihaz listesine eklenir (isim + eklenme tarihi + silme).
- Giriş UX: login formunun altına "🔐 Parmak izi / Yüz tanıma ile gir"
  düğmesi. E-posta alanı doluysa credential'lı doğrudan akış;
  boşsa **discoverable credential** (resident key) denenir.
- JS: paketin verdiği yardımcı script yeterli (ek npm bağımlılığı yok);
  Alpine ile form entegrasyonu.
- Parola akışı ve mevcut 2FA (google2fa) **aynen kalır**; passkey ile
  girişte 2FA adımı atlanabilir (passkey zaten iki faktör sayılır) —
  bu karar tek satırlık bir koşul, uygulamada netleştirilir.
- Yerelde test: `localhost` güvenli bağlam sayılır, çalışır.

**Bitti sayılır:** Bir telefonda kayıt + Face ID/parmak iziyle giriş
uçtan uca çalışır; credential silme çalışır; parola girişi bozulmamıştır.

**Uygulandı (2026-07-17):**
- `laragear/webauthn` v5 + `@laragear/webpass` (npm, JS tarafı);
  `webauthn_credentials` migration'ı çalıştı. `User` →
  `WebAuthnAuthenticatable` + `WebAuthnAuthentication`.
- Giriş: `POST /webauthn/giris(/secenekler)` (guest, `throttle:login`) —
  `WebAuthnLoginController`; başarıda `last_seen_at` güncellenir ve
  `{redirect}` JSON'ı döner. E-posta boşsa discoverable credential.
- Yönetim: `POST/DELETE /panel/profil/passkey*` — kayıt + kendi
  credential'ını silme; UI 2FA sayfasında (`two-factor.blade.php`):
  cihaz listesi (alias + tarih), isteğe bağlı cihaz adı, silme onayı.
  Alias query string ile taşınır (webpass'in body birleştirme
  davranışına bağımlılık yok — `MakeWebAuthnCredential` `response.alias`
  bekliyor, webpass üst seviyeye koyuyor).
- Giriş sayfasında "Parmak izi / Yüz tanıma ile gir" düğmesi (WebAuthn
  desteklenmiyorsa görünmez); guest layout'a eksik CSRF meta eklendi.
- 2FA kararı: girişte OTP sınaması zaten yok (2FA yalnızca kurulum
  altyapısı), passkey doğrudan oturum açar — ek koşul gerekmedi.
- Testler: `PasskeyTest` (8 test — auth koşulları, challenge üretimi,
  kendi/başkasının credential silme yetkisi); tam takım 530 yeşil.
- **Canlıda yapılacak:** gerçek cihazla (telefon) kayıt + giriş seremonisi
  doğrulanmalı — yerel gömülü tarayıcıda platform authenticator yok.
  `config/webauthn.php` RP id'si üretimde `nisoya.com` olarak env'den
  doğrulanmalı (varsayılan APP_URL'den türetilir).

---

## Faz M3 — Kamera-önce ilan oluşturma

- Yeni rota: `GET /panel/ilan/hizli` — mobil öncelikli tek ekran:
  büyük "📷 Fotoğraf çek" düğmesi
  (`<input type="file" accept="image/*" capture="environment">`).
- Akış: fotoğraf seçilir → istemcide önizleme → `POST /panel/ilan/analiz`
  → sunucu Intervention/Image ile ~1024px'e küçültür (maliyet kontrolü)
  → görüntü analiz API çağrısı → JSON döner:
  `{ baslik, kategori_tahmini, durum, fiyat_araligi, aciklama_taslagi }`.
- Model önerisi: **Claude Haiku 4.5** (görüntü destekli, düşük maliyet)
  veya Gemini Flash — tek prompt, yapılandırılmış JSON çıktı; kategori
  listesi prompt'a Nisoya'nın gerçek kategori slug'larıyla verilir ki
  tahmin doğrudan `Category`'ye eşlensin.
- Sonuç ekranı: öneriler **düzenlenebilir alanlar** olarak mevcut ilan
  formuna önceden doldurulmuş gelir (`/panel/ilan/yeni?prefill=...` veya
  session flash) — kullanıcı onaylamadan hiçbir şey yayınlanmaz.
- Koruma: `throttle:10,60` rate limit; API anahtarı `.env`;
  `SiteSetting`'e "Hızlı ilan aktif" anahtarı (admin kapatabilsin);
  API hatasında akış zarifçe normal forma düşer.
- Giriş noktası: alt sekme çubuğundaki "İlan Ver" düğmesi mobilde önce
  küçük bir seçim sheet'i açar: "📷 Fotoğrafla hızlı ilan" / "📝 Normal form".

**Bitti sayılır:** Telefonla çekilen eşya fotoğrafından ~3 sn içinde
makul başlık+kategori önerisi gelir; API kapalıyken normal form akışı
etkilenmez.

**Uygulandı (2026-07-17):**
- **Sağlayıcıdan bağımsız AI katmanı** (bkz. aşağıdaki bölüm) üzerinden çalışır.
- `App\Services\ListingVisionService`: Intervention ile görseli 1024px'e
  küçültüp EXIF strip eder (GPS sağlayıcıya sızmaz), prompt + JSON şema üretir
  ve `App\Contracts\AiProvider::analyzeImage()`'a devreder — hangi AI'ın
  çalıştığını bilmez. Slug → category_id eşlemesi sunucuda; `refusal`/hata/
  kapalı → null.
- `QuickListingController`: `GET /panel/ilan/hizli` (kamera ekranı),
  `POST /panel/ilan/analiz` (`throttle:quick-listing-analyze` = 10/dk).
  Öneriler `withInput()` ile normal forma taşınır (form zaten `old()`
  okuyor — partial'a dokunulmadı); onaylamadan yayınlanmaz. API
  başarısızsa zarifçe normal forma düşer.
- `quick.blade.php`: `capture="environment"` ile kamera-önce ekran,
  önizleme + spinner. Create formunda "Fotoğrafla hızlı doldur" giriş
  kartı (yalnız ürün tipi + özellik açıksa) + prefill banner'ı.
- Testler: `QuickListingTest` (8) + `AiProviderTest` (11); tam takım 549 yeşil.
- **Üretimde yapılacak:** `.env`'e seçili sağlayıcının anahtarını ekle (ör.
  `ANTHROPIC_API_KEY`) — yoksa özellik kapalı kalır, hiçbir şey kırılmaz.
- **Gelecek iyileştirme:** analiz edilen fotoğraf şu an ilana otomatik
  eklenmiyor (kullanıcı normal formda tekrar yükler) — sürtünmeyi azaltmak
  için geçici depolama ile taşınabilir.

---

## Yapay Zeka Katmanı — sağlayıcıdan bağımsız (2026-07-17)

Sisteme eklenen **her** AI özelliği tek bir soyutlamadan geçer; böylece
sağlayıcı (Claude / OpenAI / Gemini / gelecekte başkası) kod değiştirmeden,
sadece `.env` ile değiştirilebilir.

- **Sözleşme:** `App\Contracts\AiProvider` — `isConfigured()`, `name()`,
  `analyzeImage(base64, mediaType, prompt, ?schema)`. Özellik kodu yalnızca
  bu arayüzü konuşur.
- **Sağlayıcılar** (`app/Services/Ai/`): `AnthropicProvider` (Messages API,
  structured output ile şema zorlaması), `OpenAiProvider` (Chat Completions;
  `base_url` ile Azure/yerel uçlar), `OpenRouterProvider` (OpenAiProvider'ı
  genişletir — tek uçtan yüzlerce model, HTTP-Referer/X-Title başlıkları,
  çok-modelli uyumluluk için json_object modu), `GeminiProvider`
  (generateContent, `responseMimeType=json`). Ortak JSON ayrıştırma:
  `AiJson` (```json``` sarmalını da temizler).
- **Çözümleyici:** `App\Services\Ai\AiManager` — `config('ai.default')`'a göre
  sağlayıcıyı seçer; `register()` ile çalışma zamanında yeni sağlayıcı eklenir.
  `AppServiceProvider`'da `AiProvider::class` bu manager'ın seçtiği sağlayıcıya
  bağlanır (tip-ipucu = seçili sağlayıcı).
- **Yapılandırma:** `config/ai.php` — `default` (env `AI_PROVIDER`),
  `features.quick_listing` (env `AI_QUICK_LISTING`), ve `providers` blokları.
  `.env.example` üç sağlayıcının anahtar/model/base_url alanlarını listeler.

**Sağlayıcı değiştirmek:** `.env`'de `AI_PROVIDER=openrouter` (veya `openai`/
`gemini`) yap ve o sağlayıcının `*_API_KEY`'ini doldur — başka hiçbir şey
gerekmez. (Üretimde OpenRouter kullanılıyor.)

**Yeni sağlayıcı eklemek:** `AiProvider`'ı uygulayan bir sınıf yaz →
`AiManager::$providers`'a bir satır → `config/ai.php`'ye bir `providers` bloğu.

**Yeni AI özelliği eklemek:** özelliğin servisine `AiProvider` enjekte et,
`analyzeImage(...)` (veya arayüze eklenecek yeni bir metodu) çağır — sağlayıcı
seçimi otomatik gelir. Yeni bir operasyon türü gerekiyorsa arayüze metot ekle
ve üç sağlayıcıda uygula.

**Admin panelinden yönetim (2026-07-17):** `/yonetim` → "Site Yönetimi →
Yapay Zeka" (`YapayZekaAyarlari` Filament sayfası). Sağlayıcı + API anahtarı
(maskeli password alanı) + model + aç/kapa buradan girilir; `site_settings`
tablosuna yazılır. `AppServiceProvider::mergeAiConfig()` bunları
`config('ai.*')` üzerine **runtime'da** yazar (öncelik: DB > env > kod). Yani
admin panelden anahtar girilince özellik **ANINDA aktifleşir** — config:cache
ya da SSH gerekmez. Sayfadaki "Bağlantıyı test et" düğmesi 1×1 test görseliyle
sağlayıcıya minik bir çağrı yapıp anahtarın/modelin çalıştığını doğrular.

---

## Faz M4 — Gerçek zamanlı & zengin mesajlaşma

### M4.1 WebSocket altyapısı
- **Laravel Reverb** (birinci parti, ücretsiz, self-host):
  `php artisan install:broadcasting` → Reverb + Echo kurulumu.
- VPS'te Reverb daemon'ı systemd servisi olarak (`reverb:start`),
  web sunucusunda `/app` path'i için WebSocket reverse proxy.
- Kanal: `private-conversation.{id}` — yetki `routes/channels.php`'de
  (konuşmanın iki tarafından biri mi?).

### M4.2 Özellikler
- **Anlık mesaj:** `MessageSent` event'i `ShouldBroadcast`; sohbet
  ekranı Echo ile dinler, gelen mesajı Alpine listesine ekler
  (mevcut polling varsa fallback olarak kalabilir).
- **Yazıyor... göstergesi:** whisper (client event) — sunucuya
  yazılmaz, ucuz.
- **Fotoğraf paylaşımı:** `messages` tablosuna nullable `attachment_path`
  + `type` (`text|image|location`) migration'ı; upload'ta
  Intervention/Image ile yeniden boyutlandırma (ListingImage'daki
  mevcut desenle aynı).
- **Konum paylaşımı:** `navigator.geolocation` → `type: location`,
  gövdede `lat,lng`; baloncukta OpenStreetMap statik karo önizleme +
  tıklayınca haritaya link (site zaten harita kullanıyor).
- Push entegrasyonu: M1.3'teki yeni-mesaj bildirimi, kullanıcı sohbet
  ekranında değilse gönderilir (Echo presence ile anlaşılır).

**Bitti sayılır:** İki telefon arasında mesaj yenilemesiz akar;
yazıyor göstergesi görünür; fotoğraflı mesaj gönderilir.

**Uygulandı (2026-07-17) — TRANSPORT KARARI: Reverb YOK, mevcut polling.**
- **M4.1 (Reverb) bilinçli olarak atlandı.** Mesajlaşma zaten çalışan bir
  polling akışına sahipti (`MessageController::stream`, 5 sn). Tek düğümlü
  ücretsiz platformda Reverb = ayrı systemd daemon + nginx WS proxy +
  ölçekleme derdi; marjinal gecikme kazancı için orantısız altyapı.
  Değerli olan zengin özellikler transport'tan bağımsız; onları mevcut
  polling üzerine kurduk. Poll aralığı 5 sn → 3 sn. (Reverb ileride bir
  optimizasyon olarak eklenebilir; gerekli değil.)
- **M4.2 zengin mesajlaşma (uygulandı):**
  - Migration: `messages`'a `type` (text|image|location) + `attachment_path`.
    `Message` modeli: tür sabitleri, `isImage/isLocation/imageUrl/coords`,
    ve tek biçim `toChatArray()` (store + stream aynı çıktıyı üretsin diye).
  - **Fotoğraf:** `ImageService::storeSingle()` — tek varyant webp, EXIF/GPS
    strip (sohbet fotoğrafından konum sızmaz). `store()` çoklu tür alır
    (metin/foto/konum), boş metni reddeder. Balon: tıklanınca tam boy açılır.
  - **Konum:** `navigator.geolocation` → `type=location`, body="lat,lng".
    Balon: "📍 Konumu haritada aç" → OpenStreetMap linki (gömülü karo/dış
    çağrı yok — gizlilik + basitlik).
  - **Yazıyor…:** `POST /panel/mesajlar/{c}/yaziyor` (`throttle:message-typing`
    40/dk) kısa TTL'li cache bayrağı yazar; `stream()` karşı tarafın bayrağını
    döndürür (ayrı polling yok — mevcut poll'a piggyback). İstemci ~2.5 sn'de
    bir pingler.
  - Validasyon: bootstrap `shouldRenderJsonWhen` yalnızca api/* olduğundan
    panel fetch uçlarında `$request->validate()` 422 yerine redirect döner —
    manuel `Validator` ile JSON 422 (M1'deki tuzağın aynısı).
  - UI: initial render `partials/bubble.blade.php`; JS poll `appendMessage()`
    üç türü de kurar (ikisi senkron).
- Testler: `LiveMessagingTest` +6 (boş red, foto, konum, konum aralık, yazıyor
  görünüm/yetki); tam takım 555 yeşil.
- **Üretimde:** ek altyapı YOK. `storage:link` zaten var (foto URL'leri).
  Cache sürücüsü mevcut (yazıyor bayrağı); Reverb daemon'ı gerekmiyor.

---

## Faz M5 — AR / boyut önizleme (deneysel)

Önce **düşük efor sürümü**: gerçek AR değil, ölçek karşılaştırma.
- `listings`/`ListingPropertyDetail` benzeri şekilde mobilya
  kategorisine opsiyonel en/boy/derinlik alanları.
- İlan sayfasında "📏 Boyut karşılaştır" — kapı (200×90), insan (170cm)
  gibi referans silüetlerin yanında ürünün ölçekli SVG çizimi.
Gerçek AR (USDZ/WebXR) ancak 3D model üretimi ucuzlarsa gündeme alınır;
ikinci el eşya fotoğrafından otomatik 3D bugün hâlâ pahalı/kalitesiz.

## Faz M6 — Akıllı bildirim & asistan hazırlığı

- `users`'a bildirim tercihleri (anlık / günlük özet / kapalı) —
  panel ayar sayfası + push gönderimlerinde kontrol.
- Günlük özet: zamanlanmış job, kullanıcının **yerel saat dilimine**
  göre sabah saatinde tek push'ta toplar (ülke bilgisi GeoLite2'den
  zaten var).
- JSON-LD genişletme: `Offer`/`priceValidUntil`, `FAQPage` (Nasıl
  Çalışır sayfası), `SearchAction` — asistanların site içeriğini eylem
  olarak tanıması için temel.

---

## Uygulama sırası ve bağımlılıklar

```
M1.1 manifest ─┐
M1.2 SW ───────┼─► M1.3 push ─► M1.4 yükleme ipucu ─► (M4 push entegrasyonu, M6 özet)
M2 passkey ────┘   (bağımsız, paralel yapılabilir)
M3 hızlı ilan      (bağımsız)
M4 Reverb          (M1'den bağımsız; push entegrasyonu M1.3 ister)
```

Önerilen sprint dilimi: **1) M1.1+M1.2** (bir oturum), **2) M1.3+M1.4**
(bir oturum), **3) M2**, **4) M3**, **5) M4.1+M4.2**. Her dilim kendi
başına deploy edilebilir; hiçbiri mevcut akışları kırmaz (hepsi ekleme/
opsiyonel katman).

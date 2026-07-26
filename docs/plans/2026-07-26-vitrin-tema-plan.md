# Vitrin Teması — Uygulama Planı (onay bekliyor)

Tarih: 2026-07-26 · Kaynak: Claude Design Lab handoff paketi ("Laravel Filament marka rengi sistemi.zip" → `design_handoff_vitrin_temasi/`)
Değerlendirme: 13-ajanlı çalışma (4 keşif + 3 bağımsız mimari önerisi + hakem + düşmanca risk avı + 3 metin yazarı + slogan hakemi). Tüm dosya:satır iddiaları kod tabanından doğrulandı.

## 0) Karar özeti

- **Mimari: view-finder path-prepend.** `tema=vitrin` iken bir middleware `resources/views/vitrin` dizinini Laravel view finder yollarının başına ekler; `view('home')` otomatik `vitrin/home.blade.php`'a çözülür, override yoksa klasiğe düşer. Controller/route/composer/zone-key **sıfır dokunuş**.
- **Tema alanı: ayrı eksen.** `gorunum.tema` = `klasik | vitrin` (varsayılan `klasik`, `site_settings`). Mevcut `tasarim_modu` (eski/yeni/obsidian/nordic) 5. preset YAPILMAZ; **yalnız `tema=klasik` iken hükümlüdür**. Vitrin→klasik dönüşte obsidian/nordic tercihi aynen geri gelir.
- **Geçiş davranışı:** panelden tek tık → `Settings::setMany` → cache forget → bir sonraki istekte anlık geçiş; deploy/SSH gerekmez. Geri dönüş de tek tık. Kabul kriteri: `tema=klasik` → bugünkü görünümle birebir aynı (mevcut test süiti değiştirilmeden yeşil kalması bu garantinin kendisidir).

Alternatifler ve neden elendi: **explicit-map** (controller'da tema-farkında view haritası) — eşlenmemiş onlarca sayfa süresiz yeşil-klasik kalır, handoff'un "vitrin seçilince tüm ön yüz geçer" kriterini karşılamaz; **component-swap** (`@if` dallanması) — klasik dosyaları sarmalar, tek hata iki temayı birden düşürür, Vitrin'in yapısal ayrışması yüzünden bu projede en zayıf yöntem.

## 1) Mekanizma (dosya dosya)

**Yeni:**
- `app/Support/Tema.php` — `aktif()` (Settings + whitelist + Throwable→klasik fallback + admin session-önizleme), `vitrinMi()`, `koyuKilit()`, **`tasarimModu()` (tek kapı: vitrin'de daima `'eski'` döndürür — 'yeni'/obsidian sızıntısını kökten keser)**.
- `app/Http/Middleware/TemaViewYollari.php` — `prependLocation(resource_path('views/vitrin'))`; `bootstrap/app.php` web append listesine (StartSession'dan sonra). Console/queue/mail/Filament etkilenmez → `view:cache` tema-bağımsız.
- `resources/views/vitrin/**` — yalnız aynı-ad override: `components/layouts/app.blade.php`, `home.blade.php`, `listings/index.blade.php`, `listings/show.blade.php` (+ yalnız gerçekten farklılaşırsa `partials/listing-card.blade.php`). İlk satırı "handoff'taki dosya şeması geçersizdir; aynı-ad kuralı geçerlidir" olan `vitrin/README.md`.
- `resources/views/components/vitrin/**` — Vitrin'e ÖZGÜ yeni bileşenler (hero-bento, kategori şeridi, şehirler, satıcı bandı…). Klasik ağaçta yaşarlar çünkü `view:cache` prepend'siz koşar (ComponentTagCompiler tuzağı).
- `resources/views/components/vitrin-theme.blade.php` — emerald→`#3E63F0` rampası, karanlık set (`#0b1220/#131c2f/#6b8afd`), `--font-sans: 'Plus Jakarta Sans', …` (tam fallback yığını + emoji fontları), `--shadow-brand`, `--radius-*`, `--nisoya-seal`.
- **Paylaşılan sözleşme bileşenleri (çatal önleme):** `<x-layout-head-scripts/>`, `<x-layout-tail-scripts/>` ve **`<x-layout-head-meta/>`** (SEO/og/canonical/JSON-LD/adsense-meta tek kopya). Klasik iskelet çıktı-özdeş refactor edilir; vitrin iskeleti aynılarını kullanır. Consent zinciri (analytics/ads template + `nisoyaActivateConsent` + cookie-consent + sw kaydı) tek kopyada kalır.
- `tests/Feature/TemaTest.php`.

**Değişen:** `bootstrap/app.php` · `config/site_defaults.php` (`gorunum.tema`) · `app/Filament/Pages/TasarimAyarlari.php` (Klasik/Vitrin segmenti; "2. 2027 Vitrin & Neo-Craft" preset etiketi → "2. Neo-Craft 2027" rename) + view'ı · `AdminPanelProvider` (`resolveBrandColor()` vitrin dalı `#3E63F0`) · `app/Support/HomeSections.php` (+`vitrin_kategori_seridi`, `vitrin_sehirler`, `vitrin_satici_bandi`) · klasik iskelet (çıktı-özdeş ekstraksiyon) · `theme-init` / `layouts/guest` / `command-palette` / `home` / `companies/show` / `verified-badge` (tasarim_modu okumaları `Tema::tasarimModu()` tek kapısına) · `resources/css/app.css` (statik vitrin token'ları + `header{view-transition-name}` seçicisini `body > header`'a daraltma) · `vite.config.js` (bunny: Plus Jakarta Sans 400–800).

**Admin önizleme:** `?tema_onizleme=vitrin` (yalnız admin yetkisi, session'da kalıcı, `?tema_onizleme=kapat` ile biter) — DB bayrağına dokunmadan canlıda gizli QA.

## 2) Fazlar (her biri ayrı PR)

- **P0 — Altyapı:** Tema + middleware + tek-kapı `tasarimModu()` + head-meta/head-scripts/tail-scripts ekstraksiyonu + TemaTest + bekçi testi. `tema=klasik` → prod'da sıfır görsel etki. Ekstraksiyon çıktı-özdeşliği mevcut consent testleriyle mühürlenmeden P1'e geçilmez.
- **P1 — İskelet + ana sayfa:** vitrin iskeleti (mega menü verisi/⌘K/alt sekme çubuğu paylaşılır; mobile-tab-bar KOPYALANMAZ, paylaşılır) + `vitrin-theme` + `vitrin/home` (hero-bento, kategori şeridi, öne çıkanlar, nabız, nasıl çalışır, şehirler, satıcı bandı). Bayrak kapalı; gizli önizlemeyle QA.
- **P2 — İlan listesi + detay:** `vitrin/listings/index` + `show` (morph view-transition değişkenleri taşınır). Üçlü tamamlanınca panelden `tema=vitrin` açılır.
- **P3 — Hero Yöneticisi:** `hero_settings` migration/model + Filament sayfası (İçerik → Görsel/Video → Bloklar; **Kampanya + A/B ikinci turda**). Admin bento panosu ayrı iz. A/B kuralları: atama `laravel_session` üzerinden (ayrı takip çerezi YOK — KVKK), metrik server-side, tarih/varyant kararı her render'da (cache'lenmiş karar yok).

## 3) Kritik riskler ve önlemler (düşmanca incelemeden)

| Risk | Düzey | Önlem |
|---|---|---|
| Consent/AdSense zinciri vitrin iskeletinde kopar (gelir sıfırlanır, JS hatasız/sessiz) | YÜKSEK | Tek-kopya ekstraksiyon + çift-tema testte banner kökü VE ad-slot render assert'i |
| SEO head çatalı (og/canonical/JSON-LD/adsense-meta sürüklenmesi) | ORTA | `<x-layout-head-meta/>` tek kopya + `@type`/og:image/canonical/noindex assert'leri |
| `tasarim_modu='yeni'` kalıntısı vitrin'e serif/terracotta sızdırır | ORTA | Tek kapı `Tema::tasarimModu()` + test |
| **Ücretli "öne çıkan" rozeti vitrin kartında düşerse ödenmiş görünürlük sessizce kaybolur** | ORTA | Sözleşme testine "featured ilan vitrin grid'inde rozetli" assert'i |
| `header{view-transition-name}` global seçicisi: ikinci `<header>` tüm geçişi sessizce iptal eder; öne çıkan+grid aynı ilanı gösterirse morph adı çiftlenir | ORTA | Seçici daraltma + vitrin gövdesinde `<header>` yasak + öne çıkan kartta transition adı basılmaz |
| Handoff'un dosya şeması (`layouts/vitrin.blade.php` vb.) mimariyle çelişir → uygulayan YZ izlerse 500 | ORTA | `vitrin/README.md` ilk satır + bekçi testi P1'den ÖNCE |
| PWA offline sayfası eski temayla kalır; manifest `theme_color` emerald | DÜŞÜK | Canlıya geçişte SW `CACHE='nisoya-v4'`; manifest kozmetik, kabul |
| Octane'a geçilirse prepend birikir | — | `Tema.php`'ye uyarı yorumu (bugün Octane yok) |
| Üç doğruluk kaynağı (home.* settings / home_highlights / hero_settings) admin karmaşası | ORTA | İlgili Filament sayfalarına aktif-tema rozeti; P3'te vitrin'de kullanılmayan highlight sorgularını atlama istisnası |

## 4) Test planı (özet)

`TemaTest`: varsayılan klasik işaretsiz · vitrin'de `data-tema` + remap + consent + composer akışı · override'sız sayfa fallback 200 · geçersiz değer→klasik · obsidian/yeni kalıntı sızmaz · **bekçi: `vitrin/**` dosya kümesi ≡ `OVERRIDE_LISTESI` beyaz listesi ve her dosyanın klasik adaşı var** · çift-tema sözleşme (dataProvider): zone anahtarları, filtre alan adları, JSON-LD tipleri, morph değişkeni, HomeSections, featured rozeti · Livewire `secTema` kaydı. Mevcut süit değiştirilmeden yeşil. Push öncesi PHPStan.

## 5) Hero metinleri ("ilk 3 saniye" çalışması)

Teşhis: mevcut H1 aynı nefeste hem satıcıya hem alıcıya sesleniyor → kimse "bu benim için" diyemiyor; "yeteneğini paraya dönüştür" her gig platformunun klişesi; açıklama üç soruyla açılıp yükü okuyucuya atıyor.

Finalist 3 set (15 aday arasından hakem seçimi):

**Set 1 — "tanıdık" (durdurma gücü en yüksek):** "Gurbette en kıymetli şey: / **güvenebileceğin bir tanıdık**" · Alt: "Ders, taşınma, tamirat, ev yemeği… Şehrindeki Türklerden hizmet al; yeteneğinle kendi insanından kazan. Selam da pazarlık da Türkçe." · CTA: Tanıdığını bul / İlanını ver · Placeholder: "Kime ihtiyacın var? (ör. taşınma yardımı)" · Satıcı bandı: "Elinden gelen her iş, burada birinin ihtiyacı." (Risk: vurgu 27 karakter, mobilde sarabilir.)

**Set 2 — "ana dilinde" (en tutarlı bütün):** "Derdini yarım dille anlatma, / **ana dilinde hallet**" · Alt: "Muslukçudan matematik hocasına, şehrindeki Türk ustalar ve yetenekler tek yerde. Sor, anlaş, güvenle buluş — hepsi Türkçe." · CTA: Türkçe hizmet bul / Yeteneğini ekle · Placeholder: "Türkçesini ara (ör. kuaför, diş hekimi)" · Satıcı bandı: "Zanaatını Türkçe anlat, şehrindeki Türklere ulaş."

**Set 3 — "soru" (en net/en güvenli):** "Nakliyeci mi, hoca mı? / **hepsi burada, Türkçe**" · Alt: "Taşınma, ders, tamir, ev yemeği, davetiye — yaşadığın şehirde Türkçe konuşan birini dakikalar içinde bul, direkt yaz." · CTA: Şehrinde ara / Ücretsiz ilan · Placeholder: "Kim lazım? (ör. Berlin'de nakliyeci)" · Satıcı bandı: "İlk ilanın 3 dakikada yayında, kuruş ödemeden."

Yedek (satıcı kampanya başlığı olarak saklanacak): "Elinden iş geliyorsa / parası da gelir".

Not: metinler `site_settings`'te yaşadığı için seçilen set hem klasik hem vitrin hero'suna panelden uygulanabilir; temadan bağımsız bir içerik kararıdır.

## 6) Kapsam dışı / ertelenen

Kampanya zamanlayıcı + A/B (P3 ikinci tur) · Filament bento panosu (ayrı iz) · manifest theme_color dinamikleştirme · `partials/listing-card` override'ı (yalnız tasarım gerçekten ayrışırsa).

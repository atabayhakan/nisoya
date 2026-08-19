# Rehber ülke-varsayılanı + Nisoya AI arama — tasarım planı

**Tarih:** 2026-08-19 · **Durum:** Plan — uygulamaya geçilmedi (sahibin talebi: *"bunun icin bir plan cikar ama uygulamaya gecme"*)
**Kapsam:** (A) Konsolosluk rehberinin ziyaretçinin ülkesiyle açılması + elle ülke değiştirme, (B) rehber içine doğal dille arama, (C) anasayfada mevcut arama çubuğunun üstüne "Nisoya AI" çubuğu.
**Devraldığı belgeler:** [2026-08-01 Ülke-Adaptif Rehber](2026-08-01-ulke-adaptif-rehber-tasarimi.md) — buradaki K1 kararının ("her rehber yüzeyinde elle ülke değiştirici") uygulamada eksik kalan yarısını tamamlıyor. [2026-08-13 İlan AI planı](2026-08-13-ilan-yapay-zeka-plani.md) — aynı disiplin burada da geçerli: kullanıcı onayı, zarif geri düşüş, maliyet sınırı, uydurma yasak.

---

## 0. Özet

Sahibin isteği dört madde gibi görünüyor ama kod okuması iki gerçek iş parçası olduğunu gösteriyor:

- **A. Rehber ülke-varsayılanı** — küçük iş. Yeni tablo yok, yeni servis yok; var olan `RehberYuzeyi` metotları üç yeni yere doğru bağlanacak.
- **B. Doğal dil arama** — hem "rehber içi arama" hem "anasayfa Nisoya AI çubuğu" aslında AYNI motorun iki yüzeyi. Ayrı ayrı tasarlamak iki kat iş ve iki ayrı bakım yükü demek.

Her iki parça da var olan altyapıyı genişletiyor, sıfırdan icat etmiyor: `RehberYuzeyi` (Rehber ülke çözümleme), `App\Contracts\AiProvider` + `DogalDilArama`'nın ispatlanmış deseni (public AI arama), `QuickSearchController`'ın rehber eşleştirici mantığı.

---

## 1. Kod tabanı gerçekliği (bu planın dayandığı, kodda doğrulanmış bulgular)

| Parça | Nerede | Durum |
|---|---|---|
| Ziyaretçi ülke tespiti | `App\Services\VisitorLocationService::resolve()` | Hazır. MaxMind GeoLite2, dış çağrı yok, session önbellekli, local'de `?test_country=XX` |
| Rehber ülke önceliği | `App\Services\RehberYuzeyi::cozulenUlkeKodu($uye, $request)` | Hazır ve DOĞRU (üye ikamet > GeoIP) — ama yalnız anasayfa widget'ında (`partials/home/rehber.blade.php`) kullanılıyor |
| Hazır ülke listesi | `RehberYuzeyi::hazirUlkeler()` | Hazır — yalnız yayında içeriği olan ülkeler |
| Footer linki | `vitrin/components/layouts/app.blade.php:217` | **Sorunun kendisi**: `RehberYuzeyi::varsayilanUlkeKodu()`'na gidiyor — bu "ilk hazır ülke", yani HERKESE aynı sabit ülke gösteriliyor, ziyaretçinin kendi ülkesine bakılmıyor |
| `/{ulke}` sayfası | `RehberController::ulke()` + `resources/views/rehber/ulke.blade.php` | Yalnız açık URL ile çalışıyor, ülke-tespitine hiç dokunmuyor. Boş hali (içerik girilmemiş ülke) düz metin: *"ana sayfadan bulabilirsin"* — çözüm değil, geri yönlendirme |
| Sayfa içi ülke değiştirici | — | **Yok.** 2026-08-01 planının K1 kararı ("her rehber yüzeyinde elle ülke değiştirici") yalnız anasayfa widget'ında yerine geldi; `/{ulke}` sayfasının kendisinde hiç yok |
| AI sağlayıcı katmanı | `App\Contracts\AiProvider::analyzeText()` | Hazır, sağlayıcıdan bağımsız (Claude/OpenAI/OpenRouter/Gemini), `config('ai.features')`'ten yönetiliyor |
| Public AI arama emsali | `App\Services\DogalDilArama`, tetikleyici `BrowseController::render()` (`/ilanlar`) | Tek canlı örnek. Desen: 3+ kelime eşiği, 7 gün cache (`md5(query)`), sonucu gerçek kategori/ülke listesine karşı doğrula, `?ham=1` kaçışı, hata → sessizce normal aramaya düş. **Throttle YOK** — bilinen, düzeltilmemiş bir boşluk |
| Kâhya'nın araç-çağırma mimarisi | `EylemKatalogu`/`EylemCalistirici`, `KahyaAjani` | Yazma tarafı admin'e sıkı bağlı (`$isteyen` User zorunlu, onay kuyruğu). Salt-okur araçları (`TabloSorgula`, `RehberOku`) doğru şekil ama admin sohbetine bağlı — public arama için doğrudan taşınamaz |
| Rehber veri modeli | `Temsilcilik`, `IslemTuru` (`aciklama` ≤500 karakter), `TemsilcilikIslemi` (`notlar` serbest metin) | AI'nın eşleştireceği hazır metin bunlar. "Durum/ihtiyaç" için ayrı bir alan yok — AI JSON şemasına gerçek `islem_turleri` listesini kendisi taşımalı |
| İçerik hacmi | Birkaç ülke, 2-3 tam yazılmış işlem türü (Apostil, Vefat/Cenaze) | **İnce.** Bu, Bölüm B'nin kapsamını doğrudan sınırlıyor (bkz. §3.4) |
| Hızlı arama (Cmd+K) | `QuickSearchController::index()` → `/arama/hizli` | AI DEĞİL, 4 ayrı LIKE sorgusu (ilan/iş ilanı/yetenek/rehber). Rehber eşleştirmesi zaten var (`islemTuru.ad`/`temsilcilik.ad`/`sehir`) — B'nin AI katmanının altına düşecek güvenlik ağı olarak yeniden kullanılabilir |
| Maliyetli AI için throttle deseni | `bootstrap/app.php`, örn. `ai-listing-draft` | `[Limit::perMinute(N), Limit::perDay(N)]` ikilisi — "her istek gerçek para" gerekçesiyle. B'nin her iki yüzeyi de bu ikiliyi taşımalı |

---

## 2. Özellik A — Rehber, ziyaretçinin ülkesiyle açılsın

### A.1 Üç küçük değişiklik (yeni servis/tablo yok — var olanı doğru yere bağlamak)

1. **Giriş noktası düzeltmesi.** Rehbere spesifik ülke belirtmeden giden her link (bugün: Vitrin footer'ı, `varsayilanUlkeKodu()`'na sabit gidiyor) önce `cozulenUlkeKodu($request->user(), $request)`'i dener; sonuç `hazirUlkeler()` içinde değilse (ya da null ise) `varsayilanUlkeKodu()`'na düşer. Aynı kontrol Klasik temada da (varsa) menü/footer linki için yapılmalı — bugün Klasik footer'da Rehber linki bulunamadı, bu ayrıca doğrulanmalı.
2. **Sayfa içi ülke değiştirici.** `rehber/ulke.blade.php` (ve gezinme tutarlılığı için `temsilcilik.blade.php`/`islem.blade.php` breadcrumb'ı) küçük bir seçici kazanır — `hazirUlkeler()`'den beslenen bir `<select>` ya da çip listesi, seçilince `/{kod}`'a gider. Bu, 2026-08-01 planının K1 kararını ("her rehber yüzeyinde elle ülke değiştirici") tamamlıyor.
3. **Boş hal onarımı.** `/{ulke}` sayfasının içerik-yok hali bugün yalnız düz metin ("ana sayfadan bulabilirsin"). Aynı `hazirUlkeler()` listesi SAYFANIN İÇİNDE gösterilir — ziyaretçiyi geri göndermek yerine doğrudan seçenek sunulur. Küçük bir değişiklik, dürüstlük ilkesiyle (gerçek bilgi kuralı, envanter kapısı) birebir örtüşüyor: "ülken hazır değil ama işte hazır olanlar" — "git ana sayfada ara" değil.

### A.2 Sınır durumları

| Durum | Davranış |
|---|---|
| Ziyaretçinin ülkesi tespit edilemedi (local dev, GeoIP başarısız) | `varsayilanUlkeKodu()`'na düş — bugünkünden farksız |
| Ziyaretçinin ülkesi tespit edildi ama rehberi boş | `/{kod}` açılır, boş hal `hazirUlkeler()` listesini gösterir (A.1 madde 3) — silent redirect YOK, ne aradığını gördüğü ülkede kalır |
| Üye, profilindeki ülkeden farklı bir yerde geziniyor | `cozulenUlkeKodu()` zaten üye ikametini GeoIP'nin önüne koyuyor (K1) — bu davranış aynen korunuyor, yeni bir kural icat edilmiyor |

### A.3 Kapsam dışı
- `cities`/şehir bazlı otomatik yönlendirme yok (K2 zaten `cities`'i rehber dışı tutuyor).
- Yeni tablo, yeni servis, yeni migration yok — yalnız 2-3 view + 1-2 controller/route bağlantısı.

---

## 3. Özellik B — Doğal dille arama (Rehber içi + Anasayfa "Nisoya AI")

### 3.1 Mimari öneri: tek motor, iki yüzey

İki ayrı AI pipeline kurmak yerine tek bir paylaşılan servis öneriliyor:

- **`RehberDogalDilArama`** (yeni, `DogalDilArama` ile birebir aynı isimlendirme/desen) — soruyu gerçek `islem_turleri`/`countries` listesine karşı yapılandırılmış JSON'a çevirir, DB'de doğrular, `TemsilcilikIslemi` sonuçları döner.
- **`NisoyaAiYonlendirici`** (yeni) — anasayfa çubuğunun arkasındaki ince yönlendirme katmanı. Tek bir AI çağrısıyla niyeti sınıflandırır (`rehber` | `ilan` | `belirsiz`) ve uygun motora (yukarıdaki `RehberDogalDilArama` ya da var olan `DogalDilArama`) devreder. Kendi başına yeni bir arama mantığı YAZMAZ — yönlendirir.

Bu ayrım şu yüzden önemli: Rehber içi arama zaten "ben bu ülkedeyim, bu bölümdeyim" bağlamını taşıyor (ülke sabit, yalnız işlem türü aranıyor — daha dar, daha ucuz sorgu). Anasayfa çubuğu bağlamsız (hangi ülke, hangi konu belli değil — önce SINIFLANDIRMA sonra ARAMA gerekiyor). İkisini aynı fonksiyona zorlamak ya rehber içi aramayı gereksiz yere pahalılaştırır ya da anasayfa çubuğunu eksik bırakır.

### 3.2 Rehber içi arama (B — madde 3)

- **Yer:** `/{ulke}` sayfasının üstünde, ülke zaten bağlamda (URL'den biliniyor) — AI'nın yalnız işlem türünü çözmesi yeterli, ülke tahmini gerekmez (soruda başka bir ülke geçse bile, "şu an hangi ülkedesin" bağlamı sayfadan gelir; kullanıcı "Kırgızistan'a..." yazsa bile aslında o an Kırgızistan sayfasındaysa bu zaten tutarlı — A.1'in çalışması sayesinde büyük ihtimalle doğru sayfada).
- **Girdi → çıktı:** Serbest metin → `AiProvider::analyzeText()` → JSON şeması `{islem_turu_slug?: string, anahtar_kelimeler: string[]}` (gerçek `islem_turleri` slug listesi PROMPT'A verilir, model yalnız bunlardan seçer). Dönen slug gerçek bir `IslemTuru`'ya karşılık gelmiyorsa → `QuickSearchController`'ın var olan LIKE-eşleştirme mantığına düş (anahtar kelimelerle).
- **Sonuç:** Eşleşen `TemsilcilikIslemi` kayıtlarına (var olan `/{ulke}/{temsilcilik}/{islem}` sayfalarına) bağlantı. AI yeni metin ÜRETMEZ, yalnız var olan (zaten `resmi_kaynak_url` + `dogrulanma_tarihi` taşıyan, insan tarafından yazılmış) sayfalara yönlendirir.
- **Boş sonuç:** "Bu konuda hazır rehberimiz yok" + o ülkedeki mevcut işlem türlerinin listesi (yine `ulkeOzeti()`'nin zaten döndürdüğü veri) — asla sessiz/boş ekran değil.

### 3.3 Anasayfa "Nisoya AI" çubuğu (C — madde 4)

- **Yer:** Var olan arama çubuğunun üstünde, ayrı ve görsel olarak farklı bir giriş noktası (kullanıcı isteğiyle birebir: "arama cubugunun ustune").
- **Akış:** tek AI çağrısı → niyet + yapılandırılmış alanlar aynı anda çıkarılır (`{niyet: 'rehber'|'ilan'|'belirsiz', ulke_kodu?, islem_turu_slug?, ilan_anahtar_kelimeleri?}`) → `niyet`'e göre §3.1'deki iki motordan birine devredilir → sonuçlar, Cmd+K'nin (`command-palette.blade.php`) zaten kullandığı canlı-sonuç panel deseniyle çubuğun altında gösterilir (yeni bir sonuç-UI paradigması icat edilmiyor).
- **Boş/belirsiz sonuç:** Sessiz başarısızlık YOK. "Şunu deneyebilirsin" — tüm rehbere ya da tüm ilanlara bağlantı.

### 3.4 Reddedilen/ertelenmiş yaklaşımlar ve gerekçesi

| Fikir | Karar | Gerekçe |
|---|---|---|
| Kâhya'nın `EylemKatalogu`/`EylemCalistirici`/`KahyaAjani` makinesini doğrudan public arama için kullanmak | **REDDEDİLDİ** | Mimari admin'e sıkı bağlı: her `Eylem` bir `$isteyen` (User) varsayıyor, onay kuyruğu admin paneline yazıyor. Ziyaretçi tarafında anlamsız — yeniden inşa yerine yalnız DESENİ (salt-okur, doğrulanmış, kısıtlı) ödünç alıyoruz |
| AI'nin soruya DOĞRUDAN cevap üretmesi (örn. "senin durumunda şu evrakları getir") | **REDDEDİLDİ** | K7 ihlali olur: rehber içeriği yalnız resmî kaynaktan özetlenmiş, doğrulanmış metinle yayınlanıyor. Danışmanlık hattı konsolosluk/göçmenlik gibi hassas bir alanda — model uydurursa gerçek zarar (yanlış evrakla konsolosluğa gidip geri çevrilmek) olur. AI yalnız EŞLEŞTİRİR, var olan doğrulanmış sayfaya yönlendirir; asla yeni metin üretmez |
| Anasayfa çubuğunun gün-1'den TÜM site içeriğinde (statik sayfalar, SSS, iş ilanları) serbest anlamsal arama yapması | **ERTELENDİ** | İçerik hacmi bunu desteklemiyor (§1: birkaç ülke, 2-3 işlem türü; pazaryeri 3. taraf arzı da ayrı bir belgede sıfıra yakın ölçüldü). Anasayfa, sitenin EN göze çarpan, ilk-temas yüzeyi — burada sık "bulamadım" cevabı vermek, aynı hatanın derinlerde bir sayfada olmasından çok daha pahalıya patlar (güven kaybı). V1 yalnız Rehber+İlan yönlendirmesiyle sınırlı; ikisi de zaten gerçek, sorgulanabilir veri ve AI altyapısı taşıyor |

---

## 4. Her iki alt-özellikte de geçerli kurallar

İlan AI planındaki disiplinin doğrudan devamı:

- **AI kapalıysa/kırıksa zarif geri düşüş.** `AiProvider::analyzeText()` `null` dönerse her iki yüzey de LIKE-tabanlı eşleştirmeye (Rehber içi: `QuickSearchController`'ın mantığı; anasayfa: var olan normal arama kutusu) sessizce düşer. Hiçbir akış AI'ye bağımlı olmaz.
- **Maliyet sınırı — perMinute + perDay ikilisi, ikisi de yeni.** Önerilen: `rehber-ai-arama` ve `nisoya-ai-arama`, `[perMinute(5), perDay(50)]`, kullanıcı-veya-IP. **Not:** `DogalDilArama`'nın bugünkü canlı rotasında (`/ilanlar`) HİÇ throttle yok — bu planın kapsamı dışında ama aynı ailede bilinen bir boşluk; aynı fazda kapatılması ucuz ve tutarlı olur (ayrı bir onay gerektirir, bu plana dahil edilmedi).
- **Admin kapatma anahtarı.** Anasayfa çubuğu sitenin en görünür konumu — `config/ai.php`'deki deploy-zamanı bayrak yerine, `Settings`-destekli ÇALIŞMA-ZAMANI bir anahtar öneriliyor (`gorunum.logo_animasyon` ile aynı desen): maliyet sıçrarsa sahip deploy beklemeden kapatabilir.
- **Uydurma yasak.** AI yalnız var olan `IslemTuru`/`Country`/kategori listesine karşı doğrulanmış alanlar döner; dönen değer gerçek listede yoksa güvenlik ağına (LIKE arama) düşülür — `DogalDilArama`'nın zaten yaptığı gibi.
- **Her sonuç gerçek, insan-yazılı bir sayfaya çıkar.** AI yeni açıklama metni üretmez, var olan `resmi_kaynak_url` + `dogrulanma_tarihi` taşıyan içeriğe yönlendirir.
- **Kullanıcı onayı burada geçerli değil (yalnız okuma).** İlan AI planındaki "kullanıcı onaylamadan yayınlanmaz" kuralı yazma işlemleri için — bu özellik salt-okur arama olduğundan karşılığı yok, ama "AI asla yeni içerik üretmez" ile aynı ruhu taşıyor.

---

## 5. Değişiklik envanteri (özet — kod değil, planlama referansı)

| Katman | Değişiklik |
|---|---|
| Route | `POST /arama/rehber-ai` (Rehber içi), `POST /arama/ai` (anasayfa) — ikisi de `QuickSearchController`'dan AYRI, kendi throttle'larıyla |
| Servis | `App\Services\RehberDogalDilArama` (yeni), `App\Services\NisoyaAiYonlendirici` (yeni) |
| Controller | Rehber sayfalarına küçük ekleme (A.1); yeni 1-2 controller metodu (B) |
| View | `rehber/ulke.blade.php` + değiştirici parçası (yeni partial); anasayfa hero'ya yeni çubuk bileşeni; Cmd+K'nin sonuç panel deseni yeniden kullanılıyor |
| Admin | Yeni `Settings` anahtarı: anasayfa AI çubuğu aç/kapa |
| Config | `bootstrap/app.php`'ye 2 yeni `RateLimiter::for()` girişi |
| DB | **Yok** — hiçbir yeni tablo/migration gerekmiyor |

---

## 6. Test stratejisi (uygulama fazında)

Bu oturumda kurulan disiplinin devamı — mutation-test edilmiş guard testleri:

- **A:** footer/nav linki → ziyaretçi ülkesi X ise `/x`'e gider (test_country ile simüle); X'in rehberi boşsa `varsayilanUlkeKodu()`'na düşer; `/{ulke}` boş halinde `hazirUlkeler()` listesi görünür (assertSee).
- **B (her iki yüzey):** `AiProvider` sahte `null` dönünce sonuç sayfası hâlâ 200 + LIKE-arama sonucu döner (asla 500, asla boş beyaz sayfa); AI'nin uydurduğu geçersiz slug/kod sessizce reddedilir (DB'de olmayan bir `islem_turu_slug` sonuçlara sızmaz); throttle konfigürasyonu var ve doğru limitte; admin anahtarı kapalıyken anasayfa çubuğu render edilmez.
- **Mevcut testler bozulmamalı:** `AnasayfaSiraTest`, `TemaJetonlariTest` ailesi (yeni admin anahtarı görünüm sistemine dokunmuyorsa etkilenmez), Rehber'in var olan feature testleri (`RehberController` etrafında olması beklenen testler).

---

## 7. Bu planda benim yaptığım varsayımlar (kilit değil, kolayca değişebilir)

| Varsayım | Neden böyle seçtim | Alternatif |
|---|---|---|
| B, tek paylaşılan motor + iki ince yüzey (§3.1) | İki ayrı pipeline = iki kat bakım; rehber-içi ve anasayfa senaryoları zaten örtüşüyor | İki tamamen ayrı servis — daha basit ama kodun %70'i birbirinin kopyası olurdu |
| Anasayfa çubuğu V1'de yalnız Rehber+İlan'a yönlendirir, site-geneli serbest arama YAPMAZ (§3.4) | İçerik hacmi bugün ince; göze çarpan bir yerde sık "bulamadım" pahalı | V1'den itibaren tüm statik sayfalar/SSS'yi de kapsamak — daha iddialı ama bugünkü içerikle çoğu soruda boş dönme riski yüksek |
| Anasayfa çubuğu için admin kill-switch `Settings` (çalışma-zamanı) üzerinden, `config/ai.php` (deploy-zamanı) üzerinden DEĞİL | En görünür/en maliyetli yüzey — deploy beklemeden kapatılabilmeli | `config/ai.php`'deki `features` bayrağıyla aynı desen — daha az kod ama kapatmak deploy gerektirir |
| `DogalDilArama`'nın throttle-yok boşluğunu bu plana DAHİL ETMEDİM, yalnız not düştüm (§4) | Kapsam taşırması — sahip istemeden var olan bir özelliğin davranışını değiştirmiş olmayayım | Aynı fazda sessizce eklemek — daha tutarlı ama talep edilmeyen bir değişiklik |

---

## 8. Uygulama sırası (yalnız "uygulamaya geç" denirse)

1. **Faz 1 — Özellik A.** En küçük, en düşük riskli, yeni servise ihtiyaç duymuyor. Tek başına da değer üretir.
2. **Faz 2 — Rehber içi arama (B, dar bağlam).** A bittikten sonra doğal devam; ülke zaten bağlamda olduğundan daha basit AI şeması.
3. **Faz 3 — Anasayfa Nisoya AI çubuğu.** En görünür, en riskli konum; Faz 2'nin motoru zaten test edilmiş olacağından üstüne inşa ediliyor, sıfırdan değil.

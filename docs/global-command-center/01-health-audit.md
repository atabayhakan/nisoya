# NISOYA — Faz 1: Sistem sağlığı ve kaynak kod denetimi

Tarih: 14 Eylül 2026. Kapsam: yerel kaynak kod, bağımlılık kilidi, ilgili migration ve testlerin okunması. **Üretim verisi, canlı sunucu, gerçek API yanıtları, Redis durumu ve tarayıcı performansı incelenmedi.** Bu rapor mevcut üretim arızasının veya ölçülmüş gecikmenin kanıtı değildir. Aşağıdaki kod yolları ve ölçek riskleri doğrulanmıştır; performans eşikleri uygulama sonrası kabul hedefidir.

Bu çalışma uygulama dosyalarını değiştirmez. Dosya ve satır başvuruları `D:\Nisoya` depo köküne göredir ve denetim anındaki sürümü gösterir.

## 1. Sürüm doğrulaması

Talepteki Laravel 12 / Filament 3 / Livewire 3 varsayımı mevcut depoyla uyuşmuyor. `composer.json:9,11,16` PHP `^8.3`, Filament `^5.6`, Laravel `^13.8` ister. `composer.lock` içinde kilitli sürümler:

| Bileşen | Mevcut kilitli sürüm | Uygulama sonucu |
|---|---|---|
| Laravel | 13.31.0 | Bağlam ve kuyruk kodları bu ana sürümde doğrulanmalı. |
| Filament | 5.8.1 | Formlar `Filament\Schemas\Schema`; yeni sayfalar ve aksiyonlar mevcut API ile yazılmalı. |
| Livewire | 4.4.4 | Olaylar, durum doğrulama ve bileşen yaşam döngüsü bu sürümde test edilmeli. |

Filament 3 örneklerinin doğrudan kopyalanması dağıtım kabulü değildir. İşlevsel niyet korunarak mevcut sürüme uyarlanmalıdır.

## 2. Öncelikli bulgular

Önem düzeyleri: **P1** ilk dağıtım öncesi ele alınması gereken veri güvenilirliği, güvenlik veya ciddi işleme riski; **P2** ölçek büyümeden çözülmesi gereken doğruluk, dayanıklılık veya performans sorunu. Ölçüm yapılmadığından gecikme rakamları bulgu olarak verilmez.

### H01 — P1: Canlı kaynak hatası örnek veri üretip yayımlayabiliyor

**Kanıt:** `app/Services/Diaspora/DiasporaSyncEngine.php:193–199` API sonucunu yalnız boş değilse kabul ediyor. Boş sonuç, anahtar yokluğu ve hata aynı şekilde `:201–327` sabit örnek havuzuna veya `:329–341` kullanıcı adının MD5 değerinden üretilen yapay Reel bağlantısına düşüyor. Bu kayıtlara sabit izlenme ve beğeni değerleri veriliyor. `:81–112` otopilot açıkken bu içerikleri yayımlıyor. `app/Console/Commands/SyncDiasporaReels.php:33–34` hiç hesap yoksa otomatik örnek hesap ekliyor; aynı dosyanın `:61–62` ve diğer örnekleri doğrulanmış/otopilot açık oluşturuyor. RapidAPI yanıtında eksik metrikler de `DiasporaSyncEngine.php:383–384` üzerinden `1000` görüntülenme / `100` beğeniye dönüşüyor.

**Sonuç:** Kaynak kesintisi, hiç gönderi bulunmaması veya yapılandırılmamış entegrasyon, başarılı tarama ve gerçek etkileşim olarak sunulabiliyor. Yeni bir hesap gerçek içerik sağlamadan aktif vitrine girebiliyor. Bu sonuç yalnız birkaç ülkeye özgü değil; genel hesap fallback'i her yeni kullanıcı adına uygulanıyor.

**Düzeltme:** Üretim import akışından örnek üretimini çıkar. Kaynak yanıtını `success`, `empty`, `rate_limited`, `failed` olarak ayır. Örnek veriler yalnız açık demo komutunda, demo işaretiyle ve üretim yayınından ayrı tutulmalı. Eksik metrik `unknown/null` veya açıkça ölçülmedi durumu olmalı. Başarı tarihi yalnız gerçek başarılı kaynak yanıtında güncellenmeli. Hesap doğrulaması seed tarafından gerçek doğrulama gibi işaretlenmemeli.

### H02 — P1: Metadata çıkarıcı kullanıcı URL'siyle sunucudan keyfî hedefe istek yapıyor

**Kanıt:** `app/Filament/Resources/DiasporaReels/DiasporaReelResource.php:76–107` URL için yalnız zorunluluk/uzunluk ve aksiyonda boşluk denetimi kullanıyor. `app/Services/Diaspora/InstagramMetadataExtractor.php:39–40` shortcode çıkarılamazsa verilen URL'yi olduğu gibi tutuyor. `:129–137` fallback bu URL'ye HTTP isteği gönderiyor; bu yolda alan adı/IP/yönlendirme sınırlaması görünmüyor.

**Sonuç:** Formun yöneticiye açık olması etki alanını sınırlar; buna rağmen yöneticiye yapıştırılan bir bağlantı sunucuyu yerel ağ veya beklenmeyen dış hedefe yöneltebilir. Bu, kaynak kodda doğrulanmış SSRF yüzeyidir; canlı ağ erişimi veya veri sızıntısı denenmedi.

**Düzeltme:** İsteği yalnız geçerli shortcode'dan kurulan sabit `https://www.instagram.com/reel/{shortcode}/` hedefiyle yap. Shortcode/host doğrulanamadığında ağ çağrısı yapmadan hata dön. Kaynak URL doğrulamasını sadece formda değil servis sınırında uygula. Yönlendirmeyi kapat veya her adımda aynı güvenilir hedef politikasını uygula. Bu kontrol için özel IP'lere veya gerçek servis uçlarına ağ testi yapılmasına gerek yok; HTTP fake ile “hiç istek gönderilmedi” doğrulanabilir.

### H03 — P1: Tüm hesapları tarama ve sıralama web isteği içinde çalışıyor

**Kanıt:** `app/Filament/Resources/DiasporaAccounts/Pages/ListDiasporaAccounts.php:26–27`, `app/Filament/Resources/DiasporaReels/Pages/ListDiasporaReels.php:43–44` doğrudan `syncAll()` çağırıyor. Tek hesap aksiyonu `app/Filament/Resources/DiasporaAccounts/DiasporaAccountResource.php:200–201` içinde doğrudan çalışıyor. `DiasporaSyncEngine.php:137–144` bütün aktif hesapları belleğe alıp sırayla işliyor. Kaynak isteği `:364` en fazla 12 saniyelik timeout kullanıyor; eksik metinlerde extractor ve gerekirse AI ayrıca devreye giriyor. Sıralama aksiyonu `ListDiasporaReels.php:58–59` aynı web isteği içinde tam yeniden sıralama yapıyor.

**Sonuç:** Hesap sayısı ve kaynak gecikmesi arttıkça yönetici aksiyonu açık kalır, PHP worker meşgul olur; istek timeout'una uğrama ve sonucun belirsiz kalması riski vardır. Birden fazla yöneticinin aynı anda taraması bu yükü çoğaltır. Bu bir SQL deadlock iddiası değildir; dış I/O ve tam arşiv işinin istek işleyicisini meşgul etmesidir.

**Düzeltme:** UI yalnız tarama kaydı ve hesap başına kuyruk işi oluştursun. Hesapları `lazyById`/`chunkById` ile dağıt. Ayrı `diaspora-sync` kuyruğu için gerçek worker tanımla; yalnız kuyruğun adını vermek yeterli değildir. Yöneticiye iş kimliği, bekliyor/çalışıyor/tamamlandı/hata durumu ve gerçek son başarılı tarama zamanı göster. Aynı hesaba yinelenen tetiklemeler tek etkin işte birleştirilsin.

### H04 — P2: Retry, oran sınırı ve başarısızlık izolasyonu yok; diaspora zamanlaması kayıtlı değil

**Kanıt:** `DiasporaSyncEngine.php:359–397` timeout uygular, ancak 429/5xx ve bağlantı hatalarında retry/backoff/`Retry-After` işleme yok; sonuç boş listeye düşer. `InstagramMetadataExtractor.php:97–121,129–171` için de bu ayrım yoktur. `DiasporaSyncEngine.php:143–147` hesapları istisna sınırı olmadan sırayla işler. `SyncDiasporaReels.php:37–49` hata sayaçları görmeden başarı çıktısı üretir. `routes/console.php:1–93` içinde `diaspora:sync` planı yoktur; ilgili komut imzası `SyncDiasporaReels.php:17` içinde bulunur.

**Sonuç:** Geçici kaynak hatası güvenilir biçimde yeniden denenmez; bir hesapta veri/AI istisnası kalan hesapları kesebilir. Depoda periyodik diaspora taraması tanımlı değildir. Haricî cron bulunup bulunmadığı canlı sunucuda kontrol edilmedi.

**Düzeltme:** Hesap başına bağımsız iş; sağlayıcı başına paylaşılan rate-limit; sınırlı exponential backoff + jitter; `Retry-After` uyumu; 4xx kalıcı hatalar için ayrı durum; `retryUntil` ve son hata kaydı kullan. Scheduler yalnız iş dağıtsın; `withoutOverlapping`, çok sunucuda paylaşılan cache ile `onOneServer` uygula. UI, scheduler ve CLI için aynı hesap kilidini kullan; scheduler kilidi tek başına manuel tetiklemeyi kapsamaz. Senkron komut sürerse hata sayısına göre anlamlı exit code dönsün; kuyruk sürümünde komut başarısı “işler kuyruğa alındı” anlamında sunulsun.

### H05 — P2: Shortcode tekrarsızlığı eşzamanlı çalışmada korunmuyor

**Kanıt:** `DiasporaSyncEngine.php:46` `exists()` kontrolünden sonra `:92` bağımsız `create()` çağırır. `database/migrations/2026_09_13_190000_create_diaspora_reels_table.php:21` shortcode için yalnız normal index tanımlar. İncelenen sonraki diaspora migration'ında unique kısıtı yoktur. `tests/Feature/DiasporaSyncTest.php:77–95` aynı işlemi sırayla iki kez çalıştırır; eşzamanlı yarış testi değildir.

**Sonuç:** İki tarama aynı shortcode için “yok” okuyup iki kayıt oluşturabilir. Uygulama düzeyindeki ön sorgu veritabanı tekrarsızlığı sağlamaz.

**Düzeltme:** Var olan tekrarları raporla, hangi kaydın korunacağını belirle; ardından canonical shortcode veya `(platform, external_id)` üzerinde veritabanı unique kısıtı ekle. Yazmayı bu kısıta göre idempotent yap; beklenen unique çakışmasını güvenli şekilde ele al. Kilit süre aşımı ve manuel tekrarlar için veritabanı kısıtı son koruma olmaya devam etsin. Duplicate temizliği kullanıcı içeriklerini gelişigüzel silmemeli.

### H06 — P2: Listing ve JobListing satır callback'lerinde N+1 sorgu yolları var

**Kanıt:** `app/Filament/Resources/Listings/Tables/ListingsTable.php:52` satır başına `user`; `:62` `category.parent`; `:92–93` `country` okur. Kaynağın ve tablonun incelenen sorgu kurulumunda bu callback ilişkilerini önceden yükleyen `with()` bulunmuyor. `app/Filament/Resources/JobListings/Tables/JobListingsTable.php:49,71–73` aynı şekilde `category` ve `country` okur. `:241` her satırdaki aksiyon görünürlüğü için `applications()->count()` çalıştırır; oysa aynı tabloda `:94–96` `counts('applications')` zaten vardır.

**Sonuç:** Özellikle farklı satıcı/ülke/kategorilerle sayfa büyüdükçe ilişki sorguları artar. Başvuru aksiyonu, yüklenmiş `applications_count` değerini kullanmayarak her satırda tekrar sorgular. Bu kod yolunun riski doğrulanmıştır; toplam sorgu sayısı henüz ölçülmedi.

**Düzeltme:** Listing sorgusuna `user`, `country`, `category.parent`; JobListing sorgusuna `category`, `country` eager-load ekle. Filament'in zaten yüklediği görsel ve şirket ilişkilerini koru. Başvuru aksiyonunu yüklenmiş `applications_count > 0` değeriyle göster. Yalnız gerekli alanlar seçilecekse bütün ilişki anahtarlarını da seç.

**Yanlış pozitif sınırı:** Filament, noktalı ilişki sütunlarını otomatik eager-load eder (`vendor/filament/tables/src/Columns/Concerns/InteractsWithTableQuery.php:41–53`). Dolayısıyla `coverImage.path_thumb`, `company.logo_path`, `country.name_tr` sütunları tek başına N+1 bulgusu değildir. Diaspora Reel/Account ülke sütunları bu mekanizmayı kullanır. `UserResource` tablosunda incelenen düz alanlar için kanıtlanmış ilişki N+1 bulgusu yoktur.

### H07 — P2: Widget'lar sık ve önbelleksiz toplu sorgu yapıyor; hareket grafiği tüm kayıtları belleğe alıyor

**Kanıt:** `app/Filament/Resources/Listings/Widgets/ListingsStatsWidget.php:20,24–31` eager render ve yedi ayrı aggregate; `app/Filament/Resources/JobListings/Widgets/JobListingStatsWidget.php:20,24–30` beş aggregate; `app/Filament/Resources/DiasporaReels/Widgets/DiasporaReelsStatsWidget.php:16,20–23` dört aggregate içerir. Sınıflar `StatsOverviewWidget` kullanır ve polling'i değiştirmez; mevcut vendor varsayılanı `vendor/filament/widgets/src/Concerns/CanPoll.php:7` içinde `5s`'dir. `app/Filament/Widgets/IlanHareketleriWidget.php:39–50` son sekiz aylık ilanları model koleksiyonu olarak alıp PHP'de gruplar.

**Sonuç:** Çok sayıda açık yönetim oturumunda aynı toplamlar tekrar hesaplanır. Hareket grafiğinin yalnız iki sorgu yapması, bellek/taşınan satır miktarını sınırlamaz; maliyet sekiz aylık kayıt hacmiyle artar. `isLazy=false` ilk render yüküne bu işleri dahil eder.

**Düzeltme:** Aynı tabloya ait sayaçları koşullu aggregate ile birleştir. Geo-context + yetki kapsamı + metrik sürümü içeren cache anahtarları ve 30–60 saniyelik tazelik hedefi kullan. Veri değişince ilgili anahtarları geçersiz kıl. Grafik için veritabanı aggregate veya günlük ülke/şehir özet tablosu kullan. Widget polling'ini işin gereğine göre 30–60 saniyeye çek; yoğun raporlar kullanıcı isteğiyle yenilensin. Hiç verisi olmayan ülke satırları Country ana tablosundan LEFT JOIN ile korunmalı.

### H08 — P2: Sıralama bütün arşivi iki kez yükler; öne çıkarmayı kaldırma kuralı hatalıdır

**Kanıt:** `app/Services/Diaspora/DiasporaRankingEngine.php:25–36,40–62` tüm aktif yayımlanmış Reel kayıtlarını iki ayrı koleksiyona alır ve her geçişte kayıt başına kaydeder. `:52` ilk iki kaydı, skor `>80` ise öne çıkarır; `:57` kaldırmayı yalnız `$index >= 3` olduğunda yapar. Böylece önceden öne çıkmış üçüncü kayıt (`index=2`) kaldırılmaz; ilk iki sırada kalıp skoru 80 altına inenler de kaldırılmaz.

**Sonuç:** Büyük arşivde bellek ve yazma maliyeti büyür; “ilk iki içerik” kuralı mevcut bayrakları doğru temizlemez. Eşzamanlı sıralamada tutarlı tek sonuç garantisi yoktur. Bu işlem SQL deadlock olarak ölçülmemiştir.

**Düzeltme:** Puan hesaplamasını sınırlı gruplara böl; yalnız değişen alanları yaz. Final sıralamayı deterministik bağlayıcı anahtarla üret ve kısa atomik yayın adımında etkinleştir. `is_featured` değerini her kayıt için belirlenen kurala göre açıkça ata; manuel editoryal öne çıkarma varsa ayrı alanla ifade et. İlk iki sınırı global mi ülke lensi başına mı uygulanıyor ürün kuralında net olsun.

### H09 — P2: Engagement ve içerik güvenlik puanları gösterilen içeriğin güncelliğini tam temsil etmiyor

**Kanıt:** `DiasporaSyncEngine.php:46–50` var olan shortcode'u metrik güncellemeden atlıyor. `DiasporaRankingEngine.php:82–87` kaynak yayın zamanı yerine yerel kaydın `created_at` zamanını güncellik bonusunda kullanıyor. `app/Services/Diaspora/DiasporaIntelligenceService.php:189–227` güvenlik skorunu sınırlı anahtar kelime kurallarıyla hesaplıyor; `:258` bu değerlendirme yapılırken `:277–289` daha sonra AI ile başlık/açıklama değiştirilebiliyor; nihai metin tekrar taranmıyor.

**Sonuç:** Tekrar tarama metrikleri tazelemez; eski Instagram paylaşımının yeni import edilmesi “yeni içerik” bonusu yaratır. Rozet AI güvenlik değerlendirmesi olarak sunulursa mevcut implementasyonun kapasitesi olduğundan fazla anlaşılır. Üretilen nihai metin önceki metnin güvenlik sonucunu taşıyabilir.

**Düzeltme:** `source_published_at`, `metrics_observed_at`, `content_hash`, `scorer_version` ve `scored_at` sakla. Var olan içerikte sağlayıcının gerçekten verdiği metrikleri güncelle. Nihai yayımlanacak metni denetle; kural puanı ile AI puanını kaynak adı ve sürümüyle ayır. Kaynak veya denetim eksikse “değerlendirilmedi” durumu kullan; bunu otomatik 100 puana çevirmeyin.

### H10 — P2: Kuyruk gözlem hatası “kuyruk boş” olarak görünüyor

**Kanıt:** `app/Filament/Widgets/SystemHealthWidget.php:197–198` Redis'te sabit `queues:default` listesini okur; bağlantının/queue adının config değerlerini kullanmaz. `:200–204` hata veya desteklenmeyen sürücüde `0` döner. `:246–249` sıfırı yeşil “kuyruk boş” olarak sunar. Redis hazır listesinin uzunluğu tek başına bekletilmiş ve işlenmekte olan işleri göstermez. Aynı widget `:178–180` sabit `sys_health.txt` dosyasını yazıp kontrol edip siler.

**Sonuç:** Redis bağlantısı/queue adı değiştiğinde veya gözlem isteği başarısız olduğunda yanlış olumlu sağlık sonucu üretilebilir. Birden çok açık panelde sabit test dosyası kontrolleri birbirinin dosyasını silerek yanlış storage alarmı oluşturabilir. Bu durumlar canlıda denenmedi.

**Düzeltme:** Queue bağlantısı ve adını config'ten çöz; hazır, gecikmeli, işlenmekte, başarısız ve en eski bekleme ölçümlerini ayrı göster. Gözlem hatasında `unknown/error` üret; sıfır olarak çevirmeyin. Sağlık probunu kısa süre cache'le; storage için benzersiz prob anahtarı veya ayrılmış sağlık servisi kullan. Cache arızasında sağlık kartının kendi gözlem temizliğinin de render'ı bozmamasını test et.

### H11 — P2: Coğrafi sezgiler ve sekmeler iki/sekiz ülke merkezli kalıyor

**Kanıt:** `app/Filament/Resources/DiasporaReels/Pages/ListDiasporaReels.php:189–199` Almanya, Kırgızistan ve diğerleri biçiminde sabit sekmeler üretir. `app/Services/Diaspora/DiasporaIntelligenceService.php:25–93,118–140` sınırlı şehir/ülke eşleştirme listesi taşır; `:251` ülke tespit edilemezse `DE` kullanır. `DiasporaSyncEngine.php:189–190` bilinmeyen ülke/şehir için `DE/Berlin` varsayılanı içerir; AI hızlı ekleme aksiyonları da `ListDiasporaReels.php:105–107` üzerinden `DE`'ye düşebilir.

**Sonuç:** Veritabanı ülkeleri genişlemeye açık olsa da bilinmeyen veya yeni pazarlardaki içerik yanlış ülkeye atanabilir. Global lens, pazar likiditesi ve cold-start skorları bu varsayımlarla güvenilir olamaz.

**Düzeltme:** Ülke/şehir lookup'ını Country/City ve sürümlenmiş alias verisine bağla. Bilinmeyeni `null/atanmamış` tut; coğrafya tahmininin confidence/source alanlarını sakla. Ülke sekmelerini dinamik lensle değiştir. Bölgeleri ülke kodu dizileriyle PHP'ye gömmek yerine doğrulanmış, yönetilebilir bölge üyelikleri olarak tut. Kullanıcının açık seçimi metinsel tahminden öncelikli olmalı.

## 3. Korunması gereken mevcut önlemler

- **Kaynak yetkisi var:** User, DiasporaAccount ve DiasporaReel kaynakları `RestrictsToAdmins` kullanır. `app/Filament/Concerns/RestrictsToAdmins.php:24–26` yönetici kontrolünü kaynak sınırında uygular. Bu rapor genel erişim bypass'ı iddia etmez.
- **Filament ilişki optimizasyonu var:** Noktalı sütun ilişkileri mevcut vendor tarafından eager-load edilir. Tüm ilişki erişimlerini topluca N+1 diye etiketlemek doğru değildir.
- **Temel timeout ve gözlem var:** Metadata çağrılarında 4/5 saniye, RapidAPI'de 12 saniye timeout ve istisna loglama bulunur. Eksik olan; timeout'un varlığı değil, hata sınıflandırma/retry ve işlemlerin UI'dan ayrılmasıdır.
- **Şema bazı bütünlük önlemleri içerir:** Account username unique; ülke foreign key'leri, Account foreign key'i ve bazı durum/ülke index'leri mevcuttur. Shortcode'un normal index olması unique koruması değildir.
- **Kuyruk timeout değişmezi belgelenmiş ve testlenmiş:** `config/queue.php:47,77` database/Redis için varsayılan `retry_after=180`; `deploy/supervisor-nisoya-worker.conf:13` worker `timeout=120`, `tries=3` tanımlar. `tests/Unit/KuyrukDegismeziTest.php:35–60` ana worker şablonunu korur. Canlı env değerleri ve çalışan süreçler ayrıca kontrol edilmelidir; yerel şablon canlı yapılandırmanın kanıtı değildir.
- **Diğer zamanlanmış işler kilit kullanır:** `routes/console.php` mevcut işlerde `withoutOverlapping` uygular. Diaspora için plan henüz yoktur. Dosyanın `:92` satırındaki database worker `tries=1` kullanır; yeni diaspora işleri bu varsayılanın tesadüfi davranışına bırakılmamalı, iş düzeyindeki retry ve ayrılmış worker açıkça belirlenmelidir.
- **İşlev testleri mevcut:** Diaspora taslak/yayın, sıralama, seri duplicate ve panel erişim testleri var. Bu audit sırasında çalıştırılmadı; üretim verisine veya haricî kaynaklara dokunulmadı. `DiasporaIntelligenceTest.php:25–33` extractor testi ağ yanıtını bu test içinde fake etmez; test paketi tekrarlanabilir ağ fake'leriyle güçlendirilmelidir.

## 4. Faz 1 uygulama sırası

1. **Önce doğruluk:** Üretim sync yolundan örnek/fabrikasyon fallback'lerini çıkar; sonuç tiplerini ve bilinmeyen metrikleri tanımla. Var olan olası örnek kayıtlar için salt okunur tespit raporu üret; otomatik silme yapma.
2. **Servis sınırını kapat:** Metadata URL doğrulaması ve yönlendirme politikasını servis içinde uygula; geçersiz URL'de ağ çağrısının hiç çıkmadığını test et.
3. **Idempotency ve izlenebilirlik:** Kaynak kimliği, tarama kaydı, hata durumu ve son başarı tarihi ekle. Duplicate veriyi incelemeden unique migration çalıştırma.
4. **Kuyruklaştır:** Hesap başına iş, sağlayıcı rate-limit, backoff, hesap kilidi, kuyruk işleyicisi ve scheduler kurulumunu birlikte tamamla. UI yalnız iş başlatsın ve durumu izlesin.
5. **Sorgu maliyetini azalt:** Callback ilişki yüklemeleri, başvuru sayacı, aggregate widget cache'i ve veritabanında grafik gruplamayı düzelt.
6. **Skor ve sıralamayı düzelt:** Nihai metni puanla; kaynak yayın ve metrik gözlem zamanını sakla; deterministik sıralama ve öne çıkarma kuralını uygula.
7. **Geo-context temeline bağla:** Ülke varsayılanlarını kaldır; widget/table/chart sorgularının aynı doğrulanmış bağlamı kullandığını test et. Cache anahtarlarında kullanıcı yetkisi ve lens ayrımı zorunludur.
8. **Staging kabulünden sonra aç:** Kısıtlı eşzamanlılıkla canary tarama; gerçek hata oranı, kuyruk bekleme ve panel render ölçümleri; feature flag ile genişletme. Geri alma, yeni iş dağıtımını durdurup eski panel görünümüne dönebilmeli.

## 5. Ölçüm ve kabul tablosu

Bu değerler mevcut ölçümler değil, önerilen başlangıç kabul eşikleridir. Aynı staging donanımı, aynı fixture, aynı cache koşulları ve sabit eşzamanlılıkla ölçülmeli; sonuç raporunda ortam mutlaka yazılmalıdır.

| Alan | Ölçüm / senaryo | Kabul ölçütü |
|---|---|---|
| Kaynak yokluğu | Anahtar yok, boş 200, 429, 500, timeout | Üretim modunda sıfır uydurma Reel/metrik; her yanıt doğru sonuç durumu. |
| SSRF sınırı | Özel IP, localhost, farklı host, kullanıcı bilgisi içeren URL, hedef dışı redirect | İzin verilmeyen hedefe sıfır HTTP isteği; güvenli kullanıcı hatası. |
| Idempotency | Aynı hesabı/shortcode'u iki eşzamanlı iş ve tekrar teslimle işle | Tek kalıcı kaynak kaydı; ikinci teslim güvenli sonuç; tarama sayaçları doğru. |
| Rate limit | HTTP fake ile 429 + `Retry-After`; kalıcı 4xx; ardışık 5xx | Planlanan gecikmeye uyum; sınırlı deneme; başka hesapların işlenmesine devam. |
| UI yanıtı | 1, 50, 500 hesabı tarama isteği | UI request içinde kaynak API çağrısı yok; iş kabulü p95 ≤ 1 saniye hedefi. |
| N+1 | Farklı satıcı/ülke/kategorilerden 10 ve 50 satırlı Listing/JobListing sayfası | İlişki sorgu sayısı satır sayısıyla büyümez; satır başına application count sorgusu yok. |
| Widget cache | Aynı lensle eşzamanlı dashboard yenilemeleri | Sıcak cache'de sayaç aggregate tekrarları yok; tazelik ≤ 60 saniye hedefi. |
| Lens izolasyonu | Global, bölge, ülke, şehir; iki farklı yetki kapsamı | Aynı filtre bütün widget/table/chart'larda; farklı kapsamların cache sonuçları karışmaz. |
| Sıfır verili ülke | Aktif ama ilan/hesap/kullanıcı sayısı sıfır olan ülke | Country tabanlı sonuçta görünür; Cold-Start; yanlış `DE` ataması yok. |
| Sıralama | Önceden featured üçüncü kayıt; eşik altına düşen ilk iki; eşit skor | Belirlenen featured kuralı birebir; bağlayıcı anahtarla aynı deterministik sıra. |
| Güvenlik puanı | AI sonradan başlığı/açıklamayı değiştirir; AI hata verir | Nihai metin hash'i ile eşleşen skor; hata durumunda “değerlendirilmedi”; fail-open yayın yok. |
| Kuyruk sağlığı | Redis bağlantı hatası, farklı queue adı, gecikmeli/işlenmekte olan iş | Hata “boş/başarılı” görünmez; her queue durumu ayrı doğru gözlem. |
| Mobil panel | 360 px telefon ve 768 px tablet; uzun Türkçe başlıklar; dark/light | Sayfa düzeyinde yatay taşma yok; lens ve temel aksiyonlar erişilebilir; görsel test gerekir. |

SQL deadlock, gerçek sorgu gecikmesi, sunucu kapasitesi ve mobil yatay taşma bu salt okunur kaynak incelemesinde ölçülmedi. Bu alanlarda “yoktur” veya “çözüldü” sonucu verilmez; yukarıdaki kabul kontrolleriyle doğrulanır.

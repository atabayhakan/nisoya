# Yerel uygulama teslimi — 15 Eylül 2026

Küresel Operasyon Merkezi mevcut Laravel 13.31.0 / Filament 5.8.1 / Livewire 4.4.4 uygulamasına bağlandı. Sürüm düşürülmedi. Bu kayıt güncel uygulamayı anlatır; `reference/` ve `filament-v3/` ilk mimari teslimin arşividir.

## Kullanım

- Yönetim paneli → **Pazarlama & Büyüme → Küresel Operasyon Merkezi** (`/yonetim/global-command-center`).
- Üst çubuktaki **Görünümü değiştir** ile dünya, bölge, ülke veya şehir seçilir. Uygulama sonrası operasyon merkezi açılır; açık form önce kaydedilmelidir.
- **Pazarlama & Büyüme → Şehir Adları** (`/yonetim/sehir-adlari`) farklı dildeki şehir adlarını katalogdaki şehre bağlar. Büyük/küçük harf, boşluk ve Türkçe karakter farkları otomatik normalize edilir. Aynı ülkede başka şehre bağlı ad reddedilir; belirsiz katalog adları şehir görünümüne dahil edilmez.
- Operasyon merkezindeki **Görevi yönet** ile aktif bir yönetici atanır; görev önerildi, çalışılıyor, tamamlandı veya ertelendi durumuna alınır. Çalışılıyor/tamamlandı için sorumlu, tamamlandı için sonuç notu gerekir. **Yalnız benim görevlerim**, durum filtresi ve sayfalama bulunur. Yeniden açılan görevin önceki notu işlem kaydında korunur.
- **İçerik & Tasarım → Diaspora Medya Radarı** (`/yonetim/diaspora-medya-radari`) video kartları, kaynak önizlemesi, ölçülmüş etkileşimler ve hesap doğrulamasını sunar.
- İlan tablosundaki **Kalite İncelemesi** sonucu operasyon merkezine gelir.
- İncelemeler başlık ve işlem durumuyla aranır; sayfalama eski sonuçlara erişim sağlar. **İçeriği aç** kaynağa gider. Başarısız, iptal edilmiş veya kaynağı değişmiş incelemelerde **Yeniden incele** güncel içerik için talep oluşturur; çift tıklama aynı işi çoğaltmaz.
- Kâhya: “Bu ay Almanya ve Kırgızistan'daki kiralık ev ilanlarını karşılaştır” veya “Fransa'daki onay bekleyen iş ilanlarını incele”. Karşılaştırma para birimi ve fiyat dönemini ayrı tutar; beşten az fiyat örneğinde ortalama göstermez. İnceleme içeride kuyruğa alınır, sonuç gelmeden tamamlanmış sayılmaz.

## Uygulanan kapsam

| Alan | Sonuç / ana dosyalar |
|---|---|
| Coğrafi bağlam | `GeoContext`, `ResolveAdminGeoContext`, `GeoSwitcher`; kullanıcıya bağlı session, her Livewire isteğinde çözümleme, geçersiz seçimde 409 |
| Panel entegrasyonu | İlan, iş ilanı, kullanıcı, diaspora hesap/video kaynakları; ilgili istatistikler ve ilan grafikleri aynı lensi kullanır. Eski sekmelerden işlem 409 ile durur. Kamu modellerine global scope eklenmedi. |
| Dünya kataloğu | 249 ISO 3166-1 ülke/bölge kodu, 30 coğrafi grup, 153 para birimi ve 203 dil kaydı. CLDR eşlemeleri ve kaynak SHA-256 değerleri `database/data/global-geo/catalog.json` içinde; lisanslar aynı klasörde. Mevcut adlar, aktiflik ve XN gibi yerel uzantılar korunur. |
| Pazar radarı | `CountryLiquidity` ülke sayısından bağımsız gruplanmış sorgular ve 60 saniyelik önbellek kullanır. İlanı olmayan ülke kalır. Puan platform arzını gösterir; işlem likiditesi iddiası taşımaz. |
| Yeni pazarlar | `PlanColdStart` haftalık tekrarsız iç öneriler üretir. Yerelde 250 öneri oluştu. Davet, e-posta veya sosyal mesaj göndermez. |
| Görev takibi | `GlobalGrowthTask`, `GrowthTaskWorkflow`: sorumlu, durum, sonuç notu, tamamlayan yönetici ve tarih; transaction içinde işlem kaydı. Eski sürümle kaydetme 409 ile reddedilir. Önceki haftadan açık görev varsa aynı ülke/aksiyon için tekrar öneri üretilmez. |
| Şehir adları | `CityName`, `CityNames`, `CityNameAlias`: ülkeyle sınırlanan normalize anahtarlar, yönetilebilir ek adlar, ad değişiminde eski adın korunması; alias değişiminde bağlam/önbellek anahtarı değişir. Kaynak modellerin şehir ataması anahtarı birlikte günceller. |
| Medya taraması | `DiasporaDispatch`, `SyncDiasporaAccount`, `StrictDiasporaSync`: aktif/doğrulanmış hesap, kuyruk, ortak kilit, dakika başına sınır, tekrar deneme, benzersiz kaynak kimliği, yalnız taslak. Sağlayıcı hatasında örnek içerik/uydurulmuş metrik yok. |
| URL kontrolü | `RadarMediaUrl` ve `InstagramMetadataExtractor`: Instagram URL doğrulaması, yönlendirmesiz ve süre sınırına bağlı istekler, dar önizleme/kapak URL kuralları |
| Kalite / AI | `ContentQuality`, `AssessContent`, `ContentAssessment`: kaynak özeti + sürüm, tekrarsız talep, yetki geri kontrolü, eski kaynak sonucunu reddetme, AI çıktı şeması ve kaynakta geçen kanıt kontrolü; yayın durumu değiştirilmez. Kural puanı ile AI riski ayrıdır. |
| Kâhya | `GlobalOperasyon` gerçek veriden ülke karşılaştırması ve bekleyen işlerin inceleme taleplerini üretir. Sohbet geçmişi yöneticiye ayrıldı; eylem uygulama ve audit yazımı aynı kilitli transaction içinde. |
| Performans / görünüm | İlişkiler toplu yüklenir, grafikler SQL ile gruplanır, senkron tarama ve sıralama kuyruğa taşındı. Yeni sayfalarda duyarlı kartlar, karanlık/açık tema, 44px dokunma hedefleri ve azaltılmış hareket desteği vardır. EXIF widgetının panoda fazladan keşfedilmesi düzeltildi. |

## Yerelde gerçekleştirilen kurulum

1. Ortamın yerel SQLite olduğu doğrulandı; `storage/app/global-catalog-build/` altında tutarlı veritabanı yedeği oluşturuldu.
2. Dört yeni migration başarıyla uygulandı. Önceki migrationların tamamı zaten uygulanmıştı; hiçbir tablo sıfırlanmadı veya kayıt silinmedi.
3. `global-command:import-geo` çalıştı: 249 standart kod + mevcut XN = 250 ülke kaydı.
4. `global-command:plan-cold-start` çalıştı: 250 iç öneri.
5. Ön yüz üretim derlemesi tamamlandı. Canlı sunucuya dağıtım yapılmadı.
6. Devam geliştirmesinde yeni yedek alındı ve `2026_09_15_120000_add_quality_scan_progress` migrationı uygulandı. `QualityScanner` için üç kalıcı ilerleme kaydı oluştu; 16 yerel içerik kontrol edilip kuyrukta işlendi. Tamamı `rules_only` durumuna ulaştı. Ücretli AI ve canlı diaspora taraması kapalı kaldı.
7. Yeni yedek ardından şehir adları ve büyüme görevi migrationları uygulandı; toplam yedi yeni migration yerelde kurulu. 109 katalog şehri için ad kaydı oluştu. Altı kaynak tabloda şehir bilgisi bulunan kayıtların tamamına normalize anahtar işlendi; mevcut 250 öneri korundu.

## Doğrulama

- **185 test geçti, 690 doğrulama**, SQLite bellek veritabanı; son toplu koşu 85,2 saniye. Görsel örnek dışa aktarımı ayrıca 1 test / 4 doğrulamayla geçti.
- Şehir eşleştirmeleri, aynı ülke içindeki belirsizlik, ad değişimi, yönetici erişimi; görev atama/tamamlama/yeniden açma, zorunlu sonuç notu, eski sürümü reddetme, ülke sınırı, modal form ve açık görevin tekrar üretilmemesi doğrulandı. Mevcut üyelik, ilan ve profil testleri de toplu koşuya dahil edildi.
- Operasyon merkezi, şehir adları ve medya radarı gerçek sunucu şablonlarından test verileriyle tarayıcıda görüntülendi. 390px telefon ve 768px tablet genişliklerinde açık/koyu temada yatay taşma ölçülmedi. Uzun başlık ve tamamlanma notu örnekleri kullanıldı; 44px form/düğme hedefleri güncellendi. Önizlemede uygulama JavaScript'i kapalıdır; bu kontrol oturumlu uçtan uca tarayıcı testi değildir.
- Kalite iyileştirmesi: çok partili tarama, tur sırasında yeni kayıt ve eski kaydın değişmesi, hata sonrası ilerlemenin geri alınması, tekrar talebi, gecikmiş eski iş/sağlayıcı sonucu, çağrı sırasında kaldırılan yönetici yetkisi, sayfalama/arama/ülke sınırı için 9 ek test.
- Kapsam: yeni coğrafya/kalite entegrasyonu, diaspora tarama ve zeka, mevcut diaspora CRUD, tüm yönetim kaynaklarının açılması, panel sıralaması, giriş yetkileri, Kâhya sohbet/eylem/balon/panel ve kuyruk zaman aşımı değişmezi.
- Kaynak değişimi, yetkisi kaldırılan yönetici, diğer sekmede lens değişimi, yöneticiye özel sohbet geçmişi, uydurma AI kanıtı, boş API sonucu, sağlayıcı hatası, sıfır hacimli ülke, idempotent katalog ve büyüme görevleri doğrulandı.
- `npm run build`, değişen PHP dosyalarında Laravel Pint ve `git diff --check` başarılı.
- HTTP ve AI davranış testleri sahte sağlayıcılarla çalıştı; canlı sağlayıcı başarısı iddia edilmez. Tüm depo test paketi çalıştırılmadı.

```powershell
php vendor/bin/phpunit tests/Feature/GlobalQualityWorkflowTest.php tests/Feature/GlobalCommandTest.php tests/Feature/GlobalCommandIntegrationTest.php tests/Feature/DiasporaSyncTest.php tests/Feature/DiasporaIntelligenceTest.php tests/Feature/DiasporaReelsTest.php tests/Feature/PanoSiralamasiTest.php tests/Feature/AdminPanelTest.php tests/Feature/YonetimGirisiTest.php tests/Feature/KahyaSohbetTest.php tests/Feature/KahyaEylemTest.php tests/Feature/KahyaBalonuTest.php tests/Feature/KahyaPaneliTest.php tests/Unit/KuyrukDegismeziTest.php tests/Feature/CityAliasesTest.php tests/Feature/CityTest.php tests/Feature/GrowthTaskWorkflowTest.php tests/Feature/AuthTest.php tests/Feature/ListingTest.php tests/Feature/ProfileSettingsTest.php
npm run build
```

## Başka ortama dağıtım sırası

1. Veritabanı yedeğini alın. Tekrarlanan `shortcode` varsa önce inceleyin; migration bilinçli olarak durur, kendiliğinden silmez.
2. Yeni dosyalarla birlikte önce migrationları çalıştırın, sonra web trafiğine açın:

```sh
php artisan migrate --force
php artisan global-command:import-geo --dry-run
php artisan global-command:import-geo
php artisan global-command:plan-cold-start
npm run build
php artisan config:cache
php artisan queue:restart
```

3. Zamanlayıcının her dakika çalıştığını doğrulayın. Eklenen görevler: UTC 00:10 pazar önerileri, her beş dakikada kalıcı ilerlemeli içerik taraması, koşullu saatlik diaspora taraması. Zamanlayıcı adlandırılmış kuyrukları her dakika sınırlı süreyle işler. Kalıcı işçi için `deploy/supervisor-nisoya-worker.conf` içindeki `nisoya-global-command` kullanılabilir; bağlantı ayarı uygulamayla aynı olmalıdır. İşçi timeout 120 saniye, queue retry_after 180 saniyedir. `--max-time` çalışan işi yarıda kesmez.
4. Varsayılan ayarlar:

```dotenv
GLOBAL_COMMAND_ENABLED=true
GLOBAL_COMMAND_DIASPORA_SYNC_ENABLED=false
GLOBAL_COMMAND_AI_ASSESSMENT_ENABLED=false
GLOBAL_COMMAND_DIASPORA_QUEUE=database
GLOBAL_COMMAND_DIASPORA_CACHE=database
GLOBAL_COMMAND_SCHEDULER_ACTOR_ID=
```

5. Diaspora taramasını açmadan sağlayıcı sözleşmesini ve yetkili yönetici kimliğini staging'de doğrulayın. AI incelemesini açmadan mevcut AI sağlayıcısının yapılandırmasını, maliyet sınırlarını ve örnek sonuçlarını kontrol edin. Anahtarlar kaynak dosyalara yazılmaz. Kalite işi `global-command`, medya işleri `diaspora-sync` kuyruğunu kullanır.

## Bilinen sınırlar / sonraki kabul kontrolleri

- MySQL gerçek eşzamanlılık, yüksek hacim ve canlı API sözleşmesi testi yapılmadı. Tekrar denemeler ve SQL tarih gruplaması bu ortamda ayrıca doğrulanmalı.
- Mobil/tablet şablon kontrolleri tamamlandı; gerçek cihaz ve oturumlu JavaScript etkileşimlerinin uçtan uca kabulü yapılmadı. Modal davranışı Livewire testleriyle doğrulandı; tüm cihazlarda sıfır taşma garantisi verilmez.
- Şehir lensi ülkeyle sınırlanan normalize ad ve kayıtlı ek adları kullanır. Dünya şehir kataloğu/çok dilli alias aktarımı ve `city_id` dönüşümü ayrı çalışmadır. Toplu SQL veya query-builder güncellemeleri model mutatorünü çalıştırmaz: bu tür aktarımda `city_key` de `CityName::key()` ile yazılmalı, yeni katalog şehirlerinin ad kayıtları oluşturulmalıdır. Mevcut uygulama yazımları model üzerinden ilerler. 30 standart coğrafi grubun yanında özel operasyon kümeleri veri üyelikleriyle eklenebilir.
- Lens yetkilendirme değildir; bu teslim ülke bazında ayrı yönetici rolleri tanımlamaz. Panelde bağlanan kaynaklar yukarıda açıkça listelenmiştir; tüm CMS ve sistem ayarları ülkeye göre filtrelenmez.
- Kalite taraması her çalışmada içerik türü başına varsayılan 100, en fazla 500 kayıt kontrol eder. Her turun üst kimliği sabitlenir ve ilerleme veritabanına kaydedilir; yeni kayıtlar eski düzenlemeleri sürekli geriye itemez. Tur bitince başa döner, değişmeyen sürümler için tekrar iş oluşturmaz. Bir düzenleme mevcut tarama konumunun gerisindeyse sonraki turda yakalanır; gecikme katalog hacmine bağlıdır. Bekleyen iş incelemesi çağrı başına 100 kayıt ve `has_more` döndürür.
- Kalite AI'ı metin, eksik fotoğraf ve gözlenen etkileşimleri değerlendirir; video kareleri analizi veya dış piyasa fiyat doğrulaması yapmaz. Model güveni doğrulanmış olasılık değildir. Başarısız/iptal edilmiş incelemeler panelden tekrar başlatılabilir. Eski kuyruk denemesi veya gecikmiş sağlayıcı sonucu yeni denemeyi değiştiremez.
- Mevcut CMS hızlı hikaye üretimi hâlâ açık bir kullanıcı aksiyonunda senkron çalışır; yeni tarama ve kalite akışları kuyruktadır. Hızlı hikaye artık taslak kaydeder.
- Eski MCP ortak anahtarının kullanıcı kimliğine taşınması, bütün eski Kâhya eylemlerinin geri alma/red yarışlarının düzenlenmesi ve dış büyüme gönderimleri bu entegrasyonun kapsamına dahil edilmedi. İlk denetim belgesindeki her bulgu kapatılmış sayılmamalıdır.

## Geri dönüş

Yeni giriş noktalarını `GLOBAL_COMMAND_ENABLED=false` ile kapatın, tarama ve AI bayraklarını kapalı tutun; config önbelleğini yenileyip işçileri yeniden başlatın. Veri tablolarını silmeyin. Kaynak şeması eklemeleri eski iş kayıtlarını korur; tam sürüm geri dönüşünde alınan yedek ve önceki kod birlikte değerlendirilmelidir.

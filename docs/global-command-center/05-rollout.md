# Uygulama, kurulum ve dağıtım kontrol listesi

> İlk tasarım planı. Yerel entegrasyon tamamlandı; güncel uygulamaya referans dosyalarını tekrar kopyalamayın. Güncel kurulum ve kalan üretim kontrolleri [IMPLEMENTATION.md](IMPLEMENTATION.md) içindedir.

Bu belge uygulanacak işlemleri tarif eder; aşağıdaki komutlar bu çalışma sırasında üretimde çalıştırılmadı. Referans kodları uygulamaya almak ayrı bir entegrasyon değişikliğidir. `migrate:fresh`, kör duplicate silme veya bağımlılık downgrade'i bu plana dahil değildir.

## Dosya sırası

`reference/` altındaki yollar hedef uygulama köküne göredir. Var olan dosyalar aşağıdaki gibi birleştirilir; mevcut provider/theme dosyaları komple değiştirilmez.

| Sıra | Açılacak / eklenecek dosyalar | Amaç |
|---|---|---|
| 1 | `config/global-command.php` | Ana feature flag kapalı, ayrı sync flag kapalı |
| 2 | `database/migrations/2026_09_14_120000_create_global_geo_context_tables.php` | Bölge/dil/ülke pivotları ve büyüme görevleri |
| 3 | `app/Support/GlobalCommand/GeoContext.php`, `app/Http/Middleware/ResolveAdminGeoContext.php`, `app/Providers/GlobalCommandServiceProvider.php` | İstek bağlamı, sunucu doğrulama ve Livewire persistence |
| 4 | `bootstrap/providers.php` | Yeni service provider'ı mevcut diziye ekle |
| 5 | `app/Livewire/Admin/GeoSwitcher.php`, `resources/views/livewire/admin/geo-switcher.blade.php` | Ülke/bölge/şehir seçici |
| 6 | `app/Providers/Filament/AdminPanelProvider.php` | Middleware ve topbar hook'u mevcut tanıma birleştir |
| 7 | `app/Support/GlobalCommand/{CountryLiquidity,LiquidityScore}.php`, `app/Filament/Widgets/CountryLiquidityWidget.php`, `resources/views/filament/widgets/country-liquidity.blade.php` | Ülke metrikleri ve cold-start görünümü |
| 8 | `app/Filament/Pages/GlobalCommandCenter.php`, ilgili Blade | Ayrı operasyon sayfası; mevcut dashboard korunur |
| 9 | `app/Support/GlobalCommand/{RadarMediaUrl,IntelligenceScore}.php`, rozet Blade, `app/Filament/Pages/DiasporaMediaRadar.php`, ilgili Blade | Medya duvarı |
| 10 | `app/Jobs/GlobalCommand/SyncDiasporaAccount.php`, `app/Support/GlobalCommand/StrictDiasporaSync.php`, `...120100_harden_diaspora_ingest.php` | Önkoşullar tamamlanınca kuyruklu ingest |
| 11 | `app/Console/Commands/PlanColdStart.php`, `routes/console.php` | Tekrarsız iç büyüme görevleri |
| 12 | Mevcut Listing/JobListing/User/Diaspora Resource sorguları, widgetlar, grafikler ve aksiyonlar | Coğrafyayı bütün ilgili tüketicilere bağla ve Faz 1 açıklarını kapat |
| 13 | `resources/css/filament/admin/theme.css` | Özel Blade kaynaklarını tema taramasına ekle |

## Provider bağlantıları

```php
// bootstrap/providers.php: mevcut provider dizisine ekleyin.
App\Providers\GlobalCommandServiceProvider::class,
```

`AdminPanelProvider::panel()` içindeki mevcut `authMiddleware` çağrısını aşağıdakiyle **birleştirin**. Mevcut oturum, güvenlik header'ları ve 2FA akışı korunur. Yeni login kapısı açmayın.

```php
->authMiddleware([
    \Filament\Http\Middleware\Authenticate::class,
    \App\Http\Middleware\YonetimIkiFaktorZorunlu::class,
    ...(config('global-command.enabled')
        ? [\App\Http\Middleware\ResolveAdminGeoContext::class]
        : []),
], isPersistent: true)
->renderHook(
    \Filament\View\PanelsRenderHook::TOPBAR_AFTER,
    fn (): string => config('global-command.enabled') && auth()->user()?->isAdmin()
        && auth()->user()->status === \App\Enums\UserStatus::Aktif
        ? \Illuminate\Support\Facades\Blade::render("@livewire('admin.geo-switcher')")
        : '',
)
```

Hook üst çubuğun hemen altında tam genişlikte yer alır; dar ekranda global arama/hesap ikonlarını sıkıştırmaz. Filament render hook API'si framework view'larını kopyalamadan ek içerik yerleştirmeyi sağlar. [Filament 3 render hooks](https://filamentphp.com/docs/3.x/support/render-hooks), [Filament 5 render hooks](https://filamentphp.com/docs/5.x/advanced/render-hooks).

Yeni sayfalar mevcut `discoverPages()` ile bulunur. Likidite widgetı `isDiscovered=false` olduğundan ana dashboard'a kendiliğinden eklenmez; operasyon sayfasında açıkça kaydedilir. Mevcut paneldeki widget sort testleri bu nedenle bozulmamalıdır.

Tema dosyasının mevcut `@source` satırlarına ekleyin:

```css
@source '../../../../resources/views/components/intelligence-score-badge.blade.php';
```

Mevcut tema Filament/Livewire view dizinlerini zaten tarıyor. Tailwind v3 hedefinde eşdeğer `content` glob'ları gerekir; v3 kurulum ayrımı [Filament v3 notlarında](filament-v3/README.md) açıklanır.

## Fazlara göre kabul listesi

### Faz 1 — Veri doğruluğu ve dayanıklılık

- [ ] Sahte diaspora fallback'i canlı akıştan çıkarıldı; başarısız/boş/başarılı sonuçlar ayrıldı.
- [ ] Metadata servisinde geçersiz host/URL ağ çağrısı yapmadan reddediliyor.
- [ ] Tüm toplu sync/ranking işlemleri kuyrukta; modal/liste render'ı AI çağırmıyor.
- [ ] N+1 yolları eager loading/withCount ile düzeltildi; 25/50 satır testinde sorgu artışı sabit kaldı.
- [ ] Mevcut içerik tekrarları raporlandı; mutabakatlı birleştirme sonrası unique index eklendi.
- [ ] Heartbeat, queue oldest-job age, failed_jobs, provider 429 ve cache/memory ölçümleri kaydediliyor. “Queue driver tanımlı” tek başına sağlıklı sayılmıyor.

### Faz 2 — Küresel coğrafya

- [ ] Kaynak sürümlü tam ISO kataloğu, para birimleri, diller ve M49 üyelikleri yüklendi; kod anahtar kümeleri karşılaştırıldı.
- [ ] 200+ aktif ülke seçiciye kod değiştirmeden geliyor; önceki 60 ülke dışındaki kayıtlar doğrulandı.
- [ ] Global/bölge/ülke/şehir bağlamı Resource, tablo badge'i, grafik ve aggregate sorgularına bağlandı. Bilerek global kalan altyapı kartları etiketlendi.
- [ ] Şehir aitliği, yetkisiz ID, silinmiş ülke, boş bölge, iki yönetici, iki sekme ve Octane/queue scope sıfırlanması test edildi.
- [ ] Sıfır veri ülkesi cold-start olarak görünür; bilinmeyen ülke kovası global sayımları açıklar.
- [ ] Cold-start komutu aynı hafta tekrar çalıştırılınca görevleri çoğaltmıyor; dış davetler izinli outreach akışına bağlı.

### Faz 3 — Medya ve istihbarat

- [ ] 12 kart/sayfa, tek isteğe bağlı video, yetki ve geo kontrollü aksiyonlar doğrulandı.
- [ ] Puan yokluğu “incelenmedi”; güvenlik ve etkileşim ayrı; mevcut kural puanı AI diye sunulmuyor.
- [ ] Strict ingest için gerçek sağlayıcı sözleşmesi ve beklenen post tipi doğrulandı. Cursor, observed-at ve Retry-After gereksinimleri tamamlandı.
- [ ] İş yeniden denendiğinde duplicate yok; hesap/aktör yetki değişikliği yeniden denetleniyor; yeni içerik taslak kalıyor.

### Faz 4 — Yönetici copilot

- [ ] Kâhya konuşmaları aktör/oturumla ayrıldı; yönetim MCP çağrıları principal ve araç izinleri taşıyor.
- [ ] DE/KG kira karşılaştırması para birimi ve fiyat birimi başına; FR bekleyen işler doğru JobStatus ile filtreleniyor.
- [ ] AI sonucu content hash + model/prompt/schema sürümü + kanıt + kaynak ID'leri içeriyor; eski içerik sonucu uygulanmıyor.
- [ ] Filtre/rapor/iç inceleme işareti düşük etkiyle otomatik; yayın, toplu ret ve dış iletişim mevcut risk/izin kapısından geçiyor.
- [ ] Eylem, idempotency ve audit atomik; retry sonucu çift eylem veya çift gönderim oluşmuyor.

### Faz 5 — Enterprise UX ve responsive kabul

- [ ] 320, 375, 768, 1024 ve 1440 px; açık/koyu tema; %200 zoom ve uzun ülke/hesap adlarında sayfa yatay taşmıyor.
- [ ] Grid çocuklarında `min-w-0`, metinlerde `break-words`, aksiyonlarda `flex-wrap` var. Taşma `overflow-x-hidden` ile saklanmıyor.
- [ ] Dokunma hedefleri en az 44×44 px; referanstaki `size=sm` düğmeler gerekiyorsa tema ile büyütüldü. Mobilde sabit genişlik yok.
- [ ] İkon framework bileşenine bir kez veriliyor; buton içinde ayrıca ikinci SVG/emoji kullanılmıyor. Bölüm ve aksiyon ikonları aynı anlamı gereksiz tekrarlamıyor.
- [ ] Modal/slide-over Tab sırası, Escape, odak geri dönüşü ve ekran okuyucu başlıkları test edildi; mobilde tam genişliğe geçiyor.
- [ ] Renk tek bilgi kanalı değil; puanda metin ve durum var. Açık/koyu temada kontrast ölçüldü; varsayıldı denmiyor.
- [ ] Animasyonlar 120–180 ms opacity/transform ile sınırlı; `prefers-reduced-motion` için devre dışı. Sürekli titreşen/yanıp sönen KPI yok.
- [ ] Loading/empty/error/stale durumları ayrı; provider arızası başarı bildirimi veya sıfır veri raporu olmuyor.

Bu liste tarayıcıyla gerçekleştirilmiş doğrulama iddiası değildir; mevcut çalışmada UI render testleri vardır, gerçek viewport ve dokunma kontrolleri dağıtım kapısıdır.

## Komutlar — önerilen sıra

Depo kökünde salt okunur başlangıç kontrolleri:

```powershell
php -v
composer show laravel/framework
composer show filament/filament
composer show livewire/livewire
php artisan migrate:status
php artisan schedule:list
php artisan queue:failed
```

Referans paketi **uygulamaya kurmadan** test etmek için:

```powershell
php vendor/bin/phpunit --bootstrap docs/global-command-center/reference/tests/bootstrap.php docs/global-command-center/reference/tests
php vendor/bin/pint --test docs/global-command-center/reference
```

Paket `app/` içine entegre edildikten ve testler `tests/Feature/GlobalCommand/` altına taşındıktan sonra reference bootstrap yerine normal uygulama bootstrap'ı kullanılmalı. Migration'ları test setUp'ında ikinci kez yükleyen referans düzeni de kaldırılmalı.

Staging'de dosyalar birleştirildikten sonra, ana flag kapalıyken:

```powershell
composer dump-autoload
php artisan optimize:clear
php artisan migrate --pretend
php artisan migrate
npm run build
php artisan test --filter="GlobalCommand|AdminPanel|YonetimGirisi|PanoSiralamasi"
php artisan route:list --path=yonetim
```

`migrate --pretend` veri kontrolü içeren migration'ın tüm canlı DDL etkisini kanıtlamaz. Şema değişiklikleri özellikle MySQL kopyasında gerçekten çalıştırılmalı. Package migration import veri dosyası yüklemez; katalog yükleme ayrıca tamamlanmalıdır.

Katalog/sorgu/arayüz kapıları geçince:

```dotenv
GLOBAL_COMMAND_ENABLED=true
GLOBAL_COMMAND_DIASPORA_SYNC_ENABLED=false
GLOBAL_COMMAND_DIASPORA_QUEUE=database
GLOBAL_COMMAND_DIASPORA_CACHE=database
```

Ana flag açılması senkronizasyon flag'ini açmaz. Sağlayıcı ve mevcut NULL tüketicileri doğrulanınca sync flag ayrı açılır. Paylaşılan cache ve kuyruk hazırken worker:

```powershell
php artisan queue:work database --queue=diaspora-sync --sleep=3 --tries=5 --timeout=45
```

Worker üretimde process manager ile sürekli çalışır; tek bir terminal oturumuna güvenilmez. `retry_after` bu timeout'tan uzun olmalı. Redis seçildiyse komutta/config'te ilgili bağlantı adı kullanılır; mevcut diğer queue worker'ları durdurulmaz.

`routes/console.php` içine iç görev planını ekleyin:

```php
\Illuminate\Support\Facades\Schedule::command('global-command:plan-cold-start')
    ->dailyAt('00:10')->timezone('UTC')->withoutOverlapping(10)->onOneServer();
```

`onOneServer()` kilit destekli ortak cache gerektirir. Laravel scheduler sunucuda dakika başına `php artisan schedule:run` ile zaten işletiliyorsa ikinci cron kurmayın. Yayın sonrasında:

```powershell
php artisan global-command:plan-cold-start
php artisan schedule:list
php artisan config:cache
php artisan view:cache
php artisan queue:restart
```

Üretim migration'ı yalnız backup + restore provası ve staging kabulü sonrasında, rollout prosedüründe `php artisan migrate --force` ile yapılır. Cache yeniden oluşturma/worker restart flag değişiklikleri sonrasında tekrarlanır.

## Gözlem, açılış ve geri dönüş

İlk açılış: iç adminler → sınırlı hesap grubuyla ingest → bütün katalog. Aynı başlıklarda ölçüm yapın: istek p50/p95, SQL sayısı ve toplam süresi, cache hit oranı, queue en eski iş yaşı, failed/retry/429 sayısı, metrik yaşı ve AI birim maliyeti. İlk kabul hedefleri: sıcak cache operasyon sayfası sunucu p95 < 800 ms, sağlayıcı beklemeyen sync talebi < 500 ms, metrik yaşı normalde ≤ 60 sn. Bunlar ölçülmüş sonuç değildir; donanım ve veri hacmine göre hedefler testle kesinleşir.

Geri dönüşte önce `GLOBAL_COMMAND_DIASPORA_SYNC_ENABLED=false`, sonra gerekirse ana flag false yapılır; config cache yenilenir ve queue worker'ları yeniden başlatılır. Kuyruktaki referans işler flag kapalıyken veri yazmaz. Şema ve audit verileri tutulur; gözü kapalı `migrate:rollback` yapılmaz. Hardening migration `down()` ölçülmemiş NULL değerleri sıfıra dönüştürmez ve case-sensitive kimlik semantiğini korur. Eski uygulama NULL desteklemiyorsa şema geri alma yerine uyumlu uygulama sürümüne dönülür.

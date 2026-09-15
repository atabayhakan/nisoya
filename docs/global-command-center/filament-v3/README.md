# Laravel 12 / Filament 3 / Livewire 3 uyarlaması

Bu dizin, talepteki Filament v3 hedefi için **üç tam PHP sınıfı** içerir. Önce `../reference/` paketindeki ortak dosyalar hedef Laravel 12 uygulamasına alınır; sonra bu dizindeki aynı yollu sınıflar onların yerine kullanılır. İki varyant aynı autoload altında birlikte yüklenmez.

| Dosya | v3 uyarlaması |
|---|---|
| [GlobalCommandCenter.php](app/Filament/Pages/GlobalCommandCenter.php) | `$view` static; navigation icon/group nullable string |
| [CountryLiquidityWidget.php](app/Filament/Widgets/CountryLiquidityWidget.php) | `$view` static |
| [DiasporaMediaRadar.php](app/Filament/Pages/DiasporaMediaRadar.php) | Static view, string navigation özellikleri ve `heroicon-o-*` ikon adları |

GeoContext, middleware, service provider, Livewire seçici, puan/aggregate servisleri, job, komut ve Blade dosyaları ortak kaynak paketindedir. Bu bileşenler ortak Laravel/Livewire API'leriyle yazılmıştır; **Laravel 12/Filament 3 runtime'ında test edilmediler**. PHP sözdizimi doğrulaması ve mevcut Laravel 13/Filament 5 testleri bu eksik runtime doğrulamasının yerine geçmez.

Filament v3 sayfa/view biçimi ve render hook'ları için [resmî v3 sayfa belgesi](https://filamentphp.com/docs/3.x/panels/pages) ve [render hook belgesi](https://filamentphp.com/docs/3.x/support/render-hooks) esas alınır. Referans kendi içinde v5 `Schema` form API'si kullanmadığından form uyarlaması gerekmez; hedef uygulamanın mevcut Resource sınıflarının namespace ve `getUrl` route adları yine doğrulanmalıdır.

Örnek v3 sayfa girişi:

```php
class GlobalCommandCenter extends \Filament\Pages\Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Pazarlama & Büyüme';
    protected static string $view = 'filament.pages.global-command-center';
    // Eksiksiz sınıf bu dizindeki app/Filament/Pages dosyasında.
}
```

## Tema ayrımı

Filament 3, Tailwind 3 ile kullanılır; mevcut NISOYA Tailwind 4 temasını bu hedefe kopyalamayın. [Filament v3 gereksinimleri](https://filamentphp.com/docs/3.x/widgets/installation#requirements). Mevcut Filament 5 uygulamasını sırf örnek kodu çalıştırmak için downgrade etmeyin.

Hedef v3 uygulamasının oluşturulmuş admin temasındaki `tailwind.config.js` dosyasına, var olan kaynaklarla birleştirilecek örnek:

```js
content: [
    './app/Filament/**/*.php',
    './resources/views/filament/**/*.blade.php',
    './resources/views/livewire/admin/**/*.blade.php',
    './resources/views/components/intelligence-score-badge.blade.php',
    './vendor/filament/**/*.blade.php',
]
```

V3 temasının kendi Filament preset ve dark mode yapılandırması korunur. Bu dizin bir composer.lock veya komple Laravel projesi değildir; bağımsız PHP 8.2+ / Laravel 12 / Filament 3.3 / Livewire 3 CI matrisi kurup hedefin kilitlenmiş bağımlılıkları üzerinde test edin. Var olan uygulamanın User/Country/Diaspora modelleri bu projedeki alan ve enum sözleşmeleriyle uyumlu olmalıdır.

## V3 kabul kapısı

- [ ] Sınıflar static/instance property çakışması olmadan yükleniyor.
- [ ] Page ve widget render testleri geçiyor; `WithPagination` ve modal slotları çalışıyor.
- [ ] Panel render hook'u ve persistent auth middleware gerçek Livewire POST isteğinde çalışıyor.
- [ ] Geo değişince eski seçili kayıtlar temizleniyor; kullanıcı/rol/şehir doğrulamaları sürüyor.
- [ ] Build, dark mode ve 320 px viewport kontrolleri tamamlandı.

Bu liste henüz çalıştırılmış v3 test sonucu değildir.

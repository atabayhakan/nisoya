# KURAL: Bu ağaçta yalnız klasik ağaçla aynı-ad (birebir aynı göreli yol) override dosyaları yaşar — handoff dokümanındaki dosya şeması (layouts/vitrin.blade.php, vitrin/partials/header.blade.php vb.) GEÇERSİZDİR.

## Vitrin view ağacı nasıl çalışır

`gorunum.tema = vitrin` iken `TemaViewYollari` middleware'i bu dizini view
finder yollarının BAŞINA ekler: `view('home')` önce `vitrin/home.blade.php`'a
çözülür; burada karşılığı olmayan her view klasik dosyayla render edilir.
Ad değişmediği için composer'lar (`components.layouts.app` → nav verileri),
`HomeSections`, zone anahtarları ve controller'lar sıfır dokunuşla çalışır.

## Değiştirilemez kurallar

1. **Aynı-ad:** Buraya eklenen her dosyanın klasik ağaçta (`resources/views/`)
   birebir aynı göreli yolda bir adaşı OLMAK ZORUNDA. Farklı ad = composer
   bağlanmaz = 500. CI bekçisi (`TemaTest::test_bekci_...`) bunu zorlar.
2. **Beyaz liste:** Her yeni override, `tests/Feature/TemaTest.php` içindeki
   `OVERRIDE_LISTESI` sabitine de eklenir — dizin içeriği listeyle birebir
   eşleşmezse CI kırmızı. Override eklemek bilinçli, diff'te görünür bir beyandır.
3. **Vitrin'e özgü YENİ bileşenler buraya KONMAZ** — onlar
   `resources/views/components/vitrin/**` altında (klasik ağaçta) yaşar,
   çünkü `view:cache` bu middleware olmadan (prepend'siz) derlenir.
4. Klasik ağaçta bir partial/bileşen değiştirilirse, buradaki adaşının da
   güncellenmesi gerekip gerekmediğini kontrol et (`grep -r <dosya-adı> resources/views/vitrin`).
5. Paylaşılan sözleşme bileşenleri (`x-layout-head-meta`, `x-layout-head-scripts`,
   `x-layout-tail-scripts`) TEK kopyadır — vitrin iskeleti bunları AYNEN kullanır,
   asla kopyalamaz (consent/SEO zinciri iki temada ayrışamaz).

Mimari kararların tamamı: `docs/plans/2026-07-26-vitrin-tema-plan.md`.

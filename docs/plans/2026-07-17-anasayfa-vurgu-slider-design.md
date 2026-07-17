# Ana Sayfa Vurgu Slider'ları — Tasarım

**Tarih:** 2026-07-17
**Durum:** Onaylandı, uygulanıyor

## Bağlam

Ana sayfadaki "Değer önerileri" bento grid'inde (`resources/views/home.blade.php`, ~satır 131-169) iki kart admin panelden yönetilen ama TEK bir mesaj gösteren statik kartlar:

- Büyük öne çıkan kart (2x2 hücre) — şu an "Tamamen Türkçe" (`setting('home.deger1_baslik'/'deger1_metin')`)
- Küçük kart (1x1 hücre) — şu an "Ücretsiz ilan" (`setting('home.deger3_baslik'/'deger3_metin')`)

Kullanıcı isteği: bu iki kartı, admin panelden yönetilebilen, otomatik dönen (slider/reklam tarzı) çoklu mesaj gösterimine dönüştürmek. "Güvenli topluluk" ve "22 ülke · X şehir" kartlarına dokunulmuyor.

## Veri modeli

Tek tablo, iki admin görünümü:

```
home_highlights
  id
  slot        enum('buyuk', 'kucuk')
  title       string
  text        string
  icon        string  -- sabit heroicon adları listesinden (bkz. aşağı)
  sort_order  unsignedInteger
  is_active   boolean default true
  timestamps
```

`App\Models\HomeHighlight` — `Zone`/`NavigationLink` ile aynı `__PHP_Incomplete_Class` cache tuzağına dikkat (varsa cache'li okuma ham `getAttributes()` + `setRawAttributes()` ile yapılmalı; burada muhtemelen cache gerekmiyor çünkü liste küçük ve sayfa zaten cache'lenmiyor — gerek görülmezse basit `where->orderBy->get()` yeterli).

İkon seçenekleri (sabit liste, `IconOption` enum veya basit const array): `language`, `shield-check`, `sparkles`, `globe-alt`, `badge` (varsa), `heart`, `star`. Filament formunda `Select` — serbest metin YOK (yanlış heroicon adı sessizce kırılır).

## Admin (Filament)

İki ayrı kaynak, aynı model üzerinde `slot` global scope'u ile filtrelenmiş:

- `BigHighlightResource` → nav: "Ana Sayfa — Büyük Kart Mesajları", scope `slot=buyuk`
- `SmallHighlightResource` → nav: "Ana Sayfa — Küçük Kart Mesajları", scope `slot=kucuk`

`NavigationLink` deseniyle aynı: tam CRUD (create/edit/delete), `->reorderable('sort_order')`, `is_active` toggle. Yeni kayıt oluştururken `slot` alanı formda GÖSTERİLMEZ (resource'un scope'undan otomatik atanır — `mutateFormDataBeforeCreate`).

## Seed / geçiş

`HomeHighlightSeeder`, mevcut `setting('home.deger1_baslik'/'deger1_metin')` ve `deger3_baslik'/'deger3_metin'` değerlerini **ilk mesaj** olarak `firstOrCreate(['slot' => ..., 'sort_order' => 0], [...])` ile taşır (Zone/NavigationLink'teki güvenli desen — sonraki deploy'larda admin'in eklediği içeriği asla ezmez). İkon: büyük kart için `language`, küçük kart için `sparkles` (mevcut ikonlarla aynı). `ReferenceDataSeeder`'a eklenir.

Sonuç: ilk deploy'da sitede görsel değişiklik SIFIR. Admin ikinci bir mesaj eklediği an o kart dönmeye başlar (tek mesaj varken zaten dönecek bir şey yoktur, sabit görünür — `activityTicker`'ın `count < 2` durdurma mantığıyla aynı).

## Frontend

Yeni JS bileşeni YOK — mevcut `Alpine.data('activityTicker', (count) => ...)` (`resources/js/app.js`) aynen yeniden kullanılıyor (zaten jenerik: index döngüsü, 4.5sn, `prefers-reduced-motion` durdurma, `count < 2` erken çıkış).

Blade tarafında her kart, `@foreach` ile mesajları üst üste bindirir (`activityTicker` şeridindeki `x-show="index === {{ $i }}"` + aynı `x-transition` fade/kaydırma ayarları). Kartın boyutu/pozisyonu (2x2 / 1x1 grid hücresi) değişmez, sadece içerik geçiş yapar. İkon da mesajla birlikte döner (`x-dynamic-component` ile `icon` alanına göre).

Controller/composer tarafı: `HomeController` (ya da ana sayfayı besleyen composer), `home_highlights` tablosundan `slot` ve `is_active`'e göre iki koleksiyon çeker, view'e `$bigHighlights`/`$smallHighlights` olarak geçer.

## Test kapsamı

- `HomeHighlightTest`: admin CRUD (create/edit/delete/reorder/toggle), scope'ların doğru filtrelediği, seeder'ın mevcut `setting()` değerlerini doğru taşıdığı, ana sayfanın aktif olmayan mesajları göstermediği, tek mesaj varken slider'ın statik kaldığı (`count < 2`).

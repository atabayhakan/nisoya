# Vurgu Kartlarına Medya (Resim/Galeri/Video) — Tasarım

**Tarih:** 2026-07-18
**Durum:** Onaylandı, uygulandı

## Bağlam

[[2026-07-17-anasayfa-vurgu-slider-design.md]] ile büyük/küçük vurgu kartları (`App\Models\HomeHighlight`) admin panelden yönetilen, otomatik dönen ikon+metin mesajlarına dönüştürülmüştü. Kullanıcı isteği: bu kartlara resim, video, kart-içi mini galeri ve gömülü YouTube video desteği eklemek — medya eklenen bir kartta ikon yerine bu içerik gösterilsin.

## Veri modeli

Yeni tablo/ilişki YOK — tek `media` JSON kolonu eklendi (`2026_07_18_120000_add_media_to_home_highlights_table`), `array` cast (`App\Models\HomeHighlight::casts()`). Filament'in `Builder` bileşeninin ürettiği standart şekil (bkz. `App\Filament\Support\ContentBlocks` ile birebir aynı desen — `resources/views/partials/page-block.blade.php`):

```json
[
  {"type": "resim", "data": {"path": "highlights/xxx.jpg"}},
  {"type": "youtube", "data": {"url": "https://youtu.be/..."}},
  {"type": "video", "data": {"path": "highlights/xxx.mp4"}}
]
```

`icon` kolonu kalıyor (geriye dönük uyum) ama `HomeHighlight::hasMedia()` true dönerse (`media` boş değilse) kartta ikon yerine medya gösterilir — `media` boşsa mevcut davranış hiç değişmez.

Bu kolon `home_highlights` tablosunun tamamına ait olduğu için **hem Büyük hem Küçük Kart** aynı anda bu yeteneği kazandı (ayrı migration/kod gerekmedi — ikisi de `HomeHighlightResourceBase`'i paylaşıyor).

## Admin (Filament)

`App\Filament\Support\HighlightMediaBlocks::schema()` — 3 blok tipi (`ContentBlocks` ile aynı desen, `Builder\Block`):

- **resim** — `FileUpload` (image, disk `public`, dizin `highlights`, max 4MB)
- **youtube** — `TextInput` (url), kayıt sırasında `App\Support\HighlightMedia::youtubeId()` ile doğrulanır (`watch?v=`, `youtu.be/`, `shorts/` formatları); eşleşmezse form hatası
- **video** — `FileUpload` (sadece `video/mp4`, max 20MB)

`HomeHighlightResourceBase::form()`'a `MediaBuilder::make('media')` eklendi (`icon` alanının altında) — `maxItems(6)`, sürükle-bırak sıralama, varsayılan katlanmış (`collapsed()`). **Namespace çakışması notu:** `Filament\Forms\Components\Builder` ile `Illuminate\Database\Eloquent\Builder` (dosyada zaten `getEloquentQuery()` için kullanılıyor) aynı isim — `Builder as MediaBuilder` alias'ı gerekti.

## Frontend

`resources/views/partials/highlight-media.blade.php` — tek bir medya öğesini türüne göre render eder (`@switch($item['type'])`): `resim` → `<img object-cover>`, `youtube` → `youtube-nocookie.com/embed/{id}` iframe (tıkla-oynat, otomatik oynatma yok), `video` → `<video controls>`.

`home.blade.php`'deki büyük/küçük kart bloklarında, ikon `<span>`'ının yerine koşullu bir medya kutusu geldi: `@if ($highlight->hasMedia())`. Birden fazla medya öğesi varsa (`count($media) > 1`), mevcut `Alpine.data('activityTicker')` bileşeni (`resources/js/app.js`) **iç içe, bağımsız bir ikinci örnek** olarak yeniden kullanılıyor — dıştaki ticker hangi highlight kaydının gösterileceğini, içteki ticker o kaydın hangi medya öğesinin gösterileceğini döndürüyor. Aynı JS bileşeni, aynı `x-transition` deseni; yeni JS kodu YOK.

**Blade derleyici tuzağı (öğrenme):** `@php($media = $highlight->media)` kısa formu bu Livewire/Blade derleyici kombinasyonunda (`Filament v5.6` + `Livewire v3`, bkz. [[nisoya-dev-ortami]]) `<?php($media = ...)` şeklinde **kapanış `?>` etiketi olmadan** derlenip tüm sayfayı `ParseError: unexpected end of file` ile kırdı. Çözüm: blok formu kullanmak — `@php $media = $highlight->media; @endphp` (`page-block.blade.php`'nin zaten kullandığı desenle aynı). Bu depoda artık kısa `@php(...)` formundan kaçınılmalı.

## Doğrulama

- `php artisan test` — 653 test, tümü geçti (yeni test eklenmedi; mevcut `HomeHighlightTest` model/admin sözleşmesini zaten kapsıyor, `hasMedia()`/media alanı ek bir iş kuralı içermiyor).
- `phpstan analyse` (memory_limit=512M gerekti — portable php.ini varsayılanı 128M) — 0 hata.
- `pint --test` — geçti.
- Yerel tarayıcıda uçtan uca doğrulandı: admin formunda 3 blok tipi eklenip kaldırıldı, geçersiz YouTube linkinde "Geçerli bir YouTube linki girin." hatası, geçerli linkle kayıt → ana sayfada gömülü `youtube-nocookie.com` iframe doğru boyutlarda render edildi. Test verisi kayıttan temizlendi.

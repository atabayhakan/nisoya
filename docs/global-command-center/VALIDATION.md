# Doğrulama kaydı

> Bu sayfa ilk referans paketinin tarihsel test kaydıdır. Yerel uygulama entegrasyonunun güncel test sonuçları ve sınırları [IMPLEMENTATION.md](IMPLEMENTATION.md) içindedir. Aşağıdaki “çalışan dosyalar değiştirilmedi” ifadesi yalnız ilk aşamayı anlatır.

Tarih: 15 Eylül 2026. Ortam: yerel Windows, PHP 8.3.31; depo bağımlılıkları Laravel 13.31.0 / Filament 5.8.1 / Livewire 4.4.4. Referans testleri uygulamanın `phpunit.xml` ayarlarıyla SQLite `:memory:`, array session/cache ve sahte HTTP yanıtları üzerinde çalıştırıldı.

## Son sonuç

| Kontrol | Sonuç |
|---|---|
| Referans PHPUnit paketi | **14 test geçti, 56 assertion** |
| PHP sözdizimi | **22 PHP dosyası geçti**; Blade dosyaları bu sayıya dahil değil |
| Laravel Pint | Referans ve v3 kaynakları biçimlendirildi; `--test` geçti |
| Livewire/Blade render | Seçici, likidite widgetı, medya radarı ve önizleme yolu testte oluşturuldu |
| Gerçek dış API çağrısı | Yapılmadı; HTTP yanıtları fake |
| Çalışan uygulama dosyaları | Değiştirilmedi; paket `docs/global-command-center/` altında |

Çalıştırılan test komutu:

```powershell
php vendor/bin/phpunit --bootstrap docs/global-command-center/reference/tests/bootstrap.php docs/global-command-center/reference/tests
```

## Davranış kapsamı

1. Sabit ülke allowlist'i olmadan katalogdan Fiji gibi yeni bir ülke seçimi ve sıfır üyeli bölgenin sıfır sonuç üretmesi.
2. Başka ülkeye ait şehir ID'sinin reddedilmesi.
3. Geçerli şehir adı eşleşmesi; normal model sorgularının panel lensinden etkilenmemesi.
4. Session tercihinin farklı aktöre taşınmaması; pasif ülke tercihinin isteği 409 ile durdurması.
5. Scoped bağlamın sonraki uygulama yaşam döngüsü için global varsayılana sıfırlanması.
6. Sıfır verili ülkenin radarda kalması; demo ilan/kullanıcıların dışlanması; bağlamlar arasında cache ayrımı.
7. Puanın 0–100 sınırı; null güvenlik puanının güvenli sayılmaması; güvenilmeyen medya URL'lerinin reddi.
8. Ülke seçicisinin doğrulanmış tercihi kaydetmesi ve admin olmayan kullanıcıyı reddetmesi.
9. Widget/radar render'ı ve video embed çıktısının gerçek sunucu HTTP isteği olmadan oluşması.
10. Katı ingest'in yalnız taslak oluşturması, eksik metrikleri NULL tutması ve tekrarda aynı kaydı çoğaltmaması.
11. Sağlayıcı 429 hatasında yeni içerik veya başarı tarihi yazılmaması.
12. Cold-start iç görevlerinin aynı hafta tekrar komutunda çoğalmaması.
13. Başarılı boş sağlayıcı listesi ile geçersiz yanıt şemasının ayrılması.
14. Radar önizleme ve hesap doğrulama eylemlerinde başka coğrafyadaki kayıt ID'sinin yeniden reddedilmesi.

## Doğrulanmayanlar

- Laravel 12 / Filament 3 / Livewire 3 bağımlılık matrisi üzerinde çalışma; v3 sınıflarına yalnız PHP sözdizimi kontrolü yapıldı.
- Üretim veritabanı, tam ISO/M49 kataloğu veya yerel veri kaynağının doğruluğu.
- MySQL DDL, case-sensitive unique index, eşzamanlı worker yarışı ve gerçek Redis/database kuyruk kilitleri.
- Gerçek sağlayıcı endpoint sözleşmesi, yetkili erişim, pagination/cursor, HTTP kota davranışı ve video erişilebilirliği.
- Tarayıcıda 320–1440 px viewport, odak/klavye, dokunma, kontrast ve performans ölçümü.
- Bütün mevcut Resource, widget, chart ve mutasyonların GeoContext'e entegrasyonu; bunlar dağıtım planında yapılacak işlerdir.
- Tam ISO katalog importörü, bölgesel RBAC, AI kalite değerlendirme worker'ı, Kâhya araç genişlemesi veya dış davet gönderimi.

Bu sonuç referans bileşenlerinin belirtilen ortamda doğrulanmasıdır; beş fazın tamamının uygulanmış olduğu veya üretime kabul edildiği anlamına gelmez.

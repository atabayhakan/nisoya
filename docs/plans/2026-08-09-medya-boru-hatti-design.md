# Medya Boru Hattı — tasarım

**Tarih:** 2026-08-09 · **Durum:** onaylandı, A adımı uygulanıyor

## Neden

Sahip hero'ya **4469×2979 / 402 KB** bir görsel yükledi; slot 1265×603. Hiçbir
şey itiraz etmedi. Sonra ikinci turda 1800×1200 yüklendi — panelin önerdiği
2400×1200 değil — ve yine itiraz olmadı, çünkü "2400×1200 önerilir" bir
**yardım metniydi**: kod onu okumuyor, yalnız insana söylüyordu.

Aynı gün metin kontrastı için karartma yüzdesi elle **%48 → %69 → %60**
gidip geldi. Mobil hâlâ eşiğin altında kaldı, çünkü karartma görselden
bağımsız sabit bir sayıydı: açık bir fotoğraf da koyu bir fotoğraf da aynı
perdeyi alıyordu. Masaüstü ve mobil ayrı ölçülmemişti.

## Ölçülen başlangıç durumu

**Ölçekleme makinesi ZATEN VAR:** `App\Services\ImageService` (458 satır) —
WebP dönüşümü, thumb/medium/large varyantları, kalite 80, EXIF temizliği,
`scaleDown` (küçültür, asla büyütmez), yön düzeltme, kare kırpma. İlan
(`ProcessListingImage`) ve etkinlik görselleri bunu kullanıyor.

**Hero ve Medya Kütüphanesi bu makineyi HİÇ kullanmıyor:**

- Hero: `FileUpload → disk('public')` → ham dosya
- Medya Kütüphanesi: `$file->storeAs(...)` → ham dosya

Yani işin büyük kısmı yeni sistem kurmak değil, **var olanı bağlamak**.
(Bu depoda tekrar eden desen: makine var, kablo yok — bkz. Hero Yöneticisi'nin
ana sayfaya hiç bağlı olmaması, Görünüm panelinin kodun yapmadığını anlatması.)

## Sahibin verdiği yön

| Soru | Karar |
|---|---|
| Karar kimin | **Otomatik düzelt, gerekirse haber ver** |
| Kapsam | **Tüm yükleme noktaları** |
| Orijinal saklansın mı | **Evet — ana kopya, her şey ondan türetilir** |
| Hero mobil görseli | **Tek yükleme + akıllı kırpma, ayrı yükleme ezer** |
| Odak tahmini | **Merkez** (öngörülebilirlik; sürükleyerek düzeltilir) |
| Kontrast sınırı | **%55'e kadar karart, yetmezse metnin arkasını koyult** |
| Slot listesi | **`config`'de** (tasarımın parçası, günlük ayar değil) |
| Medya kütüphanesi | Dördü de: kullanım takibi · boyut/ağırlık · kütüphaneden seç · arama |

## 1. Mimari: ana kopya + türevler

**`MediaAsset`** — yüklenen dosyanın kendisi: orijinal yol, mime,
genişlik/yükseklik, bayt, **içerik özeti (hash)**, odak noktası, yükleyen.

**`MediaRendition`** — belirli bir amaç için üretilmiş dosya: asset, **slot
anahtarı**, genişlik/yükseklik, biçim, yol, bayt.

**Kural: sitede görünen hiçbir şey doğrudan ana kopya değildir.** Her yüzey bir
türevi gösterir; ana kopya yalnız üretim kaynağıdır ve `public/` altında
servis edilmez.

Kazanç: slot oranı değişirse, yeni cihaz boyutu gerekirse ya da odak
kaydırılırsa **yeniden yükleme gerekmez** — sistem yeniden türetir.

**Hash ile tekilleştirme:** aynı dosya iki kez yüklenirse tek ana kopya olur.

Üretim motoru sıfırdan yazılmaz; `ImageService` çağrılır. `ProcessListingImage`
boru hattına **bu turda dokunulmaz** (çalışan şeyi bozmamak için).

## 2. Slot: sistemin beyni

Tek kayıt yeri `config/media_slots.php`:

```php
'hero.masaustu' => ['en' => 2400, 'boy' => 1200, 'kip' => 'kapla', 'azami_kb' => 250],
'hero.mobil'    => ['en' => 1080, 'boy' => 1620, 'kip' => 'kapla', 'azami_kb' => 150,
                    'turet' => 'hero.masaustu'],
```

Yükleme "bir dosya al"dan **"bu slot için bir dosya al"a** dönüşür.

- **`kip`** — `kapla` (doldur, taşanı kırp) ya da `sigdir` (kırpma yok; logo).
  Bugünkü `object-fit: cover` kararı artık görünümde değil **veride**.
- **`turet`** — mobilin masaüstünden türetileceğini söyler; ayrı dosya
  yüklenirse o kazanır.

**Slot değişince:** `media:yeniden-turet <slot>` tüm ana kopyalardan yeniden
üretir. Yeniden yükleme yok.

**Sınır:** `azami_kb` tutmazsa kalite kademeli düşürülür (80 → 70 → 60);
orada da tutmazsa panelde uyarı çıkar ama dosya kabul edilir. Sessizce çöp
üretmez, sessizce reddetmez de.

## 3. Kırpma ve odak

Odak noktası **ana kopyada** saklanır (yüzde: `odak_x`, `odak_y`); tüm
türevler onu merkeze almaya çalışır. Bir kez ayarlanır, masaüstü de mobil de
uyar.

Arayüz zaten var: `focalDrag` (avatar hizalama + ilan düzenleme). Yeniden
yazılmaz, bağlanır.

**Varsayılan: merkez.** Yüz algılama / entropi analizi **bu turda YOK** —
yanlış bir "akıllı" kırpma, öngörülebilir bir merkez kırpmadan daha sinir
bozucudur. İleride `ListingVisionService` üzerinden ayrı iş olarak bakılabilir.

**Küçük görsel:** ana kopya slottan küçükse büyütme yapılmaz; panelde
"1800px yüklendi, slot 2400px istiyor — retinada yumuşak görünecek" uyarısı.

## 4. Kontrast otomasyonu (hero)

Karartma bir ayar değil, **ölçümün sonucu**.

**Ne zaman:** görsel yüklendiğinde ve hero metinleri değiştiğinde.

**Nasıl:** sunucu tarafında, metin bloklarının denk geldiği bölge ana kopyadan
örneklenir, WCAG göreli luminansı hesaplanır, beyaz metne karşı kontrast
bulunur. **Masaüstü ve mobil AYRI hesaplanır** — 2026-08-09 ölçümü: aynı
karartmada (%60) masaüstü 4.72/5.21/4.17, mobil 3.71/3.71/3.84 verdi.

**Karar zinciri:**

1. Eşiği geçen **en düşük** karartmayı bul (H1 için 3.0, küçük metin 4.5)
2. Gereken karartma **%55'i aşıyorsa dur**, metin bloğunun arkasına gradyan aç
3. Onunla da geçmiyorsa panelde kırmızı uyarı: *"bu görselle metin okunmuyor"*

**Panelde:** *"Masaüstü: karartma %38, kontrast 5.1 ✓ · Mobil: %55 + metin
paneli, 4.7 ✓"*. Elle ezme alanı açık kalır ama üstünde ölçülen değer yazar.

## 5. Medya Kütüphanesi

- **Kullanım takibi** — bir görsel bir yüzeye bağlandığında bağ kayıt altına
  alınır; kütüphanede "Hero — masaüstü", "Zone: ana sayfa üst" rozetleri.
  **Etiket "silinebilir" DEMEZ, "bağ bulunamadı" der** — bağ takibi ancak yeni
  sistemden geçen bağlantıları görür, eski/elle girilmiş yollar görünmez.
  Yanlış bir "güvenle sil" rozeti pahalı bir yanıltıcı sinyal olurdu.
- **Boyut ve ağırlık** — listede piksel/bayt/biçim; slotundan büyük olanlar
  işaretlenir, tek tıkla yeniden türetilir.
- **Kütüphaneden seç** — `x-medya-secici`; dosya kopyalanmaz, yeni bağ kurulur.
- **Arama ve düzen** — ad/tarih/tür araması, klasör, toplu işlem. Klasör ve tür
  süzgeci mevcut sayfada zaten var.
- **Geriye dönük:** diskteki dosyalar bir kerelik tarama ile `MediaAsset`
  olarak içeri alınır. **Dosyalar yerinden oynatılmaz** — bağlantılar kırılmaz.

## 6. Yürütme sırası

Her adım kendi başına canlıya çıkabilir.

- **A — Boru hattı ve slotlar.** Tablolar, slot kaydı, türetme servisi,
  `media:yeniden-turet`. Yüzey değişikliği yok.
- **B — Hero.** Slot bağlama, tek yükleme + mobil türetme, odak, kontrast.
  *Bugün acıyan yer; ilk görünür kazanç.*
- **C — Diğer yükleme noktaları.** Zone, vurgu kartı, logo, sayfa içerikleri.
- **D — Medya Kütüphanesi.** Bağ takibi, boyut/ağırlık, seçici, arama, tarama.

## Riskler

- **En büyüğü: mevcut görselleri bozmak.** Hiçbir adım eski dosyayı taşımaz
  veya silmez; yeni sistem yanına kurulur, yüzeyler tek tek geçirilir.
- **Disk** — ana kopya + türevler yer kaplar; slot başına azami boyut var.
- **Kuyruk** — türetme kuyrukta çalışır; worker durursa görsel "işleniyor"
  kalır. Panelde durum rozeti + yeniden deneme, sessiz bekleme yok.
- **Bellek** — büyük görseller GD'de bellek yer; piksel sayısı sınırı (50 MP)
  ve net hata mesajı.

## Sessizce bozulabilecekler (test şart)

- Kontrast kuralının eşiği geçmesi (parlak + koyu örnek)
- Slot değişince yeniden türetmenin çalışması
- Mobil türevin doğru orandan çıkması
- **Hiçbir yüzeyin ana kopyayı servis etmemesi**
- Hash tekilleştirmesi
- "Bağ bulunamadı" etiketinin var olan bağı kaçırmaması

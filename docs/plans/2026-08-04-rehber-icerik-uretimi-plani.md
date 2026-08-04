# Ülke Rehberi — İçerik Üretim Planı (2026-08-04)

Sahip kararı: *"Bu konuda ciddi bir ajan takımı kuralım ve bu konu bizim için
önemli olsun. Kaynak linki ziyaretçide ve üyelerde görünsün. ABD'yi de
ekleyelim. İçerikleri sen yaz, ben kontrol eder onaylarım. Kırgızistan'ı da
ekleyebilirsin."*

---

## 0. Neden bu plan var — ölçülen durum

Rehber modülü 2026-08-01'de canlıya alındı ve Almanya için 210 kayıt tohumlandı.
Bugün o kayıtların içeriği ölçüldü:

| Alan | Durum |
|---|---|
| Toplam kayıt | 210 |
| `sure_metni` boş | **210** (hepsi) |
| `ucret_metni` boş | **210** (hepsi) |
| `dogrulanma_tarihi` dolu | **1** |
| `resmi_kaynak_url` işleme özel | **0** — 210'u da `konsolosluk.gov.tr` genel adresi |

**Sonuç: bunlar "doğrulanmayı bekleyen içerik" değil, iskelet.** Yalnız evrak
listesi var. 2026-08-04 büyüme turundaki "18 sayfa 2-4 saatte doğrulanır"
tahmini bu yüzden yanlıştı — yapılacak iş doğrulama değil, **yazma**.

Bu içerikler vekaletname, pasaport, doğum bildirimi gibi işlemleri anlatıyor.
İnsan bu sayfaya bakıp randevu alıyor, yol gidiyor, evrak hazırlıyor. Eksik
evrak listesi ya da yanlış ücret **somut zarar** demek. K7 kararı ("resmî
kaynaktan kendi ifadeyle, taslak-önce") tam bunun için konmuştu.

## 1. Zaten hazır olan (yeniden yapılmayacak)

- **Kaynak linki ziyaretçide görünüyor.** `rehber/islem.blade.php` kaynak
  bağlantısını *birincil eylem* olarak basıyor ("Resmî kaynağı aç ↗") ve
  yanında "Evrak listeleri, ücretler ve süreler değişebilir" uyarısı duruyor.
  **Sahibin isteği bu yönüyle karşılanmış durumda** — eksik olan linkin
  kendisinin işe yarar olması.
- **Doğrulanma tarihi görünüyor** ("Son doğrulama: …" / "henüz doğrulanmadı").
- **Bayatlık kavramı var**: `BAYATLIK_GUN = 90`.
- **Geri bildirim kutusu var** ("Güncel mi?" — anonim, honeypot + rate limit).
- 15 işlem türü tanımlı, `countries` tablosunda **DE, US, KG üçü de mevcut**.

## 2. Kaynak yapısı — doğrulandı

Her temsilciliğin kendi resmî sitesi var ve işlem başına bilgi notu yayımlıyor:

- `koln-bk.mfa.gov.tr/Mission/ShowInfoNote/{id}`
- `newyork-bk.mfa.gov.tr/Mission/ShowInfoNote/{id}`
- `sikago-bk.mfa.gov.tr/…` (vekaletname başvurusu PDF'i)

Ücretler yıllık **tarife PDF**'lerinde yayımlanıyor (ör. "T.C. New York
Başkonsolosluğu 2025 harç tarifesi").

**Tuzak:** bilgi notu id'leri temsilcilik başına farklı ve tahmin edilemez —
ajan her biri için indeksi taramak zorunda. Alan adı deseni de tutarsız:
`newyork-bk` (tire) ama arama sonuçlarında `newyork.bk` (nokta) de geçiyor;
ajan adresi **doğrulamadan kullanmayacak**.

## 3. Kapsam ve öncelik

Tam matris uygulanamaz: (14 DE + 9 US + 1 KG) × 15 işlem = **360 içerik**.

### Pilot parti (Faz A) — 7 temsilcilik × 6 işlem = 42 içerik

**Temsilcilikler** (Türk nüfusu ve talep yoğunluğuna göre):

| Ülke | Temsilcilik | Gerekçe |
|---|---|---|
| DE | Köln, Düsseldorf, Berlin | En büyük Türk nüfusu |
| US | New York, Los Angeles, Chicago | En büyük Türk toplulukları |
| KG | Bişkek | Sahibin ikamet ülkesi; Türk dünyası ayağının ilk adımı |

**İşlemler** — sahip onayına açık, önerilen altı:

1. **Vekaletname** — yıl boyu sürekli talep, en çok aranan
2. **Pasaport** — süre dolumu herkesi bulur
3. **T.C. Kimlik Kartı** — yenileme/kayıp
4. **Doğum Bildirimi** — yurt dışında doğan çocuk, zamana duyarlı
5. **Askerlik İşlemleri** — yurt dışındaki genç erkekler için yüksek kaygı
6. **Mavi Kart** — vatandaşlıktan çıkanlar (özellikle Almanya) için kritik

> Önceki büyüme önerisinde *evlenme bildirimi* ve *vefat işlemleri* vardı.
> Askerlik ve Mavi Kart'la değiştirildi: ikisi de diasporada daha yüksek hacimli
> ve daha çok yanlış bilgi dolaşan konular. **Bu seçim sahibin onayına açık.**

### Sonraki partiler (Faz B, C…) — pilot ölçüldükten sonra

Pilot yayına girip Search Console verisi geldikten sonra genişletme kararı
verilir. Ölçüm görmeden 360 içerik üretmek, işe yaramadığını 360 içerik sonra
öğrenmek demektir.

## 4. Ajan takımı

Workflow, dört fazlı. Her faz bir öncekinin çıktısını daraltır.

```
Faz 1  KEŞİF        7 ajan (temsilcilik başına 1)
       → her temsilciliğin resmî site adresini DOĞRULA (varsayma)
       → bilgi notu indeksini tara, 6 işlem için kaynak URL'lerini çıkar
       → çıktı: {temsilcilik, işlem, kaynak_url} haritası + bulunamayanlar

Faz 2  ARAŞTIRMA    bulunan her (temsilcilik × işlem) için 1 ajan
       → YALNIZ o URL'i oku
       → evraklar[], sure_metni, ucret_metni çıkar
       → sayfada OLMAYAN alanı BOŞ bırak (tahmin YASAK)
       → gördüğü ifadeyi kanıt olarak kaydet

Faz 3  DOĞRULAMA    her taslak için 1 BAĞIMSIZ ajan
       → aynı URL'i kendisi açar, iddiaları tek tek sınar
       → uyuşmayan alan DÜŞER (düzeltilmez — düşer)
       → çıktı: onaylanan alanlar + reddedilen alanlar + gerekçe

Faz 4  PAKET        1 ajan
       → sahibin okuyacağı markdown inceleme paketi
       → her madde: önerilen içerik + kaynak URL + kanıt + neyin boş kaldığı
```

### Halüsinasyona karşı dört kapı

Bu planın en kritik kısmı. Konsolosluk bilgisi uydurmak, hiç içerik
olmamasından kötüdür.

1. **Tahmin yasağı.** Ajan yalnız açtığı sayfadan yazar. Bulamadığı alan boş
   kalır. "Genellikle 2 hafta sürer" gibi genel bilgi YAZILMAZ.
2. **Bağımsız doğrulama.** İkinci ajan aynı kaynağı sıfırdan okur; uyuşmayan
   alan düzeltilmez, **düşer**.
3. **Yayın kapısı (kodda).** `rehber:yayinla` komutu eksik `sure_metni`/
   `ucret_metni` olan ya da kaynak URL'i genel adres olan kaydı **yayınlamayı
   reddeder**. K7 şu an yalnız bir belgede yazılı; bu onu koda gömer.
4. **Sahip onayı.** Paket okunmadan hiçbir şey yayına girmez.

> **Dürüst sınır:** 1-3 arası kapılar *eksik* bilgiyi engeller. Kaynakta gerçekten
> yazan ama ajanın yanlış okuduğu bir ücreti hiçbir otomatik kapı yakalayamaz —
> son güvence sahibin gözü. Paket bu yüzden her iddianın yanına kaynak
> bağlantısını koyuyor: doğrulama tıklama mesafesinde olsun.

## 5. Kod işleri (Claude)

| # | İş | Neden |
|---|---|---|
| K1 | US + KG temsilcilik seeder'ı | Şu an `temsilcilikler` tablosunda yalnız DE var (14 kayıt) |
| K2 | `rehber:yayinla` komutu + yayın kapısı | Eksik içerik yayınlanamasın (yukarıdaki 3. kapı) |
| K3 | İnceleme paketi → içe aktarma komutu | Onaylanan içerik elle 42 kez girilmesin |
| K4 | Liste sayfalarında "doğrulandı" rozeti | Ziyaretçi hangi içeriğin taze olduğunu listede görsün |
| ~~K5~~ | ~~Bayat içerik uyarısı Kâhya raporuna~~ | **ZATEN VARMIŞ** — aşağıya bak |

K4 pilot yayına girdikten sonra; K1-K3 pilottan önce.

> **K5 YAPILMASI GEREKMEDİ (2026-08-04 ölçümü).** Bayatlık uyarısı zincirin
> tamamıyla zaten kuruluymuş: `TemsilcilikIslemi::scopeBayat()` →
> `BekleyenIsler::topla()` içindeki `rehber_bayat` kuyruğu → `KahyaTeshisi` →
> `GunlukKahyaRaporu::neBekliyor()`. Modül kapalıyken kuyruk eklenmiyor, sayı
> sıfırken satır basılmıyor.
>
> Uçtan uca sınandı: iki kaydın `dogrulanma_tarihi`'i 100 gün geriye alınınca
> rapor şu satırı bastı — *"Doğrulaması eskimiş rehber içeriği: **2** — 90
> günden eski doğrulama — yayında"*.
>
> **Ders:** bu maddeyi "yapılacak" sanmamın sebebi plan belgesine bakıp koda
> bakmamaktı. `TemsilcilikIslemiResource` zaten kullanıcıya bu uyarıyı VAAT
> ediyordu ("Kâhya raporunda 'bayat' uyarısı çıkar") — vaadin karşılığı da
> yazılmıştı. Plandaki bir satır, kodun durumunun kanıtı değildir.

## 6. Sıra

```
1. K1  US + KG temsilcilikleri (kod, ~yarım gün)
2. K2  yayın kapısı (kod)
3. Faz 1 keşif (ajan takımı) → kaynak URL haritası
   ↳ KARAR KAPISI: kaç işlem için gerçekten kaynak bulundu?
     Yarısından azsa plan daralır, ajan israfı olmaz.
4. Faz 2+3 araştırma ve doğrulama (ajan takımı)
5. Faz 4 paket → SAHİP ONAYI
6. K3 içe aktarma + yayın
7. Ölçüm (Search Console, 2-4 hafta) → genişletme kararı
```

## 6.5 KARARLAR (2026-08-04, sahip)

> **KARAR · 2026-08-04 · İşlemler ONAYLANDI** — vekaletname, pasaport, T.C.
> kimlik kartı, doğum bildirimi, askerlik işlemleri, mavi kart.
>
> **KARAR · 2026-08-04 · Ücret YAYINLANMAYACAK** — yıllık tarifeyle değişiyor,
> bayatlaması en muhtemel alan. Ziyaretçi güncel tarifeyi resmî kaynaktan görür.
> Yayın kapısı bu yüzden ücret aramaz (`RehberYayinKapisiTest` mühürlüyor).
>
> **KARAR · 2026-08-04 · Temsilcilikler ONAYLANDI** — DE Köln/Düsseldorf/Berlin,
> US New York/Los Angeles/Chicago, KG Bişkek.
>
> **KARAR · 2026-08-04 · Oş UYGULANAMADI (fahri)** — sahip "Bişkek ve Oş" dedi
> ama Oş'taki temsilcilik **fahri başkonsolosluk**; pasaport/vekaletname/nüfus
> işlemi YAPMIYOR. Rehberde işlem yapan temsilcilik olarak göstermek insanı
> işini yaptıramayacağı adrese yollamak olurdu. Kırgızistan'da bu işlemler
> Bişkek Büyükelçiliği'nde yapılıyor. Model de "fahri" türünü tanımıyor.
> *Sahip isterse ileride "burada işlem yapılmaz, en yakın temsilcilik Bişkek"
> notuyla bilgi amaçlı bir kayıt eklenebilir — ayrı karar.*

## 6.6 UYGULANDI (2026-08-04)

- **K1 tamam** — `RehberTemsilcilikleriSeeder`: ABD 7 (1 büyükelçilik + 6
  **kariyer** başkonsolosluğu; fahri olanlar bilerek yok) + KG 1 (Bişkek).
- **K1'de beklenmeyen onarım** — Almanya'nın **14 adresinin tamamı kırıkmış.**
  `RehberAlmanyaSeeder` adresleri bir desenden üretmiş (`{sehir}.bk.mfa.gov.tr`,
  noktayla) ve docblock'unda "sahip teyit etmeli" demiş; teyit edilmemiş.
  Ölçüldü: **noktalı biçim hiç çözülmüyor**, doğrusu tireli (`koln-bk`).
  Temsilcilik sayfasındaki "Resmî siteye git" bağlantısı 14 temsilcilikte de
  hiçbir yere gitmiyordu. Onarıldı.
- **Desen üretilemez, ölçülmeli:** `berlin-be` · `biskek-be` ama
  `washington-emb` (`-be` çözülmüyor) · `sikago-bk` ve `munih-bk` (Türkçe
  yazım). Her alan adı tek tek HTTP ile denendi.
- **K2 tamam** — `rehber:yayinla` komutu + yayın kapısı. Ölçüt: evrak listesi
  dolu · kaynak adresi işleme özel (genel `konsolosluk.gov.tr` reddedilir) ·
  doğrulanma tarihi dolu. Ücret ve süre **aranmaz** (sahip kararı).
  `--rapor` ile kuru çalıştırma. 7 test.
- **Kapının ilk ölçümü:** 209 taslak aday, **0 hazır**. Yani bugün panelden
  yanlışlıkla bile eksik içerik yayınlanamaz.

## 6.7 FAZ 1 KEŞİF SONUCU (2026-08-04, 8 ajan, ~7 dk)

### Kapsama

| Ölçüt | Sayı | Oran |
|---|---|---|
| Hedef hücre (7 × 6) | 42 | %100 |
| **Kesin** kaynak | 33 | %78,6 |
| Muhtemel kaynak | 4 | %9,5 |
| Kaynak **yok** | 5 | %11,9 |

**Karar kapısı eşiği (%50) rahatça aşıldı → araştırma fazına GEÇİLİR.**

Ama daha keskin okuma: **Bişkek hariç 6 temsilcilik × 6 işlem = 36 hücrenin
35'i kapsanıyor (%97).** Yani plan daralmıyor, **tek bir temsilcilik planın
dışına düşüyor.**

### KARAR — Bişkek içerik üretiminden ÇIKARILDI

> **KARAR · 2026-08-04 · Bişkek KAPSAM DIŞI (kaynak yok)** — Bişkek
> Büyükelçiliği'nin `/Mission/InfoNotes` indeksi **tamamen boş** ("No records",
> üç dilde de). 262 duyurunun tamamı tarandı; tek bir bilgi notu yok. Elde
> kalan kayıtlar e-pasaporta geçiş dönemine (2010-2012) ait **eskimiş
> duyurular** — güncel bilgi kaynağı değil, tarihî kayıt. *Bu iddia ajanın
> raporuyla yetinilmeyip elle ayrıca doğrulandı.*
>
> Kaynak olmadan içerik yazmak, bu keşfin engellemek için yapıldığı hatanın ta
> kendisi olurdu. Kırgızistan için tek dürüst çıktı: **"Bu temsilcilik işlem
> bilgilerini kendi sitesinde yayınlamıyor; başvuru bilgisi merkezî
> konsolosluk.gov.tr üzerinden"** diyen bir yönlendirme kartı.
>
> *Sahip Kırgızistan'da yaşadığı için bunu özellikle istemişti — istek geçerli,
> kaynak yok. Merkezî konsolosluk.gov.tr'den içerik üretmek ayrı bir karar
> (o zaman "temsilcilik rehberi" değil "ülke rehberi" olur).*

> **KARAR · 2026-08-04 · Chicago pasaport BOŞ BIRAKILACAK** — güncel bilgi notu
> yok, yalnız 2010-2012 duyuruları. Diğer 5 işlem yazılır.
>
> **KARAR · 2026-08-04 · Chicago askerlik DARALTILDI** — mevcut not yalnızca
> *öğrenci ertelemesini* kapsıyor, dövizle askerlik yok. İçerik o kapsamla
> yazılır, "askerlik işlemleri" diye genellenmez.

### Sentez ajanının kendi etiketine getirdiği eleştiri (önemli)

New York ve Los Angeles'ta "kesin" işaretli hücrelerin çoğu aslında **yalnız
PDF form listesi** — form varlığı "bu temsilcilik bu işlemi yapıyor"u kanıtlar
ama **"nasıl yapılır"ı anlatmaz.**

**Gerçek zengin metin kaynağı sayısı ~23**, 40 değil. Planlama bu sayıya göre
yapılmalı. NY/LA için içerik ya PDF'ler okunarak üretilir ya da "hangi form
gerekli + randevu nereden" düzeyinde tutulur; Almanya temsilcilikleri gibi
süreç anlatımına girilmez.

### Araştırma fazı için teknik tuzaklar (ölçüldü)

1. **HTML `<title>` hepsinde aynı ve jenerik.** Gerçek başlık sayfa
   gövdesinde — başlık asla `<title>`'dan alınmayacak.
2. **`ShowInfoNote` id'leri sıralı değil ve tahmin edilemez** (374xxx–418xxx).
   İndeks üzerinden yürümek zorunlu; URL üretilemez.
3. **Vekaletname üç Almanya temsilciliğinde de ayrı sayfa değil** — "Noterlik
   İşlemleri" notunun içinde bir bölüm. Çıkarım sayfa değil **bölüm**
   düzeyinde yapılmalı.
4. Aynı sayfa birden çok işlemi kapsayabiliyor (LA: pasaport+kimlik,
   NY: kimlik+doğum).

### Genişleme uyarısı

Bişkek bir istisna değil, **muhtemelen kural**: küçük büyükelçiliklerde boş
`InfoNotes` indeksi beklenmeli. Almanya/ABD oranı (%97) genele yansıtılamaz.
Yeni ülke eklenmeden önce aynı keşif tekrarlanmalı.

### Sıradaki kapsam

**6 temsilcilik × 6 işlem = 35 hücre** (Chicago-pasaport hariç).

## 7. Sahibin karar vermesi gerekenler

1. **İşlem seçimi** — önerilen altı uygun mu? (askerlik/mavi kart ↔ evlilik/vefat)
2. **Temsilcilik seçimi** — Köln/Düsseldorf/Berlin + New York/LA/Chicago + Bişkek
3. **Kırgızistan'da Oş** başkonsolosluğu da eklensin mi, yalnız Bişkek mi?
4. **Ücret bilgisi yayınlansın mı?** Yıllık tarifeyle değişiyor ve bayatlaması
   en muhtemel alan. Seçenek: hiç yayınlamamak ve "güncel tarife için resmî
   kaynağa bak" demek. Bu, bayatlık riskini tamamen ortadan kaldırır.

## 8. Kapsam dışı (bilinçli)

- **Vize/oturum işlemleri** — Türk vatandaşının konsolosluk işlemi değil, hedef
  kitlenin sorusu da bu değil.
- **Hukuki danışmanlık niteliği taşıyan yorum.** Rehber *işlem tarif eder*,
  tavsiye vermez.
- **Diğer 11 işlem türü ve kalan temsilcilikler** — pilot ölçülene kadar.

İlgili: `docs/plans/2026-08-01-ulke-adaptif-rehber-tasarimi.md` (K1-K7
kararları), `docs/03-buyume-fikirleri.md` (2026-08-04 turu, 2. öneri).

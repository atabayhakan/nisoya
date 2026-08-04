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
| K5 | Bayat içerik uyarısı Kâhya raporuna | 90 günü geçen içerik sahibin sabah raporunda görünsün |

K4 ve K5 pilot yayına girdikten sonra; K1-K3 pilottan önce.

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

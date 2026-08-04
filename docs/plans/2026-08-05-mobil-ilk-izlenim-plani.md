# Mobil İlk İzlenim — Plan (2026-08-05)

Sahip isteği: *"Mobil görüntülemede dikkat çekici bir ilk izlenim projesi.
Ana sayfaya giren kullanıcı ilk 3 saniyede nelerden etkilenir? Daha önce
yapılmamış bir şey yapan kişiler, siteden etkilenen ya da güvenen kişilere
ihtiyaç duyar — herkes altın değerinde."*

5 ajanlı dış araştırma (ilk 3 saniye · soğuk başlangıç güveni · mobil hero ·
diaspora güveni) + canlı ölçüm. Bu plan onun çıktısı.

---

## 0. Baştan söylenmesi gereken şey

**"Etkileyici" olmak, boş bir pazaryerinde ters çalışır.**

Gösterişli bir ilk ekran bir vaat verir. Ziyaretçi ikinci ekranda 3 gerçek
ilanı görünce o vaadin karşılığı olmadığını anlar. Güven kaybını yaratan şey
boşluğun kendisi değil, **vaat ile karşılaşılan boşluk arasındaki farktır**.

Yani: sade ve dürüst bir ilk ekranla karşılanan 3 ilan, parlak bir ilk ekranla
karşılanan 3 ilandan **daha az** zarar verir.

Ve hedef kitle bu farkı ölçen kitlenin ta kendisi. Göçmen topluluklarında
alışılmış kanal (WhatsApp/Facebook grupları) **dolu ama güvenilmiyor** —
Kolombiya'daki Venezuelalı göçmenler üzerine 174 grup / 7.860 kullanıcılık bir
çalışmada iş ilanlarının yaklaşık yarısı sahte çıkıyor.

> **Nisoya doluluk yarışını kazanamaz. Dürüstlük yarışını kazanabilir.**
> Bu bir teselli değil, stratejik konum. Aşağıdaki her madde buradan çıkıyor.

## 1. "İlk 3 saniye" aslında tek bir an değil

| Zaman | Ne oluyor | Kaynak |
|---|---|---|
| 0–50 ms | Estetik yargı, bilinçsiz: "kalabalık mı, tanıdık mı?" | Lindgaard 2006; Tuch/Google 2012 |
| 1–3 sn | **"Bu ne?"** | NN/g |
| 3–10 sn | **"Bana uygun mu?"** — terk kararının yoğunlaştığı bant | NN/g |
| 10 sn+ | "Güvenilir mi?" — içerik okundukça | Sillence 2004 |

**Kritik sonuç: güven ilk 3 saniyede KAZANILMAZ, sadece KAYBEDİLİR.** İlk
saniyeler bir eleme filtresidir. İlk ekranın işi "güven vermek" değil,
**elenmemek**.

## 2. Bugünkü ilk ekran — teşhis (390px'te ölçüldü)

### Doğru olanlar

| Piksel | Öge | Neden |
|---|---|---|
| 113 | "🌍 Yurt dışındaki Türkler için" | "Bana uygun mu"nun yarısını ilk fiksasyonda cevaplıyor. Tonu da doğru — bayrak yığını değil. |
| ~380 | Arama kartı fold içinde | Baymard testinde arama modülü deneklerin %78'inde fold altında kalıyor; sende bu hata yok. |
| Genel | Prototipik düzen | Prototipiklik ölçülmüş bir ilk izlenim değişkeni; "yaratıcı" hero denenmemiş. |
| Alt | Sabit sekme çubuğu, "İlan Ver" içinde | Kullanıcıların %49'u telefonu tek elle tutuyor; alt üçte bir kolay bölge. |

### Yanlış olanlar

**a) H1 ürünün ne olduğunu söylemiyor (167px).** *"Tarif etmeye çalışma"* iyi
bir cümle ama bir **duygu** cümlesi. Tanım 250px'teki paragrafta bekliyor.

**b) O paragraf okunmuyor.** Ziyaretçi kelimelerin en fazla %28'ini okuyor;
göz izlemede deneklerin %79'u tarıyor, %16'sı okuyor. Mobilde baskın desen
"layer-cake": başlıklar okunur, gövde atlanır. **Sitenin ne olduğunu anlatan
tek cümle, okunmama olasılığı en yüksek biçimde yazılmış.**

**c) H1 ile arama arasında ~213px ölü mesafe.** Thurmtack (en yakın analog)
H1 ile arama arasına hiç paragraf koymuyor; mesafe ~104px.

**d) Kapsam çipleri 639px'te ve sadece iki tane.** Sitenin kapsamı (hizmet +
eşya + iş ilanı + rehber) iki çiple temsil edilemiyor. Baymard: mobil ana
sayfaların %42'si kapsamı doğru aktarmıyor.

**e) Kırmızı "Acil" butonu ekranın en pahalı pikselini yiyor.** En yüksek
görsel ağırlıklı öge, ürünün ne olduğunu anlatmıyor, ve 880px'te zaten bir
"Acil Yardım" kategori kartı var.

**f) Hız ölçülmemiş.** Hedef: **LCP ≤ 2,5 sn** (saha, 75. persentil).
Sayfa yüklenmeden tasarım yargıya girmiyor.

## 3. ⚠️ ÇELİŞKİ — bu bir tasarım sorunu değil, KURAL İHLALİ

Ekranda 100 piksel arayla iki öge birbirinin tersini söylüyor:

- **711px:** "Şehrinde ilk ilanı sen ver" → *burası boş*
- **~780px:** "🇩🇪 Almanya — 12 aktif ilan" → *burası dolu*

**Kök neden tek satır.** `NabizService::countryActivity()` (satır ~163) demo
ilanları süzmüyor; şerit süzüyor. Aynı eksik koşul **üç yerden** sızıyor:
mobil kanıt şeridi · masaüstü "şu an nerede ilan var" kartı · nabız haritası.

Nabız haritasının altında aynen şu yazıyor: *"Nokta boyutu, o ülkedeki aktif
ilan sayısını yansıtır — temsili değil, gerçek veri."* Yani sayfa, sahte olan
bir sayının altına "gerçek veri" yazıyor.

İroni: `hero.blade.php`'nin kendi yorumunda *"Güven üzerine kurulu bir
pazaryerinin ilk ekranında uydurma grafik olamaz"* yazıyor ve sahte grafik
bilerek kaldırılmış. **Aynı kural bir satır aşağıda unutulmuş.**

**Zararı neden büyük:** web güvenilirliği yargılarının **%46,1'i "tasarım
görünümü"ne** dayanıyor (Fogg, 2.500+ katılımcı) — ziyaretçi içeriği okumadan,
yüzeyin kendi içinde tutarlı olup olmadığından karar veriyor. Ve bu kitle
şişirilmiş sayıya karşı özellikle hassas.

**Çözüm iki katmanlı:**
- Süzgeç **zorunlu** (kural ihlali kapanmalı) — ama tek başına yetmez.
- Süzgeç eklenince ekranda "Almanya — 3 aktif ilan" yazar; bu **daha kötü**:
  düşük sayı negatif sosyal kanıttır. **Sayı düşükken sayı gösterme** —
  yerine şehir isimleri.

## 4. Önerilen ilk ekran (390 × 844)

```
  0–56   HEADER: logo + arama ikonu. Bu kadar.
         → "Acil" ÇIKAR (kategori kartı zaten var)
         → tema değiştirici ÇIKAR (menüye)
  ~88    EYEBROW: 🌍 Yurt dışındaki Türkler için — ücretsiz, Türkçe
  ~120   H1 (tanımlayıcı): "Şehrindeki Türkçe konuşan ustayı,
         hocayı, nakliyeciyi bul."
         altına küçük: "Tarif etmeye çalışma. Türkçe anlat, iş bitsin."
         (slogan ölmüyor, bir basamak iniyor)
  ~230   KAPSAM ÇİPLERİ (paragrafın YERİNE):
         Nakliyeci · Hoca · Tamirci · Kuaför · Tercüman ·
         İkinci el · İş ilanı · Ülke rehberi
  ~310   ARAMA KARTI — görünür ama BASKIN DEĞİL
  ~460   TEK SATIR GÜVEN: "Nisoya'dan para geçmez. Komisyon yok,
         aracı yok." · [Dolandırılmamak için 5 kural →]
  ~515   YERELLİK, SAYISIZ: 🇩🇪 Almanya'dasın — Berlin · Hamburg · Köln
  ~575   REHBER GİRİŞİ (yalnız DOĞRULANMIŞ sayfa varsa)
  ~640+  KATEGORİ KARTLARI — kasten YARIM görünsün (fold'dan taşsın)
```

**Arama görünür ama baskın olmasın — en ince karar.** Baymard: arama çok
belirginse denekler birincil strateji olarak aramayı seçiyor. 3 gerçek ilanla
arama ≈ garanti sıfır sonuç, ve siteler sıfır-sonuç sayfasını %68 oranında
çıkmaz sokak bırakıyor. **Bugün doğru varsayılan davranış GEZİNMEDİR.**
Envanter büyüyünce aramanın ağırlığı artırılır.

**Yarım görünen kategori sırası:** üstte az içerik + güçlü CTA sayfayı bitmiş
gösterir ("yanlış dip") ve kaydırmayı öldürür. `100vh` yerine `100dvh`/`svh`.

## 5. "İlk ilanı sen ver" — ilk izlenim olarak YANLIŞ

**İkinci temas olarak güçlü, ilk izlenim olarak zayıf.** Üç sebep:

1. Ziyaretçiye hiçbir şey vermeden **emek istiyor**; ilk ziyaretçilerin ezici
   çoğunluğu harekete hazır değil.
2. Boşluğu itiraf ediyor ama karşılığında bir şey vermiyor. NN/g'nin
   boş-durum kılavuzu sırayı ters koyuyor: önce *neden boş / ne zaman dolacak*,
   sonra eylem.
3. Gelen trafiğin çoğu **talep tarafı**; onlara dolaylı olarak "aradığın şey
   burada yok" diyor.

> "Arza odaklan" tavsiyesi doğru — ama o bir **operasyon kararıdır, ilk ekran
> mesajı değil.** Airbnb kurucuları 2009'da kapı kapı gezip ilan fotoğrafı
> çekti. Ana sayfaya afiş asmak arz stratejisi uygulamak değildir.

**Nereye gitmeli:** boş arama sonucu ekranı · kategori sayfası dibi · ana
sayfanın altında sakin bir blok. Ve metin boşluğu değil **kazancı** anlatsın:
*"Şehrinde seni Türkçe arayanlar var — 2 dakikada eklen, ücretsiz."*

## 6. 3 gerçek ilanla güven nasıl verilir (önem sırasıyla)

1. **Türkçe — uçtan uca.** CSA Research (29 ülke / 8.709 kişi): %40'ı başka
   dildeki siteden **asla** alışveriş yapmıyor; %65'i kalitesi düşük olsa bile
   kendi dilini tercih ediyor. Tek gerçek farklılaştırıcın ve **bedava**. Ama
   yarım bırakılırsa en pahalı hata: tek İngilizce hata mesajı, çeviri kokan
   form uyarısı, İngilizce 404 → "burası Türkçeleştirilmiş" hissi.
2. **"Nisoya'dan para geçmez."** Göçmen hedefli dolandırıcılığın merkezinde
   para transferi var. **Dikkat: "ücretsiz" bunun yerini tutmuyor** — ücretsiz
   *fiyat* der, para geçmez *risk* der. Üstelik ölçülmüş "zero-price effect":
   ücretsiz hizmet daha **düşük** kaliteli algılanabiliyor; sebebini
   açıklamazsan ziyaretçi boşluğu "gizli tuzak var" diye doldurur.
3. **Kurucu bloğu — gerçek ad, yüz, şehir.** İtibar sistemi yokken elde kalan
   tek ölçülmüş kaldıraç. Stanford Web Credibility (4.500+ katılımcı):
   "arkada gerçek insanlar olduğunu göster" doğrudan puanı yükseltiyor.
   Kısa tut; etki "gerçek insan var" sinyalinden geliyor, retorikten değil.
4. **"Ne yapmıyoruz" kutusu.** Craigslist güveni tam olarak bunu ilan ederek
   kurdu. Ardından **dürüst olumsuz**: "Bu yüzden parayı biz garanti
   etmiyoruz — ödemede şuna dikkat et →". *Blemishing effect* (JCR 2012):
   olumlu tablonun ardına küçük dürüst bir olumsuz eklemek, aceleci okuyucuda
   olumlu izlenimi **artırıyor**.
5. **Rehber katmanı = tek-oyunculu mod.** Pazaryerin boş, rehberin dolu.
   Ağ etkisi olmadan işe yarayan katman. **AMA doğrulanmamış 210 taslağı öne
   çıkarma** — yanlış konsolosluk bilgisi boş pazaryerinden çok daha ağır.
6. **Arz çağrısı yerine TALEP KAYDI:** *"Ne arıyorsun? Bulunca haber
   vereyim."* Ziyaretçiden emek istemek yerine söz veriyor ve arzı hangi
   şehirde toplayacağını **sana** söylüyor.

## 7. YAPILMAYACAKLAR

1. **Büyük görsel/video hero, slider.** LCP'yi bozar, "yanlış dip" yaratır.
   Yaratıcılığı **kopyada ve renkte** kullan, düzende değil.
2. **Herhangi bir uydurma sayı.** "Şu an 12 kişi bakıyor", üye sayacı, sahte
   yorum, stok fotoğraflı "mutlu gurbetçi". Sahte grafik bir kez bilerek
   kaldırıldı — aynı kapıyı başka kılıkta açma.
3. **Demo ilanları "dolu görünsün" diye vitrinde tutmak.** Etiket ilk
   izlenimden SONRA okunur ve **tek fark ediliş 3 gerçek ilanı da şüpheye
   düşürür**.
4. **"Almanya — 3 ilan" yazmak.** Çelişkiyi çözer ama yerine negatif sosyal
   kanıt koyar.
5. **"Güvenli ödeme", kalkan/kilit ikonu.** Nisoya işlemi garanti edemez;
   ima etmek hukuki risk + onarılamaz itibar kaybı.
6. **Arkasında süreç olmayan rozet.** Rozet bir iddiadır; süreç yoksa sahte veri.
7. **Rozet yığını.** Fold üstünde tek satır yeter.
8. **Doğrulanmamış rehber taslaklarını öne çıkarmak.** En yüksek riskli madde.
9. **Aidiyeti sembolle kurmak.** Bayrak yığını, milliyetçi/dinî imge. Diaspora
   homojen değil. **Sembol dışlar, dil ve gündelik dert birleştirir.**
10. **Tutulamayacak vaat.** "Her ilan elle onaylanır", "24 saatte cevap" —
    vaadi kapasitene göre yaz.
11. **İlk ekranda kayıt duvarı.**
12. **Alt sekme çubuğunu örten çerez bandı** — birincil eylemi başparmak
    bölgesinden siler.
13. **Harici CDN.** Strict CSP planıyla çelişir, LCP'yi geciktirir.
14. **Blog istatistiklerine dayanarak karar vermek.** ("Tek CTA %371 daha iyi"
    türü rakamların izlenebilir birincil kaynağı yok.)

## 8. En küçük anlamlı ilk sürüm

### 🔴 A — Çelişkiyi kapat *(yarım gün, en yüksek getiri)*
1. `NabizService::countryActivity()` → `->where('is_demo', false)`
2. `nabiz_country_activity` önbelleğini temizle
3. Mobil kanıt şeridinde **sayıyı gösterme** → şehir çiplerine çevir
4. Test: *demo ilan hiçbir ana sayfa sayısına giremez*

**Neden birinci:** bu bir tasarım iyileştirmesi değil, **kural ihlalinin
kapatılması**. Çelişki dururken H1'i güzelleştirmenin anlamı yok.

### 🟠 B — H1'i çalıştır, paragrafı çipe çevir *(yarım gün)*
H1 tanımlayıcı olsun, slogan bir basamak insin; paragraf silinip kapsam çip
şeridine dönüşsün ve ~230px'e gelsin; 639px'teki iki çip bu şeride katılsın.

### 🟡 C — Tek satır yapısal güven *(bir saat)*
Arama kartının altına: **"Nisoya'dan para geçmez. Komisyon yok, aracı yok."**
· [Dolandırılmamak için 5 kural →]

### Sonra sırayla (bu turda değil)
Kırmızı "Acil"i header'dan indir · **boş arama sonucu ekranını yeniden yaz**
(bugünkü envanterle en çok görülecek ekran bu) · demo'yu vitrinden çıkar ·
kurucu bloğu + "ne yapmıyoruz" kutusu · arz şeridini fold altına indir ·
talep kaydı · LCP ölçümü.

## 9. Ölçüm — atlanırsa iş yapılmamış sayılır

Trafik klasik A/B için yeterli değil. Ama bir akşamda yapılabilecek gerçek bir
ölçüm var:

> **6–8 gerçek yurt dışı Türk'e** WhatsApp'tan ekran görüntüsünü **5 saniye**
> göster, kapat, iki soru sor:
> 1. "Bu site ne yapıyor?"
> 2. "Senin için mi?"
>
> **Başarı ölçütü:** 8 kişiden 6'sı *"şehrimdeki Türkçe konuşan kişileri
> bulma"* benzeri bir cevap versin.

**A ve B'den önce bir kez, sonra bir kez.** Aradaki fark, bu planın işe
yarayıp yaramadığının tek dürüst kanıtı.

## 10. Sahibin karar vermesi gerekenler

1. **A'ya onay** — çelişki kapatılsın mı? (Bence tartışmasız evet: kural ihlali.)
2. **B'ye onay** — H1 değişsin mi? Slogan hiyerarşide bir basamak iner,
   silinmez. Yeni H1 metnini birlikte netleştirebiliriz.
3. **C'ye onay** — "Nisoya'dan para geçmez" satırı ilk ekrana girsin mi?
4. **5 saniye testi** — A/B'den önce yapılacak mı? Yapılmazsa değişimin işe
   yaradığını ölçemeyiz.

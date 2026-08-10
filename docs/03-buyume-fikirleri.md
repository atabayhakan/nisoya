# Büyüme Fikirleri Günlüğü

Bu dosya, Nisoya'yı yurt dışındaki Türklere ücretsiz yollarla ulaştırmak için
düzenli çalışan bir araştırma ajanının çıktısıdır. Ajan hiçbir şeyi otomatik
yayınlamaz/paylaşmaz — sadece araştırır, analiz eder ve öneri yazar. Kullanıcı
(Hakan) önerilerden istediğini seçip kendisi uygular.

Her çalışma, en üste yeni bir tarihli bölüm ekler (en yeni en üstte).
Zaten denenmiş/uygulanmış fikirleri tekrar önermemek için önce bu dosyanın
tamamını oku.

## Karar defteri nasıl okunur

Bu dosyanın ilk turunda 6 öneri yazıldı ve hiçbirine karar verilmedi. Ajan her
pazartesi çalışıp yenilerini ekliyordu; neyin ÖNERİLDİĞİNİ biliyordu ama neyin
KABUL ya da RET edildiğini bilmiyordu. Sonuç: aynı fikrin tekrar önerilmesi ve
karar bekleyen bir yığın. Karar verilmeyen öneri, yazılmamış öneriyle aynı
değerdedir — üstelik dosyayı okumayı zorlaştırır.

Bu yüzden her önerinin altına şu biçimde tek satırlık bir karar yazılır:

> **KARAR · YYYY-MM-DD · YAP** — gerekçe. Sorumlu: kim. Son tarih: varsa.

Dört durum:

| durum | anlamı | ajan ne yapar |
|---|---|---|
| `YAP` | yapılacak, sırada | tekrar önermez |
| `ERTELE` | iyi fikir, şartı henüz oluşmadı | tekrar önermez; şart gerekçede yazılı |
| `KAPAT` | yapılmayacak | bir daha önermez (koşul değişirse neyin değiştiğini yazarak) |
| `YAPILDI` | uygulandı | tekrar önermez |

Karar satırı olmayan öneri "karar bekliyor" demektir. Karar bekleyen öneri
sayısı 6'yı geçerse ajan o hafta yeni öneri YAZMAZ, sadece hatırlatır.

## ⚠️ ENVANTER KAPISI — her öneriden önce okunacak

2026-07-29 ölçümü: `nisoya.com/ilanlar` **toplam 3 ilan** döndürüyor ve üçü de
site sahibine ait. `?ulke=GB`, `?ulke=US`, `?ulke=DE` → **0**. `/isler`,
`/emlak`, `/vasita` → boş. Yani **üçüncü taraf envanteri sıfır**.

İlk turdaki altı önerinin **tamamı** sessizce "Nisoya ziyaretçi getirilebilecek
dolu bir pazaryeri" varsayıyordu ve hiçbiri bunu sınamamıştı. Bu yüzden her
öneri iki sınıftan birine girer:

**Geri DÖNÜLEMEZ öneriler** (topluluk yöneticisine mesaj, dernek ortaklığı):
tek atıştır. Linki açıp boş bir site gören yönetim üyelerine bunu paylaşmaz ve
ikinci kez bakmaz. Bu kanallar Nisoya'nın en değerli ve en az sayıdaki organik
varlıkları — envanter kapısı bunlar için **bağlayıcıdır**.

**Geri DÖNÜLEBİLİR öneriler** (içerik sayfası, ürün içi özellik): kimseye söz
vermez, yanlış giderse geri alınır. Envanter kapısına takılmazlar.

**Boş vitrini yalan olmaktan çıkaran tek çerçeve:** mesaj "gelin alışveriş
yapın" (talep) değil **"gelin ücretsiz ilan verin"** (arz) olmalı. Yeni bir
pazaryerinin arz çağrısı dürüsttür; talep çağrısı tutulamayacak bir vaattir.

**Ajan için kural:** trafik çekmeyi öneren her maddede, o bölgede bugün kaç
ilan olduğunu KONTROL ET ve öneriye yaz. Sıfırsa öneriyi arz çağrısı olarak
çerçevele ya da hiç önerme.

---

## Başlangıç durumu — 2026-07-09

**Zaten yapılanlar (tekrar önerme):**
- Teknik SEO: sitemap.xml (ilan/iş ilanı/şirket/profil dahil), robots.txt,
  meta/OG etiketleri, JSON-LD (WebSite, Organization, Product, Service,
  BreadcrumbList, Person, Organization, JobPosting), kanonik slug
  yönlendirmesi (301), WhatsApp paylaş butonları (ilan/profil/iş
  ilanı/şirket sayfalarında).
- Google AdSense doğrulaması (ads.txt).
- Header'da ziyaretçi ülkesine göre bayrak/ülke gösterimi (MaxMind
  GeoLite2, dış API çağrısı yok).
- Yetenek Havuzu (`/adaylar`) — opt-in aday arama.
- İş ilanları modülü (companies/job_listings/job_applications).

**Platform bağlamı:**
- Ücretsiz platform, pazarlama/reklam bütçesi YOK. Sadece organik/ücretsiz
  kanallar geçerli.
- Hedef kitle: dünya genelinde yurt dışında yaşayan Türkler (Avrupa, ABD,
  Avustralya, Körfez ülkeleri dahil — belirli bir bölgeye kilitli değil).
- Marka tonu: samimi, "kendi insanından" güven vurgusu, "Hizmet
  ücretsizdir 💛" — bu yüzden spam/bot gibi görünen otomasyon,
  toplulukların güvenini zedeler ve kaçınılmalı.

**Ajanın önerileri şu kategorilerde olmalı (öncelik sırasıyla):**
1. Belirli, isim verilebilir topluluklar/kanallar (örn. "X ülkesindeki
   Türkler" Facebook grubu, belirli subreddit'ler, Türk dernekleri,
   Türkçe YouTube/TikTok hesapları) — genel "sosyal medyada paylaş" gibi
   soyut tavsiyeler değil.
2. İçerik fikirleri (blog/SEO odaklı sayfalar, örn. "Almanya'da elektrikçi
   bulma rehberi") — arama trafiği çeker.
3. Ürün içi viral/referral mekanizmalar (davet sistemi, paylaşılabilir
   içerik, vb.).
4. Ortaklık/işbirliği fikirleri (Türk dernekleri, ATA'lar, yerel Türkçe
   gazete/radyo, influencer'lar) — ücretsiz/karşılıklı fayda temelli.

Her öneri için: ne, neden işe yarayabilir, tahmini efor (düşük/orta/
yüksek), ve varsa somut ilk adım.

---

## [2026-08-10]

**Envanter ölçümü (bu tur, canlıdan):** `/ilanlar` filtresiz **1 ilan** —
"Web Programlama e Web Sitesi Yapiyorum" (Bişkek, Kırgızistan, 3 gün önce).
İlan sahibi hesap adı **`nisoya`**, yani sitenin kendisi. `?ulke=DE` **0**,
`?ulke=GB` **0**, `?ulke=US` **0**. `/isler` boş, `/emlak` **0**,
`/vasita` **0**. → **Üçüncü taraf arz hâlâ SIFIR.** 2026-07-29'dan bu yana
üçüncü kez aynı sonuç. Envanter kapısı aynen geçerli.

Değişen tek şey vitrinin dürüstlüğü: PR #130 sonrası demo ilanlar sayıma
girmiyor, `[ÖRNEK]` kartları "Nisoya demo verisidir" ibaresiyle ayrı bir
şeritte duruyor. Yani vitrin artık yalan söylemiyor — ama boş.

**Bu turun ayırt edici bulgusu — rehber yayında ve artık sitenin ANA yüzeyi.**
`sitemap.xml`'de **59 URL var, bunların 36'sı ülke rehberi**:

- **DE:** Berlin (6 işlem), Köln (6), Düsseldorf (3) + 3 şehir hub'ı
- **US:** New York (6), Los Angeles (6), Chicago (3) + 3 şehir hub'ı

`/de/berlin/vekaletname` açılıyor, gerçek içerik var, `verified_at`
**04 Ağustos 2026**. Nisoya'nın indekslenebilir yüzeyinin **%61'i** artık demo
olmayan, gerçek kamu hizmeti içeriği. "Boş pazaryerine davet" sorunu yapısal
olarak çözülmüş durumda — geri dönülemez kanallara pazaryeri değil rehber
götürülebilir.

**İki öneri karar satırı olmadan uygulanmış (ölçüldü, canlıdan doğrulandı):**

- *2026-08-04 · öneri 2* (rehberi 18 sayfayla aç) → **yapılmış ve aşılmış.**
  Planda 3 Alman şehri vardı; canlıda 30 işlem sayfası + 6 hub, ABD dahil.
- *2026-08-04 · öneri 3* (WhatsApp paylaşım kartı) → **yapılmış.** İlan
  detayında **"Durumuma koy"** butonu canlıda (PR #79).

İkisine de **aynı gün `YAPILDI` satırı düşüldü** (bkz. 2026-08-04 bölümü),
yani o bölümde karar bekleyen 4 öneriden 2'si kapandı; geriye **öneri 1 (TGD)**
ve **öneri 4 (Metropol FM)** kaldı. Aşağıdaki 4 yeni öneriyle birlikte karar
bekleyen toplam **6** — sınırın tam üstünde. Bu 6'ya karar verilmezse ajan
gelecek hafta yeni öneri yazmaz, yalnız hatırlatır.

---

**1. Posta kodu → bağlı temsilcilik bulucu** *(kategori 2 + 3: SEO + ürün içi)*

*Ölçüm:* `/de/berlin` sayfasında ne bir bulucu var, ne de diğer Alman
şehirlerine bağlantı — tek çıkış "Almanya Rehberi" geri linki. Yani 36 sayfa
birbirini görmüyor.

Türkiye'nin Almanya'da Berlin Büyükelçiliği'ne ek olarak **Berlin, Düsseldorf,
Essen, Frankfurt, Hamburg, Hannover, Karlsruhe, Köln, Mainz, Münih, Münster,
Nürnberg, Stuttgart** başkonsoloslukları var. Kritik nokta: bağlı olduğun
temsilcilik **oturduğun şehre değil, ikamet adresinin posta koduna** göre
belirleniyor.

*Neden işe yarayabilir:* "hangi başkonsolosluğa bağlıyım" arayan kişi işlem
yapmak üzeredir — bu, rehberin çekebileceği en yüksek niyetli sorgu. Nisoya
bugün bu soruyu hiç yanıtlamıyor: Berlin sayfasına düşen biri aslında
Hamburg'a bağlı olabilir ve bunu öğrenemez. Araç üç iş birden yapar:
(a) 36 sayfanın huni tepesi olur; (b) kapsamı **dürüstçe itiraf eder** —
"Hamburg'a bağlısın, o sayfa henüz bizde yok, resmî adres şurada";
(c) **talep ölçer** — hangi şehirlerin sorulduğunu saydığında, kalan 10
temsilcilikten hangisini yazacağını tahminle değil veriyle seçersin.
2026-08-04'teki öneri 2 zaten "getirmiyorsa kalan 192'yi doğrulamaya hiç
girişilmez" diyordu; bu araç o kapının ölçüm aleti.

*Dürüst uyarı:* posta kodu → temsilcilik eşlemesi resmî, tek parça bir tabloda
yayımlanmıyor ve yeni başkonsolosluk açıldığında sınırlar değişiyor. Araç
**kesin hüküm vermemeli**: "büyük olasılıkla X" + "randevu alırken
konsolosluk.gov.tr üzerinden adresine göre teyit et" satırı zorunlu.

*Efor:* orta. Eşleme eyalet düzeyinde başlatılabilir (posta kodunun ilk hanesi
≈ eyalet), ihtilaflı bölgeler elle düzeltilir. Kod tarafı küçük: tek form +
arama tablosu.

*İlk adım:* 13 başkonsolosluğun kendi "görev bölgesi" sayfalarından eyalet
listelerini topla, `/de` hub'ına tek kutu olarak koy, sorgulanan şehirleri
logla.

**2. Rehberdeki belge listesini paylaşılabilir yap** *(kategori 3: ürün içi
viral)*

Bugün `/de/berlin/vekaletname`'de gerekli belgeler listesi var ama sayfada onu
**yanına alma** yolu yok — ne WhatsApp'a gönderme, ne kaydetme. Oysa ilan
tarafında paylaşım altyapısı (PR #79) hazır ve canlıda çalışıyor.

*Öneri:* her işlem sayfasına **"Belge listemi WhatsApp'a gönder"** butonu.
Kart görseli bile gerekmiyor, düz metin yeter: "Berlin Başkonsolosluğu ·
Vekaletname · Gerekenler: 1… 2… 3… · Kaynak: nisoya.com/de/berlin/vekaletname
· Son doğrulama: 04.08.2026".

*Neden işe yarayabilir:* konsolosluk işlemi neredeyse hiç **tek kişilik**
değildir — vekaletname Türkiye'deki kardeş için alınır, doğum bildirimi eşle
beraber yapılır. O liste zaten aile sohbetine gidiyor; bugün **ekran
görüntüsü** olarak, kaynağı ve tarihi olmadan gidiyor. Buton, aynı davranışı
kaynaklı ve linkli hale getirir. Spam değil — paylaşan kişi kendi işini
paylaşıyor, marka tonuna ("kendi insanından") birebir uyuyor. Envantere hiç
bağlı değil, yani envanter kapısına takılmaz.

*Efor:* düşük-orta. Yeni bağımlılık yok.

*İlk adım:* tek işlem tipinde (vekaletname) pilot buton, tıklama sayısını ölç.

**3. Rehberin gerçekten indekslenip indekslenmediğini ölç** *(kategori 2:
diğer önerilerin ön koşulu)*

36 rehber sayfası yayında ama **bunların Google'da görünüp görünmediğini
kimse bilmiyor.** İki gözlem: (a) repoda `google-site-verification` etiketi
yok — Search Console ya kurulu değil ya da DNS ile doğrulanmış (bu repodan
görünmez, **önce bu teyit edilmeli**); (b) "nisoya.com vekaletname Berlin
başkonsolosluğu rehber" araması nisoya.com'u hiç döndürmedi, onun yerine
almanyadakiturkler.de'nin aynı konudaki sayfasını döndürdü. Tek sorgu kanıt
değildir, ama uyarı işaretidir.

*Neden işe yarayabilir:* bu, karar bekleyen bir önerinin **açıkça yazılı** ön
koşulu. 2026-08-04 · öneri 4 (Metropol FM) kelimesi kelimesine "öneri 2 yayına
girip rehber birkaç yüz ziyaret aldıktan sonra veriye dayalı bir açıyla
gidilirse şansı artar" diyor. Rehber yayına girdi; **o veri toplanmıyor.**
Ölçüm kurulmazsa Metropol FM kalıcı olarak beklemede kalır. Ayrıca "kalan 10
temsilciliğin hangisini yazayım" sorusunun cevabı da burada.

*Efor:* düşük (yarım saat, kod yok).

*İlk adım:* Search Console'da nisoya.com mülkü var mı bak; yoksa aç (DNS TXT
en kalıcısı), `sitemap.xml`'i gönder. 2 hafta sonra 36 URL'in kaçının
"İndekslendi" olduğuna ve hangi sorguların geldiğine bak. Bing Webmaster Tools
aynı sitemap'le ücretsiz — ikisi birden kurulabilir.

**4. almanyadakiturkler.de — karşılıklı içerik ortaklığı** *(kategori 1 + 4)*

almanyadakiturkler.de, Almanya'daki Türklere yönelik günlük güncellenen bir
rehber/haber portalı. Bu tur bakıldığında en son içeriği **10 Ağustos 2026**
tarihliydi — yani bugün; site canlı ve aktif. Kategorileri: Almanya'da yaşam,
maaşlar, göç/oturum, üniversite, finans, ulaşım, etkinlikler. Ve
**ilan/pazaryeri bölümü yok.**

*Neden işe yarayabilir:* bu, 2026-07-29'da Dubai Rehberi'ni ERTELE'ye
düşüren çıkar çatışmasının **tam tersi durumu**. Dubai Rehberi aynı kitleye
aynı içerikle rakipti; burada karşı taraf pazaryeri işine hiç girmiyor,
Nisoya da haber işine girmiyor. Örtüşme yalnız konsolosluk rehberinde ve orada
Nisoya'nın elinde onlarda olmayan bir şey var: **temsilcilik × işlem şeklinde
yapılandırılmış, tarih damgalı 36 sayfa.** Üstelik bu site zaten Nisoya'nın
hedef sorgularında görünüyor (bkz. öneri 3) — yani kitlesi doğrulanmış.

*Dürüst uyarı:* listedeki en zayıf halka bu. Günlük yayın yapan bir sitenin
yeni ve bilinmeyen bir siteye link vermek için sebebi olmayabilir; en olası
sonuç sessizliktir. Bu yüzden teklif "bizi paylaşın" değil **karşılıklı**
olmalı ve somut bir şey vermelidir.

*Efor:* düşük (tek e-posta). **Ön koşul: öneri 3.** Kendi sayfalarının
indekslenip indekslenmediğini bilmeden başkasına link önermek anlamsız.

*İlk adım:* şimdi değil. Search Console verisi geldikten sonra sitenin
iletişim sayfasından **tek kişiselleştirilmiş** mesaj: "konsolosluk işlem
sayfalarımızı kaynak gösterin, biz de rehber sayfalarımızdan sizin yaşam
rehberi içeriklerinize link verelim." ATAA dersi geçerli: toplu gönderim YOK.

---

### Ek — Lalafo ilan ekranı incelemesi (aynı gün, ajan turu dışında)

Sahip, Bişkek'te kullanılan **Lalafo**'nun ilan detay/satıcı ekranından üç
ekran görüntüsü paylaştı ve incelenmesini istedi. Bu, haftalık ajan turunun
çıktısı değil; ayrı bir istek. Buraya yazılmasının sebebi: incelemede
**alınmayan** maddelerin gerekçesi bir yerde durmazsa altı ay sonra aynı
fikirler sıfırdan tartışılır.

**Ekranın yaptıkları:** galeri sayacı · görüntülenme/favori sayısı · kategori
çipi · yan yana iki iletişim düğmesi (mesaj + ara) · boş kalmayan fiyat alanı
("pazarlıklı") · benzer ilan karuseli · satıcı kartında "%73 yanıt verir,
genelde 3 saat içinde" · çift tarih (yayınlanma + güncellenme) · ilan ID +
şikayet bayrağı · sabit alt barda **hazır cevap çipleri** · her ekranda ortada
duran büyük **+ Sat** düğmesi.

**Zaten Nisoya'da olduğu ölçülenler** (yani yeniden önerilmemeli): ortadaki
"İlan Ver" düğmesi mobil alt barda var · fiyat boşsa "Görüşülür" yazıyor ·
şikayet formu vardı (giriş yapmış kullanıcıya, kapalı bir bölüm içinde) ·
"İlan no NS-{id}" Vitrin şablonunda vardı.

> **KARAR · 2026-08-10 · YAPILDI** — iki madde uygulandı (PR #143, CI beş
> kontrolde de yeşil): **(1) hızlı cevap çipleri** — iletişim kutusu boş bir
> zorunlu metin alanıydı, çipler cümleyi kutuya düşürür ama **göndermez**;
> **(2) ilan numarası klasik şablona taşındı + misafire şikayet yolu açıldı**
> (şikayet bloğu tümüyle giriş şartına bağlıydı, giriş yapmamış biri şüpheli
> ilanı görüp hiçbir çıkışa sahip değildi). Çipler tek paylaşılan partial;
> testler her iddiayı **iki temada da** ölçüyor.

**Alınmayan maddeler ve nedenleri.** Ortak gerekçe şu: Lalafo'nunki
**likiditesi olan** bir pazaryerinin ekranı. Nisoya'da bugün üçüncü taraf ilan
sıfır (bkz. bu bölümün envanter ölçümü). Bunları şimdi kopyalamak boş odayı
dekore etmek olurdu — üstelik bazıları **boşluğu ilan ederdi**.

> **KARAR · 2026-08-10 · ERTELE — benzer ilanlar bloğu.** Kod zaten yazılı
> (`vitrin/listings/show.blade.php`) ama `Tema::vitrinMi()` ile kapalı ve
> canlıda klasik tema koşuyor. Kapatma bilinçli: ilan detayının **<25 sorgu
> bütçesi** var. Ayrıca gösterecek benzer ilan yok. **Şart:** aynı kategoride
> en az birkaç üçüncü taraf ilan biriksin; açılırken sorgu bütçesi ölçülsün.

> **KARAR · 2026-08-10 · ERTELE — görüntülenme sayacı.** Veri var
> (`listings.views_count` kolonu), gösterim yok. Bugün açılırsa her ilanda
> "0 görüntülenme" yazar; bu, ziyaretçiye site boş demenin en hızlı yolu.
> **Şart:** ilan başına görüntülenme anlamlı bir sayıya çıksın.

> **KARAR · 2026-08-10 · ERTELE — satıcı yanıt oranı/süresi kartı.** Nisoya'da
> bu verinin **hiçbir karşılığı yok** (ölçüldü: kodda yanıt oranı/süresi
> tutan hiçbir alan yok). Uydurulamaz — Gerçek Bilgi Kuralı: tasarım
> referansından yerleşim alınır, sayı alınmaz. Üstelik mesaj trafiği
> yokken hesaplansa da anlamsız çıkar. **Şart:** düzenli mesajlaşma başlasın,
> önce ölçüm yazılsın, sonra kart.

> **KARAR · 2026-08-10 · KAPAT — VIP rozetleri / ücretli öne çıkarma.**
> Lalafo'nun ekranındaki kartların çoğu "VIP" etiketli. Nisoya ücretsiz bir
> platform ve tonu "Hizmet ücretsizdir 💛" — ödemeli görünürlük bu vaadi
> doğrudan çeler. Gelir modeli kararı zaten alınmış durumda (bağış + reklam;
> komisyon yok). Koşul değişirse gerekçesi bu satırla birlikte yazılmalı.

> **KARAR · 2026-08-10 · KAPAT — içerik ortasına reklam bloğu.** Lalafo benzer
> ilanların hemen üstüne reklam koyuyor. Nisoya'da AdSense zaten var; ilan
> detayının gövdesine ikinci bir reklam yüzeyi eklemek, sayfanın tek işi olan
> "satıcıya ulaş"ı bulandırır.

---

## [2026-08-04]

**Envanter ölçümü (bu tur, canlıdan):** `/ilanlar` **31 ilan** — ama görünenlerin
**tamamı `[ÖRNEK]` demo**. Ülke kırılımı: DE 12, GB 4, US 1, **AE 0**.
`/emlak` **0**, `/vasita` **0**. Yani 2026-07-29'a göre vitrin *doldu* ama
**üçüncü taraf ilan hâlâ sıfır** — envanter kapısı aynen geçerli, geri
dönülemez kanallara giden her mesaj **arz çağrısı** olmalı.

**Bu turun ayırt edici gözlemi:** Nisoya'nın elinde artık demo olmayan,
gerçek bir varlık var — **Ülke Rehberi (Almanya)**. 14 temsilcilik × 15 işlem
türü = 210 içerik canlı veritabanında duruyor. Bu, "boş pazaryerine davet"
sorununu yapısal olarak çözüyor: geri dönülemez kanallara **pazaryerini
değil rehberi** götürürsek, karşı taraf linke tıkladığında demo ilan değil
gerçek bir kamu hizmeti görür. Aşağıdaki önerilerin üçü bu eksene oturuyor.
Almanya bugüne dek hiç hedeflenmemişti (önceki turlar: İngiltere, Körfez, ABD).

**⚠️ Ama 210 içeriğin tamamı TASLAK, yayında 0.** Öneri 1 ve 4'ün ön koşulu
öneri 2'dir. Rehber yayında değilken bu kanallara gidilirse elimizdeki tek
gerçek varlık da boş görünür.

---

**1. TGD — Türkische Gemeinde in Deutschland** *(kategori 1 + 4: topluluk
kanalı + ortaklık)*

Almanya'nın en büyük Türk çatı örgütü: **260 üye dernek**, eyalet örgütleri ve
meslek federasyonlarıyla birlikte. Merkez Berlin-Kreuzberg (Obentrautstr. 72,
10963), iletişim **info@tgd.de**, tel 030-896 83 81 0. 1995'te Hamburg'da
kuruldu, Bundestag lobi siciline kayıtlı (R002736) — yani kurumsal ve
doğrulanabilir.

*Neden işe yarayabilir:* Almanya ~3 milyonla en büyük Türk diasporası ve
Nisoya'nın rehber modülü **tam olarak Almanya için** yazılmış. TGD'ye gidecek
teklif "pazaryerimize gelin" değil, **"üyeleriniz için ücretsiz, reklamsız bir
konsolosluk işlem rehberi hazırladık — 14 temsilcilik, 15 işlem türü"**.
Bu, bir dernek bülteninin gerçekten paylaşabileceği türden bir içerik; ilan
sayısına bağlı değil, dolayısıyla envanter kapısına takılmıyor.

*Efor:* düşük (tek e-posta). **Ön koşul: öneri 2 — rehber yayında olmalı.**

*İlk adım:* `info@tgd.de`'ye kısa bir tanıtım; ilanlardan hiç söz etmeden
yalnız rehberi anlat, dönerse ikinci adımda pazaryeri arz çağrısı açılır.
ATAA dersini uygula: **çatı örgüte tek kişiselleştirilmiş mesaj**, üye
derneklere toplu gönderim YOK (gönderim itibarı riski).

**2. 210 taslağı "hepsini doğrula" yerine talebe göre 18'e indir**
*(kategori 2: SEO/içerik)*

Bugün rehberde **210 taslak, 0 yayın** var ve sahibin önündeki iş "210 içeriği
resmî kaynaktan doğrula" olarak duruyor. Bu iş bitmeyeceği için **hiçbiri
yayınlanmıyor** — modül canlıda ama SEO değeri sıfır.

*Öneri:* eşit muameleyi bırak, **3 temsilcilik × 6 işlem = 18 sayfa** ile aç.
Temsilcilikler Türk nüfusuna göre: **Köln, Düsseldorf, Berlin**. İşlemler
sürekli talep görenler: **vekaletname, pasaport, T.C. kimlik kartı, doğum
bildirimi, evlenme bildirimi, vefat ve cenaze işlemleri** (altısı da seeder'da
mevcut).

*Neden işe yarayabilir:* 18 sayfa bir oturumda (2-4 saat) doğrulanır, 210 asla
bitmez. Bu aramalar mevsimsel değil — "vekaletname Köln başkonsolosluk" tipi
sorgular yıl boyu tekrar eder ve arayan kişi **somut bir işlem** peşindedir.
Ayrıca 18 sayfa, şablonun gerçekten trafik getirip getirmediğini ölçmeye
yeter; getirmiyorsa kalan 192'yi doğrulamaya hiç girişilmez.

*Efor:* düşük (kod yok, panelden yayın kararı) + 2-4 saat içerik doğrulama.

*İlk adım:* panelde Ülke Rehberi → İşlem İçerikleri'nde bu 18'i filtreleyip
resmî temsilcilik sayfalarından doğrula ve yayına al. `verified_at` doldur
(K7 gereği 90 günde bir tazelenecek).

> **KARAR · 2026-08-10 · YAPILDI** — plan yalnız uygulanmadı, **aşıldı**:
> önerilen 3 Alman şehri yerine DE (Berlin, Köln, Düsseldorf) **ve** US
> (New York, Los Angeles, Chicago) yayında. Canlı `sitemap.xml`'de
> **30 işlem sayfası + 6 şehir hub'ı = 36 URL**; `/de/berlin/vekaletname`
> gerçek içerikli ve `verified_at` = 04.08.2026. Ölçüm: 2026-08-10 turu.
> **Ama kapının ikinci yarısı hâlâ açık:** bu öneri "getirmiyorsa kalan 192'yi
> doğrulamaya hiç girişilmez" diyordu ve *getiriyor mu* sorusu henüz
> ölçülmedi — bkz. 2026-08-10 · öneri 3 (Search Console).

**3. WhatsApp durumu için otomatik ilan paylaşım kartı** *(kategori 3: ürün
içi viral)*

Bugün `partials/share-buttons.blade.php` WhatsApp'a **düz metin linki**
gönderiyor; `og:image` ise ilanın kendi fotoğrafı — WhatsApp *durumunda*
kullanılamaz, çünkü durum dikey görsel ister.

*Öneri:* ilan başına sunucuda **1080×1920 dikey paylaşım kartı** üret
(ilan görseli + başlık + fiyat + şehir/ülke + `nisoya.com` + küçük QR) ve ilan
detayına tek bir **"Durumuma koy"** butonu ekle.

*Neden işe yarayabilir:* Diasporada WhatsApp durumu birincil yayın kanalı ve
paylaşan kişi **ilan sahibinin kendisi** — yani yayılım kendi tanıdık ağına
gider, spam değil, marka tonuna ("kendi insanından") birebir uyar. Üstelik bu
mekanizma **arz tarafını** çoğaltır: ilan veren kişiye yayma aracı verirsin,
o da kendi çevresinden ilan verenleri getirir. Envanter darboğazına doğrudan
dokunan tek ürün fikri bu.

*Efor:* orta — ama **görsel üretim yığını zaten var**: demo ilanlar için TTF
tabanlı görsel üretimi yapıldı (PR #67), aynı yaklaşım yeniden kullanılır,
yeni bağımlılık gerekmez.

*İlk adım:* tek ilan tipinde (hizmet) pilot buton; kartı istek anında üretip
kısa süreli cache'le, tıklama sayısını ölç.

> **KARAR · 2026-08-10 · YAPILDI** — PR #79 ile uygulandı; ilan detayında
> **"Durumuma koy"** butonu canlıda (2026-08-10 turunda
> `/ilan/112/…` üzerinden doğrulandı). Önerinin ilk adımındaki **tıklama
> ölçümü** ayrıca doğrulanmadı; ürün canlı ama etkisi hakkında elde veri yok.

**4. Metropol FM** *(kategori 4: ortaklık/medya)*

metropolfm.de — **Almanya'nın tek 24 saat Türkçe radyosu**, 1999'dan beri
yayında. Berlin 101.9 FM; 5 eyalet (Berlin, Kuzey Ren-Vestfalya, Baden-
Württemberg, Hessen, Bremen, Rheinland-Pfalz) ve 16 şehirde alınıyor,
Avrupa'da yarım milyondan fazla kişiye ulaşıyor; 18-49 yaş bandında %70,1
pazar payı bildiriyor.

*Neden işe yarayabilir:* Radyo reklam satar ama **haber değeri olan topluluk
içeriğini ücretsiz konuşur**. "Ücretsiz, reklamsız konsolosluk işlem rehberi"
bir sabah kuşağı için yeterince somut bir hizmet haberidir — özellikle
vekaletname/pasaport gibi dinleyicinin gerçekten sorduğu konular.

*Efor:* düşük-orta. **Dürüst uyarı:** bu, listedeki en zayıf halka — radyonun
ilgilenmesi için elimizde bir *hikâye* olması gerekiyor ve "yeni bir site
açıldı" hikâye değil. Öneri 2 yayına girip rehber birkaç yüz ziyaret aldıktan
sonra "Almanya'daki Türklerin en çok aradığı 5 konsolosluk işlemi" gibi
**veriye dayalı** bir açıyla gidilirse şansı artar.

*İlk adım:* şimdi değil. Öneri 2'nin ilk ölçüm verisi geldiğinde
metropolfm.de iletişim formundan içerik önerisi olarak sun.

---

## [2026-07-27]

**1. TUSU — Turkish Student Union of the UK** *(kategori 1: topluluk kanalı)*
İngiltere'deki ~48 üniversite Türk öğrenci derneğini çatısı altında toplayan
resmi birlik (tusu-uk.org, facebook.com/tusuuk). Her yıl Eylül-Ekim'de
binlerce Türk öğrenci ilk kez yurt dışına çıkıp ev kuruyor — ikinci el eşya
ve yerel hizmet ihtiyacı bu dönemde tepe yapıyor, tam Nisoya'nın kapsadığı
ihtiyaç. Efor: düşük. İlk adım: TUSU'nun Facebook sayfasına/yönetimine
kısa bir mesajla ulaşıp yeni gelen öğrencilere yönelik bir duyuru/post rica
etmek (sonbahar dönem başına denk getirilirse etkisi en yüksek olur).

*Doğrulama (2026-07-29):* Kurum gerçek ve canlı — Companies House 09973479
(aktif), Charity Commission 1189126, Facebook sayfası aynı gün paylaşım
yapmış. **Düzeltmeler:** adres artık **tusu.uk** (tusu-uk.org 301 ile
yönleniyor) · "48 dernek" TUSU'nun kendi *"ulaşmıştır"* ifadesi, doğrulanabilir
erişim **~6-12 üniversite** (Mart 2026 iftarı ~12, Şubat 2026 toplantısı 6) ·
iletişim **info@tusu.uk** üzerinden olmalı, Facebook DM en zayıf kanal ·
"binlerce ev kuran öğrenci" doğrulanamadı (bulunan 15.892 rakamı dil kursu
öğrencisi, ortalama kalış 4,8 hafta). Sonbahar takvimi doğrulandı: karşılama
haftaları 21 Eylül – 11 Ekim 2026.

> **KARAR · 2026-07-29 · YAP** — mesaj "gelin alışveriş yapın" değil **"üyeleriniz
> taşınırken sattıkları eşyayı ücretsiz listelesin"** (arz çağrısı) olarak
> çerçevelenecek; İngiltere'de 0 ilan olduğu için talep çağrısı tutulamayacak
> bir vaat olurdu, arz çağrısı ise yeni bir pazaryeri için dürüsttür.
> Sorumlu: Hakan (gönderim), taslak Claude'dan. Kanal: info@tusu.uk.
> **Son tarih: 2026-08-15** — öğrenci toplulukları freshers planlarını Ağustos'ta
> kilitler; kaçarsa Eylül 2027, 12 ay gecikme. Ön koşul: öneri 4'ün rehber
> sayfası yayında olmalı (mesajın gideceği yer).

**2. Dubai Türk Rehberi** *(kategori 1 + 4: topluluk kanalı + ortaklık)*
dubairehberi.com.tr — Dubai'deki Türk WhatsApp gruplarını ve Instagram
hesaplarını derleyen bir yerel rehber/topluluk sitesi. BAE'de tahmini ~10 bin
Türk yaşıyor, çoğu yeni gelen beyaz yakalı — ev kurma ve hizmet bulma
ihtiyacı yüksek, bu bölgeye özel hiçbir içerik/kanal şu ana kadar
denenmemiş (rekabetsiz alan). Efor: düşük. İlk adım: siteye e-posta ile
ulaşıp karşılıklı link/tanıtım teklif etmek ("Dubai'de yaşayan Türkler için
ücretsiz hizmet/eşya pazaryeri" içerik önerisi ile).

*Doğrulama (2026-07-29):* Site canlı (HTTP 200, ana sayfa 02.07.2026'da
güncellenmiş), iletişim gerçek: **bilgi@dubairehberi.com.tr**. WhatsApp/Instagram
derlemeleri de gerçek. **Ama üç iddia çürüdü:** (1) site bir *topluluk* değil,
aynı kitleden trafik alan **rakip SEO içerik blogu** — "Dubai'de ev kiralamak",
"Dubai ikinci el" içerikleri var, yani **çıkar çatışması**; (2) "~10 bin Türk"
değil **~20.000** (T.C. Abu Dabi Büyükelçiliği, 2018); (3) "rekabetsiz alan"
yanlış — Dubizzle/OLX Arabia ve Facebook Marketplace baskın, yalnız *Türkçe*
boşluk var. Ayrıca **BAE, Nisoya'nın ülke listesinde tanımlı bile değil.**

> **KARAR · 2026-07-29 · ERTELE** — hedef bölge (Körfez) açılacak (bkz. öneri 3
> kararı), ama bu ortaklık teklifi rakip bir yayıncıya yapılıyor ve karşı tarafın
> kabul etmesi için bir sebep yok. Şart: önce AE ülke kaydı + Dubai rehber
> sayfası yayında olsun, Körfez'de gerçek ilan birikmeye başlasın; ondan sonra
> teklif "karşılıklı içerik" olarak yeniden değerlendirilsin. Sorumlu: Hakan.

**3. Körfez bölgesine özel SEO içeriği** *(kategori 2: içerik/SEO)*
"Dubai'de/Katar'da yeni taşınan Türkler için ilk 30 gün: ev eşyası ve hizmet
bulma rehberi" başlıklı bir içerik sayfası. Bu bölge (BAE, Katar, Suudi
Arabistan) şu ana kadar hiç hedeflenmemiş; arama hacmi Avrupa/ABD'ye göre
düşük ama rekabet neredeyse sıfır ve yeni gelenler somut ihtiyaç listesiyle
arama yapıyor — dönüşüm oranı yüksek olabilir. Efor: orta (platformda blog/
rehber sayfası altyapısı henüz yok, önce basit bir statik sayfa/route
yeterli). İlk adım: tek bir pilot sayfa (Dubai) yazıp SEO performansını
izlemek.

*Doğrulama (2026-07-29):* **Gerekçe tamamen yanlış.** "Blog/rehber sayfası
altyapısı henüz yok" iddiası doğru değil: `Page` modeli, `pages` tablosu,
`PageController`, `publish_at`, SEO alanları, Filament yönetim ekranı ve
sitemap entegrasyonu **canlıda**, 10 test geçiyor, 4 sayfa yayında. **Kod
eforu sıfır** — sayfa panelden yazılıyor. Gerçek engel başka: **BAE, Katar ve
Suudi Arabistan ülke listesinde tanımlı değil**, yani ilan verilemez.

> **KARAR · 2026-07-29 · YAP** — Körfez hedeflenecek. Sıra: (1) Claude AE/QA/SA
> ülke + para birimi + şehir kayıtlarını ekler (yarım-bir gün), (2) Dubai rehber
> sayfası yazılır ve panelden yayınlanır. Zaman baskısı yok, öneri 4'ten sonra
> gelir. Sorumlu: Claude (kod+taslak), Hakan (içerik onayı).

**4. Öğrenciye özel SEO içeriği** *(kategori 2: içerik/SEO)*
"İngiltere'de üniversite için ilk kez yurt dışına çıkan Türk öğrenciler için
ikinci el eşya ve ev kurma rehberi" — TUSU'nun kapsadığı kitleye doğrudan
hitap eder, Eylül-Ekim döneminde arama trafiği artar. Efor: orta. İlk adım:
madde 3'teki pilot sayfa ile birlikte aynı rehber şablonunu kullanmak.

*Doğrulama (2026-07-29):* Fikir doğru, efor tahmini yanlış — öneri 3'teki gibi
**kod eforu sıfır** (Page CMS canlıda). Gerçek iş yalnız içerik yazımı, 2-4 saat.
Bu öneri altı öneri içinde **hiçbir kapıya takılmayan tek öneri**; üstelik
öneri 1'in (TUSU) mesajının tıklandığında gideceği zorunlu iniş sayfası.
Sayfa çoğaltma aksiyonu yok, bloklar elle kurulacak.

> **KARAR · 2026-07-29 · YAP** — bu hafta. Taslağı Claude yazar, Hakan onaylar,
> panelden yayınlanır. CTA **arz çağrısı** olacak ("eşyanı ücretsiz listele"),
> çünkü GB'de 0 ilan var. `publish_at` Ağustos başı — Eylül aramalarında
> görünmek için indekslenme süresi bırakılmalı. Öneri 1'in ön koşulu.

**5. Referral sisteminde düşük efor bir "tanınma" katmanı** *(kategori 3:
ürün içi viral/referral)*
`panel/davet` sayfasında (bkz. [InviteController.php](app/Http/Controllers/InviteController.php))
şu an davet linki, kod ve davet edilenlerin sayısı gösteriliyor ama hiçbir
teşvik/tanınma unsuru yok. Parasal ödül platformun "ücretsiz" ilkesiyle
çelişebilir; bunun yerine 3+ başarılı davet sonrası profilde görünen küçük
bir sosyal rozet ("Nisoya Elçisi" gibi) davranışsal bir dürtü olabilir —
markanın "kendi insanından" tonuna da uygun düşer. Efor: düşük-orta
(mevcut `referrals()` ilişkisi zaten sayım için kullanılabilir durumda).
İlk adım: rozet eşiği ve görünürlük yerini (profil sayfası mı, panel mi)
netleştirip küçük bir UI eklemesi yapmak.

*Doğrulama (2026-07-29):* İddialar doğru — `/panel/davet`'te teşvik yok,
profilde davet tabanlı rozet yok, `referrals()` sayıma hazır. **Çakışma
kontrolü:** `/nabiz`'deki "Şehir Elçileri" AYRI bir mekanizma (aylık, şehir
başına 1 kişi, ilan/etkinlik ölçütlü) — davet sayısı tabanlı rozetle aynı şey
değil, yani öneri yinelenmiyor. **Ama ölçek engeli var ve ölçüldü:** `/nabiz`
bugün "Bu ay hedefimiz: **2 / 50**" ve "Bu ay henüz kimse davet etmemiş"
diyor. Ayda 0 davetin olduğu bir yerde davet rozeti kimseye görünmez.
İki mekanizma birbirini de hiç görmüyor: davet sayfası Şehir Elçileri'nden,
Şehir Elçileri davet sayfasından habersiz.

> **KARAR · 2026-07-29 · ERTELE** — rozetin kendisi (~1 gün) davet hacmi sıfırken
> boşa yatırım. Şart: aylık davet sayısı düzenli olarak 0'ın üstüne çıksın.
> **Ama ucuz alt-parçası bugün yapılıyor:** davet sayfası ↔ Şehir Elçileri çift
> yönlü bağ + "şu an X davetle şehrinin elçisi olabilirsin" satırı (yarım günden
> az). Var olan tanınma katmanını görünür kılmak, yeni bir katman eklemekten
> önce gelir. Sorumlu: Claude.

**6. ATAA — Assembly of Turkish American Associations** *(kategori 4:
ortaklık)*
ataa.org/component-associations — ABD genelinde 50+ yerel Türk-Amerikan
derneğinin güncel iletişim bilgilerini içeren resmi bir liste (ör. Turkish
American Cultural Alliance of Chicago, ATA-Houston, Turkish American
Association of Florida). Efor: düşük. İlk adım: birkaç büyük şubeye kısa
bir e-posta ile Nisoya'yı tanıtıp bültenlerinde/sitelerinde bir cümlelik
bahisle paylaşılmasını rica etmek — karşılıklı fayda temelli, ücretsiz.

*Doğrulama (2026-07-29):* Kaynak gerçek ve zengin — sayfa canlı, **~41 bağımsız
ABD derneği** (+ şubelerle 52 birim), 24 eyalet, Mayıs 2026'da güncellenmiş,
linklerin %93'ü sağlam, gerçek bülten altyapısı var. "50+" biraz şişirilmiş.
**İki risk:** (1) listedeki adreslerin önemli kısmı gönüllülerin gmail/yahoo/
hotmail kutuları — toplu benzer e-posta **nisoya.com gönderim itibarını
yakabilir** ve kayıt/bildirim/destek e-postaları spam'e düşer; gönderim
işlemsel alan adından **yapılmamalı**. (2) ATAA'nın kendi menüsünde "Armenian
Issue"/"Armenian Terrorism" savunuculuk bölümleri var — marka yan yanalığı.

> **KARAR · 2026-07-29 · YAP (daraltılmış)** — siyasi yan yanalık sahip tarafından
> değerlendirildi ve engel görülmedi: muhataplar yerel topluluk dernekleri, çatı
> örgütün savunuculuk sayfaları bağlayıcı sayılmıyor. **Ama gönderim koşullu:**
> (a) işlemsel alan adından AYRI bir gönderim kutusu kurulacak (sorumlu: Hakan),
> (b) "50'ye blast" değil, 5-8 büyük şubeye kişiselleştirilmiş pilot,
> (c) mesaj **arz çağrısı** çerçevesinde. Hazırlık (liste + taslaklar) şimdi,
> gönderim ABD'de ilan birikmeye başlayınca. Zaman baskısı yok.

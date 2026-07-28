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

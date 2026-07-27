# Büyüme Fikirleri Günlüğü

Bu dosya, Nisoya'yı yurt dışındaki Türklere ücretsiz yollarla ulaştırmak için
düzenli çalışan bir araştırma ajanının çıktısıdır. Ajan hiçbir şeyi otomatik
yayınlamaz/paylaşmaz — sadece araştırır, analiz eder ve öneri yazar. Kullanıcı
(Hakan) önerilerden istediğini seçip kendisi uygular.

Her çalışma, en üste yeni bir tarihli bölüm ekler (en yeni en üstte).
Zaten denenmiş/uygulanmış fikirleri tekrar önermemek için önce bu dosyanın
tamamını oku.

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

**2. Dubai Türk Rehberi** *(kategori 1 + 4: topluluk kanalı + ortaklık)*
dubairehberi.com.tr — Dubai'deki Türk WhatsApp gruplarını ve Instagram
hesaplarını derleyen bir yerel rehber/topluluk sitesi. BAE'de tahmini ~10 bin
Türk yaşıyor, çoğu yeni gelen beyaz yakalı — ev kurma ve hizmet bulma
ihtiyacı yüksek, bu bölgeye özel hiçbir içerik/kanal şu ana kadar
denenmemiş (rekabetsiz alan). Efor: düşük. İlk adım: siteye e-posta ile
ulaşıp karşılıklı link/tanıtım teklif etmek ("Dubai'de yaşayan Türkler için
ücretsiz hizmet/eşya pazaryeri" içerik önerisi ile).

**3. Körfez bölgesine özel SEO içeriği** *(kategori 2: içerik/SEO)*
"Dubai'de/Katar'da yeni taşınan Türkler için ilk 30 gün: ev eşyası ve hizmet
bulma rehberi" başlıklı bir içerik sayfası. Bu bölge (BAE, Katar, Suudi
Arabistan) şu ana kadar hiç hedeflenmemiş; arama hacmi Avrupa/ABD'ye göre
düşük ama rekabet neredeyse sıfır ve yeni gelenler somut ihtiyaç listesiyle
arama yapıyor — dönüşüm oranı yüksek olabilir. Efor: orta (platformda blog/
rehber sayfası altyapısı henüz yok, önce basit bir statik sayfa/route
yeterli). İlk adım: tek bir pilot sayfa (Dubai) yazıp SEO performansını
izlemek.

**4. Öğrenciye özel SEO içeriği** *(kategori 2: içerik/SEO)*
"İngiltere'de üniversite için ilk kez yurt dışına çıkan Türk öğrenciler için
ikinci el eşya ve ev kurma rehberi" — TUSU'nun kapsadığı kitleye doğrudan
hitap eder, Eylül-Ekim döneminde arama trafiği artar. Efor: orta. İlk adım:
madde 3'teki pilot sayfa ile birlikte aynı rehber şablonunu kullanmak.

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

**6. ATAA — Assembly of Turkish American Associations** *(kategori 4:
ortaklık)*
ataa.org/component-associations — ABD genelinde 50+ yerel Türk-Amerikan
derneğinin güncel iletişim bilgilerini içeren resmi bir liste (ör. Turkish
American Cultural Alliance of Chicago, ATA-Houston, Turkish American
Association of Florida). Efor: düşük. İlk adım: birkaç büyük şubeye kısa
bir e-posta ile Nisoya'yı tanıtıp bültenlerinde/sitelerinde bir cümlelik
bahisle paylaşılmasını rica etmek — karşılıklı fayda temelli, ücretsiz.

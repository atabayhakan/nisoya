# El Kitabı → Akıllı Rehber — Plan (2026-08-04)

Sahip isteği: *"El Kitabı bölümünü Kâhya ile birlikte akıllı hale getir.
(1) Her şeyi yüksek özellikli animasyonla anlatsın, gerekirse resim.
(2) İş akışlarını animasyon olarak gösterebilsin.
(3) Nisoya'yı baştan sona anlatan bir PDF indir bağlantısı olsun, her yenilik
bu PDF'e eklensin, yatırımcılara verilebilsin. (4) Ajanlar görevlendir,
internetten araştırsınlar, Kâhya ile çalışan inanılmaz akıllı bir bölüm yap."*

5 ajanlı dış araştırma yapıldı (animasyon · PDF · dokümantasyon deseni ·
yatırımcı dosyası). Bu plan onun çıktısı.

---

## 0. Mevcut durum (ölçüldü)

| Ne | Durum |
|---|---|
| `ElKitabi.php` | 88 satır, elle yazılmış bağlantı kartı dizisi |
| `el-kitabi.blade.php` | 43 satır, statik |
| Aranabilirlik | **Yok** |
| "Nasıl yaparım" cevabı | **Yok** — yalnız "nereye gideceğim" |
| PDF kütüphanesi | **Yok** |
| Animasyon kütüphanesi | **Yok** (yalnız CSS + Alpine) |
| Kâhya altyapısı | **Hazır**: PanelHaritasi · PanelHedefi · EylemKatalogu (15 eylem) · WebAramasi · KahyaTeshisi · KahyaHafizasi |

## 1. Çerçeve: dört istek, tek omurga

Dört istek ayrı dört proje gibi duruyor ama **tek içerik omurgasının dört
yüzeyi**. Ayrı ayrı inşa edilirse dört ayrı bakım borcu doğar ve üçüncü ayda
birbirleriyle çelişirler.

**Omurga:** `docs/rehber/*.md` — Türkçe markdown, git'te sürümlü, front-matter'lı.

| Yüzey | Ne yapar | Kaynağı |
|---|---|---|
| El Kitabı ekranı | Aranabilir rehber + ekran içi "Yardım" slide-over | markdown |
| Boş durumlar | Ekranın ilk paragrafı `emptyStateDescription` olur | aynı markdown |
| Kâhya | Soruyu markdown üzerinden **alıntıyla** yanıtlar | aynı markdown |
| Belgeler | Genel bakış + yatırımcı memosu | markdown + canlı veri |

Animasyon bu omurganın **süsü değil, bir bölüm tipi**: markdown içinde
`{{surec:ilan-yasam-dongusu}}` yer tutucusu bir Blade partial'ına çözülür.

## 2. İstek 1+2 — Animasyon

### Öneri: saf CSS + satır içi SVG süreç şeridi

Her akış yatay bir şerit: kutular sırayla belirir, oklar `stroke-dashoffset`
ile kendini çizer, **her adımın altındaki sayı canlı sorgudan** gelir
("moderasyonda 3", "yayında 12").

**Neden:** sıfır paket · CDN yok · strict CSP'yi zorlamaz · 1 CPU'luk VPS'e
sıfır yük · SVG içindeki Türkçe metin ekran okuyucuya ve `Ctrl+F`'e görünür ·
akış değişince tasarımcıya değil tek Blade dosyasına gidilir.

**Neden Lottie/video değil:** içine gömülü **sabit sayılarla** gelir —
projenin "her şey gerçek veriden türemeli" kuralını çiğner ve sahip
(geliştirici değil) güncelleyemez.
**Neden Mermaid değil:** 5 kutuluk şema için MB'lık motor + CSP ihlali eden
font çekimi. **Neden GSAP değil:** tek başına makul ama Livewire morph'uyla
ilk çakışmada bakım yükü tek kişilik ekibi aşar.

**En fazla 4 akış:** ilan yaşam döngüsü · mesaj→anlaşma→doğrulanmış işlem ·
moderasyon ve güven zinciri · yedek/kurtarma. *"Her şeyi animasyonla anlatmak"
hedefi konmayacak.*

### En büyük risk: şerit yalan söylemeye başlar

Akışa yeni durum eklenir, şerit eski kalır. Sessizce kırılmaz — sessizce
**eskir**, ki daha kötüdür.

**Küçültme:** adımları elle yazma, **durum enum'undan türet**. Yeni durum
eklenince şeritte kendiliğinden belirir. Üstüne test: *"enum'daki her durumun
şeritte karşılığı var"* — enum büyür, test kırılır, kimse unutamaz.

**Zorunlu teknik detaylar:** `wire:ignore` (yoksa her Livewire poll'ünde baştan
başlar) · `prefers-reduced-motion`'da animasyon kapalı ama **diyagram son
karesinde donmuş** (bilgi silinmez) · "Yeniden oynat" düğmesi · Alpine
kullanılırsa `Alpine.data()` ile kayıt (CSP günü kırılmasın).

## 3. İstek 3 — PDF

### Önce bir ayrım: bunlar İKİ AYRI BELGE

Sahip "Nisoya'yı baştan sona anlatan PDF" ile "yatırımcı dosyası"nı tek belge
sanıyor. Değiller:

| | Genel Bakış | Yatırımcı Memosu |
|---|---|---|
| Okur | Sahip, yeni ekip üyesi | Yatırımcı |
| Amaç | Her özelliği anlatmak | **Riski öldürmek** |
| Uzunluk | Uzun olabilir | **2 sayfa** |
| İçerik | Özellikler, akışlar | Problem, kanıt, koridor, ask |

**Yatırımcıya 40 özelliğin listesini vermek traction değil, traction
yokluğunun itirafıdır.** Aynı üretim hattı, iki ayrı şablon.

### Öneri: iki aşamalı

**Aşama 1 (şimdi): PDF kütüphanesi EKLEME.** Belgeyi `/yonetim` altında Blade
sayfası olarak diz, `@media print` CSS'i yaz, çıktıyı tarayıcının
Yazdır → PDF'iyle al. Maliyet sıfır, risk sıfır, Tailwind v4/flex/grid tam
çalışır.

**Aşama 2 (ancak gerçekten gerekince — arşiv, e-posta eki, "geçen ayın
dosyası"):** `spatie/laravel-pdf` + sistem Chromium'u (`chrome-php/chrome`),
**kuyrukta**, çıktı `storage/app/private` + imzalı geçici bağlantı.

**Neden dompdf/mPDF değil:** flexbox ve grid tanımaz, Tailwind v4'ün `oklch()`
renklerini bilmez ve **hata vermez** — kutular sessizce üst üste biner.
**Neden Browsershot değil:** `deploy.sh` zaten `npm ci` çalıştırıyor;
Puppeteer postinstall'ı her deploy'da 280 MB Chromium indirmeye kalkar ve ağ
hatasında **canlı deploy kırılır**. **Neden Gotenberg değil:** 4 GB'lık VPS'te
sürekli ayakta 1 GB'lık konteyner, tek rapor için orantısız.

### Mimari (aşama 1'de bile böyle)

1. `App\Reports\NisoyaDosyasi` — belgedeki **her sayı** canlı sorgudan.
   Blade'de elle yazılmış tek rakam bulunmaz.
2. Demo filtresi tek yerde, **Kâhya teşhisiyle aynı kural** — iki ayrı
   "gerçek" tanımı olmasın.
3. Her sayfada **veri kesim tarihi** damgası.
4. Her üretim `dosya_anlik_goruntuleri`'ne JSON yazsın — geçen ay hangi rakamı
   verdiğin izlenebilsin.
5. Grafikler **Blade ile SVG**; Chart.js yok, CDN yok.
6. Anlatı metinleri (vizyon, yol haritası) Filament'te düzenlenebilir olsun.

### En büyük risk: dosya olmayan bir şirketi anlatır

**Bugün pazaryerinde 3 gerçek ilan var ve hepsi sahibin.** "Baştan sona her
özelliği anlatan" 30 sayfalık parlak bir PDF yatırımcıya güç değil **zayıflık**
gösterir: çok inşa edilmiş, hiç doğrulanmamış bir ürün. Ve yatırımcı ilan
sahiplerine tek tek bakar.

**Küçültme — yatırımcı şablonu risk-azaltma memosu olur:** problem ve bugünkü
ikame (WhatsApp/Facebook grupları, Kleinanzeigen — *"rakibimiz yok" deme*) ·
talebin kanıtı (sonuçsuz aramalar, mesaj hunisi, rehber sayfası organik
trafiği) · **arzın dürüst rakamı** ("N ilan / M satıcı, K'sı benim; demo
hariç") ve bunu açıkça darboğaz diye adlandırma · **tek koridor** (ör. Almanya
× ev ürünleri) ve doyurma ölçüsü · gelir modelinin bilinçli yokluğu · tek
kuzey yıldızı metrik (haftalık karşılıklı ilk temas) · sermaye verimliliği
(tek kişi, geliştirici değil, X ayda canlı) · ask + tarihli kilometre taşları.

**Smoke test:** *"belgede boş/sıfır metrik yer tutucusu kalmadı"* ve
*"belgede sabit kodlanmış sayı yok"*.

## 4. İstek 4 — Kâhya entegrasyonu

Sohbet kutusu eklemek değil. Gerçekten değer üreten altı bağ:

**A. Kaynaklı cevap + Aç düğmesi (zorunlu, ilk gün).** Kâhya soruyu rehber
sayfasından **alıntıyla** yanıtlar ve mevcut yönlendirme mekanizmasıyla ilgili
ekranın "Aç" düğmesini basar. **Kural: kaynak gösteremiyorsa cevap vermez,
"rehberde bu yok" der.** Kullanıcı tek kişi; yanlışı yakalayacak ikinci
kullanıcı yok, alıntı zorunluluğu tek savunma hattı.

**B. Rehber boşluk avcısı (en değerli madde).** `kahya_mesajlari` içinde
kaynaksız kalan sorular haftalık toplanır → **"Rehberde eksik sayfalar"**
listesi. Doküman backlog'u sahibin tahmininden değil **gerçek sorulardan**
türer. Tek kişilik ekipte dokümantasyonun ölmesini engelleyen tek mekanizma.

**C. PanelHaritası farkı → "her yenilik PDF'e eklensin" isteğinin karşılığı.**
Haritanın haftalık anlık görüntüsü saklanır; yeni ekran belirince Kâhya der ki:
*"Bu hafta X ekranı eklendi, rehberde sayfası yok ve tanıtım dosyasının
değişiklik günlüğüne yazılmadı."* — **otomatik metin üretimiyle değil,
unutmayı imkânsız kılan hatırlatmayla.** (Otomatik yenilik metni uydurma
üretir.)

**D. Rehber sayfasından eyleme köprü.** Front-matter'ında `eylem: seo-doldur`
yazan sayfanın altında "Kâhya bunu senin yerine yapabilir" düğmesi;
`EylemCalistirici` mevcut tek onay kapısından geçer. Yeni onay yüzeyi açılmaz.

**E. Teşhis enjeksiyonu.** "Yedekleme" sayfasında statik metnin yanında
`KahyaTeshisi`'nden tek satır: *"Senin sitende son yedek 2 gün önce alındı."*
Rehber genel doğruyu değil **senin durumunu** anlatır.

**F. Yatırımcı dosyası için taslak, asla otomatik içerik.** `WebAramasi` ile
pazar araştırması **taslak** üretir, kaynak URL'siyle. Sahip onaylamadan
dosyaya girmez. **AI'nın belgeye doğrudan sayı veya iddia yazması yasak.**

**Yapılmayacak entegrasyon:** rehberi Kâhya'ya "özetletmek" ya da doküman
yokken "AI zaten anlatır" deyip markdown yazmamak. **Doküman yoksa Kâhya
uydurur; önce metin, sonra AI.**

## 5. YAPILMAYACAKLAR (sakınmadan)

1. **"Yüksek özellikli animasyon" hedefi** — sahibin dört isteği içinde
   **en düşük değerli** olanı. Sinematik animasyon tek kullanıcılı panelde bir
   kez izlenir; Lottie/After Effects yolu tasarımcıya bağımlılık yaratır ve
   süreç ilk değiştiğinde **yalan söylemeye başlar**. Şerit yeter.
2. **Otomatik başlayan karşılama turu** — ölçülmüş: yanlış zamanlı karşılama
   modallarının %38'i dört saniyede kapatılıyor; turlarda tamamlanma ~%23.
   Tek kullanıcıda değeri sıfıra yakın, bakımı en pahalısı.
3. **Video / ekran kaydı rehberler** — buton yeri değişince metni düzeltemezsin,
   videoyu baştan çekersin. Aranamaz, ekran okuyucuya kapalı.
4. **Sunucuda headless Chrome ile GIF/video üretmek** — 1 CPU'da canlı siteyi
   yavaşlatır.
5. **Harici DAP (Pendo/Appcues/Intercom)** — harici CDN + satır içi script +
   aylık ücret; n=1 için değersiz.
6. **PDF'i Canva/Word'de elle hazırlayıp depoya koymak** — ilk hafta en hızlısı,
   ikinci ayda yalan.
7. **Yatırımcı dosyasında özellik saymak** — 40 özellik traction değildir.
8. **Yukarıdan-aşağı pazar aritmetiği** ("6,5 milyon Türk × X €"), vanity
   metrikler, sahte GMV eğrisi, erken take-rate taahhüdü, n<30'da kohort.
9. **Demo veriyi envantere saymak** — Kâhya teşhisi saymıyor, dosya da
   saymayacak. Tek doğrulanan şişik rakam tüm belgeyi çöpe atar.
10. **Elle işaretlenen kurulum kontrol listesi / "%80 tamam" yüzdeleri** —
    madde ya veritabanı koşulundan otomatik tamamlanır ya listeye hiç girmez.
11. **`wire:ignore` koymadan Livewire içinde animasyon** — klasik tuzak.

## 6. Aşamalar

### M0 — Rehber omurgası *(ilk ve en önemli)*
- `docs/rehber/` altında **8-10 sayfa** markdown — hepsi değil: geri alınamaz
  işler, pahalı hatalar, "nereye gideceğim" soruları.
- `ElKitabi.php` markdown okuyucusuna dönüşür: kenar çubuğu + arama + slide-over.
- **3 kritik ekrana** "Yardım" başlık eylemi (hepsine değil — desen kanıtlansın).
- Kâhya'ya rehber kaynağı + **kaynaksız cevap vermeme kuralı** (madde A).

*Bitti ölçütü:* Kâhya'ya sorulan 10 "nasıl yaparım" sorusundan **en az 7'si**
kaynak alıntısıyla yanıtlanıyor.

### M1 — Tek animasyon, tek belge
- **BİR** süreç şeridi: ilan yaşam döngüsü, durum enum'undan türetilmiş,
  altındaki sayılar canlı, `wire:ignore` + reduced-motion + "Yeniden oynat".
- **BİR** Blade belgesi + print CSS: "Nisoya Genel Bakış". İndirme = tarayıcı
  Yazdır → PDF. Depoya paket girmez.
- Kâhya'nın **rehber boşluk avcısı** (madde B).

### M2 — Yatırımcı şablonu
- Ayrı şablon: 2 sayfalık risk-azaltma memosu, tek koridorlu anlatı, Kanıt
  Defteri + tarihli kilometre taşları, veri kesim damgası,
  `dosya_anlik_goruntuleri` tablosu.
- PanelHaritası farkı → değişiklik günlüğü hatırlatması (madde C).

### M3 — ancak gerçekten gerekirse
- Sunucu tarafı PDF (kuyrukta, imzalı bağlantı).
- 2-3 şerit daha · rehber→eylem köprüsü (D) · teşhis enjeksiyonu (E).

## 7. Sahibin karar vermesi gerekenler

1. **Animasyon beklentisi düşürülüyor** — sinematik değil, veriden türeyen
   süreç şeridi. Kabul?
2. **PDF ikiye ayrılıyor** — Genel Bakış (içeriye) + Yatırımcı Memosu
   (2 sayfa, dışarıya). Kabul?
3. **Yatırımcı memosu envanterin azlığını AÇIKÇA yazacak** ve darboğaz diye
   adlandıracak. Bu, gizlemekten daha güçlü ama sahibin onayı şart.
4. **M0'dan başlanacak** (markdown omurgası), animasyon M1'e kalacak. Kabul?

## 8. Tek cümlelik özet

Dört isteğin **omurgası bir klasör dolusu Türkçe markdown**; animasyon onun
süsü, PDF onun bir çıktısı, Kâhya onun okuyucusu ve boşluk avcısıdır.
Gösterişli animasyon kütüphanesi, tur, video ve elle bakımlı yatırımcı dosyası
— dördü de ikinci ayda sahibin aleyhine döner, çünkü hiçbiri **kendini gerçek
veriden yenilemez**.

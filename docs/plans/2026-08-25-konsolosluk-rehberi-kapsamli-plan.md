# Konsolosluk Rehberi'ni gerçek anlamda çalışır hale getirme — plan

**Tarih:** 2026-08-25 · **Durum:** Plan — kısmen uygulandı (bkz. §1), geri kalanı karar bekliyor
**Tetikleyici:** Sahip "Tayland vize işlemleri" yazdığında Nisoya AI'dan hiçbir yararlı bilgi alamadı; Keşfet'teki "Konsolosluk Rehberi" kartı ziyaretçinin ülkesi ne olursa olsun Almanya'ya gidiyordu. Üç istek: (1) bölümü çalışır hale getir, (2) Keşfet girişini akıllı yap + plan, (3) gerçek bir konsolosluk rehberi modülü için internet araştırmasına dayalı kapsamlı plan.

---

## 0. Özet — üç ayrı sorun, üç ayrı çözüm sınıfı

Araştırma üç FARKLI kök sorunu ayırdı; birbirine karıştırılırsa yanlış çözüm üretilir:

| Sorun | Sınıf | Çözüm |
|---|---|---|
| Keşfet kartı sabit `/de`'ye gidiyordu | **Bug** (kişiselleştirme eksik) | ✅ Bugün düzeltildi (§1) |
| "Tayland vize işlemleri" hiçbir sonuç vermiyor | **Kapsam netliği** — vize zaten Türk konsolosluğunun işi DEĞİL | AI'nin bunu DÜRÜSTÇE söylemesi (§2) |
| 75/77 ülkede yalnız adres var, gerçek işlem rehberi yok | **İçerik derinliği** — asıl büyük iş | Mimari + üretim hattı (§3-5) |

---

## 1. Bugün düzeltildi — Keşfet kişiselleştirme hatası

`NavigationLink` (id=9, "Konsolosluk Rehberi") elle sabit `url=/de` olarak eklenmişti. `navigation_links` tablosu `Cache::rememberForever` ile paylaşılan TEK bir listede tutulduğu için ziyaretçiye özel bir URL doğrudan cache'e yazılamazdı.

**Çözüm:** `NavigationLink::REHBER_GIRIS_SENTINEL` (`/rehber-giris`) — bu özel değeri taşıyan bir link, `AppServiceProvider`'daki View Composer'da, cache'İN DIŞINDA, istek başına, footer linkinin zaten kullandığı `RehberYuzeyi::girisNoktasiUlkeKodu()` (K1: üye ikameti > GeoIP) ile çözülür. Çözümleme paylaşılan `$navLinks` temelinde yapıldığı için mega menü kartı, mobil Keşfet paneli VE Cmd+K komut paleti üçü de otomatik doğru URL'i alır (ilk denemede yalnız mega menüyü düzeltmek Cmd+K'yı atlıyordu — testte yakalandı). Hazır ülke yoksa/modül kapalıysa kart tamamen kaldırılır.

**Kalan elle iş:** üretimdeki `navigation_links` id=9 kaydının `url` alanı `/de`'den `/rehber-giris`'e güncellenmeli (bir satırlık `UPDATE`, onay gerektirir).

Kod: `app/Models/NavigationLink.php`, `app/Providers/AppServiceProvider.php`, testler `tests/Feature/NavigationLinkTest.php`.

---

## 2. "Vize" — kapsam dışı, ama AI bunu SÖYLEMELİ

Araştırma resmî kaynaktan (mfa.gov.tr) net cevap verdi: **T.C. konsoloslukları vatandaşlarına ÜÇÜNCÜ BİR ÜLKEYE (Tayland, Almanya, ABD…) gitmek için hiçbir vize hizmeti sunmaz** — bu tamamen gidilecek ülkenin kendi işidir. T.C. konsolosluğunun seyahatle tek ilgisi acil durum desteği (kayıp pasaport, tutuklanma, kriz). "Vize" başlığı Ülke Rehberi'ne EKLENMEMELİ — eklemek yanlış bir vaat olurdu.

**Asıl düzeltme `NisoyaAiYonlendirici`'de:** bugün "vize" gibi bir soru `rehber`/`yasam` niyetlerinden birine zorlanmaya çalışılıyor, ikisi de boş dönünce sessizce `/ilanlar`'a düşülüyor — ziyaretçi NEDEN sonuç alamadığını hiç öğrenmiyor. Öneri: AI'nin niyet şemasına **"kapsam_disi"** (ya da "belirsiz"in bir alt-türü) eklenip, tespit ettiğinde dürüst bir mesaj dönmesi: *"Bu bizim (Türk konsolosluğu) kapsamımızda değil — [ülke] vizesi için [ülke]'nin kendi konsolosluğuna/e-vize sistemine bakman gerekir."* Küçük, düşük riskli bir iş; ayrı bir AI şeması alanı gerektirmez, yalnız istem (prompt) + bir yeni niyet dalı.

**Kapsam dışı bırakılan (bilerek):** üçüncü ülke vizesi için GERÇEK rehber içeriği yazmak (örn. "Tayland vizesi nasıl alınır") — bu Nisoya'nın konsolosluk modülünün doğal sınırları dışında; istenirse ayrı bir Yaşam Rehberi kategorisi olarak (gündelik yaşam, ülkeden bağımsız) değerlendirilebilir, bu planın kapsamında değil.

---

## 3. İçerik derinliği — asıl büyük iş, gerçek sayılarla

Ölçülen durum: 77 temsilcilikten yalnız **DE (14) + US (7) = 21'inde** gerçek İşlem içeriği var (Bişkek dahil geri kalan 56'sında YALNIZ adres + genel yönlendirme notu — bugün ayrıca harita+ipuçları eklendi ama adım-adım süreç bilgisi yok). Mevcut 8 İşlem Türü de T.C.'nin gerçekte sunduğu ~15 kategorinin altında kalıyor (bkz. §4).

Bu açığı "210 taslağı doğrula" mantığıyla kapatmaya çalışmak (2026-08-04'te denenmiş, 2026-08-10'da "18'e indir" ile kısmen çözülmüştü) yanlış BİRİM üzerinden düşünüyor. Araştırma kritik bir yapısal gerçek buldu:

### 3.1 İşlem türü, ülkeden daha belirleyici

Vekaletname ve pasaport gibi işlemler **TÜM ülkelerde aynı merkezi sistemi** (vekaletname → konsolosluk.gov.tr/e-Devlet, pasaport → epasaport.gov.tr) ve **aynı Türk hukukunu** (ör. Noterlik Kanunu md.87) kullanıyor — Berlin, Paris, Dubai, Bişkek'in resmî sayfaları karşılaştırıldığında çekirdek süreç BİREBİR aynı çıktı. Yerel fark küçük ve sayılabilir: randevu sistemi/linki, para birimi, yeminli tercüman notu, yerel iletişim kanalı, nadiren bir ön-adım (ör. Dubai'de taslak metnin e-posta ile ön-onayı).

**Sonuç:** 75 ülke için sıfırdan "210 taslak" yazmak yanlış birim. Doğru birim: **İşlem türü başına BİR ana şablon** (hukuki dayanak, gereken evraklar, genel süreç — %90 ortak) + **temsilcilik başına küçük bir "yerel ek" bloğu** (4-6 alan: randevu linki, ücret/para birimi, tercüman notu, yerel iletişim, varsa özel ön-adım).

### 3.2 Veri modeli etkisi

Bugünkü `TemsilcilikIslemi` (temsilcilik × işlem türü, HER kombinasyon için ayrı tam kayıt) modeli bu mimariyi yansıtmıyor — her kombinasyonun evrak listesini/süresini/ücretini SIFIRDAN taşıyor, oysa çoğu alan İşlem Türü seviyesinde ortak olabilirdi. Önerilen yön (bu planın parçası, ayrı bir tasarım turu gerektirir): `IslemTuru`'ya ortak alanlar (hukuki dayanak, genel evrak listesi, genel süreç metni) eklenir; `TemsilcilikIslemi` yalnız SAPMALARI (yerel randevu linki, ücret, tercüman notu, varsa ek evrak) taşır. Mevcut veri KAYBOLMAZ — geriye dönük uyumlu bir genişletme.

---

## 4. Gerçek kapsam — eksik kategoriler (önceliklendirilmiş)

Resmî konsolosluk.gov.tr menüsünden doğrulanan tam liste, Nisoya'nın bugünkü 8 kategorisinin ÜSTÜNDE:

| Eksik kategori | Tahmini talep | Not |
|---|---|---|
| Nüfus/adres beyanı + **yurt dışı seçmen kaydı** | Yüksek (seçim dönemlerinde) | Kayıt süreci basit, tek sayfa |
| **T.C. Vatandaşlık + Mavi Kart** | Yüksek | İkinci kuşak diaspora için kritik |
| Sürücü belgesi (yenileme + yabancı ehliyet çevirme) | Orta-yüksek | USAilan'ın en çok görüntülenen konularından biri (bkz. büyüme günlüğü 2026-08-19) |
| Boşanma tescili | Orta | Evlenme bildirimiyle aynı ailede, ucuz ek |
| Adli konular (sabıka kaydı) | Orta | Sık istenen tek belge türü |
| e-Devlet şifresi | Düşük-orta | Basit, tek sayfa, düşük efor/yüksek hacim adayı |
| Çalışma/SGK bilgisi | Düşük | Daha çok Yaşam Rehberi'ne yakın (ülkeye özgü değil) — oraya mı, buraya mı karar gerekir |

**Not:** YTB'nin "Yurtdışı Vatandaş Rehberi" konsolosluğun ötesine (gümrük, SGK, eğitim/diploma denkliği, vergi) geçiyor — bunlar zaten Nisoya'nın ayrı **Yaşam Rehberi** modülünün doğal alanı, Konsolosluk Rehberi'nin değil. İki modül arasındaki sınır burada teyit edildi: Konsolosluk Rehberi = T.C. temsilciliğinin YAPTIĞI iş; Yaşam Rehberi = ülkede yaşamanın GENEL bürokrasisi.

---

## 5. Üretim hattı — AI taslak + bağımsız doğrulama

Araştırma somut bir yöntem adı verdi: **Chain-of-Verification (CoVe)** — AI taslak yazar → AYRI bir AI çağrısı taslaktaki HER iddia için doğrulama sorusu üretir → o sorular kaynağa bakılarak yanıtlanır → çelişki varsa taslak düzeltilir. Bu, Nisoya'nın Yaşam Rehberi F1'de zaten kullandığı "AI araştır + bağımsız doğrula (Workflow)" desenin BİREBİR akademik/isimli karşılığı — yani şu an kullanılan yöntem zaten doğru yönde, adı ve disiplini netleşti.

Kritik ek kural (araştırmadan): taslak SERBEST üretim değil, doğrudan İLGİLİ resmî sayfanın metnine dayanmalı (RAG) — saf serbest üretimde bile "hukuki" içerikte %17-33 halüsinasyon ölçülmüş (Stanford RegLab). Nisoya'nın mevcut "gerçek bilgi kuralı" zaten bunuZORLUYOR; bu yalnız NEDEN önemli olduğunun dışarıdan doğrulanmış hâli.

Ölçekleme disiplini için emsal (**Visa Process Infos**, 131+ ülke, küçük ekip): *"editörü/doğrulanmış danışmanı olmayan ülke yayınlanmaz — ölçek doğrulama kapasitesiyle sınırlanır, tersi değil."* Bu, Nisoya'nın K7 ilkesiyle (taslak asla sızmaz) aynı ruh — dışarıdan bir doğrulama daha.

### 5.1 Somut üretim akışı (önerilen)

1. **İşlem türü ana şablonu** (yeni İşlem Türü başına BİR kez): AI resmî kaynaktan taslak yazar → CoVe doğrulama turu → sahip onayı → yayına alınır. 15 kategori için ~15 şablon (bugünkünün ~2 katı iş, 210 kombinasyonun değil).
2. **Temsilcilik yerel eki** (temsilcilik × işlem türü başına, küçük): randevu linki + ücret + tercüman notu + varsa özel adım — bu daha ucuz, AI tek geçişte + hafif doğrulamayla halledilebilir (bugünkü embassy-adres araştırmasındaki desenle aynı: resmî /Mission/Contact sayfasından oku).
3. **Öncelik sırası:** talep verisine göre (bkz. büyüme günlüğü öneri 3 — Search Console henüz kurulmadı, bu ön koşul) — veri gelene kadar nüfus diaspora büyüklüğüne göre (Almanya, ABD zaten hazır; sıradaki adaylar: Fransa, Hollanda, Belçika, BAE — Türk dünyası + Körfez zaten temsilcilik-only durumda).

---

## 6. Keşfet girişini "akıllı" yapmak — ötesi

§1'deki bug düzeltmesi asgari düzeltmeydi. "Akıllı" için ek fikirler (karar bekliyor, kod yazılmadı):

- **Arama/filtre:** Keşfet panelindeki "Konsolosluk Rehberi" kartı tıklanınca doğrudan ülke sayfasına gitmek yerine, KISA bir ülke seçici gösterebilir (zaten `x-rehber.ulke-secici` var, `/{ulke}` sayfasında kullanılıyor) — ama bu ekstra bir tık ekler, mevcut "doğrudan kendi ülkene git" daha az sürtünmeli. Önerim: DEĞİŞTİRME, mevcut davranış zaten doğru.
- **Nisoya AI arama ile birleşme:** Keşfet panelindeki karta tıklamak yerine, doğrudan panelin İÇİNE küçük bir "Sor" kutusu (mevcut ana sayfa çubuğunun küçük hâli) eklenebilir — kullanıcı Keşfet'i açar açmaz soru sorabilir. Küçük, orta öncelikli, ayrı bir tasarım turu ister.
- **§2'nin AI dürüstlük düzeltmesi zaten Keşfet'ten gelen sorguları da kapsar** (aynı `NisoyaAiYonlendirici`).

---

## 7. Açık kararlar (sahipten)

1. **§2 (AI dürüstlük düzeltmesi)** — küçük, düşük riskli, hemen uygulanabilir. Onay?
2. **§4'teki eksik kategoriler** — hangileri öncelikli? (Önerim: seçmen kaydı + vatandaşlık/Mavi Kart + sürücü belgesi, üçü de yüksek/orta-yüksek talep.)
3. **§3.2 veri modeli genişletmesi** (İşlem Türü'ne ortak alan ekleme) — ayrı bir tasarım turu gerektirir, onaylanırsa `/brainstorming` ile detaylandırılır.
4. **§5.1 üretim hattı** — hangi ülke/işlem kombinasyonuyla pilot yapılsın? (Önerim: Fransa ya da Hollanda — zaten temsilcilik kaydı var, büyük diaspora, sıfır İşlem içeriği.)
5. **§6 Keşfet+Sor birleşimi** — şimdi mi, sonra mı?

Hiçbiri acil değil — §1 zaten canlıya hazır, §2 küçük ve bağımsız, §3-6 sahibin yönlendirmesini bekliyor.

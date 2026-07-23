# Nisoya Tanıtım & Büyüme Ajanı — Ciddi Proje Planı

**Tarih:** 2026-07-23
**Durum:** Araştırma tamamlandı, uygulama başlamadı (kullanıcının kararı bekleniyor)
**Kapsam:** Nisoya içine, admin'e yardımcı olacak bir yapay zekâ ajanı; işletme keşfi + iletişim zenginleştirme + kişiselleştirilmiş e-posta taslakları + onaylı gönderim + diaspora ortaklık erişimi.

> Bu belge derin bir web araştırmasına dayanır (hukuk, Google ToS, e-posta teslim edilebilirliği, diaspora pazarlaması, Laravel AI mimarisi). Kaynaklar en sonda.

---

## 0. Yönetici Özeti — ve Dürüst Yeniden Çerçeveleme

Vizyonun net: **Nisoya'yı otonom olarak tanıtan, Google Maps'ten Türkçe isimli işletmeleri bulup e-posta gönderen, diaspora sitelerini keşfedip onlara ulaşan bir ajan.** İçgüdü doğru — büyümenin motoru bu tür sistematik erişimdir. Ama "olduğu gibi, otonom toplu gönderim" versiyonu üç somut sebeple **başarısız olur ve sana zarar verir**:

1. **nisoya.com e-posta teslim edilebilirliğini yok eder.** Ana alan adından binlerce soğuk e-posta atarsan, şikâyet oranı yükselir → Gmail/Outlook nisoya.com'u "spam gönderen" olarak işaretler → gerçek kullanıcılarının **şifre sıfırlama, mesaj bildirimi, hesap doğrulama** e-postaları spam'e düşer. Yani pazarlama uğruna çalışan siteni bozarsın.
2. **Google Maps ToS'unu ihlal eder.** Google, Maps içeriğini "telefon/telemarketing listesi" veya "alternatif dizin" oluşturmak için toplu çekmeyi (scraping) açıkça yasaklar. Ayrıca **e-posta adresleri resmî Places API'sinde zaten yok** — onları almak için ayrı, gri-bölge kazıma gerekir.
3. **Hukuki risk taşır.** AB'de tüketiciye (B2C) soğuk e-posta ön-onay ister; ihlali GDPR'da **20M€ / cironun %4'ü**'ne kadar ceza. ABD'de CAN-SPAM ihlali **e-posta başına ~51.744$**. Türkiye'de ETK/İYS kuralları var.

**İyi haber:** Aynı hedefe **hem yasal hem de daha yüksek dönüşümlü** bir yoldan ulaşabiliriz. Bu planın özü şu yeniden çerçeveleme:

> **Ajan = admin'in kopilotu, otonom spam makinesi değil.** Ajan keşfeder, zenginleştirir, kişiselleştirilmiş taslak yazar, kuyruğa alır; **admin partiler halinde onaylar**; ayrı bir gönderim altyapısı, ısıtılmış itibarla, uyumluluk korkuluklarıyla gönderir. Bu, senin kendi çerçevenle ("bu ajanın görevi sitenin adminine yardımcı olmak") birebir örtüşür.

Bu yaklaşım daha yavaş görünür ama **gerçekte daha hızlıdır**, çünkü kara listeye girip sıfırdan başlamazsın ve e-postaların gelen kutusuna ulaşır.

---

## 1. Hedefleme & Bölge Dışlama Mimarisi + Hukuk

**Karar (2026-07-23):** Nisoya yurtdışındaki Türkler için olduğundan **Türkiye hedef DIŞI**. Ayrıca **AB de dışlanıyor** (GDPR/ePrivacy karmaşası + tüketiciye ön-onay zorunluluğu bu iş için değmez). Yeni hedef kitle: **ABD + Orta Asya (KZ/KG/UZ) + Güneydoğu Asya (Tayland/Kamboçya) + (dikkatle) Rusya.**

### 1.1 İki katmanlı mimari: KEŞİF küresel, GÖNDERİM bölge-kapılı (2026-07-23 kararı)
Kritik ayrım: **keşif (veri toplama) ≠ gönderim (mail).** Kullanıcı kararı: ajan **AB dahil her yeri tarasın, hafızaya alsın** (pazar zekâsı, ileriye dönük); ama **mail konusunda bölge kuralı katı uygulansın.**

- **Keşif katmanı (küresel):** Ajan AB/TR dahil tüm ülkelerde Türk işletme keşfi yapar, `outreach_targets`'a **ülke + şehir + sektör + işletme adı** olarak kaydeder. Bu, "hangi ülkede ne kadar Türk esnaf var" haritası — ileride pazar açmak için değerli veri.
- **Gönderim katmanı (bölge-kapılı):** Her kayıtta `marketing_status` bayrağı: `allowed` (ABD/Orta Asya/GD Asya) veya `region_blocked` (AB/TR). **Sadece `allowed` kayıtlar gönderim kuyruğuna girebilir.** AB/TR kayıtları veritabanında durur ama asla mail kuyruğuna düşmez.
- **Savunma derinliği (iki kat):** (1) `marketing_status` bayrağı; (2) gönderim anında adres yine dışlanan bölgeye çözülürse (`.de` alan adı, `+49` telefon, AB adresi) motor tekrar bloklar. Kaza mail iki filtreden de geçemez.
- Admin panelden `excluded_regions` (varsayılan AB-27 + TR) ve `included_countries` allowlist'i serbestçe düzenlenir ("AB ve Türkiye'yi gönderimden çıkar" → ülke kodlarına çevrilir).

> **GDPR dürüst notu (AB verisi saklama):** GDPR'da "işleme" sadece göndermeyi değil **toplama + saklamayı** da kapsar (m.4/2). Bu yüzden AB için **işletme + konum düzeyinde** kal (ad, şehir, sektör — senin dediğin "ülke şehir kaydı"); **kişisel e-posta/sahip adı gibi kişisel veriyi AB için ŞİMDİLİK toplama** (net bir kullanım amacı doğana dek — amaç ve saklama sınırlaması ilkeleri). Böylece pazar haritasını alırsın ama GDPR yükünü (veri sahibi hakları, saklama süresi, kayıt tutma) sırtlanmazsın. ABD/Orta Asya/GD Asya için kişisel iletişim zenginleştirme serbest.

### 1.2 Yeni hedef setinin hukuki matrisi
| Ülke/bölge | Soğuk B2B e-posta | Not |
|---|---|---|
| **ABD** | ✅ Yasal (opt-out) | CAN-SPAM: fiziksel adres + net opt-out + çıkanı 10 iş günü içinde durdur + aldatıcı başlık yok. En elverişli pazar. Ceza: mail başına ~51.744$. |
| **Orta Asya (KZ/KG/UZ)** | ✅ Pratik olarak uygun | Zayıf yasal uygulama; B2B opt-out + net ret yeterli sayılır. Yerel dil + güven sinyali şart. |
| **Tayland** | ⚠️ B2B'de uygun | PDPA: işletmenin **genel adresine** (info@) opt-out modeli kabul görür; tüketiciye (gerçek kişi) ön onay şart. Kimlik + unsubscribe zorunlu. |
| **Kamboçya** | ✅ Pratik olarak serbest | Kapsamlı veri-koruma yasası yok; yine de taban kuralları (kimlik + opt-out) uygula. |
| **Rusya** | ❌ ZOR — dikkat | 152-FZ + Reklam Kanunu 38-FZ: pazarlama e-postası **ön onay (opt-IN)** ister + veri-yerelleştirme (Rus vatandaşı verisi Rusya'da tutulmalı). ABD'den bile katı. → Öneri: soğuk mail YOK, sadece keşif + ortaklık/organik. |

**Ortak taban (her ülkede uygula):** gerçek gönderen kimliği + fiziksel adres + tek-tık opt-out + aldatıcı olmayan içerik + suppression listesine anında uyma.

> **Dürüst uyarı — Rusya paradoksu:** AB'yi "karmaşık" diye dışladık, ama araştırma net: **Rusya soğuk e-posta için AB'den bile katı** (ön onay + veri yerelleştirme + anti-spam reklam kanunu). Rusya'yı "kolay pazar" sanma. Orada ajan yalnızca *keşif + ortaklık araştırması* yapsın; toplu mail atma. Gerçek kolay pazar sırası: **ABD > Orta Asya > GD Asya > (Rusya en sonda, sadece organik).**

---

## 2. Veri Kaynağı Stratejisi (Google Maps Sorusu)

**Gerçek:** Resmî **Google Places API** yasaldır, yapılandırılmıştır; işletme adı/adres/koordinat/puan verir — **ama e-posta VERMEZ.** Maps'i toplu kazımak ToS ihlalidir (Google IP/hesap engelleyebilir) ve isimli e-posta, kaynağı ne olursa olsun GDPR'da kişisel veridir.

**Önerilen katmanlı model:**

1. **Keşif = Places API (yasal zemin).** "Almanya'da Türk restoranı", "Rotterdam döner" gibi sorgularla işletme adı/adres/web sitesi/telefon topla. Place ID süresiz saklanabilir; koordinat en fazla 30 gün önbelleklenebilir (ToS).
2. **İletişim zenginleştirme = işletmenin KENDİ web sitesi.** Places'ten gelen resmî web sitesindeki **herkese açık iletişim e-postasını** (info@, iletisim@) hedefli, hız-limitli çek. Bu, "Maps kazıma" değil; işletmenin kamuya açık iletişim bilgisini almaktır — yine de her kayda **kaynak + zaman + hukuki dayanak** notu tut (GDPR hesap verebilirlik).
3. **Alternatif/tamamlayıcı:** Yerel dizinler, ticaret odası listeleri, diaspora işletme rehberleri; ve B2B veri sağlayıcılardan (ör. hazır veri setleri) satın alma — ölçek gerekirse.
4. **Yapma:** Maps'ten binlerce kaydı otomatik toplayıp bir "telemarketing dizini" kurma. Hem ToS'a hem hedefimize aykırı.

> Karar noktası: Places API kullanımı ücretlidir (aşağıda maliyet). Aylık keşif hacmini sınırlayıp önce **tek ülke + tek dikey** ile doğrula.

### 2.5 Sorgu Permütasyon Motoru (senin "Bishkek mobilyacı, Almaty elektrikçi" fikrin, sistematik hâli)
Bu, projenin keşif kalbi. Ajan üç boyutu çarpar ve otomatik, hız-limitli, sürekli tarar:

**{ŞEHİR} × {MESLEK/DİKEY} × {DİL VARYANTI}**

- **Şehir listesi:** Hedef ülkenin Türk yoğunluklu şehirleri (ör. KZ: Almatı, Astana, Şımkent · KG: Bişkek, Oş · TH: Bangkok, Phuket, Pattaya · US: NY, NJ, LA, Chicago, Houston).
- **Meslek/dikey listesi:** berber/kuaför, mobilyacı, elektrikçi, çilingir, lokanta, inşaat ustası, oto tamir, terzi, nakliyat, emlakçı… (Nisoya'nın hizmet kategorileriyle eşleşen).
- **Dil varyantı:** Her sorgu **3 dilde** üretilir → yerel dil + İngilizce + Türkçe. Örnek:
  - Almatı × elektrikçi → `["электрик Алматы", "electrician Almaty", "Almatı elektrikçi", "Türk elektrikçi Almaty"]`
  - Bangkok × berber → `["ช่างตัดผม Bangkok", "Turkish barber Bangkok", "Bangkok Türk berber"]`
- **"Türk işareti" enjeksiyonu:** Sorgulara `Turkish` / `Türk` eklenmiş varyantlar en yüksek isabetli sonuçları verir (senin dediğin "Thailand Türk berber" mantığı).

Sonuç: Ajan gece gündüz bu kombinasyonları döner, yeni işletmeleri `outreach_targets`'a ekler, tekrarları eler (Place ID ile dedup).

### 2.6 "Türk mü?" Tespit Motoru (çok-sinyalli skorlama)
Bir işletmenin/kişinin Türk olduğunu tek sinyalle anlamak hatalıdır (yanlış-pozitif: Azeri/Balkan/diğer Türki isimler; yanlış-negatif: asimile olmuş isimler). Bu yüzden **ağırlıklı skor** kullanılır — eşik üstü otomatik, sınırda olanlar admin onayına düşer:

| Sinyal | Örnek | Ağırlık |
|---|---|---|
| **İşletme adı belirteçleri** | "Anadolu", "İstanbul", "Bosphorus", "Kebap", "Döner", "Usta", "Lokantası", "Marmara", "Efes", şehir adları | Yüksek |
| **Sahip/kişi adı** | Türkçe ad+soyad (Mehmet Yılmaz, Ayşe Demir); diakritik ipuçları (ç,ş,ğ,ı,ö,ü); isim-milliyet API'si (NamSor **diaspora/köken** ayrımı yapar, Türkçe↔Azerice'yi ayırır) veya yerel sözlük | Yüksek |
| **Web sitesi/yorum dili** | İşletmenin sitesinde/Google yorumlarında Türkçe metin tespiti | Orta |
| **Öz-tanımlama** | Kategoride/açıklamada "Turkish barber", "Turkish cuisine" | Orta-Yüksek |
| **Menü/hizmet ipuçları** | "döner, lahmacun, çay" gibi kültürel işaretler | Orta |

**Uygulama (KARAR 2026-07-23: LLM tabanlı, OpenRouter üzerinden):** Tespit, mevcut AI katmanıyla (bkz. hafıza: Nisoya AI Katmanı) OpenRouter'daki krediyle yapılır — ticari isim-API'sine (NamSor) bütçe gerektirmez. Akış:
1. **Ucuz deterministik ön-filtre (LLM'siz, bedava):** diakritik (ç,ş,ğ,ı,ö,ü) + Türkçe anahtar-kelime sözlüğü (Anadolu, kebap, usta, döner, lokantası…). Bariz Türk / bariz Türk-değil olanları burada ele → LLM çağrısı sadece **belirsiz** adaylar için harcanır (token tasarrufu).
2. **LLM sınıflandırma (ucuz model önce):** Belirsiz adayın sinyallerini (işletme adı + kategori + yorum örneği + site dili) yapılandırılmış-çıktı isteyen bir prompt'a ver → `{turk_mu: bool, guven: 0-1, sinyaller: [...], gerekce: "..."}`. OpenRouter'da ucuz bir model ilk geçişi yapar (routing/cheapest-model deseni); yalnızca sınırdakiler güçlü modele yükseltilir. Parti başına **birden çok aday tek çağrıda** → maliyet düşer.
3. **Eşik + insan onayı:** `guven` eşik üstü → otomatik `allowed`; sınırda → admin onay kuyruğu. `gerekce` denetim için loglanır (LLM tutarsızlığına/halüsinasyona karşı korkuluk).

> NamSor/Name2Nat sadece ileride **ölçek veya daha yüksek doğruluk** gerekirse opsiyonel yedek kalır; başlangıçta LLM + deterministik ön-filtre yeterli ve ucuz.

> **Dürüst sınır:** Skor **%100 değil.** Sınırdaki kayıtlar (ör. sadece isim ipucu var, başka sinyal yok) admin onay kuyruğuna düşer — yanlış kişiye mail gitmesin. Bu hem doğruluk hem uyumluluk (yanlış hedefleme itibar yakar) için şart.

### 2.7 Google Maps ötesi derin keşif kaynakları
Senin "başka derin kazıma yöntemleri var mı?" sorunun cevabı — Places API'yi tek kaynak sanma, katmanla:

1. **Cross-reference zenginleştirme:** Places'te bulunan işletmenin sitesine/sosyaline git → Türk işaretlerini doğrula + herkese açık e-postayı çıkar.
2. **Sosyal platformlar:** Facebook işletme sayfaları + Türk topluluk grupları, Instagram işletme profilleri (ToS sınırlarına dikkat, hız-limitli).
3. **Yorum-dili sinyali:** Türkçe yorum alan işletmeler güçlü bir "Türk müşteri kitlesi/sahibi" göstergesi.
4. **Etnik/yerel dizinler:** Türk dernekleri üye rehberleri, konsolosluk işletme listeleri, "Turkish restaurants in X" listeleri, ülkedeki Türk iş insanları dernekleri.
5. **Kartopu (snowball):** Bir Türk işletmesinin "benzer yerler"/yorumları sık sık başka Türk işletmelerini ortaya çıkarır → seed genişletme.

### 2.8 Hedef önceliklendirme (dürüst ROI notu)
Araştırma verisi: en büyük Türk diasporası Avrupa'da (artık dışladık) + ABD'de. Orta Asya'da (özellikle KZ/KG) **gerçek ve büyüyen bir Türk iş insanı/usta topluluğu** var. Tayland/Kamboçya'da topluluk **küçük ama gerçek** (turistik bölgelerde lokanta/berber/hizmet — Phuket, Bangkok, Pattaya). Keşif mekanizması küçük toplulukları da bulur (uzun kuyruk), ama **birim çabaya düşen getiri yoğunluğa göre değişir.** Önerilen pilot sırası:

**1) ABD (en yüksek yoğunluk + en kolay hukuk) → 2) Orta Asya (KZ/KG) → 3) GD Asya (küçük ama rekabetsiz) → 4) Rusya (yalnızca organik/ortaklık).**

> Not: "Tayland/Kamboçya'da inanılmaz Türk nüfusu" beklentisini biraz ölçekle — oralar **niş** pazarlar (değerli ama küçük). Hacim ABD ve Orta Asya'dan gelir; GD Asya'yı düşük-maliyetli bonus keşif olarak çalıştır.

---

## 3. Teslim Edilebilirlik Altyapısı (Projenin Can Damarı)

Bu bölüm atlanırsa proje çöker. Araştırma bulguları:

- **Ayrı gönderim alanı ŞART.** Soğuk/pazarlama e-postalarını **ana nisoya.com'dan ASLA gönderme.** Ayrı bir alt alan (`mail.nisoya.com` / `gonder.nisoya.com`) veya ayrı alan (`nisoya.email` gibi) kullan. Kötü kampanya sadece o alt alanın itibarını yakar, ana alan (işlemsel e-postalar) korunur.
- **Kimlik doğrulama:** Gönderim alanına **SPF + DKIM + DMARC** + RFC 8058 **tek-tık abonelikten çık** başlıkları.
- **Alan ısıtma (warmup):** Yeni alan 1. hafta günde 5–10 e-posta; 4–6 haftada kademeli artış (~4. haftada 40–50/gün). 3. haftadan önce gerçek soğuk erişim yok.
- **Toplu gönderen eşikleri (Gmail/Yahoo/Microsoft, >5.000/gün):** spam şikâyeti **<%0.3** (ideal <%0.1 = 1000'de 1), bounce **<%2**. Bu eşikleri aşan ajan **otomatik durmalı**.
- **ESP seçimi:** Soğuk erişim için **Amazon SES** (ucuz, esnek) veya **Mailgun**; işlemsel için mevcut Hostinger/ayrı stream. Postmark soğuk e-postaya izin vermez (dikkat).

**Kritik mimari kural:** Nisoya'nın mevcut işlemsel e-posta akışı (kayıt, mesaj bildirimi, şifre) **dokunulmadan** kalır; tanıtım ajanı **tamamen ayrı** bir gönderim kimliği ve kuyruğu kullanır.

---

## 4. Teknik Mimari (Nisoya Yığınına Oturmuş)

Nisoya zaten **Laravel + Filament admin + kuyruk + sağlayıcıdan-bağımsız AI katmanı** (Claude/OpenAI/OpenRouter/Gemini) içeriyor (bkz. hafıza: Nisoya AI Katmanı). Bunun üstüne kurarız.

### 4.1 Temel: Laravel AI SDK
Laravel'in birinci-parti **AI SDK'sı** (`laravel/ai`) tam da bu iş için: 14 sağlayıcı (Anthropic/OpenAI/Gemini/OpenRouter dahil — mevcut AI katmanımızla uyumlu), **ajan sınıfları, tool-calling, yapılandırılmış çıktı, kuyruk entegrasyonu, konuşma hafızası, MCP desteği** ve çok-ajanlı desenler (prompt-chaining, routing, orchestrator-workers, evaluator-optimizer). Python gerektirmez, tek kod tabanı.

### 4.2 Ajanın Yetenekleri (araç = "tool")
Ajan, admin'in verdiği hedefe göre şu araçları çağırır:

| Araç | Ne yapar | İnsan onayı? |
|------|----------|--------------|
| `discoverBusinesses` | Places API ile ülke/dikey bazlı işletme keşfi | Hayır (sadece okuma) |
| `enrichContact` | İşletme sitesinden herkese açık e-posta + hukuki dayanak notu | Hayır |
| `classifyRegion` | Alıcı bölge + tür (B2B/B2C) → uyumluluk kuralı seç | Hayır |
| `draftOutreach` | Kişiselleştirilmiş, bölge-dilinde e-posta taslağı | — |
| `queueCampaign` | Taslakları "onay bekliyor" durumunda kuyruğa al | **Evet — admin partiyi onaylar** |
| `sendApproved` | Onaylı partiyi ısıtma/eşik kurallarıyla gönder | Otomatik ama korkuluklu |
| `trackAndLearn` | Açılma/yanıt/şikâyet/opt-out → sonraki taslakları iyileştir | Hayır |
| `researchPartner` | Diaspora sitesi bul, analiz et, ortaklık teklifi taslağı | **Evet** |

### 4.3 İnsan-Döngüde (Human-in-the-Loop) Kapıları
- Ajan **hiçbir e-postayı admin onayı olmadan göndermez.** Filament'te bir "Erişim Kampanyaları" ekranı: ajan taslakları listeler, admin **toplu onayla / düzenle / reddet**.
- Geri döndürülemez eylem = gönderim → her zaman onay kapısı (bkz. güvenlik ilkesi: dışa-dönük eylemler onay ister).
- Ajan bir "belirsizlik" ya da "sınırda hukuki durum" tespit ederse otomatik durup admin'e sorar.

### 4.4 Korkuluklar (Guardrails)
- Suppression listesi (opt-out + şikâyet + bounce) → kalıcı, her gönderimde kontrol.
- Günlük/alan bazlı hız limiti; şikâyet oranı eşiği aşınca **otomatik duraklat**.
- Bölge katılık bayrağı (AB B2C → gönderme).
- Türkiye → İYS kayıt/ret kontrolü (entegratör).
- Tüm ajan eylemleri `activity_log`'a (mevcut sistem) yazılır — denetlenebilirlik.

### 4.5 Veri Modeli (yeni tablolar, taslak)
- `outreach_targets` (işletme/site, ülke, şehir, sektör, tür, kaynak, hukuki_dayanak, **marketing_status: allowed|region_blocked**, turk_guven_skoru, durum)
- `outreach_contacts` (e-posta, doğrulama durumu, suppression bayrakları)
- `outreach_campaigns` (hedef segment, dil, şablon, durum: taslak/onaylı/gönderiliyor/bitti)
- `outreach_messages` (kampanya×kişi, gönderim/açılma/yanıt/şikâyet zaman damgaları)
- `outreach_suppressions` (kalıcı kara liste)

---

## 5. Diaspora Ortaklık Erişimi (Ayrı ve Daha Değerli Kol)

"Turkish American Diaspora" gibi siteler için doğru hamle **toplu soğuk mail değil, 1:1 ortaklık.** Araştırma net: diaspora topluluklarına ulaşmanın en etkili yolu **topluluk örgütleri, kültür merkezleri, dernekler ve liderlerle ortaklık** + **kültürel olarak alakalı, ana dilde mesaj.**

**Ajanın rolü burada "araştırmacı asistan":**
1. Hedef ülke için diaspora derneklerini/sitelerini/Facebook gruplarını/dernekleri bulur.
2. Her biri için kısa bir dosya çıkarır (kitle, dil, iletişim kişisi, işbirliği açısı).
3. **Kişiselleştirilmiş bir ortaklık teklifi** taslağı yazar (karşılıklı fayda: "üyelerinize ücretsiz Türkçe hizmet/ilan platformu").
4. Admin gözden geçirip gönderir — bu **ilişki kurma**, blast değil. Dönüşümü kat kat yüksek, itibar riski düşük.

Bu kol, e-posta itibarından bağımsız çalışır ve genelde **en yüksek getiriyi** verir (bir dernek bülteni binlerce nitelikli kişiye ulaşır).

---

## 6. Fazlı Yol Haritası

Her faz **tek başına değer üretir** ve bir sonrakine kapı açar. Sıra, riski en aza indirecek şekilde dizildi.

### Faz 0 — Temel & Karar (altyapı, gönderim yok)
- Ayrı gönderim alt-alanı + SPF/DKIM/DMARC + ESP hesabı (SES/Mailgun).
- İYS gerekliliği kararı (Türkiye hedefte mi?).
- Hukuki taban şablonu (opt-out, fiziksel adres, dil varyantları).
- **Çıktı:** Gönderime hazır, ısıtılmaya başlanmış altyapı. Kod: minimal.

### Faz 1 — Ajan İskeleti + Keşif (okuma-only)
- Laravel AI SDK entegrasyonu, mevcut AI katmanına bağlama.
- `discoverBusinesses` + `enrichContact` + Filament "Erişim Hedefleri" ekranı.
- **Gönderim YOK** — sadece keşfet, zenginleştir, listele. Admin veriyi görür.
- **Çıktı:** "Şu ülkede N Türkçe işletme buldum, M'sinin e-postası var" tablosu.

### Faz 2 — Taslak + Onay + İlk Gönderim (küçük, ısıtmalı)
- `draftOutreach` + `queueCampaign` + onay ekranı + `sendApproved` (ısıtma eşikli).
- Suppression/opt-out/tek-tık-unsubscribe + şikâyet-eşik-otomatik-durdurma.
- **Tek ülke, tek dikey, günde 10–50** ile başla (ısıtma).
- **Çıktı:** İlk gerçek, uyumlu, ölçülen kampanya.

### Faz 3 — Ölçme & Öğrenme
- `trackAndLearn`: açılma/yanıt/şikâyet/opt-out panosu (Filament).
- Ajan sonuçlara göre konu/başlık/dil varyantı önerir (evaluator-optimizer deseni).
- **Çıktı:** Hangi mesaj/ülke/dikey çalışıyor — veri temelli ölçekleme.

### Faz 4 — Diaspora Ortaklık Kolu
- `researchPartner` + ortaklık teklifi taslak akışı + admin gönderim.
- **Çıktı:** Ölçeklenebilir, yüksek-getirili ortaklık pipeline'ı.

### Faz 5 — Ölçekleme & Otomasyon (opsiyonel, güven oluşunca)
- Çoklu ülke/dikey, zamanlanmış keşif, admin için haftalık "erişim özeti".
- İYS entegratör bağlama (Türkiye ölçeklenirse).

---

## 7. Maliyet Modeli (kaba, aylık)

| Kalem | Tahmini maliyet | Not |
|-------|-----------------|-----|
| Google Places API | Kullanıma göre; keşfi sınırla | Aylık kota + bütçe alarmı şart |
| ESP (SES/Mailgun) | Düşük (SES ~0,10$/1000 e-posta) | Hacme göre |
| LLM (taslak/araştırma) | Mevcut AI katmanı bütçesi | Ucuz model routing ile düşük |
| İYS entegratör (TR) | Aylık abonelik (opsiyonel) | Sadece Türkiye ölçeklenirse |
| Geliştirme | Faz bazlı, artımlı | Her faz bağımsız teslim |

**İlke:** Her dış servise **bütçe alarmı** koy; ajan bütçe/eşik aşınca durur.

---

## 8. Risk Kaydı

| Risk | Etki | Azaltma |
|------|------|---------|
| Ana alan e-posta itibarı yanması | Yüksek | **Ayrı gönderim alanı** (Faz 0), asla karıştırma |
| Kara listeye girme | Yüksek | Isıtma + eşik-otomatik-durdurma + suppression |
| GDPR/İYS ihlali | Yüksek | Bölge kuralı motoru + B2B odak + LIA belgesi + İYS |
| Google ToS / IP engeli | Orta | Places API (yasal) + hız-limitli, hedefli zenginleştirme |
| Spam algısı → marka zararı | Orta | Kişiselleştirme + değer-önce mesaj + düşük hacim |
| Otonom ajan hatası | Orta | İnsan-döngüde onay kapıları + activity_log |

---

## 9. Başarı Ölçütleri (KPI)

- **Teslim:** gelen-kutusu oranı >%90, bounce <%2, şikâyet <%0.1.
- **Etkileşim:** açılma, **yanıt oranı** (asıl metrik), opt-out oranı.
- **Dönüşüm:** erişimden gelen yeni kayıt / yeni ilan / yeni şirket profili.
- **Ortaklık:** kurulan diaspora işbirliği sayısı + onlardan gelen trafik.

---

## 10. Kararlar & Açık Sorular

**Verilmiş kararlar (2026-07-23):**
- ✅ **Keşif küresel (AB dahil), gönderim bölge-kapılı.** AB/TR verisi toplanır/saklanır (pazar zekâsı) ama mail atılmaz; AB için işletme+konum düzeyi, kişisel veri toplanmaz (GDPR).
- ✅ **Tespit LLM tabanlı, OpenRouter üzerinden** (mevcut kredi) + ucuz deterministik ön-filtre. NamSor opsiyonel yedek.
- ✅ **Gönderim hedefi:** ABD + Orta Asya + GD Asya (Rusya yalnızca organik/ortaklık).

**Hâlâ açık:**

1. ~~Hedef ülkeler?~~ → Çözüldü (yukarı).
2. ~~Türkiye hedefte mi?~~ → Hayır, gönderim dışı (keşifte var).
3. **Gönderim alanı:** yeni alt-alan (`mail.nisoya.com`) mı, ayrı alan mı?
4. **Risk iştahı:** katı-uyumlu B2B-öncelikli mi, yoksa daha agresif mi? (Ben katı-uyumluyu şiddetle öneririm.)
5. **Başlangıç kapsamı:** hangi tek ülke + tek dikey ile pilot yapalım?
6. **Diaspora kolu önce mi?** (Düşük risk, yüksek getiri — pilot için ideal aday.)

---

## Kaynaklar

**Hukuk — soğuk e-posta:**
- [Is Cold Email Illegal? Country-by-Country Guide (Overloop)](https://overloop.com/blog/cold-email-illegal)
- [GDPR Cold Email B2B Compliance (Scrap.io)](https://scrap.io/gdpr-cold-email-b2b)
- [Legitimate Interest for GDPR Cold Email (Sales Force Europe)](https://salesforceeurope.com/blog/what-is-legitimate-interest-for-gdpr-cold-email-b2b-rules)
- [CAN-SPAM Compliance Guide (Shopify)](https://www.shopify.com/blog/can-spam-act)
- [Is Cold Email Illegal? (Woodpecker)](https://woodpecker.co/blog/is-cold-email-illegal/)

**Türkiye — İYS / ETK (referans; Türkiye artık hedef dışı):**
- [İleti Yönetim Sistemi resmî](https://iys.org.tr/iys/nedir)
- [Tacir/esnaf istisnası — Aksan Hukuk](https://aksan.av.tr/en/blog/ticari-elektronik-ileti-onayi-ve-reddetme-hakki)

**Rusya & Tayland (yeni hedef seti hukuku):**
- [Rusya elektronik pazarlama — DLA Piper](https://www.dlapiperdataprotection.com/index.html?t=electronic-marketing&c=RU)
- [Rusya 152-FZ — Securiti](https://securiti.ai/russian-federal-law-no-152-fz/)
- [Tayland PDPA rıza gereklilikleri — Securiti](https://securiti.ai/blog/consent-requirements-under-thailands-data-protection-framework/)
- [B2B soğuk e-posta 44 ülke matrisi — B2B Data Index](https://b2bdataindex.com/compliance/)

**İsim/etnisite tespiti:**
- [NamSor — diaspora/köken sınıflandırması](https://namesorts.com/2017/09/27/visually-comparing-name-nationality-classification-services/)
- [NamePrism — isim-milliyet sınıflandırma](https://www.name-prism.com/about)
- [Name-ethnicity classification (araştırma)](https://www.researchgate.net/publication/221654368_Name-ethnicity_classification_from_open_sources)

**Türk diasporası nüfus verisi:**
- [Turkish diaspora — Wikipedia](https://en.wikipedia.org/wiki/Turkish_diaspora)

**Google Maps / veri:**
- [Google Places API Terms: scrape/store/cache (biz collect)](https://bizcollect.dev/blog/google-places-api-terms)
- [Is scraping Google Maps legal? (Thunderbit)](https://thunderbit.com/blog/is-scraping-google-maps-legal)
- [Google Maps API vs Web Scraping (Outscraper)](https://outscraper.com/google-maps-api-vs-web-scraping/)

**Teslim edilebilirlik:**
- [90%+ Cold Email Deliverability (Instantly)](https://instantly.ai/blog/how-to-achieve-90-cold-email-deliverability-in-2025/)
- [Google Bulk Sender Rules: SPF+DKIM+DMARC (Growleads)](https://growleads.io/blog/dmarc-email-sender-guidelines-2025-checklist/)
- [Transactional vs Marketing separation (Mailgun)](https://www.mailgun.com/blog/deliverability/transactional-emails-vs-marketing-emails/)
- [Subdomain for bulk sending (Suped)](https://www.suped.com/knowledge/email-deliverability/sender-reputation/should-i-use-a-separate-domain-or-subdomain-for-bulk-email-sending)

**Diaspora pazarlama:**
- [Community Outreach Toolkit for Diaspora Orgs (iDiaspora)](https://www.idiaspora.org/en/learn/resources/laws-and-policies/community-outreach-toolkit-diaspora-organizations)
- [Ethnic Marketing Essentials (Number Analytics)](https://www.numberanalytics.com/blog/ethnic-marketing-essentials)

**Teknik mimari:**
- [Building AI Agents with Laravel: No Python Required](https://laravel.com/blog/building-ai-agents-with-laravel-no-python-required)
- [Laravel AI Agents Now Support MCP Servers](https://laravel.com/blog/laravel-ai-agents-now-support-mcp-servers)

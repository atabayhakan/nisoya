# İlan tarafında yapay zeka — sekiz adımlık plan

**Tarih:** 2026-08-13 · **Karar veren:** sahip · **Durum:** 1. madde yapılıyor

## Neden bu sıra

Sıralama özelliklerin çekiciliğine göre değil, **darboğaza yakınlığına** göre.
Bugün üçüncü taraf arz sıfır: 31 aktif ilanın gerçek olanı sahibin kendi
ilanları. Arz gelmeden değerli olan özellikler önce, arz geldikten sonra
değerli olanlar sonra.

Bu ayrım olmadan sıralama yapılırsa "doğal dille arama" gibi gösterişli ama
bugün boş sonuç döndürecek şeyler öne geçer.

## Zaten var olan altyapı — sıfırdan başlamıyoruz

| Parça | Nerede | Not |
|---|---|---|
| Sağlayıcıdan bağımsız AI katmanı | `App\Services\Ai\*`, `App\Contracts\AiProvider` | Claude/OpenAI/Gemini/OpenRouter, panelden yönetiliyor |
| `analyzeText()` | `AiProvider` sözleşmesinde | Salt-metin → yapılandırılmış JSON. **Zaten var** |
| Kamera-önce ilan | `ListingVisionService` + `/panel/ilan/hizli` | Fotoğraf → form önerisi (Faz M3) |
| AI görsel üretimi | `App\Services\Ai\FotografUretici` | OpenRouter üzerinden; bugün **yalnız demo** ilanlarında |
| Eksik alan tarayıcı | `App\Services\Kahya\EksikAlanTarayici` | Ama **sitenin ayarlarına** bakıyor, satıcıların ilanlarına değil |
| Görsel moderasyonu | `ImageModerationService` | Metin ikizi henüz yok |

## Reddedilen fikir ve gerekçesi

Sahibin ilk isteği "internetten konuyla ilgili resim getirip önermek"ti
(ör. *iphone 16 pro max gri* yazınca fotoğraf bulmak). **Yapılmadı.**

1. **Telif ihlali.** Üretici/perakendeci ürün fotoğrafları korumalı;
   "Temsili resimdir" etiketi bunu değiştirmez. Kitle Almanya ağırlıklı ve
   orada tek bir *Abmahnung* birkaç bin euro.
2. **Sitenin kendi kuralıyla çelişiyor.** "Sitedeki her bilgi gerçek"
   (bkz. gerçek-bilgi kuralı). Elde olmayan bir telefonun stok fotoğrafı
   gerçek değil.
3. **Dolandırıcılık yüzeyi.** Malı olmadan ikna edici ilan açmayı
   kolaylaştırır — IBAN kara listesi, dondur-parmak izi ve doğrulanmış işlem
   rozeti gibi güvenlik işlerinin tersine çalışır.

Yerine geçen: **ürün ≠ hizmet ayrımı.** Üründe gerçek fotoğraf şart
(kamera akışı zaten var). Hizmette ("Tercüme", "Nakliyat") temsilî görsel
tamamen dürüst — o ilanların zaten fotoğrafı olmaz.

## Sıra

### Arz gelmeden değerli

1. **Serbest metin → ilan taslağı.** Birkaç kelime *veya* WhatsApp'tan
   yapıştırılan ilan metni → başlık, kategori, açıklama, fiyat önerisi.
   Tek servis, iki giriş kapısı. Diaspora ticareti bugün WhatsApp
   gruplarında dönüyor; bu, var olan davranışı siteye taşır.
2. **Sesle ilan.** Kamera akışının sesli ikizi. Kitlenin bir kısmı telefonda
   uzun metin yazmıyor.
3. **Kâhya sahibe erişim mesajı taslağı yazsın.** Elle erişim listesindeki
   22 işletme bugün tek arz kanalı. Gönderim sahibe ait — ajan göndermez.
4. **Hizmet ilanlarına temsilî görsel.** `FotografUretici` demo verisinden
   çıkarılıp gerçek kullanıcıya açılır, YALNIZ hizmet ilanlarında, kalıcı
   etiketle.

### Arz geldikten sonra değerli

5. **İlanı yerel dile çevirme.** Almanca/Hollandaca başlık+açıklama → yerel
   arama trafiği. Çevirecek ilan gerekiyor.
6. **Dolandırıcılık deseni tespiti.** Açıklamada kapora isteme, platform
   dışına çekme kalıpları. Görsel moderasyonun metin ikizi.
7. **Doğal dille arama.** Sonuç boşken anlamsız.
8. **Kâhya satıcıya eksik-ilan bildirimi.** Motoru var, hedefi değişecek.
   Bugün tek gerçek satıcı sahip olduğu için kendi kendine bildirim
   göndermiş oluruz — bilerek sona bırakıldı.

## Her maddede geçerli kurallar

- **Kullanıcı onaylamadan hiçbir şey yayınlanmaz.** AI önerir, insan karar
  verir. Kamera akışının bugünkü sözleşmesi bu; bozulmayacak.
- **AI kapalıysa/kırıksa zarif geri düşüş.** Özellik yoksa normal form
  çalışmaya devam eder — hiçbir akış AI'a bağımlı olmaz.
- **Maliyet sınırı.** Gelir bağış + reklam; her çağrı para. Kişi başı günlük
  sınır olmadan hiçbir AI özelliği açılmaz.
- **Uydurma bilgi yasak.** Modelden görmediği teknik özelliği yazması
  istenmez; emin değilse `null` döner.
- **İletişim bilgisi ayıklanır.** Yapıştırılan metindeki telefon/e-posta
  ilan metnine taşınmaz (platform dışına çekme + gizlilik).

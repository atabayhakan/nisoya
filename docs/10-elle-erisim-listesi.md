# Elle Erişim Listesi — 22 işletme

**Bu liste elle yazmak içindir. Otomatik gönderim YOK ve olmayacak.**

Kaynak: `outreach_targets` havuzu (515 hedef → 59 e-postalı → elle elenmiş 22).
Hazırlandı 2026-08-08, **canlı havuza karşı yeniden doğrulandı 2026-08-10**.

## Neden elle?

AWS, SES üretim erişimi talebini **2026-08-09'da reddetti** (case 178543966900428;
ölçüt paylaşılmadı). Sahip kararı: **soğuk e-posta otomasyonu bırakıldı.** Keşif
havuzu yalnızca *kaynak liste* olarak kalıyor — kimseye sistem üzerinden posta
gitmiyor. Sahibin kendi kutusundan yazdığı tanışma postası bu kararın dışında;
o ticari toplu gönderim değil, birebir iletişim.

Bunun bir yan faydası var: birebir yazılan posta, otomatik gönderilenden daha
yüksek dönüş alır. Kaybedilen şey ölçek, kalite değil.

## Doğrulama (2026-08-10, canlı DB)

22 e-postanın **22'si de havuzda duruyor**; hepsi `marketing_status = allowed`,
hepsi `detection_band = turkish`, ülke alanları düzeltilmiş hâlde (20 US, 2 KZ).
Ülke onarımından (PR #131/#132/#133) sonra **hiçbiri düşmedi** — düşen 11 adres
Türkiye adresliydi ve zaten bu listede değildi.

> Havuzda 22 e-posta 24 satıra karşılık geliyor: *Istanbul Furniture* ve
> *Degirmen* ikişer kayıt (iki şube / iki yazım) ama tek e-posta. Bir kez yazmak
> ikisini de kapsar.

---

## A. ABD — 20 işletme

| # | İşletme | Yer | E-posta | Tür | Yazıldı |
|---|---|---|---|---|---|
| 1 | The Bosphorus | NJ 07110 | info@thebosphorus.us | Restoran | ☐ |
| 2 | Istanbul Grill House | NJ 07503 | istanbulgrillhouse01@gmail.com | Restoran | ☐ |
| 3 | Istanbul Cafe and Restaurant | NJ 07011 | hello@istanbulcaferestaurant.com | Restoran | ☐ |
| 4 | Istanbul Furniture (Istikbal) | PA 19053 · NJ 08016 | shop@istanbulfurniture.com | Mobilya | ☐ |
| 5 | Bellona Paramus | NJ 07652 | orders@bellonausa.com | Mobilya | ☐ |
| 6 | Bellona USA (toptan/üretim) | NJ 07503 | hello@bellona.live | Mobilya | ☐ |
| 7 | Basak Turkish Bakery | NJ 08003 | basakbakery2023@gmail.com | Fırın | ☐ |
| 8 | Ala Turco Express | NY 10021 | info@alaturcoexpress.com | Restoran | ☐ |
| 9 | Sophra Grill | NY 10016 | info@sophragrill.com | Restoran | ☐ |
| 10 | Sahara's Turkish Cuisine | NY 10016 | info@saharasturkishcuisine.com | Restoran | ☐ |
| 11 | Antalia NYC | NY 10036 | info@antalianyc.com | Restoran / catering | ☐ |
| 12 | Turkish Cuisine | NY 10036 | turkishcuisinenyc@gmail.com | Restoran | ☐ |
| 13 | Turkuaz Halal Turkish Mediterranean | NY 10019 | info@turkuazrestaurant.com | Restoran | ☐ |
| 14 | NAR — Modern Turkish & Mediterranean | NY 10003 | contact@narrestaurant.nyc | Restoran | ☐ |
| 15 | Zara Terrace Mediterranean | NY 10017 | 862zara@gmail.com | Restoran | ☐ |
| 16 | Zara Austin Mediterranean | NY 11375 | 7000zara@gmail.com | Restoran | ☐ |
| 17 | Foursome Restaurant and Bar | NY 10014 | foursomenycllc@gmail.com | Restoran | ☐ |
| 18 | Turkuaz Grill | NY 11901 | turkuazriverhead@gmail.com | Restoran | ☐ |
| 19 | KraftStories Türk Mozaik Lamba Atölyesi | NY 10018 | info@kraftstories.com | Atölye / etkinlik | ☐ |
| 20 | Doner Corner | CA 90025 | info@donercorner.net | Restoran | ☐ |

## B. Kazakistan — 2 işletme

| # | İşletme | Yer | E-posta | Tür | Yazıldı |
|---|---|---|---|---|---|
| 21 | Degirmen (iki şube) | Astana + Almatı | catering@degirmen.kz | Restoran | ☐ |
| 22 | Marmaris Restaurant | Almatı 050057 | marmarisrestaurant.shop@gmail.com | Restoran | ☐ |

## C. Ayrıca bak — kurum, işletme değil

**Turkish American Community Center** (NJ 07726) — `turkishamericancommunitycenter.org`

Bir işletme değil, **topluluk merkezi**. Nisoya'nın hedef kitlesi tam olarak
oranın üyeleri; tek bir kurumla konuşmak 20 restoranla konuşmaktan daha çok iş
görebilir. Havuzdaki e-posta (`sebastian@aldusleaf.org`) sitelerini yapan kişiye
ait görünüyor — doğru kanal muhtemelen sitedeki iletişim formu ya da telefon.
Bu yüzden ana listede değil.

---

## Yazarken

- **Bu tanışma, satış değil.** Ne sattığını değil ne işe yaradığını anlat.
- **Kısa tut ve TEK şey iste.** "İlan açar mısınız" tek eylemdir; "bakın,
  kaydolun, paylaşın" üç eylemdir ve hiçbiri yapılmaz.
- **Türkçe yaz.** Hepsi Türk işletmesi; İngilizce mesafe koyar.
- **Gmail adresliler genelde sahibinin kendi kutusu** — dönüş ihtimali `info@`
  adreslerinden yüksek. Listeye onlardan başlamak mantıklı.
- **Hepsini aynı gün gönderme.** Günde 4-5 tane, birkaç güne yay. Hem daha
  doğal, hem gelen cevaplara yetişebilirsin.
- Dönüş gelenleri yukarıdaki kutucuğa işaretle; ikinci bir posta atmadan önce
  bu listeye bak.

### Taslak — işletmeye tanışma postası

Aşağıdaki metin bir iskelettir; **her birine tek cümlelik kişisel bir dokunuş
ekle** (menüsünden bir şey, kaç yıldır orada olduğu, mahallesi). O tek cümle
"toplu postaya benzemeyen posta" ile "silinen posta" arasındaki farktır.

**Konu:** Yurtdışındaki Türkler için ücretsiz ilan alanı — Nisoya

> Merhaba,
>
> Ben Hakan. Yurtdışında yaşayan Türklerin birbirine hizmet verip ürün
> satabildiği ücretsiz bir platform kurdum: **nisoya.com**.
>
> *[buraya tek cümlelik kişisel dokunuş — ör. "New Jersey'de uzun süredir
> açık olduğunuzu gördüm" ya da "mozaik lamba atölyeniz tam da sitede
> aranacak türden bir şey"]*
>
> Sizi neden yazdım: işletmenizi Nisoya'da **ücretsiz** listeleyebilirsiniz.
> Komisyon yok, üyelik ücreti yok, ödemeye aracılık etmiyoruz — site yalnızca
> bir buluşma yeri. Bulunduğunuz şehirdeki Türkler sizi Türkçe arayarak
> bulabilir.
>
> İlgilenirseniz beş dakikada açılıyor: nisoya.com
> Soru olursa bu postayı yanıtlamanız yeterli.
>
> Kolay gelsin,
> Hakan · nisoya.com

**Dürüstlük notu:** platform henüz yeni ve ilan sayısı az. Metin bunu
gizlemiyor — "şehrindeki Türkler seni bulur" diyor, "binlerce müşteri" demiyor.
Tutulamayacak vaat ilk dönüşte anlaşılır ve o işletmeyi temelli kaybettirir.

---

İlgili: `docs/09-tanitim-mesaj-taslaklari.md` (kurum/topluluk mesajları),
`docs/06-tanitim-agenti-plani.md` (havuzun nasıl toplandığı).

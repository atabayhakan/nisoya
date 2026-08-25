# Temsilcilik sayfaları — harita düğmeleri + ziyaret öncesi ipuçları

**Tarih:** 2026-08-25 · **Durum:** Uygulandı
**Tetikleyici:** Nisoya AI aramaya "Pasaportum kayboldu" yazan bir kullanıcı Bişkek Büyükelçiliği'ne düştü; sonuç sayfası (temsilciliğin kendi sitesinde işlem bilgisi yayınlamadığı, yalnız yönlendirme notu olan hâli) "hiçbir bilgi yok" hissi verdi. İstenen: (1) Google Haritalar + o ülkede Google'dan daha güncel/yaygın bilinen yerel harita uygulaması (ör. Kırgızistan'da 2GIS) düğmeleri, (2) genel ziyaret-öncesi bilgilendirme (evrak çevirisi, biyometrik foto, noter/print hatırlatması).

---

## 0. Ölçülen gerçek kapsam

Kod okuması gösterdi ki `Temsilcilik` modelinde `adres`/`latitude`/`longitude` alanları zaten VARDI ama üretimde **77 temsilcilikten 0'ında** dolu değildi. 56/77 (%73) temsilcilik yalnız `yonlendirme_notu` taşıyor, hiç işlem içeriği yok. Yani harita düğmeleri kod olarak yazılsa bile veri olmadan hiçbir sayfada görünmeyecekti — asıl iş önce gerçek adres araştırmasıydı.

## 1. Adres araştırması (77 kurum → 76 adres bulundu)

9 paralel ajan (Workflow DEĞİL, düz Agent dispatch — sahip özellikle "workflow kullanma" dedi), her biri bir ülke grubu için ilgili temsilciliğin **kendi resmî mfa.gov.tr /Mission/Contact sayfasından** adres okudu. Sonuç: 76/77 bulundu (yalnız İzlanda/Reykjavik için güvenilir kaynak yok — bilerek boş bırakıldı, uydurulmadı). Üç kayıt (AD/Madrid, LI/Bern, SM/Roma) kendi büyükelçiliği olmayan mikro-devletler — komşu ülkedeki büyükelçilikle AYNI binayı paylaşıyor, tek araştırmadan iki kayda uygulandı.

## 2. Koordinat — LLM tahmini DEĞİL, gerçek geocode

Her adres metni Nominatim'e (OSM) gönderildi, dönen ülke kodunun beklenenle eştiği **programatik** doğrulandı (bir kerelik yerel script, `GeocodingService`'in aynı Nominatim deseni). İlk turda ~%29 başarısızlık (Nominatim'in "Suite 900" gibi ticari adresleri çözememesi) — basitleştirilmiş sorgularla ve gerektiğinde şehir merkezine düşülerek 76/76 kapatıldı. **Bir gerçek hata bulundu ve düzeltildi**: Buenos Aires ilk sorguda 350km uzaktaki Tandil'e düşmüştü (ülke kodu eşleşiyordu ama şehir yanlıştı) — country-code doğrulamasının TEK BAŞINA yeterli olmadığını gösterdi, her sonucun `display_name`'i elle okunarak şehir düzeyinde de doğrulandı.

## 3. Veri nereye yazıldı

- `RehberTemsilcilikleriSeeder` (22 kayıt: DE×14+US×7+KG×1, deploy zincirinde otomatik çalışır) — `adres`/`latitude`/`longitude` yalnız hâlâ BOŞSA doldurulur (var olan `test_panelden_yapilan_duzeltme_ezilmez` testiyle aynı sözleşme).
- `RehberDunyaTemsilcilikIskeletiSeeder` (55 kayıt, deploy zincirinde DEĞİL — elle bir kez çalıştırılır) — aynı "yalnız boşsa doldur" deseni yeni eklendi.
- `resmi_url` bu turda 55 iskelet kayda EKLENMEDİ: iki bağımsız araştırma Madrid için çelişen alt alan adı buldu (`madrid-emb` vs `madrid-be`) — doğrulanmamış bir link, linksiz olmaktan kötü.

## 4. Özellik kodu

- `Temsilcilik::haritaBaglantilari()` — koordinat yoksa boş döner (kırık söz yok). Google Haritalar her zaman; ülke `AZ/KZ/KG/UZ/RU` listesindeyse 2GIS de eklenir (2026-08-25 canlı doğrulandı: `https://2gis.{domain}/geo/{boylam},{enlem}` — Google'ın TERSİ sırada). Türkmenistan bilerek YOK: `2gis.tm` alan adı yok.
- `rehber/temsilcilik.blade.php` — harita düğmeleri (varsa) + "Ziyaret öncesi" kutusu (ülkeden bağımsız, genel ve doğrulanmış 4 madde — belirli bir noter/fotoğrafçı İSİMLENDİRİLMİYOR, hiçbiri doğrulanmadı).

## 5. Test

`TemsilcilikHaritaTest` (model), seeder testlerine "adres dolduruluyor" + "panelden girilen ezilmez" + "İzlanda boş kalır" + "akredite ülke gerçek binanın koordinatını taşır" eklendi, `RehberTest`'e sayfa-düzeyi 3 test (ipuçları her zaman görünür, koordinat varsa/yoksa düğme).

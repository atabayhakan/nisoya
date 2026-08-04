---
baslik: İlan yaşam döngüsü
ozet: Bir ilan hangi durumlardan geçer, moderasyon nerede devreye girer, neden görünmüyor olabilir.
sira: 30
etiketler: ilan, moderasyon, durum, yayın
---

## Durumlar

İlan şu durumlardan geçer: **taslak → beklemede (moderasyon) → aktif**, ya da
**pasif** / **reddedildi**.

{{surec:ilan-yasam-dongusu}}

Yalnız **aktif** ilanlar sitede görünür ve aramada çıkar.

## "İlanım neden görünmüyor?"

Sırayla bak:

1. **Durumu aktif mi?** Beklemede ise moderasyon kuyruğundadır.
2. **Ülke filtresi** — ziyaretçi başka ülkedeyse listede çıkmaz.
3. **Kategori boş mu?** Boş kategori sayfaları arama motoruna kapalıdır
   (`noindex`), ama site içinde görünür.
4. **Örnek ilan mı?** `[ÖRNEK]` etiketli demo ilanlar gerçek envantere
   **sayılmaz** ve bazı yüzeylerde gizlenir.

## Moderasyon

Bekleyen ilanlar `/panel` ekranında ve Kâhya'nın günlük raporunda "Seni
bekleyen işler" altında görünür. Görsellerde otomatik kontrol var: GPS verisi
temizlenir, uygunsuz içerik işaretlenir.

## Öne çıkarma

Öne çıkarma talepleri ücretlidir ve **süre dolunca otomatik düşer**. Süresi
geçmiş ilan listede haksız yere üstte kalmaz.

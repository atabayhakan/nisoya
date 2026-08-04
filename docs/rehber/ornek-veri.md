---
baslik: Örnek (demo) veri
ozet: Demo veri nasıl üretilir, nasıl geri alınır ve neden hiçbir sayıya dahil edilmez.
sira: 80
etiketler: demo, örnek, veri
---

## Ne işe yarar

Boş bir siteyi tanıtırken ya da bir ekranı denerken gerçekçi içerik gerekir.
Örnek veri makinesi bunu üretir — ama **gerçek gibi davranmaz**.

## Üç güvence

1. **Her şey işaretli.** Üyeler `@demo.invalid` adresli, ilan başlıkları
   `[ÖRNEK]` önekli, görsellerde ÖRNEK filigranı var.
2. **Hiçbir sayıya girmez.** Kâhya teşhisi, ana sayfadaki kanıt şeridi ve
   yatırımcı belgesi demo kayıtları **saymaz**. "Gerçek envanter" demo veriyle
   şişirilemez — bu bilinçli.
3. **Geri alınabilir.** Üretilen her kayıt ve diske yazılan her dosya deftere
   yazılır; silme yalnız **defterdeki** kayıtları siler, gerçek veriye
   dokunamaz.

## Varsayılan gizli

Görünürlük istenmedikçe demo ilanlar **taslak** doğar ve sitede görünmez.
Görünür kipte bile kartlarda ÖRNEK işareti çıkar ve o ilanlara mesaj
gönderilemez.

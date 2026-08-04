---
baslik: E-posta ve kuyruk
ozet: SMTP ayarı üç yerde olabilir; e-posta gitmiyorsa sırayla nereye bakılır.
sira: 70
ekran: App\Filament\Pages\MailAyarlari
etiketler: eposta, smtp, kuyruk, bildirim
---

## E-posta gitmiyorsa

Sırayla bak:

1. **Mail ayarları sayfasından test e-postası gönder.** Hata mesajı doğrudan
   sebebi söyler.
2. **Kuyruk çalışıyor mu?** Bildirimler kuyruğa düşer; kuyruk durmuşsa
   e-posta "gönderildi" görünüp hiç çıkmaz.
3. **Kimlik bilgileri.** Sağlayıcı parolayı değiştirmiş olabilir.

## SMTP ayarı üç yerde olabilir — dikkat

Ayar sırası şu: **panelde kayıt varsa `.env`'i EZER.** Yani sunucudaki `.env`
dosyasını değiştirmek tek başına yetmez; panelde de kayıt varsa o kazanır.

Parolayı değiştirirken üç yeri birden düşün: panel ayarı · sunucu `.env` ·
sağlayıcı hesabı.

## Kuyruk

Kuyruk işçisi bildirimleri işler. Bir iş çok uzun sürerse tekrar denenir;
tekrar deneme süresi işçinin zaman aşımından **uzun** olmalı, yoksa aynı iş iki
kez çalışır (çift e-posta). Bu değişmez testle korunuyor.

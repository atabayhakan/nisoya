---
baslik: Acil durum — sen yokken
ozet: Sana ya da yapay zekâya ulaşılamadığında izlenecek adımlar.
sira: 110
ekran: App\Filament\Pages\KurtarmaKiti
etiketler: acil, kurtarma, felaket
---

Bu sayfa **bugün** okunmalı, ihtiyaç duyulduğu gün değil.

## 1. Parola unutuldu, e-posta çalışmıyor

Kurtarma Kiti'nden önceden oluşturulmuş bir kurtarma koduyla `/hesap-kurtar`
sayfasından parola **e-postasız** sıfırlanır.

> Kurtarma kodları önceden oluşturulmuş olmalı. Kilitlendikten sonra
> oluşturulamaz.

## 2. Panele hiç girilemiyor (parola + 2FA + kod kayıp)

Sunucuya erişim varsa son çare:

    php artisan admin:recover eposta@ornek.com

Parolayı sıfırlar, hesabı Yönetici + Aktif yapar.

## 3. Site bozuldu

Yedekleme sayfasından en güncel yedek indirilir; veritabanı dökümü ve `media/`
klasörü sunucuya geri yüklenir. Adımlar Yedekleme sayfasında yazılı.

## 4. Yapay zekâ sağlayıcısı çöktü

Yapay Zekâ Ayarları'ndan **tek düğmeyle** kapatılır. Site AI olmadan tam
çalışır; yalnız Kâhya ve içerik önerileri devre dışı kalır.

## İkinci yönetici

Kurtarma Kiti'nden ikinci bir yönetici tanımla. Tek yöneticili bir panel, o
kişiye ulaşılamadığı gün kilitlenir.

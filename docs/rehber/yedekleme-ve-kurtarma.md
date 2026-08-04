---
baslik: Yedekleme ve kurtarma
ozet: Günlük otomatik yedek açık; panele kilitlenirsen kurtarma kodu ile e-postasız girebilirsin.
sira: 20
ekran: App\Filament\Pages\Yedekleme
etiketler: yedek, kurtarma, acil, felaket
---

## Yedek nasıl alınır

Günlük otomatik yedek **zaten açık** (varsayılan 04:00). Elle yedek almak için
Yedekleme sayfasındaki düğmeyi kullan; yedek veritabanı dökümünü ve `media/`
klasörünü içerir.

## Panele giremiyorsan

Sırayla dene:

1. **Parolamı unuttum, e-posta çalışıyor** → giriş ekranından parola sıfırlama.
2. **E-posta da çalışmıyor** → Kurtarma Kiti'nden önceden oluşturduğun
   kurtarma koduyla `/hesap-kurtar` sayfasından e-postasız sıfırla.
3. **Parola + 2FA + kod hepsi kayıp** → sunucuya erişimin varsa son çare:
   `php artisan admin:recover eposta@ornek.com` — parolayı sıfırlar, hesabı
   Yönetici + Aktif yapar.

> Kurtarma kodlarını **bugün** oluştur. İhtiyaç duyduğun gün oluşturamazsın.

## Site bozulduysa

Yedekleme sayfasından en güncel yedeği indir; içindeki veritabanı dökümünü ve
`media/` klasörünü sunucuya geri yükle. Adımlar Yedekleme sayfasında yazılı.

## Geri alınamaz işler

- **Hesap silme** (üye kendi siler) — ilanları, yorumları ve kişisel verisi
  kalıcı temizlenir.
- **Örnek veri silme** yalnız defterdeki kayıtları siler; gerçek veriye
  dokunamaz. Bu bilinçli bir güvence.

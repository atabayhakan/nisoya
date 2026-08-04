---
baslik: İçerik ve sayfalar
ozet: Sayfa oluşturma, yayın zamanı, footer menüsü ve SEO alanları.
sira: 50
ekran: App\Filament\Resources\Pages\PageResource
etiketler: sayfa, içerik, seo, yayın
---

## Yeni sayfa

İçerik & Tasarım → Sayfalar → Yeni. Her sayfanın kısa adı (URL) benzersiz olmalı.

## Yayın zamanı — en sık yapılan hata

İki alan var ve karıştırılıyor:

| Alan | Ne yapar |
|---|---|
| **Durum** | Taslak / Yayında |
| **Yayın zamanı (ileri tarih)** | **Boşsa anında yayında.** Doluysa o tarihe kadar gizli. |

> Sayfayı hemen yayınlamak istiyorsan **yayın zamanını boş bırak**. İleri bir
> tarih girmek yayını geciktirir — arama motorunun indekslemesi de o kadar
> gecikir.

## SEO

Meta açıklaması boş bırakılırsa sayfa içeriğinden türetilir. Sayfa yayına
girdiğinde sitemap'e **en geç 15 dakikada** düşer (sitemap önbelleği).

## Footer menüsü

"Footer menüsünde göster" işaretli sayfalar alt menüde çıkar, "Sıra" alanına
göre dizilir.

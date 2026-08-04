---
baslik: Modüller ve görünüm
ozet: Bir modülü kapatınca ne oluyor, tema nasıl değişiyor, marka rengi nereyi etkiliyor.
sira: 90
ekran: App\Filament\Pages\TasarimAyarlari
etiketler: modül, tema, tasarım, marka
---

## Modül kapatmak

Bir modülü kapattığında (emlak, vasıta, davetiye, rehber…) o modülün **tüm
yüzeyi** kapanır: menü bağları, sayfalar 404 döner ve **sitemap'ten anında
düşer** — süre beklemeden.

Modül kapatmak veri silmez. Tekrar açtığında her şey yerindedir.

## Tema

İki tema var: **Klasik** ve **Vitrin**. Tema değiştiğinde ana sayfa, ilan
listesi ve ilan detayı farklı şablonlarla basılır; bazı ekranların Vitrin
karşılığı yoktur ve klasik şablona düşer — bu normaldir.

## Marka rengi

Marka rengi tek yerden yönetilir ve **beş yeri birden** etkiler: site teması,
tarayıcı sekmesi rengi, favicon, PWA manifesti ve paylaşım kartları.

> Marka rengini değiştirdiğinde daha önce üretilmiş paylaşım kartları da
> yenilenir — önbellek anahtarı rengi içeriyor.

## Ana sayfa bölümleri

Ana sayfadaki bölümler panelden **sıralanabilir** ve gizlenebilir. Her tema
kendi sırasını tutar.

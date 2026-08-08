<?php

/*
|--------------------------------------------------------------------------
| Medya slotları
|--------------------------------------------------------------------------
|
| Bir slot, görselin NE İÇİN olduğunu koda yazar. Yükleme artık "bir dosya al"
| değil "BU SLOT İÇİN bir dosya al" demektir; sistem doğru boyutu üretir,
| ağırlığı hedefin altına indirir, en-boy oranını uygular.
|
| NEDEN KOD, NEDEN PANEL DEĞİL: bunlar tasarımın parçası, günlük ayar değil.
| Panelden değiştirilebilseydi tek bir yanlış sayı tüm görselleri bozardı.
| Değişiklik kod incelemesinden geçsin, sonra `media:yeniden-turet` koşsun.
|
| ÖNCESİ (2026-08-09'a kadar): boyut yalnızca yardım metninde yazıyordu
| ("2400×1200 önerilir"). Kod onu okumuyordu — 4469×2979 / 402 KB bir dosya
| itirazsız geçti, ardından 1800×1200 yine itirazsız geçti.
|
| ANAHTARLARDA NOKTA YOK — BİLİNÇLİ
|
| `config('media_slots.hero.masaustu')` çağrısı Laravel'de İÇ İÇE arama yapar
| (media_slots → hero → masaustu) ve noktalı bir anahtarı BULAMAZ; sessizce
| null döner. Sessiz null bu depodaki en pahalı hata biçimi — anahtarlar bu
| yüzden alt çizgili: `hero_masaustu`.
|
| ALANLAR
|   en, boy   : hedef piksel. `boy` yoksa yalnız genişlik sınırlanır.
|   kip       : 'kapla'  → kutuyu doldur, taşanı kırp (hero, kart)
|                'sigdir' → kırpma yok, kutuya sığdır (logo, rozet)
|   azami_kb  : hedef ağırlık; tutmazsa kalite kademeli düşürülür.
|   turet     : bu slot başka bir slotun ana kopyasından türetilebilir.
|                Kendi dosyası yüklenirse O KAZANIR.
|   seffaf    : true ise WebP saydamlık korunur (logo).
|
*/

return [

    /*
     * HERO — ana sayfanın üst bloğu.
     *
     * 2:1 masaüstü: hero kutusu 1265×603 render oluyor (ölçüldü). 2400 genişlik
     * retina (2×) için; 1200 yükseklik 2:1 oranı verir ve kırpmayı en aza indirir.
     * 1800×1200 (3:2) yüklendiğinde dikeyde ~%28 kırpılıyordu.
     *
     * Mobil 2:3 dikey: mobil hero 360×672 render oluyor, yani DİKEY. Yatay bir
     * görseli oraya koymak alanın çoğunu boşa harcar; bu yüzden ayrı slot ve
     * masaüstü ana kopyasından türetme.
     */
    'hero_masaustu' => [
        'etiket' => 'Hero — masaüstü',
        'en' => 2400,
        'boy' => 1200,
        'kip' => 'kapla',
        'azami_kb' => 250,
    ],

    'hero_mobil' => [
        'etiket' => 'Hero — mobil',
        'en' => 1080,
        'boy' => 1620,
        'kip' => 'kapla',
        'azami_kb' => 150,
        'turet' => 'hero_masaustu',
    ],

    /*
     * ZONE — reklam/duyuru alanları (bkz. App\Support\Zone).
     */
    'zone_banner' => [
        'etiket' => 'Alan görseli (banner)',
        'en' => 1600,
        'boy' => 400,
        'kip' => 'kapla',
        'azami_kb' => 150,
    ],

    /*
     * ANA SAYFA VURGU KARTLARI (home_highlights.media).
     */
    'vurgu_buyuk' => [
        'etiket' => 'Vurgu kartı — büyük',
        'en' => 1200,
        'boy' => 800,
        'kip' => 'kapla',
        'azami_kb' => 180,
    ],

    'vurgu_kucuk' => [
        'etiket' => 'Vurgu kartı — küçük',
        'en' => 800,
        'boy' => 600,
        'kip' => 'kapla',
        'azami_kb' => 120,
    ],

    /*
     * MARKA LOGOSU — kırpılmaz.
     *
     * `sigdir` şart: logo kırpılırsa marka bozulur. Saydamlık korunur, aksi
     * hâlde koyu temada beyaz bir kutu içinde görünür.
     */
    'marka_logo' => [
        'etiket' => 'Marka logosu',
        'en' => 600,
        'boy' => 200,
        'kip' => 'sigdir',
        'azami_kb' => 60,
        'seffaf' => true,
    ],

    /*
     * SAYFA İÇERİĞİ — yönetilebilir sayfalara gömülen görseller.
     */
    'sayfa_icerik' => [
        'etiket' => 'Sayfa içeriği',
        'en' => 1600,
        'kip' => 'sigdir',
        'azami_kb' => 200,
    ],

];

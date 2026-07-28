{{--
    YAZI TİPİ YÜKLEME — paylaşılan tek kopya (klasik + Vitrin iskeletleri).

    NEDEN VAR: vite.config.js üç yazı tipini self-host ediyordu, `npm run
    build` woff2/woff dosyalarını ve `fonts-*.css`i üretiyordu, hepsi
    sunucuya kopyalanıyordu — ve HİÇBİRİ kullanılmıyordu. Üretilen font
    stil sayfası bir Vite "entry"si değil, bir yan çıktıdır; `@vite([...])`
    ona `<link>` basmaz. Onu sayfaya sokan tek şey Laravel'in `Vite::fonts()`
    çağrısıdır ve hiçbir iskelet onu çağırmıyordu.

    SONUÇ: site, tasarım sisteminin ilan ettiği yazı tipiyle DEĞİL, ziyaretçinin
    işletim sistemi fontuyla çiziliyordu (Windows'ta Segoe UI, macOS'ta SF Pro).
    Tarayıcıda ölçüldü: `document.fonts.size === 0` ve "Instrument Sans" ile
    `ui-sans-serif` aynı metni piksel piksel aynı genişlikte çiziyordu (238.81px).
    Kalın metinler de bu yüzden sahteydi — sistem fontunun 600'ü şişiriliyordu.

    `Vite::fonts($aliases)` iki şey basar: seçili ailelerin @font-face'lerini
    SATIR İÇİ stil olarak (ek istek yok, render engellemez) ve yalnız
    vite.config.js'te `preload` ile işaretlenmiş varyantlar için preload
    bağlantısı.

    ALIAS'LAR TEMAYA GÖRE: her sayfaya üç ailenin de @font-face'ini basmak
    kullanılmayan bir ailenin kurallarını taşımak demekti. Klasik iki
    Instrument'ı ister (panel yazı tipi seçicisinin iki seçeneği de bunlar —
    bkz. TemaJetonlari::FONTLAR, kapsam yalnız 'klasik'), Vitrin yalnız Plus
    Jakarta Sans'ı.

    Manifest yoksa (henüz derlenmemiş ortam) Laravel sessizce boş döner;
    sayfa font olmadan da açılır.
--}}
{{ Vite::fonts(\App\Support\Tema::vitrinMi() ? ['plus-jakarta-sans'] : ['instrument-sans', 'instrument-serif']) }}

{{-- Vitrin temasında bu bileşen TAMAMEN susar: preset'lerin tasarim_modu ile
     birlikte yazdığı font/renk/radius/cam ayarları da klasiğin iç varyantıdır
     (panel metni: "tüm ince ayarlar Klasik temada geçerlidir"). Yalnız $mod'u
     bastırıp bunları uygulamak, vitrin'e düşen fallback sayfalara önceki
     preset'in serif fontunu/özel rengini sızdırırdı (P0 inceleme bulgusu #4). --}}
@unless (\App\Support\Tema::vitrinMi())
@php
    $mod = \App\Support\Tema::tasarimModu();
    $primaryColor = setting('gorunum.primary_color', '#059669');
    $fontFamily = setting('gorunum.font_family', 'sans');
    $borderRadius = setting('gorunum.border_radius', 'modern');
    $glass = setting('gorunum.glassmorphism', '1') === '1';
    $smoothAnimations = setting('gorunum.smooth_animations', '1') === '1';

    // Geçerli #rgb / #rrggbb değilse güvenli varsayılana düş — böylece hem bozuk
    // :root bloğu hem de <style> içine CSS enjeksiyonu (denetim #10) engellenir.
    if (! preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string) $primaryColor)) {
        $primaryColor = '#059669';
    }

    // Varsayılan marka rengi (emerald-600) ise Tailwind'in kendi ince ayarlı
    // OKLCH tonlarını bozmayalım; yalnızca ÖZEL renk seçilince rampayı türet.
    $customPrimary = strtolower($primaryColor) !== '#059669';

    // Başlık/gövde yazı tipi. Liste tek kaynaktan gelir (TemaJetonlari::FONTLAR)
    // ve orada YALNIZ self-host edilen aileler bulunur; eskiden buradaki
    // 'inter'/'outfit' dalları hiçbir yerden yüklenmeyen ailelere işaret ediyor,
    // yani sessizce sistem sans'ına düşüyordu. Geçersiz/eski kayıtlı değerler
    // artık doğrudan varsayılana düşer.
    $fontFamilyCss = \App\Support\TemaJetonlari::fontCss($fontFamily);

    // Köşe yuvarlatma ölçeği — Tailwind --radius-* token'larını hedefler.
    // 'modern' bilerek Tailwind varsayılanlarına eşittir (dokunmamış siteler
    // için no-op); diğerleri belirgin şekilde sapar. rounded-full etkilenmez.
    // TEK KAYNAK — panelin önizlemesi de aynı metottan okur. İki yerde ayrı
    // tutulduğu sürece sessizce ayrışmıştı (modern 14px↔12px, pill 24px↔18px).
    $radiusScale = \App\Support\TemaJetonlari::koseOlcegi($borderRadius);

    // Mod imza aksan rengi (mühür) — app.js, verified-badge ve pulse-map okur.
    $seal = match ($mod) {
        'yeni' => '#c1440e',
        'obsidian' => '#f59e0b',
        'nordic' => '#6366f1',
        default => null,
    };

    // Açık zemin tonu — yeni/nordic hafif tint uygular. Obsidian ARTIK stone-50'yi
    // ele geçirmez: onu near-black yapmak açık modda sayfayı okunmaz, koyu modda
    // dark:text-stone-50 başlıklarını görünmez kılıyordu (denetim #4).
    $stone50 = match ($mod) {
        'yeni' => '#f3eee4',
        'nordic' => '#f8fafc',
        default => null,
    };
@endphp

<style>
    :root {
        @if ($customPrimary)
            /* Marka birincil rengi → tüm emerald tonları tek hex'ten türetilir;
               böylece buton/hover/dark/rozet/ring hepsi tutarlı olur (#3, #12). */
            --color-emerald-50: color-mix(in srgb, {{ $primaryColor }} 8%, white);
            --color-emerald-100: color-mix(in srgb, {{ $primaryColor }} 15%, white);
            --color-emerald-200: color-mix(in srgb, {{ $primaryColor }} 28%, white);
            --color-emerald-300: color-mix(in srgb, {{ $primaryColor }} 45%, white);
            --color-emerald-400: color-mix(in srgb, {{ $primaryColor }} 68%, white);
            --color-emerald-500: color-mix(in srgb, {{ $primaryColor }} 85%, white);
            --color-emerald-600: {{ $primaryColor }};
            --color-emerald-700: color-mix(in srgb, {{ $primaryColor }} 80%, black);
            --color-emerald-800: color-mix(in srgb, {{ $primaryColor }} 62%, black);
            --color-emerald-900: color-mix(in srgb, {{ $primaryColor }} 45%, black);
            /* 950 EKSİKTİ (2026-08-06 denetimi). Sitede 48 yerde kullanılıyor
               (koyu zeminler, rozetler); türetilmediği için özel renk seçen
               sahip her yerde MOR görürken bu tonlarda YEŞİL kalıntı
               kalıyordu. "Tüm emerald tonları tek hex'ten türetilir" iddiası
               bu satır olmadan doğru değildi. */
            --color-emerald-950: color-mix(in srgb, {{ $primaryColor }} 30%, black);
        @endif

        /* Tipografi motoru (#3). $fontFamilyCss sabit match() çıktısıdır (kullanıcı
           girdisi değil); font adlarındaki tırnaklar Blade kaçışıyla &#039;'e
           dönüşüp style bloğunda bozulmasın diye ham basılır. */
        --font-sans: {!! $fontFamilyCss !!};

        /* Köşe yuvarlatma (#3) */
        --radius-lg: {{ $radiusScale['lg'] }};
        --radius-xl: {{ $radiusScale['xl'] }};
        --radius-2xl: {{ $radiusScale['2xl'] }};
        --radius-3xl: {{ $radiusScale['3xl'] }};
        {{-- ÖLÜ DEĞİŞKENLER SİLİNDİ (2026-08-06 denetimi).
             `--nisoya-primary`, `--nisoya-radius`, `--nisoya-font`,
             `--nisoya-glass-blur` ve `--nisoya-glass-bg` yazılıyor ama depoda
             HİÇBİR YERDE `var()` ile okunmuyordu (ölçüldü: 0 kullanım). Gerçek
             iş `--font-sans`, `--radius-*` ve aşağıdaki backdrop-filter kuralı
             üzerinden dönüyor. Yalnız `--nisoya-seal` gerçekten tüketiliyor
             (verified-badge, pulse-map, app.js).

             Okunmayan bir değişken zararsız görünür ama "cam efekti burada
             ayarlanıyor" izlenimi verir; bu sayfadaki yanlış etiketin kaynağı
             tam olarak buydu. --}}
        @if ($seal)
            --nisoya-seal: {{ $seal }};
        @endif
        @if ($stone50)
            --color-stone-50: {{ $stone50 }};
        @endif
    }

    @unless ($glass)
        /* Glassmorphism kapalı → buzlu cam yüzeyleri opaklaşır. */
        [class*="backdrop-blur"] {
            -webkit-backdrop-filter: none !important;
            backdrop-filter: none !important;
        }
    @endunless

    @unless ($smoothAnimations)
        /* "Akıcı Geçiş Animasyonları" kapalı → tüm geçiş/animasyonlar anlık olur.
           Bileşenler çalışmaya devam eder, yalnızca yumuşak geçiş efekti kalkar. */
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    @endunless

    /*
     * ZİYARETÇİNİN HAREKET-AZALTMA TERCİHİ ARTIK BURADA DEĞİL.
     *
     * Taşındı: resources/css/app.css (gerekçe orada uzun uzun yazılı).
     *
     * Özeti: bu dosyanın TAMAMI unless(vitrinMi()) ile sarılı, yani vitrin
     * temasında hiç basılmıyor. Sahibin tasarım kontrollerinin vitrinde kapalı
     * olması bilinçli bir karar; ama hareket-azaltma sahibin ayarı değil,
     * ziyaretçinin erişilebilirlik tercihi. Aynı kapıya bağlı olduğu için
     * onlarla birlikte kapanmıştı ve canlıda ölçülene kadar fark edilmedi.
     *
     * Buraya geri KOYMA. Her iki temanın da yüklediği tek dosyada durması,
     * temaya bağlanmasını yapısal olarak imkânsız kılıyor.
     *
     * Yukarıdaki unless(smoothAnimations) bloğu FARKLI bir şeydir ve burada
     * kalmalıdır: o SAHİBİN düğmesi ("herkese kapat"), bu ise ziyaretçinin
     * tercihi ("isteyene kapat"). Biri diğerinin yerini tutmaz.
     *
     * (DİKKAT: bu yorumun içine Blade direktifi YAZILMAZ — CSS yorumu olması
     * Blade'i durdurmuyor, direktifi gerçek sanıp derliyor ve kapatılmamış blok
     * üretiyor. Bu yüzden yukarıda "unless" işaretsiz yazıldı.)
     */
</style>
@endunless

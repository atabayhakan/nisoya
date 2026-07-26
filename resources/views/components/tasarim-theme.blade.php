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

    // Başlık/gövde yazı tipi (tipografi motoru). Inter/Outfit yüklü değilse
    // sistem sans'ına zarafetle düşer; serif belirgin şekilde farklıdır.
    $fontFamilyCss = match ($fontFamily) {
        'serif' => "'Instrument Serif', Georgia, 'Times New Roman', serif",
        'inter' => "'Inter', system-ui, sans-serif",
        'outfit' => "'Outfit', system-ui, sans-serif",
        default => "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
    };

    // Köşe yuvarlatma ölçeği — Tailwind --radius-* token'larını hedefler.
    // 'modern' bilerek Tailwind varsayılanlarına eşittir (dokunmamış siteler
    // için no-op); diğerleri belirgin şekilde sapar. rounded-full etkilenmez.
    $radiusScale = match ($borderRadius) {
        'sharp' => ['lg' => '2px', 'xl' => '3px', '2xl' => '4px', '3xl' => '6px'],
        'soft' => ['lg' => '6px', 'xl' => '8px', '2xl' => '10px', '3xl' => '14px'],
        'pill' => ['lg' => '14px', 'xl' => '18px', '2xl' => '24px', '3xl' => '32px'],
        default => ['lg' => '.5rem', 'xl' => '.75rem', '2xl' => '1rem', '3xl' => '1.5rem'],
    };

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
        @endif

        /* Tipografi motoru (#3). $fontFamilyCss sabit match() çıktısıdır (kullanıcı
           girdisi değil); font adlarındaki tırnaklar Blade kaçışıyla &#039;'e
           dönüşüp style bloğunda bozulmasın diye ham basılır. */
        --font-sans: {!! $fontFamilyCss !!};
        --nisoya-font: {!! $fontFamilyCss !!};

        /* Köşe yuvarlatma (#3) */
        --radius-lg: {{ $radiusScale['lg'] }};
        --radius-xl: {{ $radiusScale['xl'] }};
        --radius-2xl: {{ $radiusScale['2xl'] }};
        --radius-3xl: {{ $radiusScale['3xl'] }};
        --nisoya-primary: {{ $primaryColor }};
        --nisoya-radius: {{ $radiusScale['xl'] }};

        /* Cam efekti (#3) */
        @if ($glass)
            --nisoya-glass-blur: blur(12px);
            --nisoya-glass-bg: rgba(255, 255, 255, 0.75);
        @else
            --nisoya-glass-blur: none;
            --nisoya-glass-bg: #ffffff;
        @endif

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
        /* "Akıcı Geçiş Animasyonları" kapalı → tüm geçiş/animasyonlar anlık olur
           (reduced-motion modu). Bileşenler çalışmaya devam eder, yalnızca
           yumuşak geçiş efekti kalkar. */
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    @endunless
</style>
@endunless

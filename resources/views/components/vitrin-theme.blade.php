{{-- VİTRİN TEMA MOTORU (P1) — yalnız vitrin iskeleti tarafından basılır.
     Handoff kuralı: sınıf adları DEĞİŞMEZ; klasik ağaçtaki emerald-*/stone-*
     sınıfları CSS değişken yönlendirmesiyle Vitrin paletine bürünür. Böylece
     vitrin karşılığı henüz yazılmamış fallback sayfalar da tutarlı görünür.
     Palet: handoff README "Renk (Vitrin / açık + karanlık)" tabloları. --}}
<style>
    :root {
        /* Birincil: Deniz mavisi rampası (#3E63F0; 950 = mürekkep #10203c) */
        --color-emerald-50: #eef2ff;
        --color-emerald-100: #dfe6fb;
        --color-emerald-200: #c3d0fa;
        --color-emerald-300: #a8c0ff;
        --color-emerald-400: #8ea5ff;
        --color-emerald-500: #5b7cff;
        --color-emerald-600: #3E63F0;
        --color-emerald-700: #2f4fd0;
        --color-emerald-800: #26409f;
        --color-emerald-900: #1d316f;
        --color-emerald-950: #10203c;

        /* Nötrler: taş grisi → Vitrin gri-mavisi (zemin #eef2f8, mürekkep #10203c) */
        --color-stone-50: #eef2f8;
        --color-stone-100: #f2f5fa;
        --color-stone-200: #e2e8f4;
        --color-stone-300: #c3ccdd;
        --color-stone-400: #9aa6bf;
        --color-stone-500: #7b89a6;
        --color-stone-600: #64748f;
        --color-stone-700: #4d5c7a;
        --color-stone-800: #10203c;
        --color-stone-900: #131c2f;
        --color-stone-950: #0b1220;

        /* Tipografi: Plus Jakarta Sans (Bunny self-host; emoji fallback'leri korunur) */
        --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji',
            'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';

        /* Yarıçap ölçeği: 12 input · 14 buton/küçük kart · 20 kart · 24 dış blok */
        --radius-lg: 12px;
        --radius-xl: 14px;
        --radius-2xl: 20px;
        --radius-3xl: 24px;

        /* İki katmanlı Vitrin gölgesi (her zaman kenarlıkla birlikte kullanılır) */
        --shadow-brand: 0 2px 4px rgba(16, 32, 64, 0.04), 0 20px 40px -28px rgba(16, 32, 64, 0.5);
        --shadow-brand-lg: 0 24px 50px -22px rgba(16, 32, 64, 0.75);

        --nisoya-primary: #3E63F0;
        --nisoya-seal: #3E63F0;
    }

    /* Karanlık set: zemin #0b1220, kart #131c2f, birincil açık mavi #6b8afd
       (butonlarda koyu metin — klasik dark:text-stone-900 sınıfı #131c2f'e
       yönlendiği için bu sözleşme kendiliğinden tutar). Gölge yok; ayrım
       kenarlık + yüzey açıklığıyla. */
    .dark {
        --color-emerald-300: #a8c0ff;
        --color-emerald-400: #8ea5ff;
        --color-emerald-500: #6b8afd;
        --color-emerald-600: #6b8afd;
        --color-emerald-700: #8ea5ff;

        --color-stone-50: #f2f5fb;
        --color-stone-100: #e6ebf5;
        --color-stone-200: #b7c2d8;
        --color-stone-300: #94a3bf;
        --color-stone-400: #8f9dbb;
        --color-stone-500: #7b89a6;
        --color-stone-600: #64748f;
        --color-stone-700: #33415e;
        --color-stone-800: #243149;
        --color-stone-900: #131c2f;
        --color-stone-950: #0b1220;

        --shadow-brand: none;
        --shadow-brand-lg: none;
    }

    /* Vitrin animasyonları (handoff "Hareket" tablosu) — hepsi
       prefers-reduced-motion ile kapanır, viewport'a girişte x-reveal tetikler. */
    @keyframes vitrin-bar-up {
        from { transform: scaleY(0.08); }
    }

    @keyframes vitrin-float-y {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-9px); }
    }

    @keyframes vitrin-pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.35; transform: scale(1.7); }
    }

    [data-tema='vitrin'] .vitrin-bar {
        transform-origin: bottom;
        animation: vitrin-bar-up 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) both;
    }

    [data-tema='vitrin'] .vitrin-float {
        animation: vitrin-float-y 7s ease-in-out infinite;
    }

    [data-tema='vitrin'] .vitrin-pulse {
        animation: vitrin-pulse-dot 2.4s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        [data-tema='vitrin'] .vitrin-bar,
        [data-tema='vitrin'] .vitrin-float,
        [data-tema='vitrin'] .vitrin-pulse {
            animation: none !important;
        }
    }
</style>

@php
    $mod = setting('gorunum.tasarim_modu', 'eski');
    $primaryColor = setting('gorunum.primary_color', '#059669');
    $fontFamily = setting('gorunum.font_family', 'sans');
    $borderRadius = setting('gorunum.border_radius', 'modern');
    $glass = setting('gorunum.glassmorphism', '1') === '1';

    $radiusPx = match($borderRadius) {
        'sharp' => '2px',
        'soft' => '8px',
        'pill' => '24px',
        default => '14px',
    };

    $fontFamilyCss = match($fontFamily) {
        'serif' => 'Instrument Serif, Georgia, serif',
        'inter' => 'Inter, system-ui, sans-serif',
        'outfit' => 'Outfit, system-ui, sans-serif',
        default => 'Instrument Sans, system-ui, sans-serif',
    };
@endphp

<style>
    :root {
        --nisoya-primary: {{ $primaryColor }};
        --nisoya-radius: {{ $radiusPx }};
        --nisoya-font: {{ $fontFamilyCss }};
        @if ($glass)
            --nisoya-glass-blur: blur(12px);
            --nisoya-glass-bg: rgba(255, 255, 255, 0.75);
        @else
            --nisoya-glass-blur: none;
            --nisoya-glass-bg: #ffffff;
        @endif
    }

    @if ($mod === 'yeni')
        :root {
            --color-emerald-600: #0f5c42;
            --color-stone-50: #f3eee4;
            --nisoya-seal: #c1440e;
        }
    @elseif ($mod === 'obsidian')
        :root {
            --color-emerald-600: #10b981;
            --color-stone-50: #090d16;
            --nisoya-seal: #f59e0b;
        }
    @elseif ($mod === 'nordic')
        :root {
            --color-emerald-600: #0f172a;
            --color-stone-50: #f8fafc;
            --nisoya-seal: #6366f1;
        }
    @endif
</style>

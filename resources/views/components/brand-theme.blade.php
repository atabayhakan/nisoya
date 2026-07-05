@php
    $brand = setting('gorunum.marka_rengi') ?: 'emerald';
    $validBrands = array_keys(config('brand_colors', []));
    if (! in_array($brand, $validBrands, true)) {
        $brand = 'emerald';
    }
@endphp

@if ($brand !== 'emerald')
    <style>
        :root {
            --color-emerald-50: var(--color-{{ $brand }}-50);
            --color-emerald-100: var(--color-{{ $brand }}-100);
            --color-emerald-200: var(--color-{{ $brand }}-200);
            --color-emerald-300: var(--color-{{ $brand }}-300);
            --color-emerald-400: var(--color-{{ $brand }}-400);
            --color-emerald-500: var(--color-{{ $brand }}-500);
            --color-emerald-600: var(--color-{{ $brand }}-600);
            --color-emerald-700: var(--color-{{ $brand }}-700);
            --color-emerald-800: var(--color-{{ $brand }}-800);
            --color-emerald-900: var(--color-{{ $brand }}-900);
            --color-emerald-950: var(--color-{{ $brand }}-950);
        }
    </style>
@endif

<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- VİTRİN GUEST İSKELETİ (P1) — klasik guest'in aynı-ad override'ı.
         Gerekçe: klasik guest ne brand-theme ne tasarim-theme basar; vitrin'de
         override edilmezse emerald sınıfları Tailwind'in varsayılan YEŞİLİ
         olarak çözülür ve giriş/kayıt sayfaları mavi sitenin ortasında yeşil
         kalırdı. Tek fark: vitrin-theme + Vitrin hex'leri. --}}
    {{-- SEO/og/favicon/manifest meta'ları — paylaşılan tek kopya (ana vitrin
         iskeletiyle aynı bileşen). Öncesinde bu layout kendi
         title/favicon/manifest'ini elle basıyordu ve canonical etiketi hiç
         yoktu. --}}
    <x-layout-head-meta :title="$title ?? null" />

    <x-theme-init />

    <x-layout-fonts />


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <x-vitrin-theme />
</head>
<body data-tema="vitrin" class="min-h-screen bg-stone-50 text-stone-800 antialiased dark:bg-stone-950 dark:text-stone-200">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-brand dark:text-stone-900">
                    <x-logo-mark class="h-5 w-5" />
                </span>
                <span class="text-2xl font-extrabold text-stone-800 dark:text-stone-50">nisoya</span>
            </a>
            @unless (\App\Support\Tema::koyuKilit())
                <button
                    type="button"
                    onclick="window.toggleTheme && window.toggleTheme()"
                    class="ml-2 inline-flex rounded-lg p-2 text-stone-600 transition hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800"
                    title="Temayı değiştir"
                    aria-label="Karanlık/aydınlık tema değiştir"
                >
                    <x-heroicon-o-moon class="h-5 w-5 dark:hidden" />
                    <x-heroicon-o-sun class="hidden h-5 w-5 dark:inline" />
                </button>
            @endunless
        </div>

        <div class="w-full max-w-md rounded-[22px] border border-stone-200/60 bg-white p-8 shadow-brand dark:border-stone-800 dark:bg-stone-900">
            {{ $slot }}
        </div>

        {{-- GÜVEN BAĞLANTILARI (2026-08-05) — gerekçe klasik guest layout'unda.
             Kısaca: yeni birinden e-posta ve parola istiyorsan, kim olduğunu
             gösterecek yolu da vermen gerekir. --}}
        <nav class="mt-6 flex flex-wrap justify-center gap-x-4 gap-y-1.5 text-xs font-medium text-stone-600 dark:text-stone-400" aria-label="Site bilgileri">
            <a href="{{ url('/hakkimizda') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Hakkımızda</a>
            <a href="{{ url('/guvenli-alisveris') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Güvenli alışveriş</a>
            <a href="{{ url('/gizlilik') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Gizlilik</a>
            <a href="{{ url('/kosullar') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">Kullanım koşulları</a>
            <a href="{{ url('/iletisim') }}" class="hover:text-emerald-700 hover:underline dark:hover:text-emerald-400">İletişim</a>
        </nav>

        <p class="mt-3 text-xs font-medium text-stone-600 dark:text-stone-400">© {{ date('Y') }} Nisoya — Ne İş Olursa Yaparız</p>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>

{{--
    KULLANICI PANELİ (/panel)

    Bu sayfa tek bir soruya göre sıralanır: "benden bir şey bekleniyor mu?"

    Eskiden 14 eşit ağırlıklı kart vardı, hiçbirinde sayı yoktu ve rota
    `Route::view` olduğu için sayfaya veri bile geçmiyordu. Artık:
      K1 Bekleyenler — yalnız EYLEM gerektirenler, sabit kanonik sırada
      K2 Durumun     — gerçek rakamlar; değeri 0 olan kutu BASILMAZ
      K3 Bölümler    — her zaman görünür dizin (rol kapısı YOK, modül kapısı VAR)
      K4 Başlangıç   — yalnız K1 ve K2'nin İKİSİ de boşsa
      K5 Hesap       — çıkış (mobilde hesaptan çıkmanın tek yolu)

    Hiçbir partial kendi sorgusunu atmaz; her şey $sinyaller içinde gelir
    (bkz. App\Support\PanelSinyalleri — azaltma defteri orada).
--}}
<x-layouts.app title="Panelim — Nisoya">
    @php
        $user = auth()->user();
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-8 sm:py-10">
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        {{-- Profil Başlık Kartı --}}
        <div class="rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-8 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="rounded-full ring-2 ring-emerald-600/20 dark:ring-emerald-400/30">
                        <x-avatar :user="$user" size="h-14 w-14" text="text-xl" />
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-stone-900 dark:text-stone-50">Merhaba, {{ $user->name }} 👋</h1>
                            <x-trust-badge :user="$user" />
                            @if ($user->is_verified)<x-verified-badge />@endif
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-medium text-stone-500 dark:text-stone-400">
                            @if ($user->city || $user->country_code)
                                <span class="inline-flex items-center gap-1 rounded-md bg-stone-100 px-2 py-0.5 font-medium text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    <x-heroicon-o-map-pin class="h-3.5 w-3.5 text-stone-500" />
                                    @if ($user->city){{ $user->city }}, @endif{{ $user->country_code }}
                                </span>
                            @endif
                            <x-kidem-rozeti :user="$user" variant="text" />
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="{{ url('/panel/profil') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs font-bold text-stone-700 transition hover:border-emerald-300 hover:bg-stone-100 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300">
                        <x-heroicon-o-user class="h-4 w-4 text-stone-500 dark:text-stone-400" />
                        <span>Profilim</span>
                    </a>
                </div>
            </div>
        </div>

        @include('panel.partials.bekleyenler', ['s' => $sinyaller])
        @include('panel.partials.durumun', ['s' => $sinyaller])

        @if ($sinyaller->bosDurum())
            @include('panel.partials.baslangic', ['s' => $sinyaller, 'user' => $user])
        @endif

        @include('panel.partials.bolumler', ['s' => $sinyaller])

        {{-- K5 · Hesap --}}
        <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-stone-200/80 pt-6 dark:border-stone-800">
            <div class="flex items-center gap-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-stone-500 transition hover:text-red-600 md:min-h-0 dark:text-stone-400 dark:hover:text-red-400">
                        <x-heroicon-o-arrow-left-on-rectangle class="h-4 w-4" />
                        <span>Çıkış Yap</span>
                    </button>
                </form>
                <span class="text-stone-300 dark:text-stone-700">·</span>
                <a href="{{ url('/panel/profil') }}" class="inline-flex min-h-11 items-center text-xs font-semibold text-stone-600 hover:text-stone-900 md:min-h-0 dark:text-stone-400 dark:hover:text-stone-200">
                    Profil ayarları
                </a>
            </div>
            <a href="{{ url('/ilanlar') }}" class="inline-flex min-h-11 items-center gap-1 text-xs font-bold text-emerald-700 transition hover:underline md:min-h-0 dark:text-emerald-400">
                <span>İlanlara göz at</span>
                <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
            </a>
        </div>
    </div>
</x-layouts.app>

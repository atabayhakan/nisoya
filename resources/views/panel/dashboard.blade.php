<x-layouts.app title="Panelim — Nisoya">
    @php
        $user = auth()->user();
        $cards = [
            ['ad' => 'İlanlarım', 'aciklama' => 'İlanlarını yönet', 'ikon' => '📋', 'url' => '/panel/ilanlarim'],
            ['ad' => 'Mesajlar', 'aciklama' => 'Gelen mesajların', 'ikon' => '💬', 'url' => '/panel/mesajlar'],
            ['ad' => 'Favorilerim', 'aciklama' => 'Kaydettiğin ilanlar', 'ikon' => '❤️', 'url' => '/panel/favorilerim'],
            ['ad' => 'Profilim', 'aciklama' => 'Bilgilerini düzenle', 'ikon' => '👤', 'url' => '/panel/profil'],
            ['ad' => 'İlanlara Göz At', 'aciklama' => 'Hizmet ara', 'ikon' => '🔍', 'url' => '/ilanlar'],
        ];
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-10">
        @if (request('verified'))
            <div class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                ✓ E-posta adresin doğrulandı. Aramıza hoş geldin!
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">Merhaba, {{ $user->name }} 👋</h1>
                <p class="mt-1 text-sm text-stone-500">
                    @if ($user->city){{ $user->city }}, @endif{{ $user->country_code }} · Panelin hazır.
                </p>
            </div>
            <a href="{{ url('/panel/ilan/yeni') }}" class="rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
                + Yeni İlan Ver
            </a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $c)
                <a href="{{ url($c['url']) }}" class="flex items-start gap-3 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                    <span class="text-2xl">{{ $c['ikon'] }}</span>
                    <span>
                        <span class="block font-semibold text-stone-800">{{ $c['ad'] }}</span>
                        <span class="block text-sm text-stone-500">{{ $c['aciklama'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        <p class="mt-8 text-sm text-stone-400">
            Not: Panel bölümleri (ilan ekleme, mesajlar, favoriler, profil) sonraki adımlarda devreye girecek.
        </p>
    </div>
</x-layouts.app>

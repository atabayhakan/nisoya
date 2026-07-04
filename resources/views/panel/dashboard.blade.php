<x-layouts.app title="Panelim — Nisoya">
    @php
        $user = auth()->user();
        $cards = [
            ['ad' => 'İlanlarım', 'aciklama' => 'İlanlarını yönet', 'ikon' => 'clipboard-document-list', 'url' => '/panel/ilanlarim'],
            ['ad' => 'Mesajlar', 'aciklama' => 'Gelen mesajların', 'ikon' => 'chat-bubble-left-right', 'url' => '/panel/mesajlar'],
            ['ad' => 'Bildirimler', 'aciklama' => 'Tüm bildirimlerin', 'ikon' => 'bell', 'url' => '/panel/bildirimler'],
            ['ad' => 'Aramalarım', 'aciklama' => 'Kayıtlı aramalar', 'ikon' => 'bookmark', 'url' => '/panel/aramalarim'],
            ['ad' => 'Favorilerim', 'aciklama' => 'Kaydettiğin ilanlar', 'ikon' => 'heart', 'url' => '/panel/favorilerim'],
            ['ad' => 'İş İlanlarım', 'aciklama' => 'İşveren: ilan ver & yönet', 'ikon' => 'briefcase', 'url' => '/panel/is-ilanlarim'],
            ['ad' => 'Başvurularım', 'aciklama' => 'Başvurduğun işler', 'ikon' => 'document-text', 'url' => '/panel/basvurularim'],
            ['ad' => 'Şirket Profili', 'aciklama' => 'İşveren profilin', 'ikon' => 'building-office-2', 'url' => '/panel/sirket'],
            ['ad' => 'Profilim', 'aciklama' => 'Bilgilerini düzenle', 'ikon' => 'user-circle', 'url' => '/panel/profil'],
            ['ad' => 'Arkadaşını Davet Et', 'aciklama' => 'Davet bağlantını paylaş', 'ikon' => 'gift', 'url' => '/panel/davet'],
            ['ad' => 'İlanlara Göz At', 'aciklama' => 'Hizmet ara', 'ikon' => 'magnifying-glass', 'url' => '/ilanlar'],
        ];
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-10">
        @if (request('verified'))
            <div class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                ✓ E-posta adresin doğrulandı. Aramıza hoş geldin!
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Merhaba, {{ $user->name }} 👋</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    @if ($user->city){{ $user->city }}, @endif{{ $user->country_code }} · Panelin hazır.
                </p>
            </div>
            <a href="{{ url('/panel/ilan/yeni') }}" class="rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                + Yeni İlan Ver
            </a>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $c)
                <a href="{{ url($c['url']) }}" class="group flex items-start gap-3 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-brand dark:border-stone-800 dark:bg-stone-900 dark:shadow-none dark:hover:border-emerald-700">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-900/40 dark:text-emerald-300 dark:group-hover:bg-emerald-500 dark:group-hover:text-stone-900">
                        <x-dynamic-component :component="'heroicon-o-'.$c['ikon']" class="h-5 w-5" />
                    </span>
                    <span>
                        <span class="block font-semibold text-stone-800 dark:text-stone-100">{{ $c['ad'] }}</span>
                        <span class="block text-sm text-stone-500 dark:text-stone-400">{{ $c['aciklama'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        <p class="mt-8 text-sm text-stone-400 dark:text-stone-500">
            Not: Panel bölümleri (ilan ekleme, mesajlar, favoriler, profil) sonraki adımlarda devreye girecek.
        </p>
    </div>
</x-layouts.app>

<x-layouts.app :title="$event->title.' — Davetiye Yönetimi — Nisoya'">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <x-panel.back-link :href="route('panel.events.index')" label="Davetiyelerim" />

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">{{ $event->type->emoji() }} {{ $event->title }}</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    {{ $event->type->getLabel() }} · {{ $event->starts_at->translatedFormat('j F Y, H:i') }}
                    @if ($event->venue_name) · {{ $event->venue_name }} @endif
                    @unless ($event->is_active) · <span class="font-medium text-red-500 dark:text-red-400">link kapalı</span> @endunless
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ $event->inviteUrl() }}" target="_blank" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800">Davetiyeyi gör</a>
                <a href="{{ route('panel.events.edit', $event) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800">Düzenle</a>
            </div>
        </div>

        {{-- Davet linki + paylaşım --}}
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 dark:border-emerald-900 dark:bg-emerald-950/20">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">🔗 Davet Linkin</h2>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <code id="invite-url" class="min-w-0 flex-1 truncate rounded-lg bg-white px-3 py-2 text-sm text-stone-700 ring-1 ring-stone-200 dark:bg-stone-900 dark:text-stone-300 dark:ring-stone-700">{{ $event->inviteUrl() }}</code>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('invite-url').textContent.trim()).then(() => { this.textContent = 'Kopyalandı ✓'; setTimeout(() => this.textContent = 'Kopyala', 2000); })"
                        class="rounded-lg bg-stone-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-900 dark:bg-stone-700 dark:hover:bg-stone-600">Kopyala</button>
                <a href="https://wa.me/?text={{ urlencode($event->title.' davetiyesi 💌 '.$event->inviteUrl()) }}" target="_blank" rel="noopener"
                   class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">WhatsApp'ta Paylaş</a>
            </div>
            <p class="mt-2 text-xs text-emerald-700/80 dark:text-emerald-400/80">Linki alan herkes davetiyeyi görüp LCV verebilir — yalnızca davet etmek istediklerinle paylaş.</p>
        </div>

        {{-- LCV sayaçları --}}
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-2xl border border-stone-200 bg-white p-4 text-center dark:border-stone-800 dark:bg-stone-900">
                <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $summary['geliyor']['people'] }}</div>
                <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">Geliyor ({{ $summary['geliyor']['entries'] }} yanıt)</div>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 text-center dark:border-stone-800 dark:bg-stone-900">
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $summary['belki']['people'] }}</div>
                <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">Belki ({{ $summary['belki']['entries'] }} yanıt)</div>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 text-center dark:border-stone-800 dark:bg-stone-900">
                <div class="text-2xl font-bold text-stone-400 dark:text-stone-500">{{ $summary['gelmiyor']['entries'] }}</div>
                <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">Gelemiyor</div>
            </div>
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-center dark:border-emerald-800 dark:bg-emerald-950/30">
                <div class="text-2xl font-bold text-emerald-800 dark:text-emerald-300">{{ $summary['expected_people'] }}</div>
                <div class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Beklenen kişi</div>
            </div>
        </div>

        {{-- Davetli listesi --}}
        <div class="mt-6 rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 px-5 py-4 dark:border-stone-800">
                <h2 class="font-semibold text-stone-800 dark:text-stone-100">Davetli Listesi</h2>
                <p class="mt-0.5 text-xs text-stone-400 dark:text-stone-500">Bu liste yalnızca sana görünür (KVKK) — davetiye sayfasında misafir isimleri gösterilmez.</p>
            </div>
            @if ($guests->isNotEmpty())
                <ul class="divide-y divide-stone-100 dark:divide-stone-800">
                    @foreach ($guests as $guest)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0">
                                <span class="font-medium text-stone-800 dark:text-stone-100">{{ $guest->name }}</span>
                                @if ($guest->party_size > 1)
                                    <span class="text-stone-400 dark:text-stone-500">+{{ $guest->party_size - 1 }} kişi</span>
                                @endif
                                @if ($guest->note)
                                    <div class="truncate text-xs text-stone-400 dark:text-stone-500">"{{ $guest->note }}"</div>
                                @endif
                            </div>
                            <span @class([
                                'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $guest->status->value === 'geliyor',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $guest->status->value === 'belki',
                                'bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400' => $guest->status->value === 'gelmiyor',
                            ])>{{ $guest->status->getLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="px-5 py-3">{{ $guests->links() }}</div>
            @else
                <p class="px-5 py-8 text-center text-sm text-stone-400 dark:text-stone-500">Henüz LCV gelmedi — linki paylaştın mı? 😊</p>
            @endif
        </div>

        {{-- Pazaryeri köprüsü: etkinlik hizmetleri --}}
        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
            <h2 class="font-semibold text-stone-800 dark:text-stone-100">Etkinliğin için bir şey mi lazım? 🤝</h2>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Bulunduğun ülkedeki Türklerden hizmet al — fotoğrafçıdan pastacıya.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ url('/ilanlar/kategori/fotograf-video') }}" class="rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">📷 Fotoğraf & Video</a>
                <a href="{{ url('/ilanlar/kategori/dj-muzik') }}" class="rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">🎵 DJ & Müzik</a>
                <a href="{{ url('/ilanlar/kategori/organizasyon') }}" class="rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">🎪 Organizasyon</a>
                <a href="{{ url('/ilanlar/kategori/pasta-tatli') }}" class="rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">🎂 Pasta & Tatlı</a>
                <a href="{{ url('/ilanlar/kategori/catering') }}" class="rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">🍽️ Catering</a>
                <a href="{{ url('/ilanlar/kategori/gelin-hazirlik') }}" class="rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">💄 Gelin Hazırlık</a>
            </div>
        </div>
    </div>
</x-layouts.app>

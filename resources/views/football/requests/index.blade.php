<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Bildirimler --}}
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-800">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← {{ $currentCity }} Futbol Ana Sayfası
                </a>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-100">
                    {{ $currentCity }} Oyuncu & Maç İlanları
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Takımında eksik oyuncu mu var? Ya da bu akşam oynayacak halı saha maçı mı arıyorsun?
                </p>
            </div>
            <a href="{{ route('football.requests.create') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                + Yeni İlan Aç
            </a>
        </div>

        {{-- Filtre Sekmeleri --}}
        <div class="mt-6 flex border-b border-stone-200 dark:border-stone-800">
            <a href="{{ route('football.requests.index', ['city' => \Illuminate\Support\Str::slug($currentCity)]) }}"
               class="border-b-2 px-5 py-3 text-sm font-bold transition {{ empty($currentType) ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-stone-500 hover:text-stone-800 dark:text-stone-400' }}">
                Tüm İlanlar
            </a>
            <a href="{{ route('football.requests.index', ['city' => \Illuminate\Support\Str::slug($currentCity), 'type' => 'oyuncu']) }}"
               class="border-b-2 px-5 py-3 text-sm font-bold transition {{ $currentType === 'oyuncu' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-stone-500 hover:text-stone-800 dark:text-stone-400' }}">
                👥 Oyuncu Aranıyor
            </a>
            <a href="{{ route('football.requests.index', ['city' => \Illuminate\Support\Str::slug($currentCity), 'type' => 'mac']) }}"
               class="border-b-2 px-5 py-3 text-sm font-bold transition {{ $currentType === 'mac' ? 'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-stone-500 hover:text-stone-800 dark:text-stone-400' }}">
                ⚽ Maç Arıyorum
            </a>
        </div>

        @if ($requests->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-stone-200 p-12 text-center text-stone-500 dark:border-stone-800 dark:text-stone-400">
                <p class="text-base font-semibold">Bu kategoride şu an yayında ilan bulunmuyor.</p>
                <a href="{{ route('football.requests.create') }}" class="mt-3 inline-block font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    Hemen yeni bir ilan bırak!
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($requests as $req)
                    <div class="flex flex-col justify-between rounded-3xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    {{ $req->type->emoji() }} {{ $req->type->getLabel() }}
                                </span>
                                @if ($req->needed_count > 0 && $req->isLookingForPlayer())
                                    <span class="rounded-lg bg-amber-50 px-2 py-0.5 text-2xs font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                        {{ $req->needed_count }} Kişi Eksik
                                    </span>
                                @endif
                            </div>

                            <p class="mt-3 text-sm font-bold text-stone-900 leading-snug dark:text-stone-100">
                                {{ $req->description }}
                            </p>

                            {{-- Detay Bilgileri --}}
                            <div class="mt-4 space-y-1.5 text-xs text-stone-500 dark:text-stone-400">
                                @if ($req->match_time)
                                    <p>📅 {{ $req->match_time->translatedFormat('d F Y, H:i') }}</p>
                                @endif
                                <p>📍 {{ $req->venue_name ?: $req->city }}</p>
                                @if ($req->team)
                                    <p>🏆 Takım: <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($req->team->city), 'team' => $req->team->slug]) }}" class="font-bold text-emerald-700 hover:underline">{{ $req->team->name }}</a></p>
                                @endif
                                <p>👤 İlan Sahibi: <a href="{{ route('profiles.show', $req->user->username) }}" class="font-semibold text-stone-700 dark:text-stone-300">{{ $req->user->name }}</a></p>
                            </div>
                        </div>

                        {{-- Başvuru / Silme Butonu --}}
                        <div class="mt-5 border-t border-stone-100 pt-3 dark:border-stone-800">
                            @auth
                                @if ((int) auth()->id() === (int) $req->user_id)
                                    <form method="POST" action="{{ route('football.requests.destroy', $req) }}" onsubmit="return confirm('Bu ilanı silmek istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full rounded-xl bg-rose-50 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300">
                                            İlanı Kaldır
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('football.requests.apply', $req) }}">
                                        @csrf
                                        <button type="submit" class="w-full rounded-xl bg-emerald-700 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500">
                                            🙋‍♂️ Ben Gelebilirim (Başvur)
                                        </button>
                                    </form>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="block text-center rounded-xl bg-stone-50 py-2 text-xs font-bold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    Giriş Yap & Katıl →
                                </a>
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>

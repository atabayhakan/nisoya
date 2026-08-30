<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">⚡ Futbolcu Kartı</span>
                <h1 class="mt-1 text-2xl font-bold text-stone-900 dark:text-stone-100">
                    Futbolcu Profilini Düzenle
                </h1>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    Oynadığın mevkileri, seviyeni ve tercihlerini belirterek halı saha takımlarının seni keşfetmesini sağla.
                </p>
            </div>

            <form method="POST" action="{{ route('football.player.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 p-4 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Şehir & Ülke --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Oynadığın Şehir</label>
                        <input type="text" name="city" value="{{ old('city', $profile->city) }}" placeholder="Örn: Berlin, Köln, Frankfurt"
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Ülke</label>
                        <select name="country_code" class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($countries as $country)
                                <option value="{{ $country->code }}" @selected(old('country_code', $profile->country_code) === $country->code)>
                                    {{ $country->emoji }} {{ $country->name_tr }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Mevkiler --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Oynayabildiğin Mevkiler</label>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @php($currentPositions = $profile->positions ?? [])
                        @foreach ($positions as $pos)
                            <label class="flex items-center gap-2 rounded-xl border border-stone-100 bg-stone-50 p-2.5 text-xs text-stone-800 dark:border-stone-800 dark:bg-stone-800/60 dark:text-stone-200 cursor-pointer">
                                <input type="checkbox" name="positions[]" value="{{ $pos->value }}" @checked(in_array($pos->value, $currentPositions)) class="rounded text-emerald-700 focus:ring-emerald-500">
                                <span>{{ $pos->labelWithEmoji() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Seviye & Tercih Edilen Ayak --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Futbol Seviyen *</label>
                        <select name="level" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($levels as $lvl)
                                <option value="{{ $lvl->value }}" @selected(old('level', $profile->level?->value) === $lvl->value)>
                                    {{ $lvl->badgeEmoji() }} {{ $lvl->getLabel() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Kullandığın Ayak</label>
                        <select name="preferred_foot" class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Belirtilmedi</option>
                            <option value="sag" @selected(old('preferred_foot', $profile->preferred_foot) === 'sag')>Sağ Ayak</option>
                            <option value="sol" @selected(old('preferred_foot', $profile->preferred_foot) === 'sol')>Sol Ayak</option>
                            <option value="cift" @selected(old('preferred_foot', $profile->preferred_foot) === 'cift')>İki Ayak / Çift</option>
                        </select>
                    </div>
                </div>

                {{-- Arama Durumları --}}
                <div class="space-y-2 rounded-2xl bg-stone-50 p-4 dark:bg-stone-800/60">
                    <label class="flex items-center gap-2 text-xs font-bold text-stone-800 dark:text-stone-200 cursor-pointer">
                        <input type="checkbox" name="is_looking_for_team" value="1" @checked(old('is_looking_for_team', $profile->is_looking_for_team)) class="rounded text-emerald-700 focus:ring-emerald-500">
                        <span>✨ Halı saha takımı arıyorum (Kaptanlar bana davet gönderebilir)</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-bold text-stone-800 dark:text-stone-200 cursor-pointer">
                        <input type="checkbox" name="is_looking_for_match" value="1" @checked(old('is_looking_for_match', $profile->is_looking_for_match)) class="rounded text-emerald-700 focus:ring-emerald-500">
                        <span>⚽ Bu hafta maç arıyorum (Eksik oyuncu arayanlar bana ulaşabilir)</span>
                    </label>
                </div>

                {{-- Futbolculuk Biyografisi --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Futbolculuk Geçmişin / Tanıtım</label>
                    <textarea name="bio" rows="3" placeholder="Örn: Türkiye'de amatör kümede 5 yıl forvet oynadım. Berlin'de haftada 1-2 gün düzenli maç yapacak takım arıyorum..."
                              class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('bio', $profile->bio) }}</textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-emerald-700 py-3.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                        ⚡ Profilimi Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>

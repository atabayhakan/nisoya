<x-layouts.app title="İlanı Düzenle — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.listings.index')" label="İlanlarım" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">İlanı Düzenle</h1>

        <form method="POST" action="{{ route('panel.listings.update', $listing) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @method('PUT')
            @include('panel.listings.partials.form-fields', ['listing' => $listing])

            @if ($listing->images->isNotEmpty())
                <div>
                    <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mevcut görseller</span>
                    <p class="text-xs text-stone-400 dark:text-stone-500">Silmek istediklerini işaretle.</p>
                    <div class="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($listing->images as $image)
                            <label class="group relative block cursor-pointer overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700">
                                <img src="{{ Storage::url($image->path) }}" alt="" class="aspect-square w-full object-cover">
                                @if ($image->is_cover)
                                    <span class="absolute left-1 top-1 rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-semibold text-white dark:bg-emerald-500 dark:text-stone-900">Kapak</span>
                                @endif
                                <span class="absolute inset-x-0 bottom-0 flex items-center gap-1 bg-white/90 px-2 py-1 text-xs text-red-600 dark:bg-stone-900/90 dark:text-red-400">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded border-stone-300 text-red-600 focus:ring-red-500 dark:border-stone-600 dark:bg-stone-800">
                                    Sil
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label for="images" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yeni görsel ekle <span class="text-stone-400">(ops.)</span></label>
                <input id="images" name="images[]" type="file" accept="image/*" multiple
                       class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                @error('images.*') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                        Değişiklikleri Kaydet
                    </button>
                    <a href="{{ route('listings.show', [$listing, $listing->slug]) }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">İlanı gör →</a>
                </div>
            </div>
        </form>

        {{-- Müsaitlik takvimi (yalnızca emlak) --}}
        @if ($listing->type->value === 'emlak')
            @php($ranges = $listing->unavailableRanges()->where('ends_on', '>=', now()->toDateString())->get())
            <div class="mt-8 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <h2 class="font-semibold text-stone-800 dark:text-stone-100">📅 Müsaitlik Takvimi</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Dolu olduğun tarih aralıklarını işaretle — ziyaretçiler ilanında bu tarihleri "dolu" görür, kısa dönem aramalarında ilanın o tarihler için listelenmez.</p>

                @if (session('status'))
                    <div class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('panel.listings.availability.store', $listing) }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label for="starts_on" class="block text-xs font-medium text-stone-600 dark:text-stone-400">Başlangıç</label>
                        <input id="starts_on" name="starts_on" type="date" required value="{{ old('starts_on') }}"
                               class="mt-1 rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label for="ends_on" class="block text-xs font-medium text-stone-600 dark:text-stone-400">Bitiş</label>
                        <input id="ends_on" name="ends_on" type="date" required value="{{ old('ends_on') }}"
                               class="mt-1 rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div class="min-w-[10rem] flex-1">
                        <label for="note" class="block text-xs font-medium text-stone-600 dark:text-stone-400">Not <span class="text-stone-400">(ops., sadece sen görürsün)</span></label>
                        <input id="note" name="note" type="text" maxlength="255" value="{{ old('note') }}" placeholder="ör. aile geliyor"
                               class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                    </div>
                    <button type="submit" class="rounded-lg bg-stone-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-900 dark:bg-stone-700 dark:hover:bg-stone-600">Dolu işaretle</button>
                </form>
                @error('starts_on') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @error('ends_on') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                @if ($ranges->isNotEmpty())
                    <ul class="mt-4 divide-y divide-stone-100 border-t border-stone-100 dark:divide-stone-800 dark:border-stone-800">
                        @foreach ($ranges as $range)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <span class="text-stone-700 dark:text-stone-300">
                                    {{ $range->starts_on->format('d.m.Y') }} — {{ $range->ends_on->format('d.m.Y') }}
                                    @if ($range->note)<span class="ml-1 text-stone-400 dark:text-stone-500">({{ $range->note }})</span>@endif
                                </span>
                                <form method="POST" action="{{ route('panel.listings.availability.destroy', [$listing, $range]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Kaldır</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-stone-400 dark:text-stone-500">Henüz dolu tarih işaretlemedin — takvimin tamamen müsait görünüyor.</p>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('panel.listings.destroy', $listing) }}" class="mt-6 border-t border-stone-200 pt-6 dark:border-stone-800"
              onsubmit="return confirm('Bu ilanı silmek istediğine emin misin? Bu işlem geri alınamaz.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">İlanı sil</button>
        </form>
    </div>
</x-layouts.app>

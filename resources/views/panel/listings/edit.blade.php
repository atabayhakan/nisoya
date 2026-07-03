<x-layouts.app title="İlanı Düzenle — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.listings.index')" label="İlanlarım" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900">İlanı Düzenle</h1>

        <form method="POST" action="{{ route('panel.listings.update', $listing) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @method('PUT')
            @include('panel.listings.partials.form-fields', ['listing' => $listing])

            @if ($listing->images->isNotEmpty())
                <div>
                    <span class="block text-sm font-medium text-stone-700">Mevcut görseller</span>
                    <p class="text-xs text-stone-400">Silmek istediklerini işaretle.</p>
                    <div class="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($listing->images as $image)
                            <label class="group relative block cursor-pointer overflow-hidden rounded-lg border border-stone-200">
                                <img src="{{ Storage::url($image->path) }}" alt="" class="aspect-square w-full object-cover">
                                @if ($image->is_cover)
                                    <span class="absolute left-1 top-1 rounded bg-emerald-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">Kapak</span>
                                @endif
                                <span class="absolute inset-x-0 bottom-0 flex items-center gap-1 bg-white/90 px-2 py-1 text-xs text-red-600">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded border-stone-300 text-red-600 focus:ring-red-500">
                                    Sil
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label for="images" class="block text-sm font-medium text-stone-700">Yeni görsel ekle <span class="text-stone-400">(ops.)</span></label>
                <input id="images" name="images[]" type="file" accept="image/*" multiple
                       class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100">
                @error('images.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
                        Değişiklikleri Kaydet
                    </button>
                    <a href="{{ route('listings.show', [$listing, $listing->slug]) }}" class="text-sm font-medium text-emerald-700 hover:underline">İlanı gör →</a>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('panel.listings.destroy', $listing) }}" class="mt-6 border-t border-stone-200 pt-6"
              onsubmit="return confirm('Bu ilanı silmek istediğine emin misin? Bu işlem geri alınamaz.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">İlanı sil</button>
        </form>
    </div>
</x-layouts.app>

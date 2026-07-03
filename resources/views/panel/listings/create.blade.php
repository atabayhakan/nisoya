<x-layouts.app title="Yeni İlan Ver — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.listings.index')" label="İlanlarım" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900">{{ $type === 'urun' ? 'Yeni Ürün İlanı' : 'Yeni Hizmet İlanı' }}</h1>

        {{-- Tip seçimi --}}
        <div class="mt-4 inline-flex rounded-xl border border-stone-200 bg-white p-1 text-sm font-medium">
            <a href="{{ route('panel.listings.create', ['tip' => 'hizmet']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'hizmet' ? 'bg-emerald-600 text-white' : 'text-stone-600 hover:bg-stone-50' }}">🧰 Hizmet</a>
            <a href="{{ route('panel.listings.create', ['tip' => 'urun']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'urun' ? 'bg-emerald-600 text-white' : 'text-stone-600 hover:bg-stone-50' }}">📦 Ürün</a>
        </div>
        <p class="mt-3 text-sm text-stone-500">{{ $type === 'urun' ? 'Evde ürettiğin ürünü sergile, alıcılarla buluş.' : 'Yeteneğini/hizmetini anlat, bulunduğun ülkedeki Türklerle buluş.' }}</p>

        <form method="POST" action="{{ route('panel.listings.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @include('panel.listings.partials.form-fields', ['listing' => null])

            <div>
                <label for="images" class="block text-sm font-medium text-stone-700">Görseller <span class="text-stone-400">(en fazla 8, ops.)</span></label>
                <input id="images" name="images[]" type="file" accept="image/*" multiple
                       class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100">
                <p class="mt-1 text-xs text-stone-400">JPG, PNG veya WEBP · her biri en fazla 4 MB. İlk görsel kapak olur.</p>
                @error('images.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
                    İlanı Yayınla
                </button>
                <a href="{{ route('panel.listings.index') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700">Vazgeç</a>
            </div>
        </form>
    </div>
</x-layouts.app>

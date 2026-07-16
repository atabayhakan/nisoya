<x-layouts.app title="Yeni İlan Ver — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.listings.index')" label="İlanlarım" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">{{ match ($type) { 'urun' => 'Yeni Ürün İlanı', 'emlak' => 'Yeni Emlak İlanı', 'vasita' => 'Yeni Vasıta İlanı', default => 'Yeni Hizmet İlanı' } }}</h1>

        {{-- Tip seçimi --}}
        <div class="mt-4 inline-flex flex-wrap rounded-xl border border-stone-200 bg-white p-1 text-sm font-medium dark:border-stone-800 dark:bg-stone-900">
            <a href="{{ route('panel.listings.create', ['tip' => 'hizmet']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'hizmet' ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">🧰 Hizmet</a>
            <a href="{{ route('panel.listings.create', ['tip' => 'urun']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'urun' ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">📦 Ürün</a>
            <a href="{{ route('panel.listings.create', ['tip' => 'emlak']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'emlak' ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">🏡 Emlak</a>
            <a href="{{ route('panel.listings.create', ['tip' => 'vasita']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'vasita' ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">🚗 Vasıta</a>
        </div>
        <p class="mt-3 text-sm text-stone-500 dark:text-stone-400">{{ match ($type) { 'urun' => 'Evde ürettiğin ürünü sergile, alıcılarla buluş.', 'emlak' => 'Evini kirala, sat veya kısa dönem misafir ağırla — kendi insanınla güvenle.', 'vasita' => 'Arabanı sat veya kirala — kendi insanınla güvenle, dil sorunu olmadan.', default => 'Yeteneğini/hizmetini anlat, bulunduğun ülkedeki Türklerle buluş.' } }}</p>

        {{-- Faz M3: prefill başarı banner'ı (fotoğraftan otomatik dolduruldu) --}}
        @if (session('quick_prefill'))
            <div class="mt-4 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                <x-heroicon-o-sparkles class="mt-0.5 h-5 w-5 shrink-0" />
                <span>Fotoğrafından bir taslak hazırladık ✨ Aşağıdaki bilgileri kontrol edip düzenle, sonra yayınla.</span>
            </div>
        @endif

        {{-- Faz M3: kamera-önce hızlı ilan giriş noktası (yalnızca ürün tipinde
             ve özellik açıksa) --}}
        @if ($type === 'urun' && app(\App\Services\ListingVisionService::class)->isEnabled())
            <a href="{{ route('panel.listings.quick') }}"
               class="mt-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-left transition hover:bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-white dark:bg-emerald-500">
                    <x-heroicon-o-camera class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-stone-800 dark:text-stone-100">Fotoğrafla hızlı doldur</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400">Ürünün fotoğrafını çek, formu senin için hazırlayalım</span>
                </span>
                <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-emerald-500" />
            </a>
        @endif

        <form method="POST" action="{{ route('panel.listings.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @include('panel.listings.partials.form-fields', ['listing' => null])

            <div>
                <label for="images" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Görseller <span class="text-stone-400">(en fazla 8, ops.)</span></label>
                <input id="images" name="images[]" type="file" accept="image/*" multiple
                       class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">JPG, PNG veya WEBP · her biri en fazla 4 MB. İlk görsel kapak olur.</p>
                @error('images.*') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                    İlanı Yayınla
                </button>
                <a href="{{ route('panel.listings.index') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">Vazgeç</a>
            </div>
        </form>
    </div>
</x-layouts.app>

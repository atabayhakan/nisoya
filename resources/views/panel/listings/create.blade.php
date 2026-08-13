<x-layouts.app title="Yeni İlan Ver — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.listings.index')" label="İlanlarım" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">{{ match ($type) { 'urun' => 'Yeni Ürün İlanı', 'emlak' => 'Yeni Emlak İlanı', 'vasita' => 'Yeni Vasıta İlanı', default => 'Yeni Hizmet İlanı' } }}</h1>

        {{-- Tip seçimi --}}
        <div class="mt-4 inline-flex flex-wrap rounded-xl border border-stone-200 bg-white p-1 text-sm font-medium dark:border-stone-800 dark:bg-stone-900">
            <a href="{{ route('panel.listings.create', ['tip' => 'hizmet']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'hizmet' ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">🧰 Hizmet</a>
            <a href="{{ route('panel.listings.create', ['tip' => 'urun']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'urun' ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">📦 Ürün</a>
            <a href="{{ route('panel.listings.create', ['tip' => 'emlak']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'emlak' ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">🏡 Emlak</a>
            <a href="{{ route('panel.listings.create', ['tip' => 'vasita']) }}" class="rounded-lg px-4 py-2 transition {{ $type === 'vasita' ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800' }}">🚗 Vasıta</a>
        </div>
        <p class="mt-3 text-sm text-stone-500 dark:text-stone-400">{{ match ($type) { 'urun' => 'Evde ürettiğin ürünü sergile, alıcılarla buluş.', 'emlak' => 'Evini kirala, sat veya kısa dönem misafir ağırla — kendi insanınla güvenle.', 'vasita' => 'Arabanı sat veya kirala — kendi insanınla güvenle, dil sorunu olmadan.', default => 'Yeteneğini/hizmetini anlat, bulunduğun ülkedeki Türklerle buluş.' } }}</p>

        {{-- Prefill başarı bandı.

             KAYNAĞI SÖYLÜYOR: metin "Fotoğrafından…" diye sabitti ve
             fotoğraf akışı tek yolken doğruydu. Sonradan metin ve ses
             kapıları eklendi; canlıda denenince, YAZARAK taslak hazırlayan
             kullanıcıya "Fotoğrafından bir taslak hazırladık" deniyordu.
             Kullanıcıya yapmadığı şeyi söylemek, ekranın geri kalanına olan
             güveni de zayıflatır. --}}
        @if (session('quick_prefill'))
            <div class="mt-4 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                <x-heroicon-o-sparkles class="mt-0.5 h-5 w-5 shrink-0" />
                <span>{{ match (session('quick_prefill')) {
                    'fotograf' => 'Fotoğrafından bir taslak hazırladık',
                    'metin' => 'Yazdığın metinden bir taslak hazırladık',
                    default => 'Anlattıklarından bir taslak hazırladık',
                } }} ✨ Aşağıdaki bilgileri kontrol edip düzenle, sonra yayınla.</span>
            </div>
        @endif

        {{-- Faz M3: kamera-önce hızlı ilan giriş noktası (yalnızca ürün tipinde
             ve özellik açıksa) --}}
        @if ($type === 'urun' && app(\App\Services\ListingVisionService::class)->isEnabled())
            <a href="{{ route('panel.listings.quick') }}"
               class="mt-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-left transition hover:bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/40">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-700 text-white dark:bg-emerald-500">
                    <x-heroicon-o-camera class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-stone-800 dark:text-stone-100">Fotoğrafla hızlı doldur</span>
                    <span class="block text-xs text-stone-500 dark:text-stone-400">Ürünün fotoğrafını çek, formu senin için hazırlayalım</span>
                </span>
                <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-emerald-500" />
            </a>
        @endif

        <form method="POST" action="{{ route('panel.listings.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5" x-data="gonderimKilidi('İlan yayınlanıyor...')" @submit="kilitle">
            @csrf
            @include('panel.listings.partials.form-fields', ['listing' => null])

            {{-- Telefonla çekilen fotoğraf 4 MB sınırını rahatlıkla aşıyordu ve
                 ilan hiç oluşmuyordu. Küçültme artık yüklemeden ÖNCE, tarayıcıda
                 yapılıyor (gerekçe app.js → gorselKucultucu). --}}
            <div x-data="gorselKucultucu">
                <label for="images" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Görseller <span class="text-stone-600">(en fazla 8, ops.)</span></label>
                <input id="images" name="images[]" type="file" accept="image/*" multiple
                       @change="secildi($event)"
                       class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">JPG, PNG veya WEBP. Büyük fotoğraflar yüklenmeden önce otomatik küçültülür. İlk görsel kapak olur.</p>
                <p class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-400" x-show="durum" x-text="durum" x-cloak></p>
                @error('images.*') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- TASLAK — "Taslak" durumu enum'da, rozeti hazır, ilan listesinde
                 gösteriliyordu; ama GERÇEK BİR KULLANICI o duruma hiçbir zaman
                 giremiyordu: store() her zaman Aktif yazıyor, formda tek düğme
                 vardı. Yarım kalan ilan kaydedilemiyor, ya bitirilecek ya
                 kaybedilecekti.

                 Yayınlama yolu da AYNI TURDA eklendi (İlanlarım satırındaki
                 "Yayınla" düğmesi) — aksi hâlde taslak bir hapishane olurdu. --}}
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button type="submit" name="eylem" value="yayinla" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                    İlanı Yayınla
                </button>
                <button type="submit" name="eylem" value="taslak" class="rounded-lg border border-stone-300 px-5 py-2.5 font-semibold text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800">
                    Taslak olarak kaydet
                </button>
                <a href="{{ route('panel.listings.index') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">Vazgeç</a>
            </div>
        </form>
    </div>
</x-layouts.app>

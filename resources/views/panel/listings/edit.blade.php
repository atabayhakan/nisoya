<x-layouts.app title="İlanı Düzenle — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.listings.index')" label="İlanlarım" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">İlanı Düzenle</h1>

        {{-- DURUM MESAJI SAYFANIN ÜSTÜNDE.

             Eskiden yalnız müsaitlik takvimi kutusunun İÇİNDE basılıyordu ve o
             kutu sadece emlak/vasıta ilanlarında var. Yani hizmet ya da ürün
             ilanında flash mesajlar hiçbir zaman görünmüyordu — kullanıcı
             düğmeye basıyor, hiçbir şey olmamış gibi aynı sayfaya dönüyordu. --}}
        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        {{-- TEMSİLÎ GÖRSEL — yalnız görseli olmayan HİZMET ilanlarında.

             Ana formun DIŞINDA: iç içe <form> geçersiz HTML ve tarayıcı
             içtekini yok sayar; düğme sessizce ilanı kaydederdi.

             Kapı controller'da (TemsiliGorselUretici::uygunMu) — bu görünüm
             karar vermiyor, yalnız gösteriyor. --}}
        @if (($temsiliGorselOnerilebilir ?? false))
            <div class="mt-6 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-900/40">
                <p class="text-sm font-medium text-stone-800 dark:text-stone-200">Bu ilanın görseli yok</p>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    Kendi fotoğrafın her zaman daha iyi sonuç verir. Yoksa hizmetini
                    çağrıştıran <strong>temsilî</strong> bir görsel üretebiliriz —
                    ilanda “Temsilî görsel” etiketiyle görünür, istediğin an silebilirsin.
                </p>
                <form method="POST" action="{{ route('panel.listings.representative-image', $listing) }}"
                      class="mt-3" x-data="gonderimKilidi('Üretiliyor...')" @submit="kilitle">
                    @csrf
                    <button type="submit"
                            class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800 transition hover:bg-stone-100 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700">
                        <x-heroicon-o-sparkles class="h-4 w-4" />
                        Temsilî görsel oluştur
                    </button>
                </form>
            </div>
        @endif

        {{-- YEREL DİLE ÇEVİRİ — ana formun DIŞINDA (iç içe <form> geçersiz).

             Amaç yerel arama trafiği: Almanya'daki bir terzinin ilanını Alman
             komşu "Änderungsschneiderei" diye arıyor ve bugün hiçbir zaman
             bulamıyor.

             Üç durum var ve üçü de ayrı şey söylüyor:
               güncel çeviri var  → "hazır", yeniden çevirme düğmesi yok
               çeviri var ama BAYAT → metin değişmiş, yeniden çevir
               hiç yok            → ilk kez çevir --}}
        @if (($cevirmen ?? null) && $cevirmen->uygunMu($listing))
            @php
                $hedefDil = $cevirmen->dilAdi((string) $cevirmen->hedefDil($listing));
                $guncelCeviri = $cevirmen->guncelCeviri($listing);
                $bayatVar = $guncelCeviri === null && $listing->translations->isNotEmpty();
            @endphp
            <div class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-900/40">
                <p class="text-sm font-medium text-stone-800 dark:text-stone-200">
                    {{ $hedefDil }} çeviri
                    @if ($guncelCeviri)
                        <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">hazır</span>
                    @endif
                </p>
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                    @if ($guncelCeviri)
                        İlan sayfanda “otomatik çeviri” etiketiyle görünüyor. Metni değiştirirsen çeviri gizlenir; buradan yenileyebilirsin.
                    @elseif ($bayatVar)
                        <strong>İlan metni değişmiş.</strong> Eski çeviri artık başka bir ilanı anlatıyor, bu yüzden gizlendi. Yeniden çevirirsen tekrar görünür.
                    @else
                        İlanını {{ $hedefDil }} da yazalım — çevredeki Türkçe bilmeyen müşteriler ve yerel arama seni bulsun. Metin “otomatik çeviri” etiketiyle görünür.
                    @endif
                </p>
                <form method="POST" action="{{ route('panel.listings.translate', $listing) }}"
                      class="mt-3" x-data="gonderimKilidi('Çevriliyor...')" @submit="kilitle">
                    @csrf
                    <button type="submit"
                            class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800 transition hover:bg-stone-100 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700">
                        <x-heroicon-o-language class="h-4 w-4" />
                        {{ $guncelCeviri || $bayatVar ? 'Çeviriyi yenile' : $hedefDil.' çeviri ekle' }}
                    </button>
                </form>
            </div>
        @endif

        <form method="POST" action="{{ route('panel.listings.update', $listing) }}" enctype="multipart/form-data" class="mt-6 space-y-5" x-data="gonderimKilidi('Kaydediliyor...')" @submit="kilitle">
            @csrf
            @method('PUT')
            @include('panel.listings.partials.form-fields', ['listing' => $listing])

            @if ($listing->images->isNotEmpty())
                <div>
                    <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mevcut görseller</span>
                    <p class="text-xs text-stone-600 dark:text-stone-400">Sürükleyerek hizala, silmek istediklerini işaretle.</p>
                    <div class="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($listing->images as $image)
                            <div
                                x-data="focalDrag({{ $image->focal_x }}, {{ $image->focal_y }}, '{{ route('panel.listings.images.align', [$listing, $image]) }}')"
                                class="group relative overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700"
                            >
                                <div
                                    x-ref="frame"
                                    @pointerdown="startDrag($event)"
                                    @dragstart.prevent="null"
                                    class="focal-drag-frame aspect-square w-full cursor-move touch-none select-none"
                                >
                                    <img src="{{ $image->url('medium') }}" alt="" draggable="false"
                                         class="h-full w-full object-cover" :style="{ objectPosition: objectPosition }">
                                </div>
                                @if ($image->is_cover)
                                    <span class="pointer-events-none absolute left-1 top-1 rounded bg-emerald-700 px-1.5 py-0.5 text-2xs font-semibold text-white dark:bg-emerald-500 dark:text-stone-900">Kapak</span>
                                @endif
                                <span class="pointer-events-none absolute right-1 top-1 rounded bg-stone-900/70 px-1.5 py-0.5 text-2xs font-medium text-white opacity-0 transition group-hover:opacity-100" x-show="!saved">Sürükle</span>
                                <span class="pointer-events-none absolute right-1 top-1 rounded bg-emerald-700 px-1.5 py-0.5 text-2xs font-medium text-white" x-show="saved">Kaydedildi ✓</span>
                                <label class="absolute inset-x-0 bottom-0 flex cursor-pointer items-center gap-1 bg-white/90 px-2 py-1 text-xs text-red-600 dark:bg-stone-900/90 dark:text-red-400">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="rounded border-stone-300 text-red-600 focus:ring-red-500 dark:border-stone-600 dark:bg-stone-800">
                                    Sil
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Düzenleme ekranı da küçültücüyü kullanır: aynı 4 MB duvarı burada
                 da vardı ve buradan eklenen fotoğraf da aynı şekilde reddediliyordu. --}}
            {{-- İşlenme durumu BURADA da gösterilir: kullanıcı görseli
                 göremeyince ilk buraya gelip tekrar yüklemeye çalışıyor.
                 Yolda olan bir görsel için ikinci kez yüklemesin. --}}
            @if ($listing->gorselDurumu() === 'isleniyor')
                <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 dark:border-emerald-700 dark:bg-emerald-950/30">
                    <p class="flex items-center gap-2 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                        <x-heroicon-o-arrow-path class="h-4 w-4 shrink-0 motion-safe:animate-spin" />
                        {{ $listing->pending_images }} görsel işleniyor — birkaç dakika içinde görünecek. Sayfayı yenileyebilirsin.
                    </p>
                </div>
            @elseif ($listing->gorselDurumu() === 'hata')
                <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 dark:border-red-700 dark:bg-red-950/30">
                    <p class="flex items-center gap-2 text-sm font-medium text-red-800 dark:text-red-300">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4 shrink-0" />
                        Son yüklediğin görsel işlenemedi. Aşağıdan tekrar deneyebilirsin.
                    </p>
                </div>
            @endif

            <div x-data="gorselKucultucu">
                <label for="images" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yeni görsel ekle <span class="text-stone-600">(ops.)</span></label>
                <input id="images" name="images[]" type="file" accept="image/*" multiple
                       @change="secildi($event)"
                       class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">Büyük fotoğraflar yüklenmeden önce otomatik küçültülür.</p>
                <p class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-400" x-show="durum" x-text="durum" x-cloak></p>
                @error('images.*') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                        Değişiklikleri Kaydet
                    </button>
                    <a href="{{ route('listings.show', [$listing, $listing->slug]) }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">İlanı gör →</a>
                </div>
            </div>
        </form>

        {{-- Müsaitlik takvimi (emlak + vasıta) --}}
        @if (in_array($listing->type->value, ['emlak', 'vasita'], true))
            @php($ranges = $listing->unavailableRanges()->where('ends_on', '>=', now()->toDateString())->get())
            <div class="mt-8 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <h2 class="font-semibold text-stone-800 dark:text-stone-100">📅 Müsaitlik Takvimi</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Dolu olduğun tarih aralıklarını işaretle — ziyaretçiler ilanında bu tarihleri "dolu" görür, tarihli aramalarda ilanın o tarihler için listelenmez.</p>

                {{-- Durum mesajı sayfanın en üstüne taşındı (orada her ilan
                     tipi için görünüyor); burada tekrar basmıyoruz. --}}

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
                        <label for="note" class="block text-xs font-medium text-stone-600 dark:text-stone-400">Not <span class="text-stone-600">(ops., sadece sen görürsün)</span></label>
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
                                    @if ($range->note)<span class="ml-1 text-stone-600 dark:text-stone-400">({{ $range->note }})</span>@endif
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
                    <p class="mt-4 text-sm text-stone-600 dark:text-stone-400">Henüz dolu tarih işaretlemedin — takvimin tamamen müsait görünüyor.</p>
                @endif
            </div>
        @endif

        <div class="mt-6 border-t border-stone-200 pt-6 dark:border-stone-800">
            {{-- Silme kararının verildiği yer BURASI — geri alınabilir seçeneği
                 de burada söylemek gerekiyor. Yoksa "sattım" diyen üye tek
                 gördüğü düğmeye basıp ilanı, görüntülenmesini ve paylaştığı
                 bağlantıyı kalıcı olarak yok ediyor. --}}
            @if ($listing->status === \App\Enums\ListingStatus::Aktif)
                <p class="mb-3 text-sm text-stone-600 dark:text-stone-400">
                    Sattın ya da ara vermek mi istiyorsun? Silmek yerine
                    <a href="{{ route('panel.listings.index') }}" class="font-medium text-emerald-700 underline dark:text-emerald-400">İlanlarım'dan yayından kaldırabilirsin</a>
                    — istediğin zaman geri açarsın.
                </p>
            @endif
            <form method="POST" action="{{ route('panel.listings.destroy', $listing) }}"
                  onsubmit="return confirm('Bu ilanı silmek istediğine emin misin? Bu işlem geri alınamaz.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">İlanı sil</button>
            </form>
        </div>
    </div>
</x-layouts.app>

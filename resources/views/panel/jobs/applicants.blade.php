@php
    use App\Enums\ApplicationStatus;
    use App\Http\Controllers\JobApplicationController;

    $durumlar = ApplicationStatus::cases();
    $toplam = array_sum($sayimlar);

    // Sütun rengi. Enum'ın getColor()'ı Filament paletini döndürür (gray/info/
    // warning/...), burada Tailwind sınıfı gerekiyor. emerald/stone dışındaki
    // tonlar Vitrin temasında da doğru: Vitrin yalnız emerald ve stone
    // rampalarını yeniden eşler, diğer renkler iki temada da aynıdır.
    $seritRengi = [
        'gonderildi' => 'bg-stone-400',
        'incelendi' => 'bg-sky-500',
        'gorusme' => 'bg-amber-500',
        'kabul' => 'bg-emerald-500',
        'red' => 'bg-red-500',
    ];
@endphp

<x-layouts.app title="Başvurular — Nisoya">
    <div
        x-data="kanbanPano('{{ $durumlar[0]->value }}')"
        class="mx-auto max-w-7xl px-4 py-8"
    >
        <x-panel.back-link :href="route('panel.jobs.index')" label="İş ilanlarım" />

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Başvurular</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $job->title }} · {{ $toplam }} başvuru</p>
            </div>

            {{-- Bildirim semantiği GÖRÜNÜR olmalı: hangi sütunun adaya haber
                 gönderdiği tahmin edilecek bir şey değil. --}}
            <p class="text-xs text-stone-500 dark:text-stone-400">
                🔕 sessiz sütun · 🔔 adaya e-posta gider
                <span class="block">Bildirim ~{{ \App\Jobs\BasvuruDurumBildirimi::GECIKME_DAKIKA }} dk gecikmeli gönderilir; o süre içinde geri alırsan adayın haberi olmaz.</span>
            </p>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        {{-- Sürükleme sonucu (yalnız JS yolunda oluşur) --}}
        <div x-show="mesaj" x-cloak x-transition
             :class="mesajTipi === 'hata'
                ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'"
             class="mt-4 rounded-lg px-4 py-3 text-sm" role="status" aria-live="polite" x-text="mesaj"></div>

        @if ($toplam === 0)
            <x-empty-state
                illustration="inbox"
                title="Henüz başvuru yok"
                description="Başvurular geldikçe burada görünecek."
            />
        @else
            {{-- Mobilde 5 sütun yan yana sığmaz; tek sütun gösterilir.
                 Varsayılan bilinçli olarak ilk sütun ("Gönderildi") ve seçim
                 KAYDEDİLMEZ: telefondan giren işverenin ilk işi yeni
                 başvuruları elemektir. --}}
            <div class="mt-6 md:hidden">
                <label for="mobil-sutun" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Gösterilen sütun</label>
                <select id="mobil-sutun" x-model="aktifSutun"
                        class="mt-1 w-full rounded-lg border-stone-300 py-2 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @foreach ($durumlar as $durum)
                        <option value="{{ $durum->value }}">
                            {{ $durum->bildirimGerektirir() ? '🔔' : '🔕' }} {{ $durum->getLabel() }} ({{ $sayimlar[$durum->value] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 gap-4 md:grid md:grid-cols-5">
                @foreach ($durumlar as $durum)
                    @php
                        $anahtar = $durum->value;
                        $kartlar = $sutunlar[$anahtar];
                        $adet = $sayimlar[$anahtar] ?? 0;
                    @endphp
                    <section
                        data-durum="{{ $anahtar }}"
                        :class="aktifSutun === '{{ $anahtar }}' ? 'flex' : 'hidden md:flex'"
                        class="flex-col rounded-2xl bg-stone-100/70 p-2 dark:bg-stone-900/50"
                        aria-label="{{ $durum->getLabel() }} sütunu"
                    >
                        <header class="px-1 pb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $seritRengi[$anahtar] }}"></span>
                                <h2 class="min-w-0 flex-1 truncate text-sm font-semibold text-stone-700 dark:text-stone-200">{{ $durum->getLabel() }}</h2>
                                <span data-sayac="{{ $anahtar }}"
                                      class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-stone-600 dark:bg-stone-800 dark:text-stone-300">{{ $adet }}</span>
                            </div>
                            <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                @if ($durum->bildirimGerektirir())
                                    <span title="Bu sütuna taşımak adayı e-posta ile bilgilendirir.">🔔 Aday bilgilendirilir</span>
                                @else
                                    <span title="Bu sütuna taşımak adaya bildirim göndermez.">🔕 Adaya bildirim gitmez</span>
                                @endif
                            </p>
                        </header>

                        <div data-liste class="flex flex-col gap-2 md:max-h-[60vh] md:overflow-y-auto">
                            @foreach ($kartlar as $basvuru)
                                @include('panel.jobs.partials.basvuru-karti', ['basvuru' => $basvuru])
                            @endforeach
                        </div>

                        @if ($adet > JobApplicationController::SUTUN_TAVANI)
                            <p class="px-1 pt-2 text-xs text-stone-500 dark:text-stone-400">
                                En yeni {{ JobApplicationController::SUTUN_TAVANI }} başvuru gösteriliyor ({{ $adet }} toplam).
                            </p>
                        @endif

                        @if ($kartlar->isEmpty())
                            <p class="rounded-lg border border-dashed border-stone-300 px-2 py-6 text-center text-xs text-stone-600 dark:border-stone-700 dark:text-stone-400">Boş</p>
                        @endif
                    </section>
                @endforeach
            </div>

            <p class="mt-4 text-xs text-stone-600 dark:text-stone-400">
                Fareyle kartları sütunlar arasında sürükleyebilirsin (sürüklerken <kbd class="rounded border border-stone-300 px-1 dark:border-stone-600">Esc</kbd> iptal eder).
                Klavye ve telefonda karttaki durum menüsünü kullan — ikisi de aynı işi yapar.
            </p>
        @endif
    </div>
</x-layouts.app>

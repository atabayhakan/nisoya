@props(['countries'])

{{-- Faz İ2 ("2. Tasarım" pilotu, bkz. /yonetim Tasarım Modu): anasayfanın
     "22 ülke" istatistiğini metin yerine gerçek veriyle gösteren imza öge.
     Her nokta gerçek bir ülkenin enlem/boylamında, boyutu o ülkedeki aktif
     ilan sayısıyla orantılı — bkz. App\Services\NabizService::countryActivity(). --}}
<div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 px-6 py-4 dark:border-stone-800">
        <h3 class="font-serif text-xl italic text-stone-900 dark:text-stone-50">Nisoya'nın Nabzı</h3>
        <span class="font-mono text-xs text-stone-600 dark:text-stone-400">{{ count($countries) }} ülke · şu an aktif</span>
    </div>
    <div
        x-data="pulseMap(@js($countries))"
        class="relative aspect-[16/8] w-full"
        style="background: radial-gradient(ellipse at 30% 30%, color-mix(in srgb, var(--color-emerald-600) 8%, transparent), transparent 60%), radial-gradient(ellipse at 75% 70%, color-mix(in srgb, var(--nisoya-seal, #c1440e) 8%, transparent), transparent 55%), var(--color-stone-50);"
    >
        <canvas x-ref="canvas" class="absolute inset-0 h-full w-full" role="img" aria-label="Nisoya'nın {{ count($countries) }} ülkedeki topluluğunu gösteren canlı nabız görselleştirmesi"></canvas>
    </div>
    <p class="border-t border-stone-100 px-6 py-3 font-mono text-2xs text-stone-600 dark:border-stone-800 dark:text-stone-400">
        Nokta boyutu, o ülkedeki aktif ilan sayısını yansıtır — temsili değil, gerçek veri.
    </p>
</div>

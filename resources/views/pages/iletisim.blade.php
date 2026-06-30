<x-layouts.app title="İletişim — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-bold text-stone-900">İletişim</h1>
        <p class="mt-2 text-stone-600">Soru, öneri veya bir sorun mu var? Bize ulaş.</p>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="text-2xl">✉️</div>
                <h2 class="mt-2 font-semibold text-stone-800">E-posta</h2>
                <a href="mailto:destek@nisoya.com" class="mt-1 block text-sm text-emerald-700 hover:underline">destek@nisoya.com</a>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="text-2xl">🛟</div>
                <h2 class="mt-2 font-semibold text-stone-800">Yardım</h2>
                <p class="mt-1 text-sm text-stone-500">Sık sorulan sorulara <a href="{{ route('pages.faq') }}" class="text-emerald-700 hover:underline">SSS sayfasından</a> göz atabilirsin.</p>
            </div>
        </div>

        <p class="mt-8 text-xs text-stone-400">Not: İletişim e-posta adresi yayına geçişte güncellenecektir.</p>
    </div>
</x-layouts.app>

<x-layouts.app title="Davetiyelerim — Nisoya">
    <div class="mx-auto max-w-4xl px-4 py-10">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">💌 Davetiyelerim</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Düğün, sünnet, iftar, doğum günü... davetiye oluştur, WhatsApp'ta paylaş, LCV'leri takip et.</p>
            </div>
            <a href="{{ route('panel.events.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                + Yeni Davetiye
            </a>
        </div>

        @if ($events->isNotEmpty())
            <div class="mt-6 space-y-3">
                @foreach ($events as $event)
                    <a href="{{ route('panel.events.show', $event) }}"
                       class="flex items-center justify-between gap-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-brand dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                        <div class="flex items-center gap-4">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-2xl dark:bg-emerald-900/40">{{ $event->type->emoji() }}</span>
                            <div class="min-w-0">
                                <div class="font-semibold text-stone-800 dark:text-stone-100">{{ $event->title }}</div>
                                <div class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">
                                    {{ $event->type->getLabel() }} · {{ $event->starts_at->translatedFormat('j F Y, H:i') }}
                                    @unless ($event->is_active) · <span class="text-red-500 dark:text-red-400">link kapalı</span> @endunless
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ $event->guests_count }}</div>
                            <div class="text-xs text-stone-600 dark:text-stone-400">LCV</div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $events->links() }}</div>
        @else
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-stone-700 dark:bg-stone-900">
                <span class="text-4xl">💌</span>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">İlk davetiyeni oluştur</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-stone-500 dark:text-stone-400">
                    Tür ve temayı seç, tarihi gir — sana özel bir davet linki verelim.
                    WhatsApp'ta paylaş, misafirlerin üye olmadan tek dokunuşla "geliyorum" desin.
                </p>
                <a href="{{ route('panel.events.create') }}" class="mt-5 inline-block rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Davetiye Oluştur</a>
            </div>
        @endif
    </div>
</x-layouts.app>

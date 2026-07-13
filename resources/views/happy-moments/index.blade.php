<x-layouts.app title="Mutlu Anlar — Nisoya"
               description="Nisoya davetiyeleriyle yapılan düğünler, sünnetler, kutlamalar — ev sahiplerinin herkese açtığı etkinlik albümleri.">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-50">💌 Mutlu Anlar</h1>
            <p class="mx-auto mt-2 max-w-xl text-sm text-stone-500 dark:text-stone-400">
                Nisoya davetiyeleriyle kutlanan özel günler — ev sahiplerinin herkesle paylaşmayı seçtiği albümler.
                Sen de <a href="{{ route('panel.events.create') }}" class="font-medium text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400">ücretsiz davetiyeni oluştur</a>.
            </p>
        </div>

        @if ($events->isNotEmpty())
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <a href="{{ $event->inviteUrl() }}"
                       class="group overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                        <div class="aspect-[4/3] w-full overflow-hidden bg-stone-100 dark:bg-stone-800">
                            @if ($event->media->isNotEmpty())
                                <img src="{{ Storage::disk(\App\Models\EventMedia::DISK)->url($event->media->first()->path_medium) }}"
                                     alt="{{ $event->title }}" loading="lazy" decoding="async"
                                     class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-5xl">{{ $event->type->emoji() }}</div>
                            @endif
                        </div>
                        <div class="p-4">
                            <div class="text-xs font-medium uppercase tracking-wider text-emerald-700 dark:text-emerald-400">{{ $event->type->emoji() }} {{ $event->type->getLabel() }}</div>
                            <h2 class="mt-1 line-clamp-2 font-semibold text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400">{{ $event->title }}</h2>
                            <div class="mt-1.5 text-xs text-stone-400 dark:text-stone-500">
                                {{ $event->starts_at->translatedFormat('j F Y') }} · {{ $event->published_media_count }} paylaşım
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $events->links() }}</div>
        @else
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-stone-700 dark:bg-stone-900">
                <span class="text-4xl">📷</span>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">Henüz herkese açık albüm yok</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-stone-500 dark:text-stone-400">
                    Davetiyeni oluştur, etkinlik gününde misafirlerin fotoğraflarını toplasın —
                    istersen albümünü buradan herkesle paylaş.
                </p>
                <a href="{{ route('panel.events.create') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Davetiye Oluştur</a>
            </div>
        @endif
    </div>
</x-layouts.app>

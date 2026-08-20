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
                            <div class="mt-1.5 text-xs text-stone-600 dark:text-stone-400">
                                {{ $event->starts_at->translatedFormat('j F Y') }} · {{ $event->published_media_count }} paylaşım
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $events->links() }}</div>
        @else
            <x-empty-state
                illustration="camera"
                title="Henüz herkese açık albüm yok"
                description="Davetiyeni oluştur, etkinlik gününde misafirlerin fotoğraflarını toplasın — istersen albümünü buradan herkesle paylaş."
                cta-text="Davetiye Oluştur"
                :cta-href="route('panel.events.create')"
            />
        @endif
    </div>
</x-layouts.app>

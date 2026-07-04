<x-layouts.app title="Mesajlar — Nisoya">
    @php
        $me = auth()->id();
    @endphp
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Mesajlar</h1>

        @if ($conversations->isNotEmpty())
            <div class="mt-6 divide-y divide-stone-100 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:divide-stone-800 dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                @foreach ($conversations as $conversation)
                    @php
                        $other = $conversation->user_one_id === $me ? $conversation->userTwo : $conversation->userOne;
                        $last = $conversation->messages->first();
                        $unread = $last && $last->sender_id !== $me && is_null($last->read_at);
                    @endphp
                    <a href="{{ route('panel.messages.show', $conversation) }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-stone-50 dark:hover:bg-stone-800">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            {{ mb_strtoupper(mb_substr($other?->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate font-semibold text-stone-800 dark:text-stone-100">{{ $other?->name ?? 'Kullanıcı' }}</span>
                                <span class="shrink-0 text-xs text-stone-400 dark:text-stone-500">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                            </div>
                            @if ($conversation->listing)
                                <div class="truncate text-xs text-emerald-700 dark:text-emerald-400">{{ $conversation->listing->title }}</div>
                            @endif
                            <div class="flex items-center gap-2">
                                <span class="truncate text-sm {{ $unread ? 'font-semibold text-stone-800 dark:text-stone-100' : 'text-stone-500 dark:text-stone-400' }}">{{ $last?->body }}</span>
                                @if ($unread)<span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>@endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center dark:border-stone-700 dark:bg-stone-900">
                <div class="text-4xl">💬</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">Henüz mesajın yok</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Bir ilana göz atıp satıcıya mesaj göndererek başlayabilirsin.</p>
                <a href="{{ route('listings.index') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlanlara göz at</a>
            </div>
        @endif
    </div>
</x-layouts.app>

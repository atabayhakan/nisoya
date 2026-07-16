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
            <x-empty-state
                illustration="chat"
                title="Henüz mesajın yok"
                description="Bir ilana göz atıp satıcıya mesaj göndererek başlayabilirsin."
                cta-text="İlanlara göz at"
                :cta-href="route('listings.index')"
            />
        @endif
    </div>
</x-layouts.app>

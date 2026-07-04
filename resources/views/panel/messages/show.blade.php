<x-layouts.app :title="($other?->name ?? 'Mesaj').' — Nisoya'">
    @php($me = auth()->id())
    @php($lastId = $conversation->messages->max('id') ?? 0)
    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-panel.back-link :href="route('panel.messages.index')" label="Tüm mesajlar" />

        {{-- Başlık --}}
        <div class="mt-3 flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <div class="grid h-11 w-11 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                {{ mb_strtoupper(mb_substr($other?->name ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                @if ($other)
                    <a href="{{ route('profiles.show', $other->username) }}" class="font-semibold text-stone-800 hover:text-emerald-700 dark:text-stone-100 dark:hover:text-emerald-400">{{ $other->name }}</a>
                @else
                    <span class="font-semibold text-stone-800 dark:text-stone-100">Kullanıcı</span>
                @endif
                @if ($conversation->listing)
                    <a href="{{ route('listings.show', [$conversation->listing, $conversation->listing->slug]) }}" class="block truncate text-xs text-emerald-700 hover:underline dark:text-emerald-400">
                        İlan: {{ $conversation->listing->title }}
                    </a>
                @endif
            </div>
            <span class="flex items-center gap-1 text-xs text-stone-400 dark:text-stone-500"><span class="h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span> canlı</span>
        </div>

        {{-- Mesajlar --}}
        <div id="thread" class="mt-4 space-y-3" data-url="{{ route('panel.messages.stream', $conversation) }}" data-last="{{ $lastId }}">
            @foreach ($conversation->messages->sortBy('created_at') as $message)
                @php($mine = $message->sender_id === $me)
                <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-2xl px-4 py-2 {{ $mine ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900' : 'bg-white text-stone-800 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-100 dark:ring-stone-700' }}">
                        <p class="whitespace-pre-line text-sm">{{ $message->body }}</p>
                        <p class="mt-1 text-[10px] {{ $mine ? 'text-emerald-100' : 'text-stone-400 dark:text-stone-500' }}">{{ $message->created_at->format('d.m H:i') }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Gönder --}}
        <form id="send-form" data-url="{{ route('panel.messages.store', $conversation) }}" class="mt-4 flex items-end gap-2">
            @csrf
            <textarea name="body" rows="2" required placeholder="Mesaj yaz..."
                      class="flex-1 rounded-xl border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"></textarea>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Gönder</button>
        </form>
    </div>

    <script>
        (function () {
            const thread = document.getElementById('thread');
            const form = document.getElementById('send-form');
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            let last = parseInt(thread.dataset.last) || 0;

            const scrollBottom = () => window.scrollTo({ top: document.body.scrollHeight });

            function appendMessage(m) {
                const wrap = document.createElement('div');
                wrap.className = 'flex ' + (m.mine ? 'justify-end' : 'justify-start');
                const bubble = document.createElement('div');
                bubble.className = 'max-w-[75%] rounded-2xl px-4 py-2 ' + (m.mine ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900' : 'bg-white text-stone-800 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-100 dark:ring-stone-700');
                const body = document.createElement('p');
                body.className = 'whitespace-pre-line text-sm';
                body.textContent = m.body;
                const time = document.createElement('p');
                time.className = 'mt-1 text-[10px] ' + (m.mine ? 'text-emerald-100' : 'text-stone-400 dark:text-stone-500');
                time.textContent = m.time;
                bubble.append(body, time);
                wrap.append(bubble);
                thread.append(wrap);
            }

            async function poll() {
                try {
                    const res = await fetch(thread.dataset.url + '?after=' + last, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.messages.length) {
                        data.messages.forEach((m) => { appendMessage(m); last = Math.max(last, m.id); });
                        scrollBottom();
                    }
                } catch (e) { /* sessizce yoksay */ }
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const ta = form.querySelector('textarea');
                const text = ta.value.trim();
                if (!text) return;
                const btn = form.querySelector('button');
                btn.disabled = true;
                try {
                    const res = await fetch(form.dataset.url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ body: text }),
                    });
                    if (res.ok) {
                        const m = await res.json();
                        appendMessage(m);
                        last = Math.max(last, m.id);
                        ta.value = '';
                        scrollBottom();
                    }
                } catch (e) { /* yoksay */ } finally {
                    btn.disabled = false;
                    ta.focus();
                }
            });

            scrollBottom();
            setInterval(poll, 5000);
        })();
    </script>
</x-layouts.app>

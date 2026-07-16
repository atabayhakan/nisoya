<x-layouts.app :title="($other?->name ?? 'Mesaj').' — Nisoya'">
    @php($me = auth()->id())
    @php($lastId = $conversation->messages->max('id') ?? 0)
    <div class="mx-auto max-w-3xl px-4 py-6">
        <x-panel.back-link :href="route('panel.messages.index')" label="Tüm mesajlar" />

        {{-- Başlık --}}
        <div class="mt-3 flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
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
            <span class="flex shrink-0 items-center gap-1 text-xs text-stone-400 dark:text-stone-500"><span class="h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span> canlı</span>
        </div>

        {{-- Mesajlar: SABİT yükseklikli, KENDİ İÇİNDE kaydırılan kutu. --}}
        <div id="thread"
             class="mt-4 h-[58vh] min-h-[280px] space-y-3 overflow-y-auto rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-950/40"
             data-url="{{ route('panel.messages.stream', $conversation) }}"
             data-recall-url="{{ url('/panel/mesajlar/'.$conversation->id.'/mesaj') }}"
             data-last="{{ $lastId }}">
            @forelse ($conversation->messages->sortBy('created_at') as $message)
                @include('panel.messages.partials.bubble', ['message' => $message, 'mine' => $message->sender_id === $me])
            @empty
                <div id="thread-empty" class="flex h-full items-center justify-center text-center text-sm text-stone-400 dark:text-stone-500">
                    Henüz mesaj yok — ilk mesajı sen yaz.
                </div>
            @endforelse
        </div>

        {{-- "Yazıyor..." göstergesi (Faz M4) --}}
        <div id="typing-indicator" class="mt-2 hidden px-2 text-xs text-stone-400 dark:text-stone-500">
            <span class="italic">{{ $other?->name ?? 'Kullanıcı' }} yazıyor…</span>
        </div>

        {{-- Gönder --}}
        <form id="send-form"
              data-url="{{ route('panel.messages.store', $conversation) }}"
              data-typing-url="{{ route('panel.messages.typing', $conversation) }}"
              class="mt-3 flex items-end gap-2">
            @csrf

            {{-- Fotoğraf ekle (birden fazla seçilebilir — her biri ayrı mesaj olarak sırayla gönderilir) --}}
            <label class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-stone-300 text-stone-500 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-400 dark:hover:bg-stone-800" title="Fotoğraf gönder (birden fazla seçebilirsin)">
                <x-heroicon-o-photo class="h-5 w-5" />
                <input id="photo-input" type="file" accept="image/*" multiple class="sr-only">
            </label>

            {{-- Konum paylaş --}}
            <button type="button" id="location-btn" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-stone-300 text-stone-500 transition hover:bg-stone-50 disabled:opacity-50 dark:border-stone-700 dark:text-stone-400 dark:hover:bg-stone-800" title="Konum gönder">
                <x-heroicon-o-map-pin class="h-5 w-5" />
            </button>

            <textarea name="body" rows="2" maxlength="2000" placeholder="Mesaj yaz... (Enter ile gönder, Shift+Enter ile alt satır)"
                      class="flex-1 resize-none rounded-xl border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"></textarea>
            <button type="submit" class="shrink-0 rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Gönder</button>
        </form>
    </div>

    <script>
        (function () {
            const thread = document.getElementById('thread');
            const form = document.getElementById('send-form');
            const photoInput = document.getElementById('photo-input');
            const locationBtn = document.getElementById('location-btn');
            const typingEl = document.getElementById('typing-indicator');
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const typingUrl = form.dataset.typingUrl;
            let last = parseInt(thread.dataset.last) || 0;
            let busy = false;
            let lastTypingPing = 0;

            const MINE = 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900';
            const THEIRS = 'bg-white text-stone-800 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-100 dark:ring-stone-700';

            const scrollBottom = () => { thread.scrollTop = thread.scrollHeight; };
            const removeEmpty = () => { const e = document.getElementById('thread-empty'); if (e) e.remove(); };

            function recalledBubble() {
                const b = document.createElement('div');
                b.className = 'max-w-[75%] rounded-2xl px-4 py-2 text-sm italic text-stone-400 ring-1 ring-stone-200 dark:text-stone-500 dark:ring-stone-700';
                b.innerHTML = '<span class="select-none">🚫</span> Bu mesaj geri alındı';
                return b;
            }

            function metaEl(m) {
                const meta = document.createElement('p');
                meta.className = 'meta mt-1 flex items-center justify-end gap-1.5 text-[10px] ' + (m.mine ? 'text-emerald-100' : 'text-stone-400 dark:text-stone-500');
                const t = document.createElement('span');
                t.textContent = m.time;
                meta.append(t);
                if (m.mine) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'recall-btn font-medium underline decoration-dotted underline-offset-2 hover:no-underline';
                    btn.textContent = '· geri al';
                    meta.append(btn);
                }
                return meta;
            }

            function fillBubble(bubble, m) {
                if (m.type === 'image' && m.image) {
                    const a = document.createElement('a');
                    a.href = m.image; a.target = '_blank'; a.rel = 'noopener';
                    const img = document.createElement('img');
                    img.src = m.image; img.loading = 'lazy';
                    img.className = 'max-h-64 w-auto rounded-lg';
                    a.append(img);
                    bubble.append(a);
                    if (m.body) {
                        const cap = document.createElement('p');
                        cap.className = 'body mt-1 whitespace-pre-line break-words text-sm';
                        cap.textContent = m.body;
                        bubble.append(cap);
                    }
                } else if (m.type === 'location' && m.lat != null) {
                    bubble.append(locationNode(m.lat, m.lng, m.mine));
                } else {
                    const body = document.createElement('p');
                    body.className = 'body whitespace-pre-line break-words text-sm';
                    body.textContent = m.body;
                    bubble.append(body);
                }
            }

            function locationNode(lat, lng, mine) {
                const a = document.createElement('a');
                a.href = 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=16/' + lat + '/' + lng;
                a.target = '_blank'; a.rel = 'noopener';
                a.className = 'flex items-center gap-2 text-sm font-medium ' + (mine ? 'text-white' : 'text-emerald-700 dark:text-emerald-400');
                a.innerHTML = '<span class="select-none">📍</span> Konumu haritada aç';
                return a;
            }

            function appendMessage(m) {
                removeEmpty();
                const wrap = document.createElement('div');
                wrap.className = 'msg flex ' + (m.mine ? 'justify-end' : 'justify-start');
                wrap.dataset.id = m.id;
                wrap.dataset.mine = m.mine ? '1' : '0';

                if (m.recalled) { wrap.append(recalledBubble()); thread.append(wrap); return; }

                const bubble = document.createElement('div');
                bubble.className = 'max-w-[75%] rounded-2xl px-4 py-2 ' + (m.mine ? MINE : THEIRS);
                fillBubble(bubble, m);
                bubble.append(metaEl(m));
                wrap.append(bubble);
                thread.append(wrap);
            }

            function markRecalled(id) {
                const wrap = thread.querySelector('.msg[data-id="' + id + '"]');
                if (!wrap || wrap.dataset.recalled === '1') return;
                wrap.dataset.recalled = '1';
                wrap.innerHTML = '';
                wrap.append(recalledBubble());
            }

            thread.addEventListener('click', async (e) => {
                const btn = e.target.closest('.recall-btn');
                if (!btn) return;
                const wrap = btn.closest('.msg');
                if (!wrap || !confirm('Bu mesajı geri almak istediğine emin misin? Karşı taraf artık içeriğini göremeyecek.')) return;
                btn.disabled = true;
                try {
                    const res = await fetch(thread.dataset.recallUrl + '/' + wrap.dataset.id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    if (res.ok) markRecalled(wrap.dataset.id);
                    else btn.disabled = false;
                } catch (err) { btn.disabled = false; }
            });

            async function poll() {
                if (busy) return;
                try {
                    const res = await fetch(thread.dataset.url + '?after=' + last, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    let grew = false;
                    if (data.messages && data.messages.length) {
                        data.messages.forEach((m) => { appendMessage(m); last = Math.max(last, m.id); });
                        grew = true;
                    }
                    if (data.recalled && data.recalled.length) {
                        data.recalled.forEach((id) => markRecalled(id));
                    }
                    typingEl.classList.toggle('hidden', !data.typing);
                    if (grew) scrollBottom();
                } catch (e) { /* sessizce yoksay */ }
            }

            // Tüm gönderimler FormData ile (metin / fotoğraf / konum ortak yol).
            async function postForm(formData) {
                if (busy) return;
                busy = true;
                const btn = form.querySelector('button[type=submit]');
                btn.disabled = true;
                try {
                    const res = await fetch(form.dataset.url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: formData,
                    });
                    if (res.ok) {
                        const m = await res.json();
                        appendMessage(m);
                        last = Math.max(last, m.id);
                        scrollBottom();
                        return true;
                    }
                } catch (e) { /* yoksay */ } finally {
                    busy = false;
                    btn.disabled = false;
                }
                return false;
            }

            async function sendText() {
                const ta = form.querySelector('textarea');
                const text = ta.value.trim();
                if (!text) return;
                const fd = new FormData();
                fd.append('body', text);
                if (await postForm(fd)) { ta.value = ''; ta.focus(); }
            }

            form.addEventListener('submit', (e) => { e.preventDefault(); sendText(); });

            const ta = form.querySelector('textarea');
            ta.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendText(); }
            });

            // "Yazıyor..." pingi — en fazla 2.5 sn'de bir.
            ta.addEventListener('input', () => {
                const now = Date.now();
                if (now - lastTypingPing > 2500 && ta.value.trim()) {
                    lastTypingPing = now;
                    fetch(typingUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).catch(() => {});
                }
            });

            // Fotoğraf seçilince hemen gönder.
            // Birden fazla fotoğraf seçilebilir (iPhone/Android galeri çoklu-seçim) —
            // her biri ayrı bir mesaj olduğu için sırayla (önceki bitince sonraki)
            // gönderiyoruz; postForm zaten aynı anda tek istek olmasını garanti ediyor.
            photoInput.addEventListener('change', async () => {
                const files = [...photoInput.files];
                photoInput.value = '';
                for (const file of files) {
                    const fd = new FormData();
                    fd.append('photo', file);
                    await postForm(fd);
                }
            });

            // Konum paylaş.
            locationBtn.addEventListener('click', () => {
                if (!navigator.geolocation) { alert('Cihazın konum paylaşımını desteklemiyor.'); return; }
                locationBtn.disabled = true;
                navigator.geolocation.getCurrentPosition((pos) => {
                    const fd = new FormData();
                    fd.append('lat', pos.coords.latitude.toFixed(6));
                    fd.append('lng', pos.coords.longitude.toFixed(6));
                    postForm(fd).finally(() => { locationBtn.disabled = false; });
                }, () => {
                    alert('Konum alınamadı. Tarayıcı iznini kontrol et.');
                    locationBtn.disabled = false;
                }, { enableHighAccuracy: true, timeout: 10000 });
            });

            scrollBottom();
            setInterval(poll, 3000);
        })();
    </script>
</x-layouts.app>

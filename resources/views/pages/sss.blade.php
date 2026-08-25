<x-layouts.app :title="$page->title.' — '.setting('genel.site_adi')" :description="$page->meta_description">
    {{-- FAQPage JSON-LD: /nasil-calisir'daki aynı desen (görünür + yapılandırılmış veri tek kaynaktan). --}}
    <x-json-ld type="FAQPage" :data="[
        'mainEntity' => $sorular->map(fn ($s) => [
            '@type' => 'Question',
            'name' => $s->soru,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $s->cevap],
        ])->all(),
    ]" />

    <article class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-50">{{ $page->title }}</h1>

        @if ($sorular->isEmpty())
            <p class="mt-6 text-stone-600 dark:text-stone-400">Şu an yayında bir soru yok.</p>
        @else
            {{-- id="soru-{id}" — Nisoya AI aramasının SssDogalDilArama'dan
                 döndürdüğü doğrudan bağlantıların hedefi. --}}
            <div class="mt-8 divide-y divide-stone-200 overflow-hidden rounded-2xl border border-stone-200 bg-white dark:divide-stone-800 dark:border-stone-800 dark:bg-stone-900">
                @foreach ($sorular as $soru)
                    <details id="soru-{{ $soru->id }}" class="group scroll-mt-24">
                        <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 text-sm font-semibold text-stone-800 marker:content-none hover:bg-stone-50 dark:text-stone-100">
                            {{ $soru->soru }}
                            <svg class="h-5 w-5 shrink-0 text-stone-600 transition group-open:rotate-180 dark:text-stone-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        <p class="px-5 pb-4 text-sm leading-relaxed text-stone-600 dark:text-stone-400">{{ $soru->cevap }}</p>
                    </details>
                @endforeach
            </div>

            {{-- Nisoya AI aramasından #soru-N ile gelindiğinde native <details>
                 kapalı başlar; tarayıcı yalnız scroll eder, açmaz. JS yoksa
                 zarar yok — kullanıcı doğru soruya scroll edilmiş, tek tık uzakta. --}}
            <script>
                if (location.hash) {
                    const hedef = document.querySelector(location.hash);
                    if (hedef && hedef.tagName === 'DETAILS') hedef.open = true;
                }
            </script>
        @endif
    </article>
</x-layouts.app>

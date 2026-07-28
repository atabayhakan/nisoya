@php
    // RichEditor içeriğini güvenli HTML'e çevirir (Filament sanitize eder).
    $renderRich = fn ($content) => \Filament\Forms\Components\RichEditor\RichContentRenderer::make($content ?? '')->toHtml();
    $data = $block['data'] ?? [];
@endphp

@switch($block['type'] ?? '')
    @case('baslik')
        @php($lvl = (($data['level'] ?? 'h2') === 'h3') ? 'h3' : 'h2')
        <{{ $lvl }} class="{{ $lvl === 'h2' ? 'text-2xl' : 'text-xl' }} font-bold text-stone-900 dark:text-stone-50">{{ $data['text'] ?? '' }}</{{ $lvl }}>
        @break

    @case('metin')
        <div class="prose prose-stone max-w-none">{!! $renderRich($data['content'] ?? '') !!}</div>
        @break

    @case('gorsel')
        <figure>
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($data['image'] ?? '') }}"
                 alt="{{ $data['alt'] ?? '' }}" class="w-full rounded-2xl shadow-sm" loading="lazy">
            @if (! empty($data['caption']))
                <figcaption class="mt-2 text-center text-sm text-stone-500 dark:text-stone-400">{{ $data['caption'] }}</figcaption>
            @endif
        </figure>
        @break

    @case('iki_sutun')
        <div class="grid gap-6 md:grid-cols-2">
            <div class="prose prose-stone max-w-none">{!! $renderRich($data['left'] ?? '') !!}</div>
            <div class="prose prose-stone max-w-none">{!! $renderRich($data['right'] ?? '') !!}</div>
        </div>
        @break

    @case('cta')
        <div class="rounded-3xl bg-emerald-700 px-6 py-10 text-center text-white sm:px-12 dark:bg-emerald-500 dark:text-stone-900">
            <h2 class="text-2xl font-bold sm:text-3xl">{{ $data['title'] ?? '' }}</h2>
            @if (! empty($data['button_text']))
                <a href="{{ $data['button_url'] ?? '#' }}" class="mt-5 inline-block rounded-xl bg-white px-6 py-3 font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:bg-stone-900 dark:text-emerald-400">{{ $data['button_text'] }}</a>
            @endif
        </div>
        @break

    @case('ayrac')
        <hr class="border-stone-200 dark:border-stone-800">
        @break
@endswitch

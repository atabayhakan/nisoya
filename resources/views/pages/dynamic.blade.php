<x-layouts.app :title="$page->title.' — '.setting('genel.site_adi')" :description="$page->meta_description">
    <article class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-bold tracking-tight text-stone-900">{{ $page->title }}</h1>

        <div class="mt-8 space-y-8">
            @foreach ($page->blocks ?? [] as $block)
                @include('partials.page-block', ['block' => $block])
            @endforeach
        </div>
    </article>
</x-layouts.app>

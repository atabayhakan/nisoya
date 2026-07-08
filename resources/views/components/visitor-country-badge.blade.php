@props(['country'])

@if ($country)
    <span
        class="hidden items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-stone-600 sm:inline-flex dark:text-stone-300"
        title="Konum: {{ $country->name }}"
    >
        <span aria-hidden="true" class="text-base leading-none">{{ $country->emoji }}</span>
        <span class="hidden md:inline">{{ $country->name }}</span>
    </span>
@endif

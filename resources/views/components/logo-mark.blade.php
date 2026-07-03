@props(['class' => 'h-5 w-5'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" {{ $attributes->merge(['class' => $class]) }}>
    <path d="M7 17V7L17 17V7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
</svg>

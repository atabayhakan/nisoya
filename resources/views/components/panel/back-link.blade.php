@props(['href' => null, 'label' => 'Panelim'])

<a href="{{ $href ?? route('dashboard') }}"
   class="group mb-4 inline-flex items-center gap-2 text-sm font-medium text-stone-500 transition hover:text-emerald-700 dark:text-stone-400 dark:hover:text-emerald-400">
    <span class="grid h-7 w-7 place-items-center rounded-full bg-stone-100 text-stone-500 transition-all duration-200 group-hover:-translate-x-0.5 group-hover:bg-emerald-100 group-hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-400 dark:group-hover:bg-emerald-900/40 dark:group-hover:text-emerald-400">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
    </span>
    {{ $label }}
</a>

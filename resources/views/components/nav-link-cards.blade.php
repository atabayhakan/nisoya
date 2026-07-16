@props(['items', 'onSelect' => '', 'gridClass' => 'sm:grid-cols-2'])

{{-- Kesfet grubu (bkz. App\Models\NavigationLink::GROUP_KESFET) kartları —
     hem masaüstü mega menüde (mega-menu.blade.php) hem mobil "Keşfet" alt
     sayfasında (mobile-tab-bar.blade.php) aynı veriyle kullanılır. --}}
<div class="grid grid-cols-1 gap-1 {{ $gridClass }}">
    @foreach ($items as $item)
        <a
            href="{{ $item->url }}"
            @if ($item->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif
            class="flex items-start gap-3 rounded-xl px-3 py-2.5 transition hover:bg-stone-50 dark:hover:bg-stone-800"
            role="menuitem"
            @if ($onSelect) @click="{{ $onSelect }}" @endif
        >
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                <x-dynamic-component :component="'heroicon-o-'.\App\Support\NavigationIcon::heroicon($item->icon)" class="h-5 w-5" />
            </span>
            <span>
                <span class="block text-sm font-semibold text-stone-800 dark:text-stone-100">{{ $item->label }}</span>
                @if ($item->description)
                    <span class="block text-xs text-stone-500 dark:text-stone-400">{{ $item->description }}</span>
                @endif
            </span>
        </a>
    @endforeach
</div>

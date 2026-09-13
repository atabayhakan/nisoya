@props(['items', 'onSelect' => '', 'gridClass' => 'sm:grid-cols-2'])

{{-- Kesfet grubu (bkz. App\Models\NavigationLink::GROUP_KESFET) kartları —
     hem masaüstü mega menüde (mega-menu.blade.php) hem mobil "Keşfet" alt
     sayfasında (mobile-tab-bar.blade.php) aynı veriyle kullanılır. --}}
<div class="grid grid-cols-1 gap-1.5 {{ $gridClass }}">
    @foreach ($items as $item)
        <a
            href="{{ $item->url }}"
            @if ($item->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif
            class="group flex items-start gap-3 rounded-xl p-2.5 transition hover:bg-stone-50 dark:hover:bg-stone-800/80"
            role="menuitem"
            @if ($onSelect) @click="{{ $onSelect }}" @endif
        >
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 transition group-hover:scale-105 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/60">
                <x-dynamic-component :component="'heroicon-o-'.\App\Support\NavigationIcon::heroicon($item->icon)" class="h-4 w-4" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-xs font-bold text-stone-900 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400">{{ $item->label }}</span>
                @if ($item->description)
                    <span class="block text-[11px] text-stone-500 line-clamp-1 dark:text-stone-400">{{ $item->description }}</span>
                @endif
            </span>
        </a>
    @endforeach
</div>

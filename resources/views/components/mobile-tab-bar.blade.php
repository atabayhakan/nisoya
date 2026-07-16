@props(['navLinksMega', 'navLinksSingle'])

@php
    // Mesajlar sekmesindeki rozet: karşı taraftan gelen, henüz okunmamış
    // mesaj sayısı (bkz. App\Http\Controllers\MessageController@show — aynı
    // "sender != ben + read_at null" tanımı orada tek tek okundu işaretlenir).
    $unreadMessages = auth()->check()
        ? \App\Models\Message::query()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->whereHas('conversation', fn ($q) => $q->where('user_one_id', auth()->id())->orWhere('user_two_id', auth()->id()))
            ->count()
        : 0;
@endphp

{{-- Faz H3: native-app hissi veren sabit alt sekme çubuğu. Panel sayfaları
     dahil HER sayfada gösterilir (bağış FAB'ının aksine — bu sabit bir çubuk,
     kendi yerini ayırır/taşmaz, bkz. app.blade.php'deki body padding'i). --}}
<div x-data="{ sheetOpen: false }" @keydown.escape.window="sheetOpen = false">
    <nav
        class="fixed inset-x-0 bottom-0 z-30 flex items-stretch justify-around border-t border-stone-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden dark:border-stone-800 dark:bg-stone-900/95"
        aria-label="Alt gezinme"
    >
        <a href="{{ url('/') }}" class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium {{ request()->routeIs('home') ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400' }}">
            <x-heroicon-o-home class="h-5 w-5" />
            Ana Sayfa
        </a>

        <button
            type="button"
            @click="sheetOpen = true"
            class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium text-stone-500 dark:text-stone-400"
            :aria-expanded="sheetOpen ? 'true' : 'false'"
            aria-haspopup="dialog"
        >
            <x-heroicon-o-squares-2x2 class="h-5 w-5" />
            Keşfet
        </button>

        <a href="{{ url('/panel/ilan/yeni') }}" class="flex flex-1 flex-col items-center justify-center gap-0.5 py-1 text-[11px] font-medium text-stone-500 dark:text-stone-400">
            <span class="-mt-5 grid h-11 w-11 place-items-center rounded-full bg-emerald-600 text-white shadow-lg ring-4 ring-white dark:bg-emerald-500 dark:ring-stone-900">
                <x-heroicon-o-plus class="h-6 w-6" />
            </span>
            İlan Ver
        </a>

        <a href="{{ route('panel.messages.index') }}" class="relative flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium {{ request()->routeIs('panel.messages.*') ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400' }}">
            <span class="relative">
                <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                @if ($unreadMessages)
                    <span class="absolute -right-1.5 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                @endif
            </span>
            Mesajlar
        </a>

        <a href="{{ route('dashboard') }}" class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium {{ request()->routeIs('dashboard') ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400' }}">
            <x-heroicon-o-user-circle class="h-5 w-5" />
            Panelim
        </a>
    </nav>

    {{-- "Keşfet" alt sayfası: masaüstü mega menüsüyle aynı group_key verisi
         (bkz. x-nav-link-cards) + tekil linkler (Harita, Nasıl Çalışır?). --}}
    <template x-teleport="body">
        <div
            x-show="sheetOpen"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 bg-stone-900/60 md:hidden"
            @click.self="sheetOpen = false"
            role="dialog"
            aria-modal="true"
            aria-label="Keşfet"
            x-cloak
        >
            <div
                x-show="sheetOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="fixed inset-x-0 bottom-0 max-h-[80vh] overflow-y-auto rounded-t-3xl bg-white p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] dark:bg-stone-900"
            >
                <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-stone-200 dark:bg-stone-700"></div>
                <h2 class="mb-3 text-base font-bold text-stone-900 dark:text-stone-50">Keşfet</h2>

                <x-nav-link-cards :items="$navLinksMega" grid-class="" on-select="sheetOpen = false" />

                @if ($navLinksSingle->isNotEmpty())
                    <div class="mt-3 border-t border-stone-100 pt-3 dark:border-stone-800">
                        @foreach ($navLinksSingle as $link)
                            <a
                                href="{{ $link->url }}"
                                @if ($link->opens_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                @click="sheetOpen = false"
                                class="block rounded-xl px-3 py-2.5 text-sm font-medium text-stone-600 hover:bg-stone-50 dark:text-stone-300 dark:hover:bg-stone-800"
                            >{{ $link->label }}</a>
                        @endforeach
                    </div>
                @endif

                {{-- PWA yükleme ipucu (Faz M1.4). Android'de native yükleme
                     istemi (beforeinstallprompt), iOS'ta "Ana Ekrana Ekle"
                     talimatı. Yüklüyse veya 30 gün içinde kapatıldıysa
                     görünmez — bkz. resources/js/app.js → Alpine.store('pwa'). --}}
                <template x-if="$store.pwa.visible">
                    <div x-data="{ iosHelp: false }" class="mt-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3 dark:border-emerald-900 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-white dark:bg-emerald-500">
                                <x-heroicon-o-device-phone-mobile class="h-5 w-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-stone-800 dark:text-stone-100">Nisoya'yı uygulama olarak yükle</div>
                                <div class="text-xs text-stone-500 dark:text-stone-400">Ana ekranından tek dokunuşla aç</div>
                            </div>
                            <button
                                type="button"
                                @click="$store.pwa.installEvent ? $store.pwa.install() : iosHelp = !iosHelp"
                                class="shrink-0 rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                            >Yükle</button>
                            <button type="button" @click="$store.pwa.dismiss()" class="shrink-0 p-1 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200" aria-label="Kapat">
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                            </button>
                        </div>
                        <div x-show="iosHelp" x-transition.opacity.duration.150ms class="mt-2 text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                            Safari'de alttaki <strong>Paylaş</strong> düğmesine (kare + ok) dokun,
                            sonra <strong>"Ana Ekrana Ekle"</strong>yi seç.
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

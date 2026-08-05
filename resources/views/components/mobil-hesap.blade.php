{{-- MOBİLDE HESAP GÖRÜNÜRLÜĞÜ (2026-08-05).

     Sahibin bildirdiği eksik: "üye olarak giriş yaptığımda giriş yaptığıma
     dair hiçbir şey görünmüyor." Doğruydu ve iki yönlüydü:

       · Giriş yapmışken avatar/ad/çıkış — hepsi `hidden md:*` idi. Telefonda
         oturumun açık olduğunu gösteren TEK işaret alt çubuktaki "Panelim"
         yazısıydı; çıkış ise yalnız "Keşfet" alt sayfasının dibinde vardı.
       · Misafirken "Giriş" ve "Kayıt" da `hidden md:inline-block` idi —
         yani telefonda GİRİŞ YAPMA yolu da başlıkta yoktu.

     Alt çubuktaki "Panelim" bunun yerini tutmuyor: orası bir sayfa
     bağlantısı, kimlik göstergesi değil. "Hangi hesapla girdim?" ve
     "nasıl çıkarım?" sorularının cevabı sağ üstte aranır.

     Yalnız mobil: md:'den itibaren başlıkta zaten avatar + ad + Panelim +
     Çıkış duruyor. --}}
@auth
    <div x-data="{ acik: false }" @keydown.escape.window="acik = false" class="md:hidden">
        <button
            type="button"
            @click="acik = true"
            class="flex items-center rounded-full ring-2 ring-transparent transition hover:ring-emerald-200 dark:hover:ring-emerald-800"
            :aria-expanded="acik ? 'true' : 'false'"
            aria-haspopup="dialog"
            aria-label="Hesabım"
        >
            <x-avatar :user="auth()->user()" size="h-8 w-8" text="text-xs" />
        </button>

        <template x-teleport="body">
            <div
                x-show="acik"
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-50 bg-stone-900/60 md:hidden"
                @click.self="acik = false"
                role="dialog"
                aria-modal="true"
                aria-label="Hesabım"
                x-cloak
            >
                <div
                    x-show="acik"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-y-full"
                    x-transition:enter-end="translate-y-0"
                    class="fixed inset-x-0 bottom-0 rounded-t-3xl bg-white p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] dark:bg-stone-900"
                >
                    <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-stone-200 dark:bg-stone-700"></div>

                    {{-- Kimlik satırı: "hangi hesapla girdim?" sorusunun cevabı. --}}
                    <div class="flex items-center gap-3 border-b border-stone-100 pb-4 dark:border-stone-800">
                        <x-avatar :user="auth()->user()" size="h-12 w-12" text="text-base" />
                        <div class="min-w-0">
                            <div class="truncate text-base font-bold text-stone-900 dark:text-stone-50">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-stone-500 dark:text-stone-400">{{ auth()->user()->email }}</div>
                        </div>
                    </div>

                    <div class="py-2">
                        <a href="{{ route('dashboard') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                            <x-heroicon-o-squares-2x2 class="h-5 w-5 text-stone-400" /> Panelim
                        </a>
                        <a href="{{ route('panel.favorites.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                            <x-heroicon-o-heart class="h-5 w-5 text-stone-400" /> Favorilerim
                        </a>
                        <a href="{{ route('panel.profile.edit') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                            <x-heroicon-o-user-circle class="h-5 w-5 text-stone-400" /> Profil ayarları
                        </a>
                        {{-- Karanlık mod başlıktan buraya taşındı: bir kez
                             ayarlanan tercih, her ekranda yer kaplayan bir
                             düğmeyi hak etmiyor — o yer hesap göstergesine
                             gitti. --}}
                        @unless (\App\Support\Tema::koyuKilit())
                            <button type="button" onclick="window.toggleTheme && window.toggleTheme()" class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                                <x-heroicon-o-moon class="h-5 w-5 text-stone-400 dark:hidden" />
                                <x-heroicon-o-sun class="hidden h-5 w-5 text-stone-400 dark:inline" />
                                Karanlık / aydınlık
                            </button>
                        @endunless
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="border-t border-stone-100 pt-2 dark:border-stone-800">
                        @csrf
                        <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="h-5 w-5" /> Çıkış yap
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
@else
    {{-- Misafir: telefonda giriş yapmanın başlıktaki tek yolu. --}}
    <a href="{{ route('login') }}" class="rounded-full bg-emerald-700 px-3.5 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-800 md:hidden dark:bg-emerald-500 dark:text-stone-900">
        Giriş
    </a>
@endauth

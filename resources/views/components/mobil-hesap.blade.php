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
    <div x-data="altSayfa" @keydown.escape.window="kapat()" class="md:hidden">
        <button
            type="button"
            @click="ac()"
            class="flex h-9 w-9 items-center justify-center rounded-full ring-2 ring-transparent transition hover:ring-emerald-200 dark:hover:ring-emerald-800"
            :aria-expanded="acik ? 'true' : 'false'"
            aria-haspopup="dialog"
            aria-label="Hesabım"
        >
            {{-- ORTAM SİNYALİ: okunmamış bildirim varsa avatarda kırmızı nokta.
                 Masaüstünde zil + sayı rozeti her sayfada duruyordu; mobilde
                 hiçbir işaret YOKTU — kullanıcı Panelim'e girmeden okunmamış
                 bildirimi olduğunu bilemiyordu. Sayı yerine nokta: 8x8'lik bir
                 avatarın yanında rakam okunmuyor, "bir şey var" yeter.

                 h-9 w-9 (36px): başlıktaki arama/ülke/acil/üye-ol düğmeleriyle
                 AYNI yükseklik — dördü farklı boyda olduğu için sıra "birbirinden
                 bağımsız" görünüyordu (ölçüldü: 28-38px arası saçılma). --}}
            <span class="relative">
                <x-avatar :user="auth()->user()" size="h-9 w-9" text="text-xs" />
                @if ($okunmamisBildirim = auth()->user()->okunmamisBildirimSayisi())
                    <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-stone-900" aria-hidden="true"></span>
                @endif
            </span>
        </button>

        <template x-teleport="body">
            <div
                x-show="acik"
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-50 bg-stone-900/60 md:hidden"
                @click.self="kapat()"
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
                    <div class="mx-auto mb-2 h-1.5 w-12 rounded-full bg-stone-200 dark:bg-stone-700"></div>
                    <div class="mb-2 flex justify-end">
                        <button type="button" @click="kapat()" class="-mr-1 grid h-11 w-11 place-items-center rounded-full text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800" aria-label="Kapat">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

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
                            <x-heroicon-o-squares-2x2 class="h-5 w-5 text-stone-600" /> Panelim
                        </a>
                        {{-- Bildirimler mobilde HİÇBİR YERDE yoktu: ne alt
                             sekme çubuğunda ne burada. Tek yol /panel'e gidip
                             "Bölümler" ızgarasında kartı bulmaktı. --}}
                        <a href="{{ route('panel.notifications.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                            <x-heroicon-o-bell class="h-5 w-5 text-stone-600" />
                            <span class="flex-1">Bildirimler</span>
                            @if ($okunmamisBildirim ?? auth()->user()->okunmamisBildirimSayisi())
                                <span class="grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1.5 text-2xs font-bold text-white">{{ auth()->user()->okunmamisBildirimSayisi() > 9 ? '9+' : auth()->user()->okunmamisBildirimSayisi() }}</span>
                            @endif
                        </a>
                        <a href="{{ route('panel.favorites.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                            <x-heroicon-o-heart class="h-5 w-5 text-stone-600" /> Favorilerim
                        </a>
                        <a href="{{ route('panel.profile.edit') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                            <x-heroicon-o-user-circle class="h-5 w-5 text-stone-600" /> Profil ayarları
                        </a>
                        {{-- Karanlık mod başlıktan buraya taşındı: bir kez
                             ayarlanan tercih, her ekranda yer kaplayan bir
                             düğmeyi hak etmiyor — o yer hesap göstergesine
                             gitti. --}}
                        @unless (\App\Support\Tema::koyuKilit())
                            <button type="button" onclick="window.toggleTheme && window.toggleTheme()" class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:text-stone-200 dark:hover:bg-stone-800">
                                <x-heroicon-o-moon class="h-5 w-5 text-stone-600 dark:hidden" />
                                <x-heroicon-o-sun class="hidden h-5 w-5 text-stone-600 dark:inline" />
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
    {{-- MİSAFİR: telefonda hem giriş hem KAYIT yolu.

         "Kayıt" eskiden yalnız `hidden md:inline-block` ile masaüstündeydi;
         telefonda üye olmanın başlıkta hiçbir yolu yoktu. Ziyaretçi ya
         footer'a kadar kaydırmak ya da "Giriş"e basıp oradaki bağlantıyı
         bulmak zorundaydı. Nisoya'nın kitlesi ağırlıkla telefonda olduğu için
         bu, doğrudan üye kaybı demekti.

         SIRA BİLİNÇLİ: yeni gelen için birincil eylem ÜYE OLMAK, o yüzden
         dolu düğme "Üye ol". Giriş, dönen kullanıcı için sade metin —
         390px'lik başlıkta iki dolu düğme yan yana sığmıyor (ölçüldü). --}}
    {{-- YELPAZE MENÜ — tek düğme, açılınca üç eylem.

         NEDEN: başlıkta yer yoktu. Bir önceki turda "Acil" etiketini geri
         getirince yer daraldı ve "Giriş" metnini <400px'te GİZLEMEK zorunda
         kaldım — yani bir eylemi tamamen feda ettim. Yelpaze o takası geri
         alıyor: başlıkta tek düğme kadar yer kaplıyor ama içinde üç eylem
         taşıyor, üstelik en dar telefonda bile.

         ÜÇÜNCÜ EYLEM BİLİNÇLİ: "İlan Ver". Nisoya'nın darboğazı talep değil
         ARZ; ilan verme yolunu başlıkta görünür kılmak, üye olma yolunu
         görünür kılmak kadar önemli. Misafir bastığında kayda yönlenir,
         kayıttan sonra ilan formuna döner.

         ERİŞİLEBİLİRLİK: gerçek <button> + aria-expanded/aria-controls,
         Escape ile kapanır, dışarı tıklayınca kapanır, açılınca ilk öğeye
         odak gider. Yelpaze hissi sıralı gecikmelerle veriliyor; hareketi
         azaltılmış cihazlarda gecikmeler devre dışı (prefers-reduced-motion
         bu depoda ayrı bir tur konusuydu, aynı sözleşmeye uyuyoruz). --}}
    <div
        x-data="{
            acik: false,
            ac() { this.acik = true; this.$nextTick(() => this.$refs.ilk?.focus()); },
            kapat(odakla = false) { this.acik = false; if (odakla) this.$refs.tetik?.focus(); },
        }"
        @keydown.escape.window="acik && kapat(true)"
        @click.outside="kapat()"
        class="relative shrink-0 md:hidden"
    >
        <button
            type="button"
            x-ref="tetik"
            @click="acik ? kapat() : ac()"
            :aria-expanded="acik ? 'true' : 'false'"
            aria-controls="misafir-yelpaze"
            class="inline-flex h-9 shrink-0 items-center gap-1 whitespace-nowrap rounded-full bg-emerald-700 px-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400"
        >
            Üye ol
            {{-- Döndürme sarmalayıcı span'de: Alpine'in `::class` kısayolunu
                 Blade bileşenine geçirmek öznitelik birleştirmesine bağlı ve
                 sessizce çalışmayabilir. --}}
            <span class="transition-transform duration-200 motion-reduce:transition-none" :class="acik && 'rotate-180'">
                <x-heroicon-o-chevron-down class="h-3.5 w-3.5 shrink-0" />
            </span>
        </button>

        {{-- `x-show` YALNIZ SARMALAYICIDA.

             İlk yazışta her bağlantının da kendi `x-show`'u ve gecikmesi
             vardı. Sonuç ölçüldü: panel açılırken ilk bağlantı odak anında
             HENÜZ GÖRÜNÜR DEĞİLDİ (`offsetParent === null`) ve `focus()`
             sessizce hiçbir şey yapmıyordu — klavye kullanıcısı menüyü açıp
             içine giremiyordu.

             Yelpaze hissi artık CSS animasyonuyla veriliyor: öğeler
             görünürlüğü sarmalayıcıdan alır (odak hemen çalışır), sıralı
             gecikme yalnız GÖRSEL. Hareket azaltılmışsa animasyon kapanır. --}}
        <div
            id="misafir-yelpaze"
            x-show="acik"
            x-cloak
            x-transition:enter="transition ease-out duration-150 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute right-0 top-full z-40 mt-2 w-44 origin-top-right"
        >
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white p-1.5 shadow-xl dark:border-stone-700 dark:bg-stone-900">
                @foreach ([
                    ['route' => 'register', 'etiket' => 'Üye ol', 'ikon' => 'user-plus', 'birincil' => true],
                    ['route' => 'login', 'etiket' => 'Giriş yap', 'ikon' => 'arrow-right-on-rectangle', 'birincil' => false],
                    ['route' => 'panel.listings.create', 'etiket' => 'İlan Ver', 'ikon' => 'plus-circle', 'birincil' => false],
                ] as $i => $eylem)
                    <a
                        href="{{ route($eylem['route']) }}"
                        @if ($i === 0) x-ref="ilk" @endif
                        style="animation-delay: {{ $i * 45 }}ms"
                        class="yelpaze-oge flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition
                               {{ $eylem['birincil']
                                    ? 'text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/30'
                                    : 'text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800' }}"
                    >
                        <x-dynamic-component :component="'heroicon-o-'.$eylem['ikon']" class="h-4 w-4 shrink-0" />
                        {{ $eylem['etiket'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @once
        <style>
            /* Yelpaze: öğeler sırayla belirir. Görünürlüğü DEĞİŞTİRMEZ —
               yalnız opaklık/kayma canlandırır, böylece odak ilk karede
               çalışır. */
            @keyframes yelpazeAc {
                from { opacity: 0; transform: translateY(-4px) scale(.97); }
                to   { opacity: 1; transform: none; }
            }
            #misafir-yelpaze .yelpaze-oge {
                animation: yelpazeAc .22s ease-out both;
            }
            @media (prefers-reduced-motion: reduce) {
                #misafir-yelpaze .yelpaze-oge { animation: none; }
            }
        </style>
    @endonce
@endauth

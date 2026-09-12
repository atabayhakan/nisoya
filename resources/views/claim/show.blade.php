<x-layouts.app :title="'İşletmenizi Sahiplenin — ' . $listing->title">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
        {{-- Üst Başlık & Rozet --}}
        <div class="mb-8 text-center sm:mb-12">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                <x-heroicon-s-sparkles class="h-4 w-4" />
                Önceden Hazırlanmış İşletme Vitrini
            </span>
            <h1 class="mt-3 text-2xl font-black tracking-tight text-stone-900 sm:text-4xl dark:text-stone-50">
                "{{ $listing->title }}" Vitrininizi Sahiplenin
            </h1>
            <p class="mx-auto mt-2 max-w-2xl text-sm text-stone-600 sm:text-base dark:text-stone-300">
                Nisoya'da işletmeniz için hazır bir profil oluşturduk. Bilgilerinizi kontrol edip tek tıkla sahiplenin, bulunduğunuz şehirdeki binlerce Türkçe konuşan yeni müşteriye ulaşın.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12">
            {{-- Sol Kolon: İşletme Kartı Önizlemesi --}}
            <div class="lg:col-span-6 space-y-6">
                <div class="rounded-3xl border border-stone-200/80 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="inline-block rounded-lg bg-stone-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                {{ $listing->category?->name ?? 'Hizmet' }}
                            </span>
                            <h2 class="mt-2 text-xl font-extrabold text-stone-900 dark:text-stone-100 sm:text-2xl">
                                {{ $listing->title }}
                            </h2>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                            {{ $listing->city }}, {{ $listing->country_code }}
                        </span>
                    </div>

                    <div class="mt-6 rounded-2xl bg-stone-50 p-4 text-xs leading-relaxed text-stone-600 sm:text-sm dark:bg-stone-950/40 dark:text-stone-300">
                        {!! nl2br(e($listing->description)) !!}
                    </div>

                    {{-- İletişim Detayları --}}
                    <div class="mt-6 space-y-2.5 border-t border-stone-100 pt-6 text-xs sm:text-sm dark:border-stone-800">
                        @if ($listing->claim_phone)
                            <div class="flex items-center gap-2 text-stone-700 dark:text-stone-300">
                                <x-heroicon-o-phone class="h-4 w-4 text-emerald-700 dark:text-emerald-400" />
                                <span>Telefon: <strong class="font-medium text-stone-900 dark:text-stone-100">{{ $listing->claim_phone }}</strong></span>
                            </div>
                        @endif

                        @if ($listing->claim_email)
                            <div class="flex items-center gap-2 text-stone-700 dark:text-stone-300">
                                <x-heroicon-o-envelope class="h-4 w-4 text-emerald-700 dark:text-emerald-400" />
                                <span>E-posta: <strong class="font-medium text-stone-900 dark:text-stone-100">{{ $listing->claim_email }}</strong></span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Neden Nisoya? Avantajlar --}}
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-2xl border border-stone-200/60 bg-white p-3 dark:border-stone-800 dark:bg-stone-900/60">
                        <div class="text-base font-black text-emerald-700 dark:text-emerald-400">0 €</div>
                        <div class="text-[11px] font-medium text-stone-600 dark:text-stone-400">Tamamen Ücretsiz</div>
                    </div>
                    <div class="rounded-2xl border border-stone-200/60 bg-white p-3 dark:border-stone-800 dark:bg-stone-900/60">
                        <div class="text-base font-black text-emerald-700 dark:text-emerald-400">%0</div>
                        <div class="text-[11px] font-medium text-stone-600 dark:text-stone-400">Sıfır Komisyon</div>
                    </div>
                    <div class="rounded-2xl border border-stone-200/60 bg-white p-3 dark:border-stone-800 dark:bg-stone-900/60">
                        <div class="text-base font-black text-emerald-700 dark:text-emerald-400">15 sn</div>
                        <div class="text-[11px] font-medium text-stone-600 dark:text-stone-400">Hızlı Kurulum</div>
                    </div>
                </div>

                {{-- Dükkan Vitrini QR Kodu & Tanıtım Kiti --}}
                <div class="rounded-3xl border border-stone-200/80 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-qr-code class="h-5 w-5 text-emerald-700 dark:text-emerald-400" />
                        <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100">
                            Dükkanınızın Dijital QR Kodu ve Tanıtım Kiti
                        </h3>
                    </div>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                        Bu QR kodu telefonunuzla tarayarak vitrininizi anında görebilir, dükkan camınıza veya masalarınıza basabilirsiniz.
                    </p>

                    <div class="mt-4 flex flex-col sm:flex-row items-center gap-4">
                        <div class="flex h-36 w-36 shrink-0 items-center justify-center rounded-2xl border border-stone-200 bg-white p-2 shadow-inner dark:border-stone-700 dark:bg-white [&>svg]:h-full [&>svg]:w-full">
                            {!! $qrSvg !!}
                        </div>
                        <div class="flex-1 space-y-2 w-full text-center sm:text-left">
                            <div class="text-xs font-semibold text-stone-700 dark:text-stone-300">
                                Vitrin Bağlantınız:
                            </div>
                            <div class="truncate rounded-xl bg-stone-100 px-3 py-1.5 font-mono text-[11px] text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                {{ $listingUrl }}
                            </div>
                            <div class="flex flex-wrap gap-2 pt-1">
                                <a href="{{ route('listings.card', $listing) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200/80 bg-stone-50 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:bg-stone-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">
                                    <x-heroicon-o-arrow-down-tray class="h-3.5 w-3.5 text-stone-500 dark:text-stone-400" />
                                    <span>WhatsApp Durum Kartı (1080x1920)</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sağ Kolon: Sahiplenme Formu --}}
            <div class="lg:col-span-6">
                <div class="rounded-3xl border border-emerald-500/20 bg-white p-6 shadow-xl shadow-emerald-500/5 sm:p-8 dark:border-emerald-500/30 dark:bg-stone-900">
                    <div class="mb-6">
                        <h3 class="text-xl font-black text-stone-900 dark:text-stone-100">
                            İşletmeyi Sahiplenin
                        </h3>
                        <p class="mt-1 text-xs text-stone-600 dark:text-stone-300">
                            Sahiplendikten sonra fotoğraflar ekleyebilir, açıklamayı düzenleyebilir veya yeni hizmetler listeleyebilirsiniz.
                        </p>
                    </div>

                    @if (auth()->check())
                        {{-- Zaten giriş yapmış kullanıcı --}}
                        <div class="space-y-6">
                            <div class="rounded-2xl border border-stone-200/80 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-800/50">
                                <div class="text-xs text-stone-600 dark:text-stone-400">Şu anki hesabınız:</div>
                                <div class="mt-1 font-bold text-stone-900 dark:text-stone-100">{{ auth()->user()->name }} ({{ auth()->user()->email }})</div>
                                <div class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">Bu vitrin doğrudan bu hesabınıza bağlanacaktır.</div>
                            </div>

                            <form method="POST" action="{{ route('claim.process', $token) }}">
                                @csrf
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                    <x-heroicon-s-check-badge class="h-5 w-5" />
                                    Hesabıma Bağla ve Yayına Al
                                </button>
                            </form>
                        </div>
                    @else
                        {{-- Ziyaretçi / Yeni kullanıcı formu --}}
                        <form method="POST" action="{{ route('claim.process', $token) }}" class="space-y-4">
                            @csrf

                            <div class="space-y-1.5">
                                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    Yetkili Ad Soyad
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                                        <x-heroicon-o-user class="h-5 w-5" />
                                    </span>
                                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                                           placeholder="Adınız Soyadınız"
                                           class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
                                </div>
                                @error('name') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    E-posta Adresi
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                                        <x-heroicon-o-envelope class="h-5 w-5" />
                                    </span>
                                    <input id="email" name="email" type="email" value="{{ old('email', $listing->claim_email) }}" required
                                           placeholder="isletme@ornek.com"
                                           class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
                                </div>
                                @error('email') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    Şifre Belirleyin
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                                        <x-heroicon-o-lock-closed class="h-5 w-5" />
                                    </span>
                                    <x-password-input id="password" name="password" required autocomplete="new-password"
                                           placeholder="En az 8 karakter"
                                           class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-10 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900" />
                                </div>
                                @error('password') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    Şifre (Tekrar)
                                </label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                                        <x-heroicon-o-lock-closed class="h-5 w-5" />
                                    </span>
                                    <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                                           placeholder="Şifrenizi tekrar girin"
                                           class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-10 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900" />
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                    <x-heroicon-s-check-badge class="h-5 w-5" />
                                    İşletmeyi Sahiplen ve Yayına Al
                                </button>
                            </div>

                            <div class="text-center pt-2 text-xs text-stone-600 dark:text-stone-400">
                                Zaten bir hesabınız var mı? <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Giriş Yapın</a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

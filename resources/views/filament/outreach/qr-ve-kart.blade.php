<div class="space-y-4" x-data="{ kopyalandi: false, linkKopyalandi: false }">
    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h4 class="font-bold text-gray-900 dark:text-white">{{ $aday->name }}</h4>
                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $aday->city }} · {{ $aday->country }} · {{ $aday->sector ?? $aday->category }}
                </div>
            </div>
            @if ($listing)
                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    {{ $listing->isClaimable() ? 'Sahiplenme Bekliyor' : 'Sahiplenildi' }}
                </span>
            @endif
        </div>
    </div>

    @if ($listing)
        <div class="flex flex-col sm:flex-row items-center gap-6 rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-white/10 dark:bg-stone-900">
            <div class="flex h-44 w-44 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-white p-2 shadow-inner dark:border-gray-700 dark:bg-white [&>svg]:h-full [&>svg]:w-full">
                {!! $qrSvg !!}
            </div>

            <div class="flex-1 space-y-3 text-center sm:text-left">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Vitrin Adresi</div>
                    <div class="mt-1 truncate rounded-lg bg-gray-100 px-3 py-1.5 font-mono text-xs text-gray-800 dark:bg-white/10 dark:text-gray-200">
                        {{ $listingUrl }}
                    </div>
                </div>

                @if ($claimUrl)
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">15 Saniyede Sahiplenme Bağlantısı</div>
                        <div class="mt-1 truncate rounded-lg bg-emerald-50 px-3 py-1.5 font-mono text-xs text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                            {{ $claimUrl }}
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                    @if ($claimUrl)
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $claimUrl }}').then(() => { linkKopyalandi = true; setTimeout(() => linkKopyalandi = false, 2500) })"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                            <span x-text="linkKopyalandi ? 'Kopyalandı ✓' : 'Sahiplenme Linkini Kopyala'"></span>
                        </button>
                    @endif

                    <a href="{{ route('listings.card', $listing) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 dark:bg-emerald-600 dark:hover:bg-emerald-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>WhatsApp Durum Kartı (1080x1920)</span>
                    </a>
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            QR kod doğrudan dükkanın vitrin sayfasına gider. Esnaf bu kodu dükkan camına, masalarına veya menülerine bastırabilir; telefon kamerasıyla anında açılır.
        </p>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-700/60 dark:bg-amber-950/30 dark:text-amber-300">
            Bu işletme için henüz bir vitrin oluşturulmamış. Lütfen önce tablodaki <strong>"Vitrin Hazırla"</strong> butonuna tıklayın.
        </div>
    @endif
</div>

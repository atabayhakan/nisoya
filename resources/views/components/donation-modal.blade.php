@php
    $paypal = setting('bagis.paypal_me') ?: config('services.donation.paypal_me');
    $iban = setting('bagis.iban') ?: config('services.donation.iban');
    $ibanOwner = setting('bagis.iban_sahibi') ?: config('services.donation.iban_owner');
    $title = setting('bagis.baslik') ?: 'Nisoya ücretsiz kalacak';
    $text = setting('bagis.metin') ?: 'Nisoya tamamen ücretsiz bir hizmettir. Bağışlarınız bize güç verir.';
    $paypalUrl = $paypal ? (str_starts_with($paypal, 'http') ? $paypal : 'https://'.$paypal) : null;
    $maliyetBaslik = setting('bagis.maliyet_baslik');
    $maliyetKalemleri = array_filter([setting('bagis.maliyet1'), setting('bagis.maliyet2'), setting('bagis.maliyet3')]);
@endphp

<div
    x-data="donationModal()"
    @keydown.escape.window="open = false"
    x-init="init()"
    x-cloak
>
    {{-- Teşekkür banner'ı: PayPal/IPC dönüşünde ?status=thanks gelirse göster --}}
    <div
        x-show="showThanks"
        x-transition.opacity
        class="fixed bottom-40 right-4 z-40 max-w-sm rounded-2xl border border-emerald-200 bg-white p-4 shadow-2xl md:bottom-24 dark:border-emerald-800 dark:bg-stone-900"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <span aria-hidden="true" class="text-3xl">💚</span>
            <div class="flex-1">
                <p class="font-bold text-stone-900 dark:text-stone-50">Teşekkürler!</p>
                <p class="mt-0.5 text-sm text-stone-600 dark:text-stone-400">
                    Bağışın Nisoya'nın ücretsiz kalmasına katkı sağladı.
                </p>
            </div>
            <button
                type="button"
                @click="showThanks = false"
                class="rounded-full p-1 text-stone-600 hover:bg-stone-100 hover:text-stone-700 dark:text-stone-400"
                aria-label="Kapat"
            >×</button>
        </div>
    </div>

    {{-- Trigger: küçük, köşeye yakın; formların/butonların üzerine binmemesi için
         kapatılabilir (× kalıcı olarak gizler) ve panel (asıl site kullanımı)
         sayfalarında hiç render edilmez, bkz. layouts/app.blade.php --}}
    <div
        x-show="!dismissed"
        x-transition.opacity
        class="fixed bottom-20 right-4 z-40 flex items-center gap-1 md:bottom-4"
    >
        <button
            type="button"
            @click="open = true"
            class="group inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-3 py-2 text-xs font-semibold text-amber-950 shadow-lg ring-1 ring-amber-500/50 transition hover:scale-105 hover:bg-amber-300 sm:px-4 sm:py-2.5 sm:text-sm"
            aria-label="Bağış yap"
            title="Nisoya'ya bağış yap"
        >
            <span aria-hidden="true">💛</span>
            <span class="hidden sm:inline">Destek Ol</span>
        </button>
        <button
            type="button"
            @click="dismiss()"
            class="grid h-5 w-5 place-items-center rounded-full bg-stone-900/75 text-xs text-white shadow transition hover:bg-stone-900/70"
            aria-label="Bu butonu kalıcı olarak gizle"
            title="Kalıcı olarak gizle"
        >×</button>
    </div>

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/60 p-3 sm:items-center sm:p-6"
        @click.self="open = false"
        role="dialog"
        aria-modal="true"
        aria-labelledby="donation-title"
    >
        <div
            x-show="open"
            x-transition.duration.200ms
            class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800"
        >
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 bg-amber-50 px-5 py-4 dark:border-stone-800 dark:bg-amber-950/30">
                <div>
                    <h2 id="donation-title" class="text-lg font-bold text-amber-900 dark:text-amber-200">{{ $title }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-amber-800/80 dark:text-amber-200/90">{{ $text }}</p>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="rounded-full p-1.5 text-amber-700 transition hover:bg-amber-100 dark:text-amber-300"
                    aria-label="Kapat"
                >
                    <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 px-5 py-5">
                @if ($maliyetBaslik && count($maliyetKalemleri) > 0)
                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ $maliyetBaslik }}</p>
                        <ul class="mt-2 space-y-1.5 text-sm text-stone-700 dark:text-stone-300">
                            @foreach ($maliyetKalemleri as $kalem)
                                <li class="flex items-start gap-2">
                                    <x-heroicon-o-check-circle class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400" />
                                    <span>{{ $kalem }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($paypalUrl)
                    <a
                        href="{{ $paypalUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center justify-between gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 transition hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-950/30"
                    >
                        <div class="flex items-center gap-3">
                            <span aria-hidden="true" class="grid h-10 w-10 place-items-center rounded-full bg-[#003087] text-base font-bold text-white">P</span>
                            <div>
                                <p class="font-semibold text-stone-900 dark:text-stone-50">PayPal ile Bağış</p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ $paypal }}</p>
                            </div>
                        </div>
                        <span class="text-sky-600 dark:text-sky-400">→</span>
                    </a>
                @endif

                @if ($iban)
                    <div
                        x-data="{ copied: false }"
                        class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/30"
                    >
                        <div class="flex items-center gap-3">
                            <span aria-hidden="true" class="grid h-10 w-10 place-items-center rounded-full bg-emerald-600 text-base dark:bg-emerald-500">🏦</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-stone-900 dark:text-stone-50">Banka Havalesi (IBAN)</p>
                                @if ($ibanOwner)
                                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ $ibanOwner }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2 rounded-lg border border-emerald-300 bg-white p-2 dark:border-emerald-700 dark:bg-stone-900">
                            <code class="flex-1 truncate font-mono text-sm text-stone-800 dark:text-stone-100" id="donation-iban">{{ $iban }}</code>
                            <button
                                type="button"
                                @click="copyIban($refs.iban, $refs.iban, copied = true)"
                                x-ref="iban"
                                :data-iban="'{{ $iban }}'"
                                class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900"
                            >
                                <span x-show="!copied">Kopyala</span>
                                <span x-show="copied" x-transition>✓ Kopyalandı</span>
                            </button>
                        </div>
                    </div>
                @endif

                @if (! $paypalUrl && ! $iban)
                    <div class="rounded-xl border border-stone-200 bg-stone-50 p-4 text-center text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-800 dark:text-stone-400">
                        Bağış yöntemleri henüz eklenmedi.
                        Yönetici <code>/yonetim</code> panelinden
                        <strong>Site Yönetimi → İçerik (Metinler) → Bağış</strong>
                        bölümünü doldurabilir.
                    </div>
                @endif
            </div>

            <div class="border-t border-stone-100 bg-stone-50 px-5 py-3 text-center text-xs text-stone-500 dark:border-stone-800 dark:bg-stone-800 dark:text-stone-400">
                Bağışlar gönüllüdür. Hizmet her zaman ücretsiz kalacaktır. 💚
            </div>
        </div>
    </div>
</div>

<script>
    function donationModal() {
        return {
            open: false,
            showThanks: false,
            dismissed: false,
            KEY: 'nisoya_donation_dismissed',
            init() {
                try {
                    this.dismissed = localStorage.getItem(this.KEY) === '1';
                } catch (e) {}
                this.checkThanks();
            },
            dismiss() {
                this.dismissed = true;
                try { localStorage.setItem(this.KEY, '1'); } catch (e) {}
            },
            checkThanks() {
                try {
                    const url = new URL(window.location.href);
                    if (url.searchParams.get('status') === 'thanks' || url.searchParams.get('bagis') === 'tesekkurler') {
                        this.showThanks = true;
                        // URL'i temizle (geri tuşu ile aynı sayfaya dönersin)
                        url.searchParams.delete('status');
                        url.searchParams.delete('bagis');
                        window.history.replaceState({}, '', url.toString());
                        setTimeout(() => { this.showThanks = false; }, 8000);
                    }
                } catch (e) {}
            },
            copyIban(button, _el, flag) {
                const iban = button.dataset.iban || document.getElementById('donation-iban').textContent.trim();
                const fallback = () => {
                    const ta = document.createElement('textarea');
                    ta.value = iban;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(ta);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(iban).catch(fallback);
                } else {
                    fallback();
                }

                setTimeout(() => { flag = false; }, 1800);
            },
        };
    }
</script>
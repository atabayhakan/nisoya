{{-- Erişim postasından gelen "listeden çık" sayfası.

     Bu sayfayı gören kişi ÜYE DEĞİL — sitemize hiç girmemiş, bizden bir posta
     almış ve istememiş biri. O yüzden burada tek bir iş var ve tek bir düğme:
     kayıt teklifi, tanıtım, "gitme" pazarlığı yok. Çıkmak isteyene direnmek,
     spam şikâyetini davet etmenin en kısa yolu. --}}
<x-layouts.guest title="Listeden çık — Nisoya">
    <div class="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
        @if ($cikildi)
            <div class="flex items-start gap-3">
                <x-heroicon-o-check-circle class="mt-0.5 h-6 w-6 shrink-0 text-emerald-700 dark:text-emerald-400" />
                <div>
                    <h1 class="text-lg font-bold text-stone-900 dark:text-stone-50">Çıkışın alındı</h1>
                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                        <span class="font-medium text-stone-800 dark:text-stone-200">{{ $eposta }}</span>
                        adresine bir daha yazmayacağız. Bu kalıcıdır; yeniden eklemek için bir yol yok.
                    </p>
                </div>
            </div>
        @else
            <h1 class="text-lg font-bold text-stone-900 dark:text-stone-50">Listeden çıkmak üzeresin</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                Onaylarsan <span class="font-medium text-stone-800 dark:text-stone-200">{{ $eposta }}</span>
                adresine bir daha yazmayız.
            </p>

            {{-- Değiştiren eylem POST'ta: posta tarayıcıları ve önizleme botları
                 bağlantıları kendiliğinden AÇAR, ve GET çıkarsaydı alıcı postayı
                 okumadan listeden düşerdi. --}}
            <form method="POST" action="{{ route('kahya.cikis.uygula', $jeton) }}" class="mt-5">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-stone-800 px-4 py-2.5 font-semibold text-white transition hover:bg-stone-900 dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white">
                    Evet, listeden çıkar
                </button>
            </form>
        @endif

        <p class="mt-5 border-t border-stone-200 pt-4 text-xs text-stone-500 dark:border-stone-800 dark:text-stone-400">
            Nisoya, yurtdışındaki Türkler için ücretsiz bir pazaryeri.
            <a href="{{ route('home') }}" class="underline hover:text-stone-700 dark:hover:text-stone-200">nisoya.com</a>
        </p>
    </div>
</x-layouts.guest>

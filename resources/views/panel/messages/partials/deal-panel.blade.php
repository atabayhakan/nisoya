{{-- Anlaşma paneli (K-C). Sohbet içinden anlaşma başlatma/kabul/tamamlama/
     sorun bildirme. Nisoya para akışını görmez — bu bir niyet defteridir.
     Değişkenler: $conversation, $deal (nullable), $other, $currencies. --}}
@php
    $me = auth()->id();
    $status = $deal?->status?->value;
    $iProposed = $deal && $deal->proposed_by === $me;
    $canStartNew = $deal === null || in_array($status, ['iptal', 'sorunlu', 'tamamlandi'], true);
    $defaultCurrency = $conversation->listing?->currency ?? auth()->user()->preferred_currency;
    $btn = 'rounded-lg px-3 py-1.5 text-xs font-semibold transition';
    $btnPrimary = $btn.' bg-emerald-700 text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900';
    $btnGhost = $btn.' border border-stone-300 text-stone-600 hover:bg-stone-50 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-800';
    $btnDanger = $btn.' text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30';
@endphp

<div class="mt-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
    <div class="flex items-center gap-2 text-sm font-semibold text-stone-700 dark:text-stone-200">
        <span aria-hidden="true">🤝</span> Anlaşma
        @if ($deal)
            <span @class([
                'ml-auto rounded-full px-2 py-0.5 text-xs font-medium',
                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => $status === 'teklif',
                'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' => $status === 'kabul',
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => $status === 'tamamlandi',
                'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300' => $status === 'iptal',
                'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' => $status === 'sorunlu',
            ])>{{ $deal->status->getLabel() }}</span>
        @endif
    </div>

    @if ($deal && $deal->amount !== null)
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Tutar: <span class="font-medium text-stone-700 dark:text-stone-200">{{ $deal->amount }} {{ $deal->currency }}</span></p>
    @endif

    {{-- Duruma göre aksiyonlar --}}
    @if ($deal && $status === 'teklif')
        @if ($iProposed)
            <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">Teklifin gönderildi, karşı tarafın onayı bekleniyor.</p>
            <form method="POST" action="{{ route('panel.deals.cancel', $deal) }}" class="mt-2">
                @csrf
                <button type="submit" class="{{ $btnGhost }}">Teklifi geri çek</button>
            </form>
        @else
            <p class="mt-2 text-xs text-stone-600 dark:text-stone-300"><span class="font-medium">{{ $other?->name }}</span> bir anlaşma teklif etti.</p>
            <div class="mt-2 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('panel.deals.accept', $deal) }}">@csrf<button type="submit" class="{{ $btnPrimary }}">Kabul et</button></form>
                <form method="POST" action="{{ route('panel.deals.cancel', $deal) }}">@csrf<button type="submit" class="{{ $btnGhost }}">Reddet</button></form>
            </div>
        @endif
    @elseif ($deal && $status === 'kabul')
        <p class="mt-2 text-xs text-stone-600 dark:text-stone-300">Anlaşma kabul edildi. İş bitince tamamlandı olarak işaretleyin.</p>
        <div class="mt-2 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('panel.deals.complete', $deal) }}">@csrf<button type="submit" class="{{ $btnPrimary }}">Tamamlandı işaretle</button></form>
            <form method="POST" action="{{ route('panel.deals.dispute', $deal) }}">@csrf<button type="submit" class="{{ $btnDanger }}">🚩 Sorun bildir</button></form>
        </div>
    @elseif ($deal && $status === 'tamamlandi')
        <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-400">✓ Anlaşma tamamlandı. Artık birbirinizi değerlendirebilirsiniz.</p>
        <div class="mt-2 flex flex-wrap gap-2">
            @if ($other)
                <a href="{{ route('profiles.show', $other->username) }}#degerlendir" class="{{ $btnPrimary }}">{{ $other->name }} kullanıcısını değerlendir</a>
            @endif
            <form method="POST" action="{{ route('panel.deals.dispute', $deal) }}">@csrf<button type="submit" class="{{ $btnDanger }}">🚩 Sorun bildir</button></form>
        </div>
    @elseif ($deal && $status === 'sorunlu')
        <p class="mt-2 text-xs text-red-600 dark:text-red-400">⚠️ Bu anlaşmada sorun bildirildi; ekibimiz inceliyor.</p>
    @elseif ($deal && $status === 'iptal')
        <p class="mt-2 text-xs text-stone-600 dark:text-stone-400">Önceki anlaşma iptal edildi.</p>
    @endif

    {{-- Yeni anlaşma başlat --}}
    @if ($canStartNew)
        <details class="mt-3 border-t border-stone-100 pt-3 dark:border-stone-800" @if ($errors->hasAny(['amount', 'currency', 'deal'])) open @endif>
            <summary class="cursor-pointer list-none text-xs font-medium text-emerald-700 hover:underline dark:text-emerald-400">＋ Anlaşma başlat</summary>
            <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">Tutar bilgilendirme amaçlıdır; ödeme yine taraflar arasındadır.</p>
            <form method="POST" action="{{ route('panel.deals.propose', $conversation) }}" class="mt-2 flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label for="deal-amount" class="block text-xs text-stone-500 dark:text-stone-400">Tutar (ops.)</label>
                    <input id="deal-amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount') }}" placeholder="0"
                           class="mt-1 w-28 rounded-lg border-stone-300 px-2 py-1.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <div>
                    <label for="deal-currency" class="block text-xs text-stone-500 dark:text-stone-400">Para birimi</label>
                    <select id="deal-currency" name="currency" class="mt-1 rounded-lg border-stone-300 px-2 py-1.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @foreach ($currencies as $c)
                            <option value="{{ $c->code }}" @selected(old('currency', $defaultCurrency) === $c->code)>{{ $c->code }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="{{ $btnPrimary }}">Teklif gönder</button>
            </form>
            @error('deal')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            @error('amount')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            @error('currency')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </details>
    @endif
</div>

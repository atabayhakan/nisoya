{{--
    Tek aday kartı. Hem masaüstü panosu hem mobil tek-sütun modu bunu kullanır.

    Kartın içindeki <select> + "Uygula" ERİŞİLEBİLİR ANA YOLDUR: klavye, ekran
    okuyucu, dokunmatik ve JS'siz tarayıcı buradan ilerler. Sürükleme yalnızca
    fare kullanıcısı için bir hızlandırıcıdır (bkz. app.js kanbanPano).

    Not: eski ekranda select `onchange="this.form.submit()"` ile kendiliğinden
    gönderiyordu ve formda submit butonu YOKTU — yani JS'siz hiçbir yol yoktu ve
    klavyeyle seçenekler arasında gezerken her adımda form gönderiliyordu. Açık
    "Uygula" butonu ikisini birden düzeltir.
--}}
@php
    $aday = $basvuru->applicant;
@endphp

<article
    data-basvuru="{{ $basvuru->id }}"
    data-url="{{ route('panel.applications.status', $basvuru) }}"
    @pointerdown="tut($event, '{{ $basvuru->status->value }}')"
    class="rounded-xl border border-stone-200 bg-white p-3 shadow-sm dark:border-stone-800 dark:bg-stone-900"
>
    <div class="flex items-start gap-2">
        @if ($aday)
            <x-avatar :user="$aday" size="h-9 w-9" text="text-sm" />
        @else
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">?</div>
        @endif
        <div class="min-w-0">
            @if ($aday)
                <a href="{{ route('profiles.show', $aday->username) }}" target="_blank"
                   class="block truncate text-sm font-semibold text-stone-800 hover:text-emerald-700 dark:text-stone-100 dark:hover:text-emerald-400">{{ $aday->name }}</a>
            @else
                <span class="block truncate text-sm font-semibold text-stone-800 dark:text-stone-100">Silinmiş aday</span>
            @endif
            <div class="text-xs text-stone-600 dark:text-stone-400">{{ $basvuru->created_at->diffForHumans() }}</div>
        </div>
    </div>

    @if ($basvuru->cover_letter)
        <p class="mt-2 line-clamp-3 whitespace-pre-line text-xs text-stone-600 dark:text-stone-300">{{ $basvuru->cover_letter }}</p>
    @endif

    @if ($basvuru->cv_path)
        <a href="{{ route('panel.applications.cv', $basvuru) }}"
           class="mt-2 inline-block rounded-lg bg-stone-100 px-2 py-1 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">📎 CV indir</a>
    @endif

    <form method="POST" action="{{ route('panel.applications.status', $basvuru) }}" class="mt-3 flex items-center gap-1.5">
        @csrf @method('PATCH')
        <label for="durum-{{ $basvuru->id }}" class="sr-only">{{ $aday?->name ?? 'Aday' }} için başvuru durumu</label>
        {{-- min-h-11 (44px) yalnız mobilde: dokunmatikte sürükleme KAPALI
             olduğu için bu ikili tek etkileşim yoludur ve Apple'ın 44px
             dokunma hedefi minimumunu karşılamalı. Masaüstünde sürükleme asıl
             yol olduğundan kompakt kalır. --}}
        <select id="durum-{{ $basvuru->id }}" name="status"
                class="min-h-11 min-w-0 flex-1 rounded-lg border-stone-300 py-1.5 text-xs focus:border-emerald-500 focus:ring-emerald-500 md:min-h-0 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            @foreach (\App\Enums\ApplicationStatus::cases() as $secenek)
                <option value="{{ $secenek->value }}" @selected($basvuru->status === $secenek)>{{ $secenek->getLabel() }}</option>
            @endforeach
        </select>
        <button type="submit"
                class="min-h-11 shrink-0 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 md:min-h-0 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">Uygula</button>
    </form>
</article>

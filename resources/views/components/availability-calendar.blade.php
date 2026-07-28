@props([
    'ranges',           // ListingUnavailableRange koleksiyonu (starts_on/ends_on)
    'months' => 2,      // bugünden itibaren gösterilecek ay sayısı
])

@php
    use Illuminate\Support\Carbon;

    // Dolu günleri hızlı sorgu için 'Y-m-d' => true haritasına aç
    $busy = [];
    foreach ($ranges as $range) {
        $cursor = $range->starts_on->copy();
        while ($cursor->lte($range->ends_on)) {
            $busy[$cursor->toDateString()] = true;
            $cursor->addDay();
        }
    }

    $today = Carbon::today();
    $dayNames = ['Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct', 'Pz'];
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    @for ($m = 0; $m < $months; $m++)
        @php
            $monthStart = $today->copy()->startOfMonth()->addMonths($m);
            $daysInMonth = $monthStart->daysInMonth;
            $leadingBlanks = $monthStart->dayOfWeekIso - 1; // Pazartesi=1
        @endphp
        <div>
            <div class="text-center text-sm font-semibold text-stone-700 dark:text-stone-300">
                {{ $monthStart->translatedFormat('F Y') }}
            </div>
            <div class="mt-2 grid grid-cols-7 gap-y-1 text-center text-2xs text-stone-600 dark:text-stone-400">
                @foreach ($dayNames as $dayName)
                    <span>{{ $dayName }}</span>
                @endforeach
            </div>
            <div class="mt-1 grid grid-cols-7 gap-y-1 text-center text-xs">
                @for ($blank = 0; $blank < $leadingBlanks; $blank++)
                    <span></span>
                @endfor
                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = $monthStart->copy()->day($day);
                        $key = $date->toDateString();
                        $isPast = $date->lt($today);
                        $isBusy = isset($busy[$key]);
                    @endphp
                    @if ($isPast)
                        <span class="py-1 text-stone-300 dark:text-stone-700">{{ $day }}</span>
                    @elseif ($isBusy)
                        <span class="rounded bg-stone-200 py-1 text-stone-600 line-through dark:bg-stone-800 dark:text-stone-400" title="Dolu">{{ $day }}</span>
                    @else
                        <span class="py-1 font-medium text-emerald-700 dark:text-emerald-400">{{ $day }}</span>
                    @endif
                @endfor
            </div>
        </div>
    @endfor
</div>

<div class="mt-3 flex items-center gap-4 text-2xs text-stone-600 dark:text-stone-400">
    <span class="inline-flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded bg-emerald-200 dark:bg-emerald-900"></span> Müsait</span>
    <span class="inline-flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded bg-stone-200 dark:bg-stone-800"></span> Dolu</span>
</div>

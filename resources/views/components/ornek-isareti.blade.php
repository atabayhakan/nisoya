@props(['listing' => null, 'bicim' => 'rozet'])

{{--
    ÖRNEK (demo) içerik işareti.

    Görünür kipe alınmış demo ilanlar sitede gerçek ilanlarla yan yana çıkar.
    O anda tek dürüst davranış, onların örnek olduğunu SÖYLEMEKTİR — sahibin
    "işaretli olsun" şartı da tam olarak budur.

    Rozetin kendisi tek başına yeterli sayılmıyor: `MessageController::start`
    demo ilana mesaj göndermeyi ayrıca engelliyor. Rozeti okumayan biri sahte
    bir satıcıya yazıp cevapsız kalmasın diye.

    Tek bileşen, tek metin: aynı uyarı dört ayrı yerde ayrı ayrı yazılsaydı
    biri değişip diğerleri unutulurdu.
--}}

@if ($listing?->is_demo)
    @if ($bicim === 'bant')
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-950/50">
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Bu bir ÖRNEK ilandır</p>
            <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                Nisoya demo verisidir; gerçek bir satıcıya ait değildir ve mesaj gönderilemez.
            </p>
        </div>
    @else
        <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-amber-900 dark:bg-amber-950 dark:text-amber-100']) }}>
            Örnek
        </span>
    @endif
@endif

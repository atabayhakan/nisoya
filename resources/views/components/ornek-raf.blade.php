@props(['listings' => null])

{{--
    ÖRNEK İLAN RAFI — gerçek sonuç boşken gösterilen, açıkça etiketli blok.

    Neden ayrı bir raf: örnek ilanları gerçeklerin ARASINA koymak, kart
    rozetine rağmen sayacı ("N ilan bulundu") yalancı yapıyordu. Sayıyı
    dürüst tutmanın bedeli demo partisini tamamen görünmez kılmak olmasın
    diye demo buraya taşındı — sayılmıyor, ama kayboluyor da değil.

    Ana sayfadaki kuralın (HomeController) liste sayfasındaki karşılığı:
    demo gerçek arzın YERİNE geçmez, yokluğunda görünür.

    Tek bileşen, tek metin — `ornek-isareti` ile aynı gerekçe: aynı uyarı
    dört ayrı görünümde ayrı ayrı yazılsaydı biri değişip diğerleri
    unutulurdu. Kart seçimi tema ile değişir, metin değişmez.
--}}

@if ($listings && $listings->isNotEmpty())
    <section class="mt-10" aria-labelledby="ornek-raf-basligi">
        <div class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-950/50">
            <h2 id="ornek-raf-basligi" class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                Aşağıdakiler ÖRNEK ilanlardır
            </h2>
            <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                Nisoya demo verisidir; gerçek satıcılara ait değildir, sayılmaz ve mesaj gönderilemez.
                Sitenin nasıl göründüğünü anlatmak için duruyorlar.
            </p>
        </div>

        <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($listings as $ornek)
                @if (\App\Support\Tema::vitrinMi())
                    <x-vitrin.listing-card :listing="$ornek" />
                @else
                    @include('partials.listing-card', ['listing' => $ornek])
                @endif
            @endforeach
        </div>
    </section>
@endif

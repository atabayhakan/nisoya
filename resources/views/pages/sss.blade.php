<x-layouts.app title="Sıkça Sorulan Sorular — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-bold text-stone-900">Sıkça Sorulan Sorular</h1>

        @php
            $faqs = [
                ['Nisoya ücretli mi?', 'Hayır, kayıt olmak ve ilan vermek tamamen ücretsiz. İleride isteğe bağlı öne çıkarma seçenekleri eklenebilir.'],
                ['Ödeme Nisoya üzerinden mi yapılıyor?', 'Hayır. Nisoya bir ilan ve iletişim platformudur. Ödeme ve anlaşma doğrudan kullanıcılar arasında yapılır.'],
                ['Türkiye’den kullanabilir miyim?', 'Nisoya yurt dışında yaşayan Türklere yöneliktir ve Türk Lirası kullanmaz. Fiyatlar bulunduğun ülkenin para biriminde gösterilir.'],
                ['Bir ilana nasıl güvenirim?', 'Satıcının profilini, değerlendirmelerini ve puanını incele. Şüpheli durumda "şikayet et" özelliğini kullan.'],
                ['İlanım neden görünmüyor?', 'İlanlar genelde anında yayınlanır. Kurallara aykırı bulunan ilanlar yöneticiler tarafından pasifleştirilebilir.'],
            ];
        @endphp

        <div class="mt-8 space-y-3">
            @foreach ($faqs as $faq)
                <details class="group rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                    <summary class="cursor-pointer list-none font-semibold text-stone-800 marker:content-none">
                        <span class="flex items-center justify-between">
                            {{ $faq[0] }}
                            <span class="text-stone-400 transition group-open:rotate-45">+</span>
                        </span>
                    </summary>
                    <p class="mt-3 text-sm text-stone-600">{{ $faq[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</x-layouts.app>

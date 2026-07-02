@props([
    'slot' => null,
    'format' => 'auto',     // auto, rectangle, vertical, horizontal
    'responsive' => true,
    'minHeight' => 90,      // CLS'yi önlemek için placeholder yüksekliği
])

@php
    $enabled = config('services.adsense.enabled') && config('services.adsense.publisher_id');
    $publisher = config('services.adsense.publisher_id');

    // Format için önerilen yükseklikler (CLS'yi en aza indirir)
    $heightByFormat = [
        'horizontal' => 90,
        'rectangle' => 250,
        'vertical' => 600,
        'auto' => 90,
    ];
    $placeholderHeight = $heightByFormat[$format] ?? $minHeight;
@endphp

@if ($enabled && $slot)
    <div
        class="adsense-slot my-4 overflow-hidden rounded-xl border border-stone-200 bg-stone-50"
        style="min-height: {{ $placeholderHeight }}px;"
        data-ad-slot="{{ $slot }}"
        data-ad-format="{{ $format }}"
        data-ads-loaded="false"
    >
        <ins
            class="adsbyblock block"
            style="display:block; min-height: {{ $placeholderHeight }}px;"
            data-ad-client="{{ $publisher }}"
            data-ad-slot="{{ $slot }}"
            data-ad-format="{{ $format }}"
            data-full-width-responsive="{{ $responsive ? 'true' : 'false' }}"
        ></ins>
        <div class="border-t border-stone-200 bg-white px-3 py-1 text-center text-[10px] uppercase tracking-wider text-stone-400">
            Reklam
        </div>
    </div>
    <script>
        (function () {
            if (document.documentElement.dataset.consentAds !== 'granted') {
                return;
            }

            // IntersectionObserver ile ekrana gelince yükle (performans + CLS)
            const slot = document.querySelector('[data-ad-slot="{{ $slot }}"]');
            if (!slot) return;

            if (slot.dataset.adsLoaded === 'true') return;

            const loadAd = () => {
                if (slot.dataset.adsLoaded === 'true') return;
                slot.dataset.adsLoaded = 'true';
                try {
                    (window.adsbygoogle = window.adsbygoogle || []).push({});
                } catch (e) {}
            };

            // Tarayıcı destekliyorsa viewport'a girince tetikle
            if ('IntersectionObserver' in window) {
                const obs = new IntersectionObserver((entries, observer) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            loadAd();
                            observer.unobserve(entry.target);
                        }
                    });
                }, { rootMargin: '200px' });
                obs.observe(slot);
            } else {
                // Eski tarayıcı fallback: doğrudan yükle
                loadAd();
            }
        })();
    </script>
@else
    {{-- AdSense devre dışı: küçük bir "destek ol" çağrısı göster (sabit yükseklik CLS yok) --}}
    <div
        class="my-4 rounded-xl border border-dashed border-emerald-200 bg-emerald-50/40 px-4 py-3 text-center text-sm text-emerald-800"
        style="min-height: {{ $placeholderHeight }}px; display: flex; align-items: center; justify-content: center; flex-direction: column;"
    >
        <span class="text-base">💚 Nisoya ücretsiz kalır.</span>
        <a href="#bagis" class="mt-1 font-semibold underline-offset-2 hover:underline">Bağış yaparak destek ol</a>
    </div>
@endif
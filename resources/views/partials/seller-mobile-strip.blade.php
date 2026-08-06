{{-- MOBİLDE SATICI ŞERİDİ (lg:hidden) — 2026-08-06.

     -----------------------------------------------------------------------
     NEDEN VAR

     İlan detayı masaüstünde iki kolon; mobilde sağ kolon en ALTA düşüyor.
     Yani telefonda ilanı açan biri açıklamayı okuyup bitirdiğinde SATICININ
     KİM OLDUĞUNU HÂLÂ BİLMİYOR — kimlik, puan ve iletişim çok aşağıda
     kalıyordu. Bir pazaryerinde "kimden alıyorum" sorusu üründen sonra gelen
     ikinci soru değil, onunla aynı anda sorulan sorudur.

     -----------------------------------------------------------------------
     NEDEN PARTIAL (öğrenilmiş ders)

     İlk yazışta bu blok DOĞRUDAN vitrin/listings/show.blade.php içine
     yazılmıştı. Canlıda ilan detayı KLASİK şablonu basıyor — yani şerit
     hiçbir zaman görünmedi. Düğmeler görünüyordu çünkü onlar en baştan iki
     şablona da eklenmişti.

     Deponun kendi kuralı bunu zaten söylüyordu (bkz. vitrin/README.md):
     "iki temada da geçerli bir eksik" ise İKİ şablon birden düzenlenmeli ve
     test `temalar()` sağlayıcısıyla iki temada koşturulmalı. Düğmeler için
     yapılmıştı, şerit için yapılmamıştı — ve tam olarak test edilmeyen taraf
     kırıldı.

     -----------------------------------------------------------------------
     KENAR ÇUBUĞUNUN KOPYASI DEĞİL

     Orada olmayan bir işi yapar: mobilde erken kimlik + iletişime kısayol.
     Ortak olan tek parça (ilan düğmeleri) iki yerde de aynı partial'dan gelir.

     Beklenen değişkenler: $listing, $sellerRating, $sellerListingCounts,
     $isArchived, $isOwner.
--}}
<div class="rounded-2xl border border-stone-200 bg-white px-5 py-4 shadow-sm lg:hidden dark:border-stone-800 dark:bg-stone-900">
    <a href="{{ route('profiles.show', $listing->user->username) }}" class="flex items-center gap-3">
        <x-avatar :user="$listing->user" size="h-11 w-11" text="text-base" />
        <div class="min-w-0 flex-1">
            {{-- font-bold (700), font-extrabold (800) DEĞİL: 800 ağırlığı yalnız
                 Vitrin temasında yükleniyor. Bu partial İKİ temada da basıldığı
                 için 800 kullanmak klasik temada tarayıcıya sahte kalın
                 sentezletirdi (YaziTipiTest bunu yakalıyor). --}}
            <div class="flex flex-wrap items-center gap-1.5 text-sm font-bold leading-tight text-stone-800 dark:text-stone-100">
                {{ $listing->user->name }}
                <x-trust-badge :user="$listing->user" />
                @if ($listing->user->is_verified)<x-verified-badge />@endif
            </div>
            <div class="mt-0.5 text-xs font-semibold text-stone-500 dark:text-stone-400">
                @if ($sellerRating['count'] > 0)
                    ★ {{ $sellerRating['avg'] }} · {{ $sellerRating['count'] }} değerlendirme ·
                @endif
                {{ $listing->user->created_at->year }}'ten beri üye
            </div>
        </div>
        {{-- text-stone-400 DEĞİL: MetinKontrastTest onu 2.59:1 ile yakalıyor. --}}
        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-600 dark:text-stone-400" />
    </a>

    @include('partials.seller-listing-links', ['seller' => $listing->user, 'counts' => $sellerListingCounts])

    {{-- İletişim formu mobilde bu şeridin ALTINDA kalıyor; bağlantı oraya
         götürür. Formu burada TEKRARLAMAK iki ayrı form, iki ayrı old()
         durumu ve iki bakım noktası demekti. --}}
    @if (! $isArchived && ! $isOwner)
        <a href="#satici-iletisim" class="mt-2 flex h-11 w-full items-center justify-center gap-2 rounded-[13px] bg-emerald-700 text-sm font-bold text-white transition hover:brightness-95 dark:bg-emerald-500 dark:text-stone-900">
            <x-heroicon-o-chat-bubble-left class="h-4 w-4" /> Satıcıya mesaj yaz
        </a>
    @endif
</div>

{{-- Tanışma postası taslağı — SALT OKUNUR + KOPYALA.

     Gönderme düğmesi bilerek YOK. Kâhya taslağı yazar, gönderme kararı ve
     eylemi sahibindir. Bu, AWS üretim erişiminin reddedilmesinden sonra
     alınan kararın devamı: soğuk e-posta otomasyonu bırakıldı, elle erişim
     tek kanal.

     Kişisel cümle üretilemediyse köşeli parantez olduğu gibi kalır ve
     kullanıcıya sebebi söylenir — uydurulmuş bir cümle, hiç cümle
     olmamasından kötüdür. --}}
<div class="space-y-4" x-data="{ kopyalandi: false }">
    <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm dark:bg-white/5">
        <span class="font-semibold text-gray-900 dark:text-white">{{ $aday->name }}</span>
        <span class="text-gray-600 dark:text-gray-400">
            @if ($aday->city){{ $aday->city }} · @endif{{ $aday->country }}
        </span>
        @if ($aday->contact_email)
            <div class="mt-1 select-all font-mono text-xs text-gray-700 dark:text-gray-300">{{ $aday->contact_email }}</div>
        @else
            <div class="mt-1 text-xs text-amber-700 dark:text-amber-400">Bu adayın e-posta adresi yok — önce adresi bulman gerekiyor.</div>
        @endif
    </div>

    @if ($taslak['kisisel_cumle'] === null)
        {{-- Sebebi söylemek şart: sessizce boş parantez bırakmak "AI bozuk"
             hissi verir. Oysa çoğu zaman bilerek boş bırakılıyor. --}}
        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-200">
            Kişisel cümle yazılamadı — elde bu işletmeye özel yeterli bilgi yok.
            Köşeli parantezi kendin doldur; uydurulmuş bir cümle, hiç cümle olmamasından kötüdür.
        </div>
    @endif

    <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Konu</div>
        <div class="mt-1 select-all rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-900 dark:border-white/10 dark:text-white">{{ $taslak['konu'] }}</div>
    </div>

    <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mesaj</div>
        <textarea x-ref="metin" readonly rows="16"
                  class="mt-1 w-full rounded-lg border-gray-200 bg-white font-mono text-xs leading-relaxed text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-white">{{ $taslak['mesaj'] }}</textarea>
    </div>

    <button type="button"
            @click="navigator.clipboard.writeText($refs.metin.value).then(() => { kopyalandi = true; setTimeout(() => kopyalandi = false, 2500) })"
            class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700">
        <span x-text="kopyalandi ? 'Kopyalandı ✓' : 'Mesajı kopyala'"></span>
    </button>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Nisoya bu mesajı göndermez. Kopyalayıp kendi posta programından gönder.
    </p>
</div>

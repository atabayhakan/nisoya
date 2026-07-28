<x-layouts.app title="Arkadaşını davet et — Nisoya">
    @php
        $shareText = 'Nisoya\'da yurt dışındaki Türklerle yeteneğini paraya dönüştür, kendi insanından hizmet al. Sen de katıl:';
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link />

        <div class="mt-4 rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-700 px-6 py-10 text-center text-white sm:px-12">
            <div class="text-4xl">🎁</div>
            <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Arkadaşlarını Nisoya'ya davet et</h1>
            <p class="mx-auto mt-2 max-w-lg text-emerald-50">
                Bulunduğun ülkedeki tanıdıklarını çağır. Topluluk büyüdükçe herkes daha kolay hizmet bulur, daha çok iş yapar.
            </p>
        </div>

        {{-- Davet bağlantısı --}}
        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <label class="block text-sm font-semibold text-stone-700 dark:text-stone-300">Sana özel davet bağlantın</label>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                <input id="referral-url" type="text" readonly value="{{ $user->referralUrl() }}"
                       class="flex-1 rounded-lg border-stone-300 bg-stone-50 px-3 py-2.5 text-sm text-stone-700 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200"
                       onclick="this.select()">
                <button type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('referral-url').value).then(() => { const s = this.querySelector('span'); const o = s.textContent; s.textContent = '✓ Kopyalandı'; setTimeout(() => s.textContent = o, 2000); })"
                        class="rounded-lg bg-stone-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-900 dark:bg-stone-700 dark:hover:bg-stone-600">
                    <span>Kopyala</span>
                </button>
            </div>

            <div class="mt-4">
                <p class="mb-2 text-xs font-medium text-stone-500 dark:text-stone-400">Hemen paylaş</p>
                @include('partials.share-buttons', ['shareUrl' => $user->referralUrl(), 'shareText' => $shareText])
            </div>

            <p class="mt-4 text-xs text-stone-600 dark:text-stone-400">
                Davet kodun: <span class="font-mono font-semibold text-stone-600 dark:text-stone-300">{{ $user->referral_code }}</span>
            </p>
        </div>

        {{-- ŞEHİR ELÇİLİĞİ BAĞI.
             Sitede iki ayrı tanınma yüzeyi vardı ve birbirinden habersizdi:
             burası davet sayısını gösteriyordu ama bu sayının bir şeye
             yaradığını söylemiyordu; /nabiz "şehrinin elçisi ol" diyordu ama
             buraya hiç bağlanmıyordu. Yani davet etmek için sebep, tam davet
             edilecek ekranda görünmüyordu. Yeni bir rozet eklemeden önce var
             olan katmanı görünür kılmak. --}}
        <a href="{{ route('nabiz') }}"
           class="mt-6 flex min-h-11 items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 transition hover:border-emerald-300 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:hover:border-emerald-700">
            <span class="relative flex h-2 w-2 shrink-0" aria-hidden="true">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            <span class="min-w-0 flex-1 text-sm text-stone-700 dark:text-stone-300">
                @if ($elcilik['sehir'] === null)
                    Profiline şehir eklersen <strong class="font-semibold">şehrinin elçisi</strong> olma yarışına girersin.
                @elseif ($elcilik['elciMiyim'])
                    Bu ay <strong class="font-semibold">{{ $elcilik['benimBuAy'] }} davetle {{ $elcilik['sehir'] }} elçisisin.</strong> Nabız sayfasında görün.
                @elseif ($elcilik['lider'] === 0)
                    Bu ay {{ $elcilik['sehir'] }}'da henüz kimse davet etmemiş — <strong class="font-semibold">tek davetle şehrinin ilk elçisi</strong> olabilirsin.
                @else
                    Bu ay {{ $elcilik['benimBuAy'] }} davetin var. <strong class="font-semibold">{{ $elcilik['fark'] }} davet daha</strong> ile {{ $elcilik['sehir'] }} elçisi olabilirsin.
                @endif
            </span>
            <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-600 dark:text-stone-400" />
        </a>

        {{-- Davet istatistiği --}}
        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-stone-900 dark:text-stone-50">Davet ettiklerin</h2>
                    <p class="text-sm text-stone-500 dark:text-stone-400">Bağlantınla kaydolan üyeler burada görünür.</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-emerald-700 dark:text-emerald-400">{{ $invitedCount }}</div>
                    <div class="text-xs text-stone-500 dark:text-stone-400">kişi</div>
                </div>
            </div>

            @if ($invited->isNotEmpty())
                <ul class="mt-4 divide-y divide-stone-100 dark:divide-stone-800">
                    @foreach ($invited as $member)
                        <li class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                </span>
                                <span class="text-sm font-medium text-stone-800 dark:text-stone-100">{{ $member->name }}</span>
                            </div>
                            <span class="text-xs text-stone-600 dark:text-stone-400">{{ $member->created_at->translatedFormat('j F Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-stone-300 bg-stone-50 px-4 py-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400">
                    Henüz kimseyi davet etmedin. Bağlantını paylaşarak başla!
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

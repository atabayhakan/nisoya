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
        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <label class="block text-sm font-semibold text-stone-700">Sana özel davet bağlantın</label>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                <input id="referral-url" type="text" readonly value="{{ $user->referralUrl() }}"
                       class="flex-1 rounded-lg border-stone-300 bg-stone-50 px-3 py-2.5 text-sm text-stone-700 focus:border-emerald-500 focus:ring-emerald-500"
                       onclick="this.select()">
                <button type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('referral-url').value).then(() => { const s = this.querySelector('span'); const o = s.textContent; s.textContent = '✓ Kopyalandı'; setTimeout(() => s.textContent = o, 2000); })"
                        class="rounded-lg bg-stone-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-900">
                    <span>Kopyala</span>
                </button>
            </div>

            <div class="mt-4">
                <p class="mb-2 text-xs font-medium text-stone-500">Hemen paylaş</p>
                @include('partials.share-buttons', ['shareUrl' => $user->referralUrl(), 'shareText' => $shareText])
            </div>

            <p class="mt-4 text-xs text-stone-400">
                Davet kodun: <span class="font-mono font-semibold text-stone-600">{{ $user->referral_code }}</span>
            </p>
        </div>

        {{-- Davet istatistiği --}}
        <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-stone-900">Davet ettiklerin</h2>
                    <p class="text-sm text-stone-500">Bağlantınla kaydolan üyeler burada görünür.</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-emerald-600">{{ $invitedCount }}</div>
                    <div class="text-xs text-stone-500">kişi</div>
                </div>
            </div>

            @if ($invited->isNotEmpty())
                <ul class="mt-4 divide-y divide-stone-100">
                    @foreach ($invited as $member)
                        <li class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700">
                                    {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                </span>
                                <span class="text-sm font-medium text-stone-800">{{ $member->name }}</span>
                            </div>
                            <span class="text-xs text-stone-400">{{ $member->created_at->translatedFormat('D MMMM Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-stone-300 bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">
                    Henüz kimseyi davet etmedin. Bağlantını paylaşarak başla!
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

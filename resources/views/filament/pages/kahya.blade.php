@php
    $teshis = $this->getTeshis();
    $sonGun = $this->getSonGun();
    $sonYas = $this->getSonRaporYasi();
    $envanter = $teshis['envanter'];
    $medya = $teshis['bozuk']['medya'];
    $log = $teshis['bozuk']['log'];
    $eksik = $teshis['eksik'];
@endphp

<x-filament-panels::page>
    {{-- SESSİZ ÖLÜM BANDI — sayfanın en üstünde ve her zaman görünür.
         Rapor gelmemesi ile "her şey yolunda" ancak burada ayrışır. --}}
    @if ($sonYas === null)
        <div class="rounded-xl border border-danger-300 bg-danger-50 p-4 dark:border-danger-700 dark:bg-danger-950/40">
            <p class="text-sm font-semibold text-danger-700 dark:text-danger-300">Kâhya henüz hiç çalışmadı</p>
            <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">
                Günlük rapor her sabah 07:30'da otomatik çalışır. Hiç kayıt yoksa zamanlayıcı (cron) çalışmıyor olabilir.
                Aşağıdaki düğmeyle şimdi elle deneyebilirsin.
            </p>
        </div>
    @elseif ($sonYas > 36)
        <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 dark:border-warning-700 dark:bg-warning-950/40">
            <p class="text-sm font-semibold text-warning-700 dark:text-warning-300">
                Son rapor {{ $sonYas }} saat önce — beklenenden eski
            </p>
            <p class="mt-1 text-sm text-warning-600 dark:text-warning-400">
                Günlük olması gerekiyor. Zamanlayıcı ya da kuyruk durmuş olabilir.
            </p>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Son rapor {{ $sonYas === 0 ? 'az önce' : $sonYas.' saat önce' }} üretildi.
        </p>
    @endif

    {{-- GERÇEK ENVANTER — raporun da en üstünde duran satır.
         "12 ilan" iyi görünür; "12 ilan, 1 satıcı" gerçeği söyler. --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Pazaryeri</h3>
        <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-50">
            {{ $envanter['ilan'] }} <span class="text-base font-normal text-gray-500 dark:text-gray-400">aktif ilan</span>
            <span class="mx-2 text-gray-300 dark:text-gray-600">·</span>
            {{ $envanter['satici'] }} <span class="text-base font-normal text-gray-500 dark:text-gray-400">satıcı</span>
        </p>
        @if ($envanter['uyari'])
            <p class="mt-2 text-sm font-medium text-warning-700 dark:text-warning-400">⚠️ {{ $envanter['uyari'] }}</p>
        @endif
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            Son 24 saat: {{ $sonGun['yeni_uye'] }} yeni üye, {{ $sonGun['yeni_ilan'] }} yeni ilan.
        </p>
    </div>

    {{-- SENİ BEKLEYEN İŞLER --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Seni bekleyen işler</h3>

        @forelse ($teshis['bekleyen'] as $kuyruk)
            <div class="mt-3 flex items-start justify-between gap-4 border-t border-gray-100 pt-3 first:border-0 first:pt-0 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $kuyruk['etiket'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $kuyruk['aciklama'] }}</p>
                </div>
                <span @class([
                    'shrink-0 rounded-full px-2.5 py-0.5 text-sm font-bold',
                    'bg-danger-100 text-danger-700 dark:bg-danger-950 dark:text-danger-300' => $kuyruk['aciliyet'] === 'yuksek',
                    'bg-warning-100 text-warning-700 dark:bg-warning-950 dark:text-warning-300' => $kuyruk['aciliyet'] !== 'yuksek',
                ])>{{ $kuyruk['adet'] }}</span>
            </div>
        @empty
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Bekleyen iş yok.</p>
        @endforelse
    </div>

    {{-- BOZUK OLANLAR --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Bozuk olanlar</h3>

        @if ($medya['kayip'] > 0)
            <p class="mt-3 text-sm font-medium text-danger-700 dark:text-danger-400">
                {{ $medya['kayip'] }} medya dosyası diskte yok ({{ $medya['taranan'] }} kayıt tarandı)
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Kullanıcı bu içeriklerde kırık görsel görüyor.</p>
            <ul class="mt-2 space-y-1">
                @foreach (array_slice($medya['ornekler'], 0, 5) as $ornek)
                    <li class="text-xs text-gray-600 dark:text-gray-300">
                        {{ $ornek['tur'] }} #{{ $ornek['id'] }} — eksik: {{ implode(', ', $ornek['eksik']) }}
                        <span class="text-gray-400 dark:text-gray-500">({{ $ornek['sahip'] }})</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($log['toplam'] > 0)
            <p class="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                Son 24 saatte {{ $log['toplam'] }} hata kaydı
            </p>
            {{-- Yalnız İMZA gösterilir (sınıf + dosya:satır + sayı).
                 Log mesajının kendisi TAŞINMAZ — bkz. LogOzeti docblock'u:
                 QueryException mesajı bağlanmış değerleri, yani kullanıcı
                 verisini içerir. --}}
            <ul class="mt-2 space-y-1">
                @foreach ($log['imzalar'] as $imza)
                    <li class="flex items-baseline justify-between gap-3 text-xs">
                        <span class="min-w-0 truncate text-gray-600 dark:text-gray-300">
                            <span class="font-mono text-gray-400 dark:text-gray-500">{{ $imza['seviye'] }}</span>
                            {{ $imza['sinif'] }}
                            @if ($imza['yer'] !== '—')
                                <span class="text-gray-400 dark:text-gray-500">{{ $imza['yer'] }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 font-semibold text-gray-700 dark:text-gray-200">×{{ $imza['adet'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($log['uyari'])
            <p class="mt-3 text-sm text-warning-700 dark:text-warning-400">{{ $log['uyari'] }}</p>
        @endif

        @if ($medya['kayip'] === 0 && $log['toplam'] === 0 && ! $log['uyari'])
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Bozuk bir şey bulunamadı.</p>
        @endif
    </div>

    {{-- DOLDURULMAYI BEKLEYENLER --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Doldurulmayı bekleyenler</h3>

        @forelse ($eksik['kritik'] as $alan)
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-gray-800">{{ $alan['anahtar'] }}</code>
                — {{ $alan['neden'] }}
            </p>
        @empty
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Kritik alanların hepsi dolu.</p>
        @endforelse

        @if ($eksik['ilansiz_kategori']['sayi'] > 0)
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                {{ $eksik['ilansiz_kategori']['sayi'] }} kategoride hiç ilan yok
                <span class="text-gray-400 dark:text-gray-500">
                    (ör. {{ implode(', ', array_slice($eksik['ilansiz_kategori']['ornekler'], 0, 3)) }})
                </span>
            </p>
        @endif

        @if ($eksik['istege_bagli_sayi'] > 0)
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                Ayrıca {{ $eksik['istege_bagli_sayi'] }} isteğe bağlı alan boş — çoğunun boş olması normaldir, listelenmiyor.
            </p>
        @endif
    </div>

    {{-- ÇALIŞMA DEFTERİ --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Rapor geçmişi</h3>

            <x-filament::button type="button" color="gray" wire:click="ornekRaporGonder" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="ornekRaporGonder">Şimdi rapor gönder</span>
                <span wire:loading wire:target="ornekRaporGonder">Gönderiliyor…</span>
            </x-filament::button>
        </div>

        @forelse ($this->getGecmis() as $kayit)
            <div class="mt-3 flex items-center justify-between gap-4 border-t border-gray-100 pt-3 first:border-0 first:pt-0 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        {{ $kayit->created_at->translatedFormat('j F Y, H:i') }}
                    </p>
                    @if ($kayit->hata)
                        <p class="text-xs text-danger-600 dark:text-danger-400">{{ $kayit->hata }}</p>
                    @elseif ($kayit->alici)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $kayit->alici }}</p>
                    @endif
                </div>
                <span @class([
                    'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-success-100 text-success-700 dark:bg-success-950 dark:text-success-300' => $kayit->gonderildi,
                    'bg-warning-100 text-warning-700 dark:bg-warning-950 dark:text-warning-300' => ! $kayit->gonderildi,
                ])>{{ $kayit->gonderildi ? 'gönderildi' : 'gönderilmedi' }}</span>
            </div>
        @empty
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Henüz rapor üretilmedi.</p>
        @endforelse
    </div>
</x-filament-panels::page>

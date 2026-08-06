@php
    $kuyruklar = $this->getKuyruklar();
    $toplam = array_sum(array_column($kuyruklar, 'adet'));
    $kullanici = $this->getKullanici();
    $kimlik = $this->getKimlik();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                {{-- Avatar vendor bileşeniyle basılır: kaldırılan AccountWidget'ın
                     tek görsel katkısı buydu, aynı görünüm korunuyor. --}}
                @if ($kullanici)
                    <x-filament-panels::avatar.user size="lg" :user="$kullanici" loading="lazy" />
                @endif

                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ $this->getSelamlama() }}</h2>

                    {{-- HANGİ HESAP: iki yönetici olduğundan beri ad tek başına
                         ayırt edici değil, e-posta ayırt edicidir. --}}
                    @if ($kimlik)
                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                            <span class="font-mono text-gray-600 dark:text-gray-300">{{ $kimlik['eposta'] }}</span>

                            <span class="rounded bg-primary-50 px-1.5 py-0.5 font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-400">
                                {{ $kimlik['rol'] }}
                            </span>

                            {{-- 2FA kapalıysa bu bir EYLEM çağrısıdır, açıksa sessiz
                                 bir doğrulama. Yönetici için middleware zaten açık
                                 olmaya zorlar; moderatör için zorlamaz. --}}
                            @if ($kimlik['ikiFaktor'])
                                <span class="text-gray-500 dark:text-gray-400">2FA açık</span>
                            @else
                                <a href="{{ route('panel.profile.2fa') }}"
                                   class="rounded bg-warning-50 px-1.5 py-0.5 font-medium text-warning-700 hover:underline dark:bg-warning-500/10 dark:text-warning-400">
                                    2FA kapalı — aç
                                </a>
                            @endif
                        </div>

                        @if ($kimlik['yoneticiSayisi'])
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <a href="{{ $kimlik['yoneticilerUrl'] }}" class="hover:underline">
                                    {{ $kimlik['yoneticiSayisi'] }} yönetici tanımlı
                                </a>
                            </p>
                        @endif
                    @endif

                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        @if ($toplam === 0)
                            Bekleyen iş yok — her şey temiz. 👌
                        @else
                            <strong class="text-gray-950 dark:text-white">{{ $toplam }}</strong> iş seni bekliyor.
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-filament::button tag="a" href="{{ url('/') }}" target="_blank" color="gray" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                    Siteyi görüntüle
                </x-filament::button>
                <x-filament::button tag="a" href="{{ \App\Filament\Pages\HeroYoneticisi::getUrl() }}" size="sm" icon="heroicon-o-sparkles">
                    Hero Yöneticisi
                </x-filament::button>

                {{-- Çıkış sağ üstteki hesap menüsünde de var; buradaki düğme
                     AccountWidget kaldırıldığında kaybolmasın diye taşındı. --}}
                <form action="{{ filament()->getLogoutUrl() }}" method="post">
                    @csrf
                    <x-filament::button type="submit" color="gray" size="sm" icon="heroicon-o-arrow-left-end-on-rectangle">
                        Oturumu kapat
                    </x-filament::button>
                </form>
            </div>
        </div>

        @if ($kuyruklar)
            <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($kuyruklar as $kuyruk)
                    <a href="{{ $kuyruk['url'] }}"
                       class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5 transition hover:border-primary-400 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500 dark:hover:bg-white/10">
                        <span @class([
                            'grid h-9 w-9 shrink-0 place-items-center rounded-lg',
                            'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400' => $kuyruk['renk'] === 'danger',
                            'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400' => $kuyruk['renk'] === 'warning',
                        ])>
                            <x-filament::icon :icon="$kuyruk['ikon']" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">{{ $kuyruk['etiket'] }}</span>
                        </span>
                        <span class="shrink-0 text-lg font-bold text-gray-950 dark:text-white">{{ $kuyruk['adet'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

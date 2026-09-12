<x-filament-panels::page>
    @php
        $m = $this->metrikler;
        $ulkeler = $this->ulkeler;
        $sehirler = $this->sehirler;
        $meslekler = $this->meslekler;
    @endphp

    {{-- 1. Büyüme & Tersine Katılım Dönüşüm Hunisi (Funnel Telemetrisi) --}}
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Kart 1: Toplam Keşif Havuzu --}}
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        Keşif Havuzu
                    </span>
                    <div class="rounded-lg bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                        <x-filament::icon icon="heroicon-m-globe-alt" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ number_format($m['toplam_kesif']) }}
                    </span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">aday işletme</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    OpenStreetMap & Google Maps üzerinden taranan diaspora işletmeleri
                </p>
            </div>

            {{-- Kart 2: Doğrulanmış Türk Esnafı --}}
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        Doğrulanan Türk Esnafı
                    </span>
                    <div class="rounded-lg bg-emerald-50 p-2 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <x-filament::icon icon="heroicon-m-check-badge" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">
                        {{ number_format($m['turk_isletmeler']) }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                        %{{ $m['turk_orani'] }} İsabet
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Kültürel sözlük + LLM analiziyle Türk olduğu kesinleşen esnaf
                </p>
            </div>

            {{-- Kart 3: Hazırlanan Dijital Vitrinler (Tersine Katılım) --}}
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        Yayındaki Vitrinler
                    </span>
                    <div class="rounded-lg bg-amber-50 p-2 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-m-building-storefront" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                        {{ number_format($m['hazirlanan_vitrinler']) }}
                    </span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">sahiplenilmeyi bekliyor</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Sitede hazır yayında olan ve esnafa davet gönderilen taslak vitrinler
                </p>
            </div>

            {{-- Kart 4: Sahiplenilen Vitrin & Dönüşüm --}}
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        Sahiplenilen & Dönüşüm
                    </span>
                    <div class="rounded-lg bg-purple-50 p-2 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                        <x-filament::icon icon="heroicon-m-sparkles" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-bold tracking-tight text-purple-600 dark:text-purple-400">
                        {{ number_format($m['sahiplenilen_vitrinler']) }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-950/50 dark:text-purple-400">
                        %{{ $m['donusum_orani'] }} Dönüşüm
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    WhatsApp davetiyle tek tıkla dükkanının yönetimini devralan esnaf
                </p>
            </div>
        </div>

        {{-- Çifte Harita Motoru Sağlık & Probe Göstergesi --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                        </span>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            Harita & Dizin Ağları Canlı Sağlık Durumu
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Aktif Kaynak Modu: <strong class="text-primary-600 dark:text-primary-400 uppercase">{{ $m['source'] }}</strong> — Kesintisiz otonom keşif için her iki servis de tek tıkla denetlenebilir.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    {{-- OpenStreetMap Overpass Probe --}}
                    <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2 text-xs dark:border-gray-800 dark:bg-gray-800/50">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">OSM Overpass</span>
                            <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">ÜCRETSİZ</span>
                        </div>
                        <x-filament::button
                            type="button"
                            size="xs"
                            color="gray"
                            wire:click="testOverpass"
                            wire:loading.attr="disabled"
                            icon="heroicon-m-signal"
                        >
                            <span wire:loading.remove wire:target="testOverpass">Sağlığı Ölç</span>
                            <span wire:loading wire:target="testOverpass">Ölçülüyor…</span>
                        </x-filament::button>
                    </div>

                    {{-- Google Places API Probe --}}
                    <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2 text-xs dark:border-gray-800 dark:bg-gray-800/50">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full {{ $m['has_google_key'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">Google Places (New)</span>
                            @if($m['has_google_key'])
                                <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300">ANAHTAR HAZIR</span>
                            @else
                                <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800 dark:bg-rose-950 dark:text-rose-300">ANAHTAR YOK</span>
                            @endif
                        </div>
                        <x-filament::button
                            type="button"
                            size="xs"
                            color="gray"
                            wire:click="testPlaces"
                            wire:loading.attr="disabled"
                            icon="heroicon-m-key"
                        >
                            <span wire:loading.remove wire:target="testPlaces">API Test Et</span>
                            <span wire:loading wire:target="testPlaces">Test Ediliyor…</span>
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Hızlı Canlı Keşif Simülatörü & Tersine Katılım Üreticisi --}}
    <div class="rounded-xl border border-primary-200/80 bg-gradient-to-r from-primary-50/50 via-white to-sky-50/30 p-5 shadow-sm dark:border-primary-900/50 dark:from-primary-950/20 dark:via-gray-900 dark:to-sky-950/10 space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-primary-600 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase">
                        Canlı Simülatör
                    </span>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Otonom Keşif ve Tersine Katılım Testi
                    </h3>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Cron zamanlayıcıyı beklemeden doğrudan seçtiğiniz ülke, şehir ve meslek üzerinde 5 işletmelik anlık canlı tarama ve vitrin açma testi yapın.
                </p>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <span>Ölçülmüş Verim: <strong>Lokanta & Fırın</strong> (%85+ Türk isabeti)</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Hedef Ülke
                </label>
                <select
                    wire:model.live="kesif_ulke"
                    class="w-full rounded-lg border-gray-300 py-2 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    @foreach($ulkeler as $uKod => $uAd)
                        <option value="{{ $uKod }}">{{ $uAd }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Hedef Şehir
                </label>
                <select
                    wire:model="kesif_sehir"
                    class="w-full rounded-lg border-gray-300 py-2 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    @foreach($sehirler as $sehir)
                        <option value="{{ $sehir }}">{{ $sehir }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Meslek Preseti
                </label>
                <select
                    wire:model="kesif_meslek"
                    class="w-full rounded-lg border-gray-300 py-2 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    @foreach($meslekler as $mItem)
                        <option value="{{ $mItem['key'] }}">
                            {{ ucfirst($mItem['tr']) }} ({{ $mItem['en'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-filament::button
                    type="button"
                    color="primary"
                    class="w-full justify-center"
                    wire:click="hizliKesifBaslat"
                    wire:loading.attr="disabled"
                    icon="heroicon-m-magnifying-glass"
                >
                    <span wire:loading.remove wire:target="hizliKesifBaslat">Hızlı Keşif Başlat (5 Ad.)</span>
                    <span wire:loading wire:target="hizliKesifBaslat">Taranıyor & Doğrulanıyor…</span>
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- 3. Ana Yapılandırma Formu --}}
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        {{-- WhatsApp Önizleme Kartı & Yasal Güvenlik Bilgilendirmesi --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- WhatsApp Mesaj Önizleme Simülatörü --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="rounded-full bg-emerald-700 p-1 text-white">
                            <x-filament::icon icon="heroicon-m-chat-bubble-left-ellipsis" class="h-4 w-4" />
                        </div>
                        <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                            WhatsApp Davet & Tersine Katılım Mesajı Önizlemesi
                        </h4>
                    </div>
                    <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                        10x Dönüşüm Oranı
                    </span>
                </div>

                <div class="rounded-xl bg-[#0b141a] p-4 text-xs font-sans text-gray-200 shadow-inner">
                    <div class="mb-2 flex items-center justify-between border-b border-gray-800 pb-2 text-[11px] text-emerald-400">
                        <span class="font-medium">Nisoya B2B Ajanı ➔ İşletme Sahibi</span>
                        <span>Bugün 11:42</span>
                    </div>

                    <div class="space-y-2 rounded-lg bg-[#202c33] p-3 text-gray-100">
                        <p>
                            Merhaba Sayın <strong>Antepli Baklava & Kebap</strong> Yetkilisi,
                        </p>
                        <p class="text-gray-300">
                            Nisoya.com üzerinde Türk diasporasındaki müşterilerin sizi daha kolay bulması için işletmeniz adına özel bir vitrin sayfası hazırlandı:
                        </p>
                        <div class="rounded border border-[#00a884]/40 bg-[#111b21] p-2 font-mono text-[11px] text-[#00a884]">
                            🔗 https://nisoya.com/sahiplen/8f9a2b1c4e7d...
                        </div>
                        <p class="text-gray-300">
                            Yukarıdaki bağlantıya tıklayarak işletme bilgilerinizi <strong>30 saniyede ücretsiz sahiplenebilir</strong>, menü ve çalışma saatlerinizi güncelleyebilirsiniz.
                        </p>
                        <div class="border-t border-gray-700/60 pt-2 text-[10px] text-gray-400">
                            Saygılarımızla,<br>
                            <span class="font-semibold text-gray-300">{{ $this->data['whatsapp_signature'] ?? config('growth.whatsapp_signature', 'Hakan · nisoya.com') }}</span><br>
                            <span class="text-[9px] text-gray-500">Bu bildirim halka açık işletme dizin kaydınıza istinaden tek seferlik iletilmiştir. Mesaj almak istemiyorsanız "DURDUR" yazabilirsiniz.</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    💡 <strong>Neden WhatsApp?</strong> Yurt dışındaki Türk ustalar, berberler ve lokantacılar kurumsal e-postalarını nadiren okur; ancak WhatsApp mesajlarını %98 oranında 15 dakika içinde görüntüler.
                </p>
            </div>

            {{-- Yasal Uyumluluk & Anti-Spam Kılavuzu --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-3">
                <div class="flex items-center gap-2">
                    <div class="rounded-full bg-blue-500 p-1 text-white">
                        <x-filament::icon icon="heroicon-m-shield-check" class="h-4 w-4" />
                    </div>
                    <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                        Küresel Yasal Uyumluluk & İletişim Güvenliği
                    </h4>
                </div>

                <div class="space-y-2.5 text-xs text-gray-600 dark:text-gray-300">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <div class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Avrupa Birliği (GDPR / DSGVO)
                        </div>
                        <p class="mt-1 text-gray-500 dark:text-gray-400 text-[11px]">
                            Almanya ve AB ülkelerindeki esnafa gönderim yapılırken yalnızca kamuya açık ticari telefon numaraları kullanılır. GDPR Madde 6(1)(f) uyarınca ticari profil sahiplendirme B2B meşru menfaat (Legitimate Interest) kapsamındadır. Her mesajda açık opt-out ("DURDUR") hakkı tanınır.
                        </p>
                    </div>

                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <div class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Amerika Birleşik Devletleri (CAN-SPAM & TCPA)
                        </div>
                        <p class="mt-1 text-gray-500 dark:text-gray-400 text-[11px]">
                            ABD esnafına yapılan bildirimler B2B bilgilendirme mahiyetindedir. Mesajın gönderici kimliği (İmza), platformun fiziksel irtibatı ve kolay iptal olanağı şablona entegre edilmiştir.
                        </p>
                    </div>

                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                        <div class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Türkiye (KVKK & İYS)
                        </div>
                        <p class="mt-1 text-gray-500 dark:text-gray-400 text-[11px]">
                            Nisoya Büyüme Ajanı Türkiye sınırları içindeki yerli şahıslara toplu pazarlama yapmaz. Sistem münhasıran yurt dışındaki diaspora pazarlarına odaklanmıştır.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Profesyonel Yapışkan Aksiyon Çubuğu --}}
        <div class="sticky bottom-0 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
            <div class="flex items-center gap-2">
                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="testOverpass"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-globe-alt"
                >
                    <span wire:loading.remove wire:target="testOverpass">OSM Overpass Test</span>
                    <span wire:loading wire:target="testOverpass">OSM Test Ediliyor…</span>
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="testPlaces"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-map-pin"
                >
                    <span wire:loading.remove wire:target="testPlaces">Google Places Test</span>
                    <span wire:loading wire:target="testPlaces">Google Test Ediliyor…</span>
                </x-filament::button>
            </div>

            <div class="flex items-center gap-3">
                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="hizliKesifBaslat"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-play"
                >
                    <span wire:loading.remove wire:target="hizliKesifBaslat">Seçili Şehri Canlı Tara</span>
                    <span wire:loading wire:target="hizliKesifBaslat">Keşif İcra Ediliyor…</span>
                </x-filament::button>

                <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                    Değişiklikleri Kaydet
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>

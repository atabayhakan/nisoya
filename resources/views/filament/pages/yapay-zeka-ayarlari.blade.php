<x-filament-panels::page>
    @php
        $durum = $this->aktifDurum;
    @endphp

    {{-- Canlı Telemetri & Sistem Sağlığı Kartı --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        @if($durum['is_active'])
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                        @else
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-rose-500"></span>
                        @endif
                    </span>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ $durum['is_active'] ? 'Yapay Zekâ Motoru Aktif' : 'Yapay Zekâ Motoru Devre Dışı (Ana Şalter Kapalı)' }}
                    </h3>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Sitedeki tüm ilan zekâsı, arama yönlendirmesi ve Kâhya asistanı tek merkezden bu motor üzerinden beslenir.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Sağlayıcı Rozeti --}}
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-m-cpu-chip" class="h-3.5 w-3.5 text-primary-500" />
                    {{ strtoupper($durum['provider_name'] ?? $durum['provider']) }}
                </span>

                {{-- Model Rozeti --}}
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-m-sparkles" class="h-3.5 w-3.5 text-amber-500" />
                    {{ $durum['model'] }}
                </span>

                {{-- Vision Yetenek Rozeti --}}
                @if($durum['is_vision'])
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <x-filament::icon icon="heroicon-m-eye" class="h-3.5 w-3.5" />
                        Vision Destekli
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-m-document-text" class="h-3.5 w-3.5" />
                        Salt Metin (Görsel Yok)
                    </span>
                @endif

                {{-- API Anahtarı Rozeti --}}
                @if($durum['is_configured'])
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <x-filament::icon icon="heroicon-m-key" class="h-3.5 w-3.5" />
                        Anahtar Girili
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-400">
                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-3.5 w-3.5" />
                        Anahtar Eksik
                    </span>
                @endif
            </div>
        </div>

        {{-- Çoklu Sağlayıcı Durum Çubuğu (Bağımsız Anahtar Görünürlüğü) --}}
        <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Sağlayıcı Anahtar Durumları (Bağımsız Hafıza)
                </span>
                <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                    {{ $durum['configured_count'] ?? 0 }} / {{ $durum['total_providers'] ?? 8 }} Sağlayıcı Yapılandırıldı
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-8">
                @foreach(($durum['provider_statuses'] ?? []) as $pKey => $pInfo)
                    <div class="flex items-center justify-between rounded-lg border p-2 text-xs transition-colors {{ $pInfo['is_active'] ? 'border-primary-500 bg-primary-50/50 dark:border-primary-500 dark:bg-primary-950/30' : 'border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-900/50' }}">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $pInfo['has_key'] ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                            <span class="truncate font-medium {{ $pInfo['is_active'] ? 'text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $pInfo['name'] }}
                            </span>
                        </div>
                        @if($pInfo['is_active'])
                            <span class="shrink-0 rounded bg-primary-600 px-1 py-0.5 text-[9px] font-bold text-white uppercase">
                                Aktif
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-gray-100 pt-2 text-xs text-gray-400 dark:border-gray-800 dark:text-gray-500">
            <span>Zamanlanmış Denetim: <strong>Her gün 04:30</strong> (Aktif model yanıt süresi ve sağlık testi otomatik icra edilir)</span>
            <span class="hidden sm:inline">Nisoya AI Gateway v2.6</span>
        </div>
    </div>

    @php
        $mcp = $this->mcpBilgisi;
    @endphp

    {{-- Model Context Protocol (MCP) Bağlantı & Entegrasyon Merkezi --}}
    <div class="rounded-xl border border-indigo-200 bg-gradient-to-br from-white via-indigo-50/20 to-white p-5 shadow-sm dark:border-indigo-900/50 dark:bg-gray-900 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm">
                        <x-filament::icon icon="heroicon-m-bolt" class="h-4 w-4" />
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white flex items-center gap-2">
                            Nisoya MCP Sunucusu (Model Context Protocol)
                            @if($mcp['is_configured'])
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                                    BAĞLANTIYA HAZIR ✓
                                </span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                                    ANAHTAR BEKLİYOR
                                </span>
                            @endif
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Claude Code, Claude Desktop, Cursor veya ChatGPT'yi Nisoya'ya bağlayarak ilanları, moderasyonu ve esnaf keşif motorunu doğrudan sohbetten yönetin.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    type="button"
                    size="sm"
                    color="primary"
                    wire:click="yeniMcpAnahtariUret"
                    wire:confirm="Mevcut MCP anahtarı geçersiz kalacak ve yeni bir anahtar üretilecek. Devam etmek istiyor musunuz?"
                    icon="heroicon-m-key"
                >
                    {{ $mcp['is_configured'] ? 'Yeni Anahtar Üret' : 'MCP Anahtarı Oluştur' }}
                </x-filament::button>
            </div>
        </div>

        {{-- Uç Nokta ve Güvenlik Parametreleri --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-800/50">
                <div class="text-[11px] font-medium text-gray-500 dark:text-gray-400">MCP HTTP Uç Noktası (Server URL)</div>
                <div class="mt-1 flex items-center justify-between gap-2">
                    <code class="text-xs font-mono font-semibold text-indigo-700 dark:text-indigo-400 truncate">{{ $mcp['endpoint'] }}</code>
                    <span class="text-[10px] font-medium text-gray-400">POST (JSON-RPC 2.0)</span>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-800/50">
                <div class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Bearer Yetki Anahtarı (API Key)</div>
                <div class="mt-1 flex items-center justify-between gap-2">
                    @if($mcp['is_configured'])
                        <code class="text-xs font-mono font-semibold text-gray-900 dark:text-gray-100 truncate">
                            {{ substr($mcp['api_key'], 0, 14) }}••••••••••••••••
                        </code>
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Şifreli</span>
                    @else
                        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">Henüz anahtar üretilmedi</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- İstemci Bağlantı Komutları --}}
        <div class="space-y-3 pt-2">
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Yapay Zekâ İstemcilerine Bağlama Kılavuzu:
            </div>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                {{-- Claude Code --}}
                <div class="rounded-lg border border-gray-200 bg-gray-900 p-3 text-white dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs font-semibold text-indigo-300 mb-1.5">
                        <span>1. Claude Code CLI</span>
                        <span class="text-[10px] text-gray-400">Terminal</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mb-2">Terminalde tek komutla bağlayın:</p>
                    <pre class="overflow-x-auto text-[11px] font-mono text-emerald-400 bg-black/40 p-2 rounded select-all whitespace-pre-wrap"><code>{{ $mcp['claude_code'] }}</code></pre>
                </div>

                {{-- Claude Desktop --}}
                <div class="rounded-lg border border-gray-200 bg-gray-900 p-3 text-white dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs font-semibold text-indigo-300 mb-1.5">
                        <span>2. Claude Desktop</span>
                        <span class="text-[10px] text-gray-400">claude_desktop_config.json</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mb-2">Masaüstü uygulaması konfigürasyonu:</p>
                    <pre class="overflow-x-auto text-[10px] font-mono text-amber-300 bg-black/40 p-2 rounded select-all max-h-28 overflow-y-auto"><code>{{ $mcp['claude_desktop'] }}</code></pre>
                </div>

                {{-- Cursor / Windsurf --}}
                <div class="rounded-lg border border-gray-200 bg-gray-900 p-3 text-white dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs font-semibold text-indigo-300 mb-1.5">
                        <span>3. Cursor & Windsurf</span>
                        <span class="text-[10px] text-gray-400">.cursor/mcp.json</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mb-2">IDE içi yapay zekâ konfigürasyonu:</p>
                    <pre class="overflow-x-auto text-[10px] font-mono text-cyan-300 bg-black/40 p-2 rounded select-all max-h-28 overflow-y-auto"><code>{{ $mcp['cursor'] }}</code></pre>
                </div>
            </div>

            <div class="rounded-lg bg-indigo-50/50 p-2.5 text-xs text-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-200 flex items-start gap-2">
                <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 text-indigo-600 mt-0.5 shrink-0" />
                <div>
                    <strong>Hazır 8 Yönetim Aracı:</strong> İlan arama (<code>nisoya_ilan_ara</code>), bekleyen ilanları listeleme (<code>nisoya_bekleyen_ilanlar</code>), ilan detayları (<code>nisoya_ilan_detay</code>), durum onay/ret (<code>nisoya_ilan_durum_guncelle</code>), esnaf keşif motoru (<code>nisoya_kesif_baslat</code>), sahipsiz esnaflar (<code>nisoya_sahipsiz_isletmeler</code>), WhatsApp davet üretici (<code>nisoya_whatsapp_davet_onizle</code>) ve genel metrikler (<code>nisoya_ozet_metrikler</code>).
                </div>
            </div>
        </div>
    </div>

    {{-- Ana Form --}}
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        {{-- Profesyonel Aksiyon Çubuğu --}}
        <div class="sticky bottom-0 z-10 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
            <div>
                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="modelleriGuncelle"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-arrow-path"
                >
                    <span wire:loading.remove wire:target="modelleriGuncelle">Canlı Modelleri Güncelle</span>
                    <span wire:loading wire:target="modelleriGuncelle">Katalog Taranıyor…</span>
                </x-filament::button>
            </div>

            <div class="flex items-center gap-3">
                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="testEt"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-signal"
                >
                    <span wire:loading.remove wire:target="testEt">Bağlantıyı Test Et</span>
                    <span wire:loading wire:target="testEt">Test Ediliyor…</span>
                </x-filament::button>

                <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                    Değişiklikleri Kaydet
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>


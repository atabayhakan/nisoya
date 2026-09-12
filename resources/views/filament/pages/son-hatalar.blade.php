<x-filament-panels::page>
    @php
        $hatalar = $this->hatalar();
        $tutuluyor = $this->kayitTutuluyorMu();
        $dosyalar = $this->dosyaAdlari();
    @endphp

    {{-- KAYIT TUTULMUYORSA bunu açıkça söyle.

         "Hata yok" ile "kayıt tutulmuyor" apayrı şeylerdir; ikincisini
         birincisi gibi göstermek, olmayan bir güvence vermek olurdu. Hata
         sayfamız ziyaretçiye "kaydedildi" diyor — o cümlenin arkasında
         durabilmemiz gerekir. --}}
    @if (! $tutuluyor)
        <x-filament::section>
            <x-slot name="heading">Hiç log dosyası yok</x-slot>

            <div class="prose prose-sm max-w-none dark:prose-invert">
                <p>
                    <strong>Bu "hata yok" demek değil</strong> — kayıt tutulmuyor olabilir demek.
                    Sunucuda <code>storage/logs</code> klasöründe hiçbir <code>.log</code> dosyası bulunamadı.
                </p>
                <p>Olası sebepler:</p>
                <ul>
                    <li>Henüz <code>LOG_LEVEL</code> eşiğini aşan bir hata olmadı (ayar <code>error</code> ise
                        uyarılar yazılmaz).</li>
                    <li>Log dosyaları elle ya da bir bakım işiyle silinmiş olabilir.</li>
                    <li><code>storage/logs</code> klasörüne yazma izni yok.</li>
                </ul>
                <p>Sunucudan kontrol:</p>
                <pre><code>ls -la storage/logs/</code></pre>
            </div>
        </x-filament::section>
    @endif

    @if ($hatalar === [])
        @if ($tutuluyor)
            <x-filament::section>
                <x-slot name="heading">Kayıtlarda hata yok</x-slot>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ implode(', ', $dosyalar) }} okundu; ERROR ve üzeri bir kayıt bulunamadı.
                </p>
            </x-filament::section>
        @endif
    @else
        @if ($seciliTeshis)
            <div class="mb-4 rounded-xl border border-primary-500/30 bg-primary-50/50 p-5 shadow-sm dark:bg-primary-950/20">
                <div class="flex items-center justify-between border-b border-primary-200 pb-3 dark:border-primary-800">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🤖</span>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Yapay Zekâ Hata Teşhisi</h3>
                        <span class="rounded px-2 py-0.5 text-xs font-bold uppercase {{ $seciliTeshis['teshis']['severity'] === 'kritik' ? 'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' : 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' }}">
                            {{ $seciliTeshis['teshis']['severity'] }}
                        </span>
                    </div>
                    <button wire:click="teshisKapat" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 font-medium">✕ Kapat</button>
                </div>
                <div class="mt-3 space-y-3 text-sm">
                    <div>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">🔍 Olası Kök Neden:</span>
                        <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $seciliTeshis['teshis']['root_cause'] }}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">💥 Platform & Kullanıcı Etkisi:</span>
                        <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $seciliTeshis['teshis']['impact'] }}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">🛠️ Çözüm Adımları:</span>
                        <ul class="mt-1 list-disc pl-5 space-y-1 text-gray-700 dark:text-gray-300">
                            @foreach ($seciliTeshis['teshis']['solution_steps'] as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">🛡️ Önleyici Tavsiye:</span>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 italic">{{ $seciliTeshis['teshis']['prevention_advice'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <x-filament::section>
            <x-slot name="heading">Son {{ count($hatalar) }} hata</x-slot>
            <x-slot name="description">
                En yeni önce. Okunan dosyalar: {{ implode(', ', $dosyalar) }}
            </x-slot>

            <div class="space-y-3">
                @foreach ($hatalar as $hata)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded bg-danger-50 px-1.5 py-0.5 font-bold text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">{{ $hata['seviye'] }}</span>
                                <span class="font-mono text-gray-500 dark:text-gray-400">{{ $hata['zaman'] }}</span>
                                @if ($hata['sinif'] !== '—')
                                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $hata['sinif'] }}</span>
                                @endif
                                @if ($hata['yer'])
                                    <span class="font-mono text-gray-500 dark:text-gray-400">{{ $hata['yer'] }}</span>
                                @endif
                            </div>
                            <button
                                wire:click="aiTeshis({{ $loop->index }})"
                                class="inline-flex items-center gap-1 rounded bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20"
                            >
                                <span>🤖 AI Teşhis Et</span>
                            </button>
                        </div>
                        <p class="mt-1.5 break-words text-sm text-gray-800 dark:text-gray-100">{{ $hata['mesaj'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Tam yığın izi BİLİNÇLE gösterilmiyor: log satırları e-posta,
                 mesaj metni ve form girdisi gibi kullanıcı verisi içerebilir.
                 Teşhis için dosya:satır yeterli — 2026-08-05'teki El Kitabı
                 hatasında da yeterliydi. --}}
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Tam yığın izi bilinçle gösterilmiyor (log satırları kullanıcı verisi içerebilir).
                Gerekirse sunucudan: <code>tail -n 100 storage/logs/{{ $dosyalar[0] ?? 'laravel.log' }}</code>
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>

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
        <x-filament::section>
            <x-slot name="heading">Son {{ count($hatalar) }} hata</x-slot>
            <x-slot name="description">
                En yeni önce. Okunan dosyalar: {{ implode(', ', $dosyalar) }}
            </x-slot>

            <div class="space-y-3">
                @foreach ($hatalar as $hata)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded bg-danger-50 px-1.5 py-0.5 font-bold text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">{{ $hata['seviye'] }}</span>
                            <span class="font-mono text-gray-500 dark:text-gray-400">{{ $hata['zaman'] }}</span>
                            @if ($hata['sinif'] !== '—')
                                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $hata['sinif'] }}</span>
                            @endif
                            @if ($hata['yer'])
                                <span class="font-mono text-gray-500 dark:text-gray-400">{{ $hata['yer'] }}</span>
                            @endif
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

<div class="space-y-3.5 text-xs">
    <div class="p-3 rounded-xl border border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/50 flex items-center justify-between">
        <div>
            <span class="font-bold text-sm text-gray-950 dark:text-white">{{ $aday->name }}</span>
            <p class="text-gray-700 dark:text-gray-300 mt-0.5">
                {{ $aday->city }} · {{ $aday->country }} ({{ $aday->sector }})
            </p>
        </div>
        <div class="text-right">
            <span class="px-2 py-1 rounded text-xs font-bold {{ $analiz['is_turkish'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                {{ $analiz['is_turkish'] ? '✓ Türk Esnafı' : '✗ Türk Değil / Belirsiz' }}
            </span>
            <div class="text-[11px] text-gray-700 dark:text-gray-300 font-semibold mt-1">
                Güven: %{{ $analiz['confidence'] }}
            </div>
        </div>
    </div>

    <div class="p-3 rounded-lg border border-primary-200 bg-primary-50/60 dark:border-primary-900/50 dark:bg-primary-950/20 space-y-1">
        <div class="font-semibold text-primary-950 dark:text-primary-300 flex items-center gap-1">
            <x-filament::icon icon="heroicon-m-sparkles" class="h-4 w-4 text-primary-700 dark:text-primary-400" />
            <span>Kültürel Analiz Gerekçesi</span>
        </div>
        <p class="text-gray-800 dark:text-gray-200 leading-relaxed">
            {{ $analiz['reasoning'] }}
        </p>
    </div>

    <div class="space-y-1.5">
        <span class="font-semibold text-gray-950 dark:text-white">Tespit Edilen Kültürel İşaretler:</span>
        <div class="flex flex-wrap gap-1.5">
            @forelse($analiz['cultural_indicators'] as $ind)
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                    {{ $ind }}
                </span>
            @empty
                <span class="text-gray-500">Özel işaret bulunamadı.</span>
            @endforelse
        </div>
    </div>

    <div class="p-2.5 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 text-gray-700 dark:text-gray-300">
        <p>
            💡 <strong>AI Tavsiyesi:</strong> Bu işletme için önerilen karar:
            <strong class="uppercase text-primary-700 dark:text-primary-400">{{ $analiz['recommendation'] }}</strong>.
            Aşağıdaki butona basarak önerilen sonucu tek hamlede uygulayabilirsiniz.
        </p>
    </div>
</div>

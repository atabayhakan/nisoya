<div class="space-y-4">
    {{-- Üst Skor Kartı --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/50">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    SEO & GEO Hazırlık Skoru
                </span>
                @php
                    $scoreColor = match(true) {
                        $audit['score'] >= 85 => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                        $audit['score'] >= 70 => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
                        $audit['score'] >= 50 => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                        default => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                    };
                @endphp
                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $scoreColor }}">
                    {{ strtoupper($audit['status']) }}
                </span>
            </div>
            <p class="text-xs text-gray-700 dark:text-gray-300 mt-1">
                {{ $audit['summary'] }}
            </p>
        </div>
        <div class="text-right">
            <span class="text-3xl font-extrabold text-gray-950 dark:text-white">
                %{{ $audit['score'] }}
            </span>
        </div>
    </div>

    {{-- GEO (Yapay Zekâ Alıntılama) Değerlendirmesi --}}
    <div class="p-3.5 rounded-lg border border-primary-200 bg-primary-50/60 dark:border-primary-900/50 dark:bg-primary-950/20 text-xs">
        <div class="font-semibold text-primary-900 dark:text-primary-300 flex items-center gap-1.5 mb-1">
            <x-filament::icon icon="heroicon-m-sparkles" class="h-4 w-4 text-primary-700 dark:text-primary-400" />
            <span>2026 Generative Engine (Claude / Perplexity / Gemini) Alıntı Hazırlığı:</span>
        </div>
        <p class="text-gray-800 dark:text-gray-200 leading-relaxed">
            {{ $audit['geo_readiness'] }}
        </p>
    </div>

    {{-- Güçlü Yönler ve İyileştirmeler --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
        <div class="p-3 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 space-y-2">
            <div class="font-semibold text-emerald-800 dark:text-emerald-400 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 text-emerald-700 dark:text-emerald-400" />
                <span>Güçlü Parametreler</span>
            </div>
            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                @forelse($audit['strengths'] as $item)
                    <li class="flex items-start gap-1.5">
                        <span class="text-emerald-700 dark:text-emerald-400 font-bold">✓</span>
                        <span>{{ $item }}</span>
                    </li>
                @empty
                    <li class="text-gray-500">Kayıtlı güçlü yön bulunamadı.</li>
                @endforelse
            </ul>
        </div>

        <div class="p-3 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 space-y-2">
            <div class="font-semibold text-amber-800 dark:text-amber-400 flex items-center gap-1">
                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4 text-amber-700 dark:text-amber-400" />
                <span>Geliştirilmesi Gerekenler</span>
            </div>
            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                @forelse($audit['improvements'] as $item)
                    <li class="flex items-start gap-1.5">
                        <span class="text-amber-700 dark:text-amber-400 font-bold">!</span>
                        <span>{{ $item }}</span>
                    </li>
                @empty
                    <li class="text-gray-500">Tespit edilen zayıf yön yok.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Eylem Listesi --}}
    @if(!empty($audit['action_items']))
        <div class="p-3.5 rounded-lg border border-gray-200 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-900/50 space-y-2 text-xs">
            <div class="font-semibold text-gray-950 dark:text-white flex items-center gap-1.5">
                <x-filament::icon icon="heroicon-m-clipboard-document-check" class="h-4 w-4 text-primary-700 dark:text-primary-400" />
                <span>Önerilen Öncelikli Eylemler</span>
            </div>
            <ol class="list-decimal list-inside space-y-1 text-gray-800 dark:text-gray-200">
                @foreach($audit['action_items'] as $act)
                    <li>{{ $act }}</li>
                @endforeach
            </ol>
        </div>
    @endif
</div>

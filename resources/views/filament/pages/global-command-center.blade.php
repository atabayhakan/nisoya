<x-filament-panels::page class="gcc-surface">
    <p class="text-sm text-gray-600 dark:text-gray-300">Yeni pazarlar için önerileri değerlendirin; medya incelemesine radar üzerinden devam edin.</p>
    <x-filament::button tag="a" :href="\App\Filament\Pages\DiasporaMediaRadar::getUrl()" color="gray">Diaspora Medya Radarı</x-filament::button>
    <x-filament::section heading="İçerik kalite ve risk incelemeleri">
        <div class="mb-4 flex min-w-0 flex-wrap gap-3 text-sm" aria-live="polite">
            <span>Bekleyen: {{ $assessmentCounts['pending'] ?? 0 }}</span>
            <span>Başarısız: {{ $assessmentCounts['failed'] ?? 0 }}</span>
            <span>Sonuçlanan: {{ ($assessmentCounts['completed'] ?? 0) + ($assessmentCounts['rules_only'] ?? 0) }}</span>
        </div>
        <div class="mb-4 grid min-w-0 gap-3 sm:grid-cols-2">
            <label class="grid min-w-0 gap-1 text-sm">İçerik başlığı ara
                <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.300ms="assessmentSearch" maxlength="100" placeholder="İlan veya video başlığı" /></x-filament::input.wrapper>
            </label>
            <label class="grid min-w-0 gap-1 text-sm">İnceleme işlemi
                <x-filament::input.wrapper><x-filament::input.select wire:model.live="assessmentStatus">
                    <option value="all">Tüm incelemeler</option>
                    <option value="pending">Kuyrukta bekleyen</option>
                    <option value="failed">Başarısız</option>
                    <option value="completed">AI sonucu alınan</option>
                    <option value="rules_only">Kural denetimi yapılan</option>
                    <option value="cancelled">İptal edilen</option>
                    <option value="stale">Kaynak değiştiği için durdurulan</option>
                </x-filament::input.select></x-filament::input.wrapper>
            </label>
        </div>
        <div class="grid min-w-0 gap-3 md:grid-cols-2" wire:poll.30s>
            @forelse($assessments as $assessment)
                <article wire:key="assessment-{{ $assessment->id }}" class="min-w-0 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="break-words font-semibold">{{ $assessment->title }}</h3>
                    <p class="text-sm">{{ $assessment->country_code ?: 'Ülke bilinmiyor' }} · {{ ['listing' => 'İlan', 'job' => 'İş ilanı', 'reel' => 'Video'][$assessment->kind] ?? 'İçerik' }} #{{ $assessment->source_id }}</p>
                    <p class="my-2 text-sm">{{ ['pending' => 'İnceleme kuyruğunda', 'rules_only' => 'Kural denetimi tamamlandı; AI incelenmedi', 'completed' => 'AI incelemesi tamamlandı', 'failed' => 'İnceleme başarısız', 'stale' => 'İçerik değişti; yeniden inceleme gerekli', 'cancelled' => 'İptal edildi'][$assessment->status] ?? $assessment->status }}</p>
                    <p class="text-sm">Kalite: {{ $assessment->quality_score ?? '—' }}/100 · Risk: {{ $assessment->risk_score ?? 'Ölçülmedi' }} {{ $assessment->risk_score !== null ? '/100 (yüksek = risk)' : '' }}</p>
                    @foreach($assessment->findings ?? [] as $finding)<p class="break-words text-sm">• {{ $finding }}</p>@endforeach
                    @foreach($assessment->ai_result['evidence'] ?? [] as $evidence)<p class="break-words text-sm text-amber-700 dark:text-amber-300">Kaynak sinyali: {{ $evidence }}</p>@endforeach
                    @foreach($assessment->ai_result['suggestions'] ?? [] as $suggestion)<p class="break-words text-sm">Öneri: {{ $suggestion }}</p>@endforeach
                    @if(isset($assessment->ai_result['confidence']))<p class="text-xs">Modelin bildirdiği güven: %{{ round($assessment->ai_result['confidence'] * 100) }}</p>@endif
                    <p class="mt-2 text-xs text-gray-500">{{ $assessment->assessed_at?->format('d.m.Y H:i') }}</p>
                    @if($assessment->source_url)
                        <div class="mt-3 flex min-w-0 flex-wrap gap-2">
                            <x-filament::button tag="a" :href="$assessment->source_url" color="gray" size="sm">İçeriği aç</x-filament::button>
                            @if(in_array($assessment->status, ['failed', 'cancelled', 'stale'], true))
                                <x-filament::button wire:click="retryAssessment({{ $assessment->id }})" wire:loading.attr="disabled" size="sm">Yeniden incele</x-filament::button>
                            @endif
                        </div>
                    @else
                        <p class="mt-3 text-sm text-gray-500">Kaynak silinmiş veya bu coğrafi görünümün dışında.</p>
                    @endif
                </article>
            @empty<p role="status" class="text-sm">Bu görünümde eşleşen inceleme yok. Aramayı veya işlem filtresini değiştirebilir, ilan tablosundan yeni inceleme başlatabilirsiniz.</p>@endforelse
        </div>
        <div class="mt-4 min-w-0">{{ $assessments->links() }}</div>
    </x-filament::section>
    <x-filament::section heading="Yeni pazarlar için büyüme görevleri">
        <p class="mb-3 break-words text-sm text-gray-600 dark:text-gray-300">Görevler ülke düzeyinde takip edilir. Atama ve durum değişiklikleri iç kayıttır; dış mesaj gönderilmez.</p>
        <div class="mb-4 flex min-w-0 flex-wrap gap-3 text-sm">
            @foreach(\App\Support\GlobalCommand\GrowthTaskWorkflow::LABELS as $key => $label)<span>{{ $label }}: {{ $growthCounts[$key] ?? 0 }}</span>@endforeach
        </div>
        <div class="mb-4 grid min-w-0 gap-3 sm:grid-cols-2">
            <label class="grid min-w-0 gap-1 text-sm">Görev durumu
                <x-filament::input.wrapper><x-filament::input.select wire:model.live="growthStatus"><option value="all">Tüm görevler</option>@foreach(\App\Support\GlobalCommand\GrowthTaskWorkflow::LABELS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper>
            </label>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model.live="onlyMyGrowthTasks">Yalnız benim görevlerim</label>
        </div>
        <div class="grid min-w-0 gap-3 md:grid-cols-2">
            @forelse($growthTasks as $task)
                <article wire:key="growth-{{ $task->id }}" class="min-w-0 rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="break-words font-medium">{{ $task->country?->name_tr ?? $task->country_code }} · {{ $task->period_start->format('d.m.Y') }}</p>
                    <p class="my-2 break-words text-sm">{{ $task->recommendation }}</p>
                    <p class="text-sm">{{ \App\Support\GlobalCommand\GrowthTaskWorkflow::LABELS[$task->status] ?? $task->status }}</p>
                    <p class="break-words text-sm">Sorumlu: {{ $task->assignee?->name ?? 'Atanmadı' }}{{ $task->assignee && $task->assignee->status !== \App\Enums\UserStatus::Aktif ? ' (hesap aktif değil)' : '' }}</p>
                    @if($task->completed_at)<p class="mt-2 text-xs">Tamamlanma: {{ $task->completed_at->format('d.m.Y H:i') }}</p><p class="break-words text-sm">{{ $task->completion_note }}</p>@endif
                    <x-filament::button class="mt-3" size="sm" color="gray" wire:click="mountAction('manageGrowthTask', { task: {{ $task->id }} })">Görevi yönet</x-filament::button>
                </article>
            @empty<p role="status" class="text-sm">Bu görünümde eşleşen görev yok. Filtreyi değiştirebilirsiniz; günlük radar yeni pazar önerilerini burada toplar.</p>@endforelse
        </div>
        <div class="mt-4 min-w-0">{{ $growthTasks->links() }}</div>
    </x-filament::section>
</x-filament-panels::page>

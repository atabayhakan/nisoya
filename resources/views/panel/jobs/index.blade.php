<x-layouts.app title="İş İlanlarım — Nisoya">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <x-panel.back-link />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">İş İlanlarım</h1>
            @if ($company)
                <a href="{{ route('panel.jobs.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">+ Yeni ilan</a>
            @endif
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        @if (! $company)
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center dark:border-stone-700 dark:bg-stone-900">
                <div class="text-4xl">🏢</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">Önce şirket profilini oluştur</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">İş ilanı verebilmek için bir şirket profiline ihtiyacın var.</p>
                <a href="{{ route('panel.company.edit') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Şirket profili oluştur</a>
            </div>
        @elseif ($jobs->isNotEmpty())
            <div class="mt-6 space-y-3">
                @foreach ($jobs as $job)
                    <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('jobs.show', [$job, $job->slug]) }}" class="font-semibold text-stone-800 hover:text-emerald-700 dark:text-stone-100 dark:hover:text-emerald-400">{{ $job->title }}</a>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $job->status->value === 'aktif',
                                        'bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400' => $job->status->value === 'kapali',
                                        'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' => $job->status->value === 'dolu',
                                    ])>{{ $job->status->getLabel() }}</span>
                                </div>
                                <div class="mt-1 text-xs text-stone-400 dark:text-stone-500">{{ $job->employment_type->getLabel() }} · {{ $job->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('panel.jobs.applicants', $job) }}" class="rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">
                                    Başvurular <span class="ml-0.5 rounded-full bg-emerald-600 px-1.5 text-[10px] text-white">{{ $job->applications_count }}</span>
                                </a>
                                <a href="{{ route('panel.jobs.edit', $job) }}" class="rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">Düzenle</a>
                                <form method="POST" action="{{ route('panel.jobs.status', $job) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $job->status->value === 'aktif' ? 'kapali' : 'aktif' }}">
                                    <button class="rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">{{ $job->status->value === 'aktif' ? 'Kapat' : 'Aç' }}</button>
                                </form>
                                <form method="POST" action="{{ route('panel.jobs.destroy', $job) }}" class="inline" onsubmit="return confirm('İlanı silmek istediğine emin misin?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">Sil</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $jobs->links() }}</div>
        @else
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center dark:border-stone-700 dark:bg-stone-900">
                <div class="text-4xl">💼</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">Henüz ilanın yok</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">İlk iş ilanını yayınlayarak aday bulmaya başla.</p>
                <a href="{{ route('panel.jobs.create') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">İlan ver</a>
            </div>
        @endif
    </div>
</x-layouts.app>

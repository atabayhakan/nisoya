<x-layouts.app title="Başvurular — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-panel.back-link :href="route('panel.jobs.index')" label="İş ilanlarım" />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Başvurular</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $job->title }} · {{ $applications->count() }} başvuru</p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        @if ($applications->isNotEmpty())
            <div class="mt-6 space-y-4">
                @foreach ($applications as $app)
                    <div class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    {{ mb_strtoupper(mb_substr($app->applicant?->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    @if ($app->applicant)
                                        <a href="{{ route('profiles.show', $app->applicant->username) }}" target="_blank" class="font-semibold text-stone-800 hover:text-emerald-700 dark:text-stone-100 dark:hover:text-emerald-400">{{ $app->applicant->name }}</a>
                                    @else
                                        <span class="font-semibold text-stone-800 dark:text-stone-100">Aday</span>
                                    @endif
                                    <div class="text-xs text-stone-400 dark:text-stone-500">{{ $app->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300' => in_array($app->status->value, ['gonderildi']),
                                'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' => $app->status->value === 'incelendi',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $app->status->value === 'gorusme',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $app->status->value === 'kabul',
                                'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $app->status->value === 'red',
                            ])>{{ $app->status->getLabel() }}</span>
                        </div>

                        @if ($app->cover_letter)
                            <div class="mt-3 whitespace-pre-line rounded-lg bg-stone-50 p-3 text-sm text-stone-600 dark:bg-stone-800/50 dark:text-stone-300">{{ $app->cover_letter }}</div>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($app->cv_path)
                                <a href="{{ route('panel.applications.cv', $app) }}" class="rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">📎 CV indir</a>
                            @endif
                            <form method="POST" action="{{ route('panel.applications.status', $app) }}" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="rounded-lg border-stone-300 py-1.5 text-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    @foreach (\App\Enums\ApplicationStatus::cases() as $s)
                                        <option value="{{ $s->value }}" @selected($app->status === $s)>{{ $s->getLabel() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state
                illustration="inbox"
                title="Henüz başvuru yok"
                description="Başvurular geldikçe burada görünecek."
            />
        @endif
    </div>
</x-layouts.app>

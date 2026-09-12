<div class="space-y-4">
    <div class="flex items-center justify-between rounded-xl border p-4 {{ $audit['level'] === 'guvenli' ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/20' : ($audit['level'] === 'orta' ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/20' : 'border-rose-200 bg-rose-50 dark:border-rose-800 dark:bg-rose-950/20') }}">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Dayanıklılık Skoru</div>
            <div class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $audit['score'] }} / 100</div>
        </div>
        <div>
            <span class="rounded-full px-3 py-1 text-xs font-bold uppercase {{ $audit['level'] === 'guvenli' ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100' : ($audit['level'] === 'orta' ? 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-100' : 'bg-rose-200 text-rose-800 dark:bg-rose-800 dark:text-rose-100') }}">
                {{ $audit['level'] }}
            </span>
        </div>
    </div>

    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ $audit['summary'] }}</p>

    <div class="grid grid-cols-2 gap-2 text-xs">
        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800">
            <span class="text-gray-500 dark:text-gray-400">Aktif Yönetici:</span>
            <span class="font-bold text-gray-800 dark:text-gray-200 ml-1">{{ $admins }}</span>
        </div>
        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800">
            <span class="text-gray-500 dark:text-gray-400">2FA Aktif:</span>
            <span class="font-bold text-gray-800 dark:text-gray-200 ml-1">{{ $twoFa }} / {{ $admins }}</span>
        </div>
        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800">
            <span class="text-gray-500 dark:text-gray-400">Kurtarma Kodu:</span>
            <span class="font-bold text-gray-800 dark:text-gray-200 ml-1">{{ $remaining }}</span>
        </div>
        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800">
            <span class="text-gray-500 dark:text-gray-400">SMTP Durumu:</span>
            <span class="font-bold {{ $smtp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} ml-1">
                {{ $smtp ? 'Yapılandırılmış' : 'Eksik' }}
            </span>
        </div>
    </div>

    @if (! empty($audit['risks']))
        <div class="space-y-1.5">
            <div class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Tespit Edilen Güvenlik Riskleri</div>
            <ul class="list-disc pl-5 space-y-1 text-xs text-gray-700 dark:text-gray-300">
                @foreach ($audit['risks'] as $risk)
                    <li>{{ $risk }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! empty($audit['action_plan']))
        <div class="space-y-1.5">
            <div class="text-xs font-bold uppercase tracking-wider text-primary-600 dark:text-primary-400">Öncelikli Eylem Planı</div>
            <ul class="list-decimal pl-5 space-y-1 text-xs text-gray-700 dark:text-gray-300">
                @foreach ($audit['action_plan'] as $action)
                    <li>{{ $action }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

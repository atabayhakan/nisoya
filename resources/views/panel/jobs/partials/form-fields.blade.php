@php($job = $job ?? null)
@php($v = fn ($k, $d = '') => old($k, $job?->$k ?? $d))
@php($enumVal = fn ($k) => old($k, $job?->$k?->value ?? ''))

@if ($errors->any())
    <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
        <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div>
    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">İlan başlığı *</label>
    <input type="text" name="title" required maxlength="150" value="{{ $v('title') }}" placeholder="örn. Kıdemli Aşçı — Berlin"
           class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
</div>

<div>
    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">İş tanımı *</label>
    <textarea name="description" rows="6" required class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ $v('description') }}</textarea>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Kategori</label>
        <select name="job_category_id" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            <option value="">—</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) $v('job_category_id') === (string) $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Çalışma tipi *</label>
        <select name="employment_type" required class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            @foreach (\App\Enums\EmploymentType::cases() as $t)<option value="{{ $t->value }}" @selected($enumVal('employment_type') === $t->value)>{{ $t->getLabel() }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Deneyim seviyesi</label>
        <select name="experience_level" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            <option value="">—</option>
            @foreach (\App\Enums\ExperienceLevel::cases() as $x)<option value="{{ $x->value }}" @selected($enumVal('experience_level') === $x->value)>{{ $x->getLabel() }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Pozisyon sayısı</label>
        <input type="number" name="positions" min="1" max="999" value="{{ $v('positions', 1) }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    </div>
</div>

{{-- Maaş --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Maaş (min)</label>
        <input type="number" name="salary_min" step="0.01" min="0" value="{{ $v('salary_min') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Maaş (max)</label>
        <input type="number" name="salary_max" step="0.01" min="0" value="{{ $v('salary_max') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Para birimi</label>
        <select name="salary_currency" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            <option value="">—</option>
            @foreach ($currencies as $cur)<option value="{{ $cur->code }}" @selected($v('salary_currency') === $cur->code)>{{ $cur->code }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Periyot</label>
        <select name="salary_period" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            <option value="">—</option>
            @foreach (\App\Enums\SalaryPeriod::cases() as $p)<option value="{{ $p->value }}" @selected($enumVal('salary_period') === $p->value)>{{ $p->getLabel() }}</option>@endforeach
        </select>
    </div>
</div>

{{-- Konum --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Ülke</label>
        <select name="country_code" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            <option value="">—</option>
            @foreach ($countries as $c)<option value="{{ $c->code }}" @selected($v('country_code') === $c->code)>{{ $c->emoji }} {{ $c->name_tr }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Şehir</label>
        <input type="text" name="city" value="{{ $v('city') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
        <input type="checkbox" name="is_remote" value="1" @checked($v('is_remote')) class="rounded border-stone-300 text-emerald-700 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
        Uzaktan çalışmaya uygun
    </label>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Son başvuru tarihi</label>
        <input type="date" name="deadline" value="{{ $job?->deadline?->format('Y-m-d') ?? old('deadline') }}" min="{{ date('Y-m-d') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
    </div>
</div>

<x-layouts.app title="Şirket Profili — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-8">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Şirket Profili</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
            @if ($company) Şirket bilgilerini güncelle. @else İş ilanı verebilmek için önce şirket profilini oluştur. @endif
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('panel.company.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-stone-100 text-xl font-bold text-stone-400 dark:bg-stone-800">
                    @if ($company?->logoUrl())<img src="{{ $company->logoUrl() }}" alt="" class="h-full w-full object-cover">@else 🏢 @endif
                </div>
                <div class="flex-1">
                    <label class="text-xs font-medium text-stone-600 dark:text-stone-300">Logo (opsiyonel)</label>
                    <input type="file" name="logo" accept="image/*" class="mt-1 block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 dark:text-stone-300 dark:file:bg-emerald-900/40 dark:file:text-emerald-300">
                </div>
            </div>

            @php($val = fn ($k, $d = '') => old($k, $company?->$k ?? $d))
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Şirket adı *</label>
                <input type="text" name="name" required value="{{ $val('name') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Slogan</label>
                <input type="text" name="tagline" value="{{ $val('tagline') }}" placeholder="Kısa bir tanıtım cümlesi" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Hakkında</label>
                <textarea name="about" rows="4" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ $val('about') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Sektör</label>
                    <input type="text" name="sector" value="{{ $val('sector') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Çalışan sayısı</label>
                    <input type="text" name="company_size" value="{{ $val('company_size') }}" placeholder="örn. 1-10" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Kuruluş yılı</label>
                    <input type="number" name="founded_year" value="{{ $val('founded_year') }}" min="1900" max="{{ date('Y') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Ülke</label>
                    <select name="country_code" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        <option value="">—</option>
                        @foreach ($countries as $c)<option value="{{ $c->code }}" @selected($val('country_code') === $c->code)>{{ $c->emoji }} {{ $c->name_tr }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Şehir</label>
                    <input type="text" name="city" value="{{ $val('city') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Web sitesi</label>
                    <input type="url" name="website" value="{{ $val('website') }}" placeholder="https://" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">LinkedIn</label>
                    <input type="url" name="social_linkedin" value="{{ $val('social_linkedin') }}" placeholder="https://linkedin.com/company/..." class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 dark:text-stone-200">Instagram</label>
                    <input type="text" name="social_instagram" value="{{ $val('social_instagram') }}" class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Kaydet</button>
                @if ($company)
                    <a href="{{ route('companies.show', $company) }}" class="text-sm text-stone-500 hover:underline dark:text-stone-400">Herkese açık sayfayı gör →</a>
                @endif
            </div>
        </form>
    </div>
</x-layouts.app>

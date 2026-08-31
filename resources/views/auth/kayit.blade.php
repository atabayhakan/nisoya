<x-layouts.guest title="Kayıt Ol — Nisoya">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 dark:text-stone-50">Aramıza katılın 🎉</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Ücretsiz hesabınızı oluşturun, yeteneğinizi kazanca dönüştürün.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="gonderimKilidi('Hesap oluşturuluyor...')" @submit="kilitle">
        @csrf
        @include('partials.honeypot')

        <div class="space-y-1.5">
            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Ad Soyad</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                    <x-heroicon-o-user class="h-5 w-5" />
                </span>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                       placeholder="Adınız Soyadınız"
                       class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
            </div>
            @error('name') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">E-posta</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                    <x-heroicon-o-envelope class="h-5 w-5" />
                </span>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                       placeholder="ornek@nisoya.com"
                       class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
            </div>
            @error('email') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
                <label for="country_code" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Ülke</label>
                <select id="country_code" name="country_code" required
                        class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 px-3 py-2.5 text-sm text-stone-900 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
                    <option value="">Seç...</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->code }}" @selected(old('country_code') === $country->code)>
                            {{ $country->emoji }} {{ $country->name_tr }}
                        </option>
                    @endforeach
                </select>
                @error('country_code') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label for="city" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Şehir <span class="text-[10px] font-normal text-stone-500">(ops.)</span></label>
                <input id="city" name="city" type="text" value="{{ old('city') }}" list="city-options" autocomplete="off"
                       placeholder="Şehir"
                       class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 px-3 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
                @error('city') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @include('partials.city-datalist')
            </div>
        </div>

        <div class="space-y-1.5">
            <label for="preferred_currency" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Para Birimi</label>
            <select id="preferred_currency" name="preferred_currency" required
                    class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 px-3 py-2.5 text-sm text-stone-900 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @selected(old('preferred_currency', 'EUR') === $currency->code)>
                        {{ $currency->symbol }} {{ $currency->name }} ({{ $currency->code }})
                    </option>
                @endforeach
            </select>
            @error('preferred_currency') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Şifre</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </span>
                <x-password-input id="password" name="password" required autocomplete="new-password"
                       placeholder="En az 8 karakter"
                       class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-10 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900" />
            </div>
            @error('password') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Şifre (Tekrar)</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </span>
                <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                       placeholder="Şifrenizi tekrar girin"
                       class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-10 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900" />
            </div>
        </div>

        <label class="flex items-start gap-2 pt-1 text-xs text-stone-600 dark:text-stone-400">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 h-4 w-4 rounded-md border-stone-300 text-emerald-700 focus:ring-2 focus:ring-emerald-500/20 dark:border-stone-700 dark:bg-stone-800 dark:text-emerald-500">
            <span><a href="{{ url('/kosullar') }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400" target="_blank">Kullanım koşullarını</a> ve <a href="{{ url('/gizlilik') }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400" target="_blank">gizlilik politikasını</a> okudum, kabul ediyorum.</span>
        </label>
        @error('terms') <p class="-mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-brand transition duration-150 hover:from-emerald-700 hover:to-emerald-800 active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-emerald-500/20 dark:from-emerald-600 dark:to-emerald-700">
            Kayıt Ol
        </button>
    </form>

    <x-google-giris-butonu etiket="Google ile kayıt ol" />

    <div class="mt-6 border-t border-stone-100 pt-5 text-center text-xs text-stone-500 dark:border-stone-800 dark:text-stone-400">
        Zaten hesabınız var mı?
        <a href="{{ route('login') }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-400">Giriş yap →</a>
    </div>
</x-layouts.guest>

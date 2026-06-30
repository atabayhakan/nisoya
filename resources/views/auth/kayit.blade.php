<x-layouts.guest title="Kayıt Ol — Nisoya">
    <h1 class="text-xl font-bold text-stone-900">Aramıza katıl 🎉</h1>
    <p class="mt-1 text-sm text-stone-500">Ücretsiz hesap oluştur, yeteneğini paraya dönüştür.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        {{-- honeypot --}}
        <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

        <div>
            <label for="name" class="block text-sm font-medium text-stone-700">Ad Soyad</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="country_code" class="block text-sm font-medium text-stone-700">Ülke</label>
                <select id="country_code" name="country_code" required
                        class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Seç...</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->code }}" @selected(old('country_code') === $country->code)>
                            {{ $country->emoji }} {{ $country->name_tr }}
                        </option>
                    @endforeach
                </select>
                @error('country_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-stone-700">Şehir <span class="text-stone-400">(ops.)</span></label>
                <input id="city" name="city" type="text" value="{{ old('city') }}" list="city-options" autocomplete="off"
                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @include('partials.city-datalist')
            </div>
        </div>

        <div>
            <label for="preferred_currency" class="block text-sm font-medium text-stone-700">Tercih ettiğin para birimi</label>
            <select id="preferred_currency" name="preferred_currency" required
                    class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @selected(old('preferred_currency', 'EUR') === $currency->code)>
                        {{ $currency->symbol }} {{ $currency->name }} ({{ $currency->code }})
                    </option>
                @endforeach
            </select>
            @error('preferred_currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700">Şifre</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Şifre (tekrar)</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>

        <label class="flex items-start gap-2 text-sm text-stone-600">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
            <span><a href="{{ url('/kosullar') }}" class="text-emerald-700 hover:underline" target="_blank">Kullanım koşullarını</a> ve <a href="{{ url('/gizlilik') }}" class="text-emerald-700 hover:underline" target="_blank">gizlilik politikasını</a> okudum, kabul ediyorum.</span>
        </label>
        @error('terms') <p class="-mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
            Kayıt Ol
        </button>
    </form>

    @include('auth.partials.social')

    <p class="mt-6 text-center text-sm text-stone-500">
        Zaten hesabın var mı?
        <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline">Giriş yap</a>
    </p>
</x-layouts.guest>

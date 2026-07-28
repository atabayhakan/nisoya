<x-layouts.guest title="Hesap Kurtarma — Nisoya">
    <h1 class="text-xl font-bold text-stone-900">Hesabını kurtar</h1>
    <p class="mt-1 text-sm text-stone-500">
        E-postana erişemiyor musun? Daha önce oluşturduğun <strong>kurtarma kodlarından</strong>
        birini kullanarak parolanı e-postasız sıfırlayabilirsin.
    </p>

    <form method="POST" action="{{ route('account-recovery.store') }}" class="mt-6 space-y-4">
        @csrf
        @include('partials.honeypot')

        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="code" class="block text-sm font-medium text-stone-700">Kurtarma kodu</label>
            <input id="code" name="code" type="text" value="{{ old('code') }}" required autocomplete="one-time-code"
                   inputmode="text" placeholder="ÖRN: A3F9K-2M7BQ"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 font-mono uppercase tracking-wider shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-stone-600">Her kod yalnızca bir kez kullanılabilir.</p>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700">Yeni parola</label>
            <x-password-input id="password" name="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" />
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Yeni parola (tekrar)</label>
            <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>

        <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-800">
            Parolayı sıfırla
        </button>
    </form>

    <p class="mt-4 rounded-lg bg-stone-50 px-4 py-3 text-xs text-stone-500">
        İki adımlı doğrulama (2FA) etkinse parola sıfırlandıktan sonra giriş yine kod ister.
        Hem parola hem 2FA hem de kurtarma kodların kaybolduysa, sunucu erişimiyle son çare
        <span class="font-mono">php artisan admin:recover</span> komutunu kullan.
    </p>

    <p class="mt-6 text-center text-sm text-stone-500">
        <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline">← Girişe dön</a>
    </p>
</x-layouts.guest>

<x-layouts.guest title="İki Adımlı Doğrulama — Nisoya">
    <h1 class="text-xl font-bold text-stone-900 dark:text-stone-100">İki adımlı doğrulama 🔐</h1>

    <div x-data="{ recovery: false }">
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400" x-show="! recovery">
            Kimlik doğrulama uygulamandaki (Google Authenticator vb.) 6 haneli kodu gir.
        </p>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400" x-show="recovery" x-cloak>
            Kurulumda kaydettiğin tek kullanımlık yedek kodlardan birini gir.
        </p>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6 space-y-4">
            @csrf

            {{-- TOTP kodu --}}
            <div x-show="! recovery">
                <label for="code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Doğrulama kodu</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                       x-bind:autofocus="! recovery" x-bind:required="! recovery"
                       placeholder="123456" maxlength="6"
                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 tracking-[0.4em] shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Yedek kod --}}
            <div x-show="recovery" x-cloak>
                <label for="recovery_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yedek kod</label>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                       x-bind:required="recovery"
                       placeholder="XXXXXXXX"
                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 uppercase tracking-widest shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('recovery_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
                Doğrula ve giriş yap
            </button>
        </form>

        <div class="mt-4 text-center text-sm">
            <button type="button" @click="recovery = false" x-show="recovery" x-cloak
                    class="text-emerald-700 hover:underline dark:text-emerald-400">
                ← Kimlik doğrulama uygulaması koduyla dene
            </button>
            <button type="button" @click="recovery = true" x-show="! recovery"
                    class="text-stone-500 hover:underline dark:text-stone-400">
                Uygulamana erişemiyor musun? Yedek kod kullan
            </button>
        </div>
    </div>

    <p class="mt-6 text-center text-sm text-stone-500 dark:text-stone-400">
        <a href="{{ route('login') }}" class="font-medium text-stone-500 hover:underline dark:text-stone-400">← Girişe dön</a>
    </p>
</x-layouts.guest>

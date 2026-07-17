<x-layouts.guest title="Şifre Sıfırla — Nisoya">
    <h1 class="text-xl font-bold text-stone-900">Yeni şifre belirle</h1>
    <p class="mt-1 text-sm text-stone-500">Hesabın için yeni bir şifre oluştur.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700">Yeni şifre</label>
            <x-password-input id="password" name="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" />
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Yeni şifre (tekrar)</label>
            <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>

        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
            Şifreyi sıfırla
        </button>
    </form>
</x-layouts.guest>

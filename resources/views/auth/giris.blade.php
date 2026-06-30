<x-layouts.guest title="Giriş Yap — Nisoya">
    <h1 class="text-xl font-bold text-stone-900">Tekrar hoş geldin 👋</h1>
    <p class="mt-1 text-sm text-stone-500">Hesabına giriş yap.</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-stone-700">Şifre</label>
                <a href="{{ route('password.request') }}" class="text-sm text-emerald-700 hover:underline">Şifreni mi unuttun?</a>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="remember" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
            Beni hatırla
        </label>

        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
            Giriş Yap
        </button>
    </form>

    @include('auth.partials.social')

    <p class="mt-6 text-center text-sm text-stone-500">
        Hesabın yok mu?
        <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:underline">Kayıt ol</a>
    </p>
</x-layouts.guest>

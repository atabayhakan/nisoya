<x-layouts.guest title="Giriş Yap — Nisoya">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-stone-900 dark:text-stone-50">Tekrar hoş geldiniz 👋</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Nisoya hesabınıza güvenle giriş yapın.</p>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50/90 p-3.5 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            <x-heroicon-s-check-circle class="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-400" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">E-posta</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                    <x-heroicon-o-envelope class="h-5 w-5" />
                </span>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="ornek@nisoya.com"
                       class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-3.5 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900">
            </div>
            @error('email') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Şifre</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline dark:text-emerald-400 dark:hover:text-emerald-300">Şifreni mi unuttun?</a>
            </div>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-500 dark:text-stone-400">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </span>
                <x-password-input id="password" name="password" required autocomplete="current-password"
                       placeholder="••••••••"
                       class="w-full rounded-xl border border-stone-200/90 bg-stone-50/60 pl-11 pr-10 py-2.5 text-sm text-stone-900 placeholder:text-stone-500 shadow-2xs transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-3 focus:ring-emerald-500/15 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-100 dark:placeholder:text-stone-300 dark:focus:border-emerald-400 dark:focus:bg-stone-900" />
            </div>
            @error('password') <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between pt-1">
            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-stone-600 dark:text-stone-300">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded-md border-stone-300 text-emerald-700 transition focus:ring-2 focus:ring-emerald-500/20 dark:border-stone-700 dark:bg-stone-800 dark:text-emerald-500">
                <span class="text-xs font-medium">Beni hatırla</span>
            </label>
        </div>

        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-brand transition duration-150 hover:from-emerald-700 hover:to-emerald-800 active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-emerald-500/20 dark:from-emerald-600 dark:to-emerald-700">
            Giriş Yap
        </button>
    </form>

    <x-google-giris-butonu etiket="Google ile giriş yap" />

    <div class="mt-6 border-t border-stone-100 pt-5 text-center text-xs text-stone-500 dark:border-stone-800 dark:text-stone-400">
        Hesabınız yok mu?
        <a href="{{ route('register') }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-400">Hemen Ücretsiz Kayıt Ol →</a>
    </div>
</x-layouts.guest>


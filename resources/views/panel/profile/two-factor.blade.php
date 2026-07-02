<x-layouts.app title="İki Faktörlü Doğrulama — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">İki Faktörlü Kimlik Doğrulama (2FA)</h1>
        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
            Google Authenticator, Authy veya 1Password gibi TOTP uygulamalarıyla hesabına ekstra güvenlik kat.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if ($enabled)
            <section class="mt-6 space-y-4 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 dark:border-emerald-800 dark:bg-emerald-950/20">
                <header>
                    <h2 class="font-semibold text-emerald-900 dark:text-emerald-200">✓ 2FA Etkin</h2>
                    <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                        Hesabın her girişte 6 haneli doğrulama kodu soracak.
                    </p>
                </header>

                <form method="POST" action="{{ route('panel.profile.2fa.disable') }}" class="space-y-3 border-t border-emerald-200 pt-4 dark:border-emerald-800">
                    @csrf
                    <div>
                        <label for="disable_password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mevcut şifren</label>
                        <input id="disable_password" name="current_password" type="password" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="disable_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Doğrulama kodu (6 haneli)</label>
                        <input id="disable_code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400 dark:text-stone-900">
                        2FA'yı kapat
                    </button>
                </form>
            </section>
        @else
            <section class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                <header>
                    <h2 class="font-semibold text-stone-900 dark:text-stone-100">2FA Kurulumu</h2>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        Aşağıdaki "Kur" düğmesine basınca QR kod oluşturulacak. TOTP uygulamanla okut ve 6 haneli kodu gir.
                    </p>
                </header>

                @if (session('qr_code_url'))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-200">1. QR Kodu Okut</h3>
                        <p class="mt-1 text-xs text-amber-800 dark:text-amber-300 break-all">
                            URL: <code>{{ session('qr_code_url') }}</code>
                        </p>
                        <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">
                            Secret: <code class="font-mono">{{ session('secret') }}</code>
                        </p>
                    </div>
                @endif

                @if (session('qr_code_url'))
                    <form method="POST" action="{{ route('panel.profile.2fa.confirm') }}" class="space-y-3 border-t border-stone-200 pt-4 dark:border-stone-800">
                        @csrf
                        <div>
                            <label for="confirm_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">2. Doğrulama kodunu gir</label>
                            <input id="confirm_code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                            Etkinleştir
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('panel.profile.2fa.setup') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                            Kur (QR Kod Üret)
                        </button>
                    </form>
                @endif

                @if (session('recovery_codes'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/20">
                        <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Yedek Kurtarma Kodları</h3>
                        <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                            Bu kodları güvenli bir yere kaydet. Telefonunu kaybedersen bu kodlarla giriş yapabilirsin.
                        </p>
                        <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm">
                            @foreach (session('recovery_codes') as $code)
                                <code class="rounded bg-white px-2 py-1 text-center text-stone-900 dark:bg-stone-800 dark:text-stone-100">{{ $code }}</code>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-layouts.app>
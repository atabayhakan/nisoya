<x-layouts.app title="Profilim — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Profil Ayarları</h1>

        {{-- Profil bilgileri --}}
        <form method="POST" action="{{ route('panel.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            @csrf
            @method('PUT')

            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 place-items-center overflow-hidden rounded-full bg-emerald-100 text-2xl font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    @if ($user->avatar_path)
                        <img src="{{ Storage::url($user->avatar_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <label for="avatar" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Profil fotoğrafı</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*" class="mt-1 text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                    @error('avatar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ad Soyad</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Kullanıcı adı</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="bio" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Hakkında <span class="text-stone-400">(ops.)</span></label>
                <textarea id="bio" name="bio" rows="3" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('bio', $user->bio) }}</textarea>
                @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="country_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ülke</label>
                    <select id="country_code" name="country_code" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @foreach ($countries as $country)
                            <option value="{{ $country->code }}" @selected(old('country_code', $user->country_code) === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Şehir</label>
                    <input id="city" name="city" type="text" value="{{ old('city', $user->city) }}" list="city-options" autocomplete="off" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @include('partials.city-datalist')
                </div>
                <div>
                    <label for="preferred_currency" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Para birimi</label>
                    <select id="preferred_currency" name="preferred_currency" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->code }}" @selected(old('preferred_currency', $user->preferred_currency) === $currency->code)>{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @php
                $selectedPaymentMethods = old('payment_methods', $user->payment_methods?->map(fn ($m) => $m->value)->all() ?? []);
                $suggestedValues = array_map(fn ($m) => $m->value, $suggestedPaymentMethods);
            @endphp
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ödeme yöntemlerin <span class="text-stone-400">(ops.)</span></label>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    Nisoya ödemeleri işlemez — burada seçtiklerin sadece diğer üyelere "bu yöntemlerle ödeme kabul ediyorum" bilgisini gösterir. Ülkene göre önerilen yöntemler işaretli.
                </p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($paymentMethods as $method)
                        @php($isSuggested = in_array($method->value, $suggestedValues, true))
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition {{ in_array($method->value, $selectedPaymentMethods, true) ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:border-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-200' : 'border-stone-300 text-stone-600 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800' }}">
                            <input type="checkbox" name="payment_methods[]" value="{{ $method->value }}" @checked(in_array($method->value, $selectedPaymentMethods, true)) class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600">
                            <span aria-hidden="true">{{ $method->icon() }}</span>
                            {{ $method->getLabel() }}
                            @if ($isSuggested)
                                <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">Önerilen</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                @error('payment_methods') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Profili Kaydet</button>
        </form>

        {{-- Şifre değiştir --}}
        <form method="POST" action="{{ route('panel.profile.password') }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            @csrf
            @method('PUT')

            <h2 class="font-semibold text-stone-800 dark:text-stone-100">Şifre Değiştir</h2>

            @if (session('status_password'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status_password') }}</div>
            @endif

            <div>
                <label for="current_password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mevcut şifre</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yeni şifre</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yeni şifre (tekrar)</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
            </div>

            <button type="submit" class="rounded-lg border border-stone-300 px-5 py-2.5 font-semibold text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800">Şifreyi Güncelle</button>
        </form>

        {{-- KVKK: Verilerini indir / Hesabı sil --}}
        <section class="mt-6 space-y-4 rounded-2xl border border-amber-200 bg-amber-50/50 p-6 dark:border-amber-800 dark:bg-amber-950/20">
            <header>
                <h2 class="font-semibold text-amber-900 dark:text-amber-200">🔐 Güvenlik</h2>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                    Hesabını iki faktörlü kimlik doğrulama ile koru.
                </p>
            </header>

            <a
                href="{{ route('panel.profile.2fa') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50"
            >
                <span aria-hidden="true">🔑</span>
                İki Faktörlü Doğrulama (2FA) Yönet
            </a>
        </section>

        {{-- KVKK: Verilerini indir / Hesabı sil --}}
        <section class="mt-6 space-y-4 rounded-2xl border border-blue-200 bg-blue-50/50 p-6 dark:border-blue-800 dark:bg-blue-950/20">
            <header>
                <h2 class="font-semibold text-blue-900 dark:text-blue-200">Kişisel verilerin (KVKK)</h2>
                <p class="mt-1 text-sm text-blue-800 dark:text-blue-300">
                    6698 sayılı KVKK kapsamında verilerine erişme, dışa aktarma ve silme haklarına sahipsin.
                </p>
            </header>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a
                    href="{{ route('panel.profile.export') }}"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-blue-300 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200 dark:hover:bg-blue-900/50"
                >
                    <span aria-hidden="true">⬇️</span>
                    Verilerimi indir (JSON)
                </a>
            </div>
        </section>

        {{-- Hesabı Sil --}}
        <section
            x-data="{ open: false }"
            class="mt-6 space-y-4 rounded-2xl border border-red-200 bg-red-50/50 p-6 dark:border-red-800 dark:bg-red-950/20"
        >
            <header>
                <h2 class="font-semibold text-red-900 dark:text-red-200">Hesabı sil</h2>
                <p class="mt-1 text-sm text-red-800 dark:text-red-300">
                    Hesabın silindiğinde tüm ilanların, yorumların ve kişisel verilerin kalıcı olarak temizlenir.
                    Bu işlem geri alınamaz.
                </p>
            </header>

            <button
                type="button"
                @click="open = !open"
                class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200 dark:hover:bg-red-900/50"
            >
                Hesabımı silmek istiyorum
            </button>

            <form
                x-show="open"
                x-transition.opacity
                x-cloak
                method="POST"
                action="{{ route('panel.profile.destroy') }}"
                class="space-y-3 border-t border-red-200 pt-4 dark:border-red-800"
                onsubmit="return confirm('Bu işlem geri alınamaz. Hesabınız silinecek. Emin misiniz?');"
            >
                @csrf
                @method('DELETE')

                <div>
                    <label for="delete_current_password" class="block text-sm font-medium text-red-800 dark:text-red-200">Mevcut şifren</label>
                    <input id="delete_current_password" name="current_password" type="password" required class="mt-1 w-full rounded-lg border-red-300 px-3 py-2 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-red-700 dark:bg-red-950/40 dark:text-red-100">
                    @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="delete_confirm_text" class="block text-sm font-medium text-red-800 dark:text-red-200">
                        Onaylamak için tam olarak <code class="rounded bg-red-100 px-1.5 py-0.5 font-mono text-red-700 dark:bg-red-900/40 dark:text-red-200">HESABIMI SİL</code> yaz
                    </label>
                    <input id="delete_confirm_text" name="confirm_text" type="text" required autocomplete="off" placeholder="HESABIMI SİL" class="mt-1 w-full rounded-lg border-red-300 px-3 py-2 font-mono text-sm uppercase shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-red-700 dark:bg-red-950/40 dark:text-red-100">
                    @error('confirm_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400 dark:text-stone-900">
                    Hesabımı kalıcı olarak sil
                </button>
            </form>
        </section>
    </div>
</x-layouts.app>

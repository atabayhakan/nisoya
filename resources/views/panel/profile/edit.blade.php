<x-layouts.app title="Profilim — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <h1 class="text-2xl font-bold text-stone-900">Profil Ayarları</h1>

        {{-- Profil bilgileri --}}
        <form method="POST" action="{{ route('panel.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="flex items-center gap-4">
                <div class="grid h-16 w-16 place-items-center overflow-hidden rounded-full bg-emerald-100 text-2xl font-bold text-emerald-700">
                    @if ($user->avatar_path)
                        <img src="{{ Storage::url($user->avatar_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <label for="avatar" class="block text-sm font-medium text-stone-700">Profil fotoğrafı</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*" class="mt-1 text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100">
                    @error('avatar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-stone-700">Ad Soyad</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-stone-700">Kullanıcı adı</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="bio" class="block text-sm font-medium text-stone-700">Hakkında <span class="text-stone-400">(ops.)</span></label>
                <textarea id="bio" name="bio" rows="3" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('bio', $user->bio) }}</textarea>
                @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="country_code" class="block text-sm font-medium text-stone-700">Ülke</label>
                    <select id="country_code" name="country_code" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach ($countries as $country)
                            <option value="{{ $country->code }}" @selected(old('country_code', $user->country_code) === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-stone-700">Şehir</label>
                    <input id="city" name="city" type="text" value="{{ old('city', $user->city) }}" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label for="preferred_currency" class="block text-sm font-medium text-stone-700">Para birimi</label>
                    <select id="preferred_currency" name="preferred_currency" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->code }}" @selected(old('preferred_currency', $user->preferred_currency) === $currency->code)>{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700">Profili Kaydet</button>
        </form>

        {{-- Şifre değiştir --}}
        <form method="POST" action="{{ route('panel.profile.password') }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <h2 class="font-semibold text-stone-800">Şifre Değiştir</h2>

            @if (session('status_password'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status_password') }}</div>
            @endif

            <div>
                <label for="current_password" class="block text-sm font-medium text-stone-700">Mevcut şifre</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-stone-700">Yeni şifre</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-stone-700">Yeni şifre (tekrar)</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>

            <button type="submit" class="rounded-lg border border-stone-300 px-5 py-2.5 font-semibold text-stone-700 transition hover:bg-stone-50">Şifreyi Güncelle</button>
        </form>
    </div>
</x-layouts.app>

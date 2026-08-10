{{--
    Google ile gelen yeni kişinin kaydını tamamlayan ekran.

    Yalnız Google'ın VEREMEDİĞİ alanlar sorulur: ülke, para birimi, koşul
    onayı (+ opsiyonel şehir). Ad ve e-posta Google'dan geldi, tekrar
    sorulmaz — sorulsaydı "Google ile devam et"in kazandırdığı adım geri
    verilmiş olurdu.

    Parola alanı YOK: bu hesabın parolası hiç kurulmuyor. Kullanıcı isterse
    sonradan "şifremi unuttum" ile kendi parolasını belirleyebilir.
--}}
<x-layouts.guest title="Kaydı tamamla — Nisoya">
    <h1 class="text-xl font-bold text-stone-900 dark:text-stone-50">Neredeyse bitti 👋</h1>
    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
        Google hesabınla geldin. Sana doğru ilanları gösterebilmemiz için iki şey kaldı.
    </p>

    <div class="mt-4 flex items-center gap-3 rounded-lg bg-stone-100 px-4 py-3 dark:bg-stone-800">
        <x-heroicon-o-user-circle class="h-9 w-9 shrink-0 text-stone-500 dark:text-stone-400" />
        <div class="min-w-0">
            <div class="truncate font-semibold text-stone-800 dark:text-stone-100">{{ $ad }}</div>
            <div class="truncate text-sm text-stone-600 dark:text-stone-400">{{ $eposta }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('register.google.store') }}" class="mt-6 space-y-4"
          x-data="gonderimKilidi('Hesap oluşturuluyor...')" @submit="kilitle">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="country_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ülke</label>
                <select id="country_code" name="country_code" required
                        class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Seç...</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->code }}" @selected(old('country_code') === $country->code)>
                            {{ $country->emoji }} {{ $country->name_tr }}
                        </option>
                    @endforeach
                </select>
                @error('country_code') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Şehir <span class="text-stone-600 dark:text-stone-400">(ops.)</span></label>
                <input id="city" name="city" type="text" value="{{ old('city') }}" list="city-options" autocomplete="off"
                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @error('city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @include('partials.city-datalist')
            </div>
        </div>

        <div>
            <label for="preferred_currency" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Tercih ettiğin para birimi</label>
            <select id="preferred_currency" name="preferred_currency" required
                    class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->code }}" @selected(old('preferred_currency', 'EUR') === $currency->code)>
                        {{ $currency->symbol }} {{ $currency->name }} ({{ $currency->code }})
                    </option>
                @endforeach
            </select>
            @error('preferred_currency') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-2 text-sm text-stone-600 dark:text-stone-400">
            <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 rounded border-stone-300 text-emerald-700 focus:ring-emerald-500 dark:border-stone-700 dark:text-emerald-400">
            <span><a href="{{ url('/kosullar') }}" class="text-emerald-700 hover:underline dark:text-emerald-400" target="_blank">Kullanım koşullarını</a> ve <a href="{{ url('/gizlilik') }}" class="text-emerald-700 hover:underline dark:text-emerald-400" target="_blank">gizlilik politikasını</a> okudum, kabul ediyorum.</span>
        </label>
        @error('terms') <p class="-mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

        <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900">
            Hesabı oluştur
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500 dark:text-stone-400">
        Vazgeçtin mi?
        <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">E-posta ile kayıt ol</a>
    </p>
</x-layouts.guest>

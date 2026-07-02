<x-layouts.guest title="Şifremi Unuttum — Nisoya">
    <h1 class="text-xl font-bold text-stone-900">Şifreni mi unuttun?</h1>
    <p class="mt-1 text-sm text-stone-500">Sorun değil. E-posta adresini gir, sana sıfırlama bağlantısı gönderelim.</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        @include('partials.honeypot')

        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
            Sıfırlama bağlantısı gönder
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-stone-500">
        <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline">← Girişe dön</a>
    </p>
</x-layouts.guest>

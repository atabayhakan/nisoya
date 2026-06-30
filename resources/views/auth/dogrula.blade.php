<x-layouts.guest title="E-postanı Doğrula — Nisoya">
    <h1 class="text-xl font-bold text-stone-900">E-postanı doğrula 📧</h1>
    <p class="mt-2 text-sm text-stone-600">
        Kaydolduğun için teşekkürler! Devam etmeden önce, sana gönderdiğimiz bağlantıya tıklayarak
        e-posta adresini doğrula. E-posta gelmediyse yenisini gönderebiliriz.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Kayıt sırasında verdiğin e-posta adresine yeni bir doğrulama bağlantısı gönderildi.
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">
                Doğrulama e-postasını tekrar gönder
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-stone-500 hover:text-stone-700">Çıkış yap</button>
        </form>
    </div>
</x-layouts.guest>

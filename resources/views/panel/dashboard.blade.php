{{--
    KULLANICI PANELİ (/panel)

    Bu sayfa tek bir soruya göre sıralanır: "benden bir şey bekleniyor mu?"

    Eskiden 14 eşit ağırlıklı kart vardı, hiçbirinde sayı yoktu ve rota
    `Route::view` olduğu için sayfaya veri bile geçmiyordu. Artık:
      K1 Bekleyenler — yalnız EYLEM gerektirenler, sabit kanonik sırada
      K2 Durumun     — gerçek rakamlar; değeri 0 olan kutu BASILMAZ
      K3 Bölümler    — her zaman görünür dizin (rol kapısı YOK, modül kapısı VAR)
      K4 Başlangıç   — yalnız K1 ve K2'nin İKİSİ de boşsa
      K5 Hesap       — çıkış (mobilde hesaptan çıkmanın tek yolu)

    Hiçbir partial kendi sorgusunu atmaz; her şey $sinyaller içinde gelir
    (bkz. App\Support\PanelSinyalleri — azaltma defteri orada).
--}}
<x-layouts.app title="Panelim — Nisoya">
    @php
        $user = auth()->user();
    @endphp

    <div class="mx-auto max-w-6xl px-4 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-stone-100 px-4 py-3 text-sm text-stone-700 dark:bg-stone-800 dark:text-stone-200">
                {{ session('status') }}
            </div>
        @endif

        {{-- K0 · Selamlama. Sağdaki "+ Yeni İlan Ver" butonu KALDIRILDI:
             aynı eylem üst çubukta ve mobil FAB'da zaten sabit duruyor. --}}
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Merhaba, {{ $user->name }} 👋</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
            @if ($user->city){{ $user->city }}, @endif{{ $user->country_code }}
        </p>

        @include('panel.partials.bekleyenler', ['s' => $sinyaller])
        @include('panel.partials.durumun', ['s' => $sinyaller])

        @if ($sinyaller->bosDurum())
            @include('panel.partials.baslangic', ['s' => $sinyaller, 'user' => $user])
        @endif

        @include('panel.partials.bolumler', ['s' => $sinyaller])

        {{-- K5 · Hesap. Çıkış formu mobilde hesaptan çıkmanın TEK yolu —
             düşük vurgulu ama her zaman erişilebilir. --}}
        <div class="mt-10 flex flex-wrap items-center gap-4 border-t border-stone-200 pt-6 dark:border-stone-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="min-h-11 text-sm font-medium text-stone-500 hover:text-stone-800 md:min-h-0 dark:text-stone-400 dark:hover:text-stone-100">
                    Çıkış Yap
                </button>
            </form>
            {{-- `inline-flex items-center` ŞART, süs değil.

                 `min-h-11` dokunma hedefini 44px'e çıkarıyor ama tek başına
                 metni ortalamıyor: <button> içeriğini kendiliğinden dikey
                 ortalar, <a> ortalamaz — metin 44px'lik kutunun TEPESİNDE
                 kalır. Ölçüldü (360px, sahibin bildirdiği hata): iki metin
                 arasında 11px kayma vardı, "biri aşağıda biri yukarıda"
                 görüntüsü buradan geliyordu.

                 Masaüstünde görünmemesinin sebebi `md:min-h-0` — orada
                 yükseklik zorlaması kalkıyor ve kutular metne oturuyor. --}}
            <a href="{{ url('/panel/profil') }}" class="inline-flex min-h-11 items-center text-sm text-stone-600 hover:text-stone-700 md:min-h-0 dark:text-stone-400 dark:hover:text-stone-200">
                Profil ayarları
            </a>
        </div>
    </div>
</x-layouts.app>

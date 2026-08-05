<x-layouts.app>
    {{-- VİTRİN ANA SAYFA (P1) — klasik home'un aynı-ad override'ı. Aynı
         HomeController verisiyle çalışır (sıfır controller dokunuşu) ve
         bölüm göster/gizle için klasikle AYNI HomeSections anahtarlarını
         kullanır (admin tercihi iki temada da geçerli). Vurgu kartları
         (home_highlights) bilinçli P3 kapsamında — Hero Yöneticisi ile
         birlikte gelecek. --}}

    {{-- pulse-countries: sahte haftalık çubukların yerine geçen GERÇEK ülke
         hareketi (HomeController'da zaten hesaplanıyor, yeni sorgu yok).
         hero-cips: ilanı olan gerçek kategoriler — çipler artık boş sonuç
         döndüremez. --}}
    <x-vitrin.hero :countries="$countries" :stats="$stats" :latest-listings="$latestListings" :activity-feed="$activityFeed" :pulse-countries="$pulseCountries" :hero-cips="$heroCips" :ziyaretci-ulke="$ziyaretciUlke" />

    {{-- Alan: anasayfa üst (klasikle aynı zone anahtarı — reklam sözleşmesi) --}}
    <div class="mx-auto max-w-6xl px-4">
        <x-zone zone-key="anasayfa_ust" />
    </div>


    {{-- Bölümler artık SIRALANABİLİR (Faz 2 · G5). Sıra panelden yönetilir
         (Anasayfa Bölümleri sayfası); her bölüm kendi @if görünürlük kapısını
         partial'ının içinde taşır, yani mantık yer değiştirmedi.
         Sıralanamayan bloklar bir SIRA İNDEKSİNE çapalanır (bkz. HomeSections
         ::CAPALAR) — varsayılan sırada sayfa bugünküyle birebir aynıdır. --}}
    @foreach (\App\Support\HomeSections::sirali('vitrin') as $sira => $bolum)
        @include('partials.home.'.$bolum)

        @if (\App\Support\HomeSections::capa('vitrin', 'nabiz') === $sira)
            @include('partials.home.capa-nabiz')
        @endif
        @if (\App\Support\HomeSections::capa('vitrin', 'zone_orta') === $sira)
            @include('partials.home.capa-zone-orta')
        @endif
    @endforeach

    {{-- Alan: anasayfa alt (sitewide reklam/duyuru) --}}
    <div class="mx-auto max-w-6xl px-4 pt-14">
        <x-zone zone-key="anasayfa_alt" />
    </div>
</x-layouts.app>

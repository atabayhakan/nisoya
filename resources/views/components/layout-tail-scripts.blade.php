{{-- PAYLAŞILAN SÖZLEŞME BİLEŞENİ (Vitrin P0): body sonu zinciri — consent'e
     kilitli AdSense template'i + çerez onay banner'ı + service worker kaydı.
     Klasik ve (gelecekteki) vitrin iskeleti aynı bileşeni kullanır; banner
     unutulursa yeni ziyaretçi hiç onay veremez ve reklam/analytics sessizce
     sıfırlanırdı — tek kopya bu riski yapısal olarak kapatır.
     İçerik layouts/app.blade.php'den ÇIKTI-ÖZDEŞ taşındı (sw kaydı body
     sonundan buraya alındı; yalnızca 'load' dinleyicisi eklediği için
     konumdan bağımsızdır). --}}
{{-- Google AdSense — kullanıcı reklam çerezi onayı verene kadar bu
     script hiç yüklenmez/çalışmaz (bkz. layout-head-scripts içindeki
     nisoyaActivateConsent ve <template> açıklaması). Admin panelden Auto
     Ads kodu girildiyse onu kullan (adsbygoogle.js'i zaten kendi içinde
     yükler); yoksa temel script'e düş. --}}
@if (config('services.adsense.enabled') && config('services.adsense.publisher_id'))
    <template id="nisoya-consent-ads">
        @if (config('services.adsense.auto_ads_code'))
            {!! config('services.adsense.auto_ads_code') !!}
        @else
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('services.adsense.publisher_id') }}" crossorigin="anonymous"></script>
        @endif
    </template>
@endif

{{-- Çerez onayı banner'ı (AdSense + Analytics için) --}}
<x-cookie-consent />

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
</script>

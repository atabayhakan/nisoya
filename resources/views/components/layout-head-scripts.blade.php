{{-- PAYLAŞILAN SÖZLEŞME BİLEŞENİ (Vitrin P0): head sonu script zinciri —
     DNS prefetch + JSON-LD + AdSense doğrulama meta'sı + consent'e kilitli
     Analytics template'i + window.nisoyaActivateConsent. Consent zincirinin
     TEK kopyası: klasik ve (gelecekteki) vitrin iskeleti aynı bileşeni
     kullanır — banner/template/ad-slot sözleşmesi iki temada asla ayrışamaz.
     İçerik layouts/app.blade.php'den ÇIKTI-ÖZDEŞ taşındı. --}}
{{-- DNS prefetch / preconnect: AdSense + Analytics için bağlantı kurulumunu erkenden başlat --}}
<link rel="dns-prefetch" href="//pagead2.googlesyndication.com">
<link rel="dns-prefetch" href="//www.googletagmanager.com">
<link rel="dns-prefetch" href="//googleads.g.doubleclick.net">
<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
<link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>

{{-- JSON-LD: WebSite + Organization (SEO + AdSense kalite) --}}
<x-json-ld type="WebSite" :data="[
    'name' => setting('genel.site_adi'),
    'alternateName' => 'Nisoya',
    'url' => url('/'),
    'description' => setting('footer.aciklama'),
    'inLanguage' => 'tr-TR',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => [
            '@type' => 'EntryPoint',
            'urlTemplate' => route('listings.index').'?q={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string',
    ],
]" />
<x-json-ld type="Organization" :data="[
    'name' => setting('genel.site_adi'),
    'url' => url('/'),
    'logo' => asset('icons/icon-192.png'),
    'description' => setting('footer.aciklama'),
]" />

{{-- Google AdSense doğrulama meta etiketi (yayıncı id .env / admin panelden) --}}
@if (config('services.adsense.enabled') && config('services.adsense.publisher_id'))
    <meta name="google-adsense-account" content="{{ config('services.adsense.publisher_id') }}">
@endif

{{-- Google Analytics 4 — yalnızca analytics etkinse ve ölçüm id varsa.
     GİZLİLİK: Bu script'ler kullanıcı çerez onayı vermeden ÇALIŞMAZ.
     <template> içeriği tarayıcı tarafından inert kabul edilir — script
     çalışmaz, hiçbir ağ isteği atılmaz. window.nisoyaActivateConsent()
     (aşağıda tanımlı) kullanıcı onay verdiğinde bu içeriği gerçek
     <script> elemanlarına çevirip DOM'a ekler. Bkz. cookie-consent.blade.php
     ve cerez-tercihleri.blade.php. --}}
@if (config('services.analytics.enabled') && config('services.analytics.measurement_id'))
    <template id="nisoya-consent-analytics">
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics.measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json(config('services.analytics.measurement_id')), { anonymize_ip: true });
        </script>
        {{-- Analytics özel kodu (admin panelden — Site Yönetimi → İçerik).
             GÜVENLİK: Bu alana sadece admin rolündeki kullanıcılar yazabilir
             (Filament `canAccessPanel()` ile korunur). Üretim ortamında
             admin'in kendi itibarı ve KVKK gereği sadece güvenilir 3. parti
             (AdSense, Analytics, vb.) kodları eklemesi beklenir. Bilinmeyen
             kullanıcıya bu alanı kullandırmayın. --}}
        @if (config('services.analytics.custom_code'))
            {!! config('services.analytics.custom_code') !!}
        @endif
    </template>
@endif

{{-- Çerez onayına bağlı script'leri etkinleştiren paylaşılan fonksiyon.
     cookie-consent.blade.php (banner) ve cerez-tercihleri.blade.php
     (detaylı tercihler sayfası) bunu çağırır. <template> içeriğini
     klonlayıp gerçek <script> elemanlarına çevirmek ZORUNLU: script
     elemanları innerHTML/cloneNode ile eklendiğinde tarayıcı bunları
     çalıştırmaz — bu yüzden her script manuel olarak yeniden oluşturulur. --}}
<script>
    window.nisoyaActivateConsent = function (category) {
        var tpl = document.getElementById('nisoya-consent-' + category);
        if (!tpl || tpl.dataset.activated === 'true') return;
        tpl.dataset.activated = 'true';
        var clone = tpl.content.cloneNode(true);
        clone.querySelectorAll('script').forEach(function (oldScript) {
            var newScript = document.createElement('script');
            for (var i = 0; i < oldScript.attributes.length; i++) {
                newScript.setAttribute(oldScript.attributes[i].name, oldScript.attributes[i].value);
            }
            newScript.textContent = oldScript.textContent;
            oldScript.replaceWith(newScript);
        });
        document.body.appendChild(clone);
        if (category === 'ads') {
            window.dispatchEvent(new Event('nisoya:consent-ads-granted'));
        }
    };
</script>

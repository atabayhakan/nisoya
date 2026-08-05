{{-- HATA SAYFASI ORTAK İSKELETİ (2026-08-05)

     Bu dosya BİLİNÇLE kendi kendine yeter: uygulama layout'unu kullanmaz,
     `setting()` çağırmaz, veritabanına dokunmaz, Vite varlığı yüklemez.

     Sebep: bir 500 hatasının en olası sebepleri veritabanı erişimi ve derleme
     sorunlarıdır. Hata sayfası bunlara bağımlı olsaydı, hatayı gösterirken
     KENDİSİ çökerdi ve kullanıcı yine çıplak sunucu hatası görürdü. Aynı
     mantıkla `route()` yerine düz yol (`/`) kullanılır — rota önbelleği
     bozuksa route() de patlar.

     Karanlık mod `prefers-color-scheme` ile: tema tercihi localStorage'da ve
     onu okuyan script uygulama layout'unda. Buraya taşımak yerine sistem
     tercihine uymak yeterli — hata anında doğru olan basit olandır. --}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('baslik') — Nisoya</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="data:image/svg+xml,&lt;svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'&gt;&lt;rect width='24' height='24' rx='6' fill='%233E63F0'/&gt;&lt;path d='M7 17V7L17 17V7' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/&gt;&lt;/svg&gt;">
    <style>
        :root {
            --zemin: #eef2f8; --kart: #ffffff; --murekkep: #10203c;
            --soluk: #4d5c7a; --vurgu: #3E63F0; --kenar: rgba(16,32,60,.10);
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --zemin: #0b1220; --kart: #131c2f; --murekkep: #f2f5fb;
                --soluk: #94a3bf; --vurgu: #6b8afd; --kenar: rgba(255,255,255,.10);
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            padding: 24px; background: var(--zemin); color: var(--murekkep);
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .kutu {
            width: 100%; max-width: 520px; background: var(--kart);
            border: 1px solid var(--kenar); border-radius: 24px; padding: 32px 24px;
            text-align: center; box-shadow: 0 20px 40px -28px rgba(16,32,64,.5);
        }
        .marka { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: inherit; margin-bottom: 20px; }
        .marka span { font-size: 18px; font-weight: 800; letter-spacing: -.02em; }
        .kod { font-size: 13px; font-weight: 700; color: var(--soluk); letter-spacing: .08em; }
        h1 { margin: 8px 0 0; font-size: 26px; font-weight: 800; line-height: 1.2; text-wrap: balance; }
        p { margin: 12px 0 0; font-size: 15px; line-height: 1.6; color: var(--soluk); text-wrap: pretty; }
        .eylemler { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        a.dugme, button.dugme {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 46px; padding: 0 20px; border-radius: 14px;
            font-size: 15px; font-weight: 700; text-decoration: none; cursor: pointer;
            border: 1px solid var(--kenar); background: transparent; color: var(--murekkep);
            font-family: inherit;
        }
        a.dugme.birincil { background: var(--vurgu); border-color: var(--vurgu); color: #fff; }
        @media (prefers-color-scheme: dark) { a.dugme.birincil { color: #131c2f; } }
        .alt { margin-top: 20px; font-size: 13px; color: var(--soluk); }
        .alt a { color: var(--soluk); }
    </style>
</head>
<body>
    <main class="kutu">
        <a class="marka" href="/">
            <svg width="32" height="32" viewBox="0 0 24 24" aria-hidden="true">
                <rect width="24" height="24" rx="6" fill="var(--vurgu)"/>
                <path d="M7 17V7L17 17V7" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            <span>Nisoya</span>
        </a>

        <div class="kod">@yield('kod')</div>
        <h1>@yield('baslik')</h1>
        <p>@yield('aciklama')</p>

        <div class="eylemler">@yield('eylemler')</div>

        <div class="alt">
            Sorun sürerse <a href="/iletisim">bize yaz</a>.
        </div>
    </main>
</body>
</html>

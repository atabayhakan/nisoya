@props(['title' => null, 'description' => null, 'ogImage' => null])
{{-- PAYLAŞILAN SÖZLEŞME BİLEŞENİ (Vitrin P0): SEO/og/canonical/favicon/manifest
     meta'larının TEK kopyası. Hem klasik iskelet (layouts/app) hem gelecekteki
     vitrin iskeleti bunu kullanır — iki iskelet arasında meta sürüklenmesi
     (unutulan og/canonical/adsense-doğrulama) yapısal olarak imkânsız olsun diye.
     İçerik layouts/app.blade.php'den ÇIKTI-ÖZDEŞ taşındı. --}}
{{-- SEO varsayılanları panelden yönetilir (Site Yönetimi → SEO). Sayfa
     kendi $title/$description/$ogImage'ini verirse o önceliklidir. --}}
@php
    $seoTitle = $title ?? setting('seo.default_title');
    $seoDescription = $description ?? setting('seo.default_description');
    $seoOgPath = setting('seo.og_image');
    $seoOgImage = $ogImage ?? ($seoOgPath ? Storage::disk('public')->url($seoOgPath) : asset('og.png'));
@endphp
@if (setting('seo.robots_index', '1') === '0')
    <meta name="robots" content="noindex,nofollow">
@endif
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:site_name" content="Nisoya">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="tr_TR">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $seoOgImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{{ $seoOgImage }}">
@php
    $faviconPath = setting('gorunum.favicon_path');
    $faviconHref = $faviconPath
        ? Storage::disk('public')->url($faviconPath)
        : "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='6' fill='%23".ltrim(brandColorHex(), '#')."'/><path d='M7 17V7L17 17V7' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/></svg>";
@endphp
<link rel="icon" href="{{ $faviconHref }}">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="{{ brandColorHex() }}" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0c0a09" media="(prefers-color-scheme: dark)">
<link rel="apple-touch-icon" href="/icons/icon-192.png">

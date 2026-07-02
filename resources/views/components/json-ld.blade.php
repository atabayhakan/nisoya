@props([
    'type' => 'WebSite',     // WebSite, Organization, Product, Service, BreadcrumbList, LocalBusiness
    'data' => [],
])

@php
    $base = [
        '@context' => 'https://schema.org',
        '@type' => $type,
    ];
    $payload = array_merge($base, $data);
@endphp

<script type="application/ld+json">
{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
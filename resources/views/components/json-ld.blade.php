@props([
    'type' => 'WebSite',     // WebSite, Organization, Product, Service, BreadcrumbList, LocalBusiness, Person, JobPosting
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
{{-- JSON_HEX_TAG/AMP/APOS/QUOT: ilan başlığı gibi kullanıcı girdisi payload'a
     girdiğinde "</script>" veya tırnak enjeksiyonuyla script etiketinin
     kırılmasını (stored-XSS) engeller. JSON anlamı değişmez, yalnızca <>&'"
     karakterleri \uXXXX kaçışına çevrilir. --}}
{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
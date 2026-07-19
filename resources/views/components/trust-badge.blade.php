@props(['user'])

{{-- Satıcının HESAPLANMIŞ güven kademesi (bkz. App\Enums\TrustTier). Admin'in
     elle açtığı "Doğrulanmış" rozetinden farklı olarak bu tamamen objektif
     sinyallere dayanır ve her üyede görünür. --}}
@php $tier = $user->trustTier(); @endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset align-middle '.$tier->badgeClasses()]) }}
    title="{{ $tier->description() }}"
>
    <span aria-hidden="true">{{ $tier->icon() }}</span>{{ $tier->label() }}
</span>

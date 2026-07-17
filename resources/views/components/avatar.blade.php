@props(['user', 'size' => 'h-16 w-16', 'text' => 'text-2xl'])

{{-- DİKKAT: img `absolute inset-0` OLMALI. Grid + place-items-center içinde
     h-full/w-full yüzdeleri çözümlenmez; img kendi en-boy oranında dizilir,
     kutu fotoğrafla aynı oranda kalır ve object-position görsel olarak hiçbir
     şey yapmaz (odak hizalama "çalışmıyor" gibi görünür — 2026-07-17 raporu).
     absolute inset-0 kutuyu çerçeveye sabitler, object-fit:cover gerçekten kırpar. --}}
<div {{ $attributes->merge(['class' => "relative grid $size shrink-0 place-items-center overflow-hidden rounded-full bg-emerald-100 $text font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"]) }}>
    @if ($user->avatar_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar_path) }}" alt=""
             class="absolute inset-0 h-full w-full object-cover" style="object-position: {{ $user->avatarObjectPosition() }}">
    @else
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    @endif
</div>

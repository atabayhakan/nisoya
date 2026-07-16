@props(['user', 'size' => 'h-16 w-16', 'text' => 'text-2xl'])

<div {{ $attributes->merge(['class' => "grid $size shrink-0 place-items-center overflow-hidden rounded-full bg-emerald-100 $text font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"]) }}>
    @if ($user->avatar_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar_path) }}" alt=""
             class="h-full w-full object-cover" style="object-position: {{ $user->avatarObjectPosition() }}">
    @else
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    @endif
</div>

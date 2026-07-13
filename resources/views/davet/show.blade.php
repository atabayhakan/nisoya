<!DOCTYPE html>
<html lang="{{ $locale }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event->title }} — Davetiye</title>
    <meta name="description" content="{{ $event->type->getLabel() }} davetiyesi — {{ $event->starts_at->translatedFormat('j F Y') }}">
    {{-- Davet linki özeldir: arama motorlarına kapalı --}}
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:title" content="{{ $event->title }} 💌">
    <meta property="og:description" content="{{ $event->type->getLabel() }} · {{ $event->starts_at->translatedFormat('j F Y, H:i') }}{{ $event->venue_name ? ' · '.$event->venue_name : '' }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='6' fill='%23059669'/><path d='M7 17V7L17 17V7' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/></svg>">

    @unless ($theme['dark_only'] ?? false)
        <x-theme-init />
    @endunless

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen antialiased {{ $theme['page'] }}">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">

        {{-- Dil seçici (misafirin Türkçe bilmeyen eşi/arkadaşı için) --}}
        <div class="mb-4 flex items-center gap-1 rounded-full bg-white/70 px-2 py-1 text-xs shadow-sm ring-1 ring-stone-200 dark:bg-stone-900/70 dark:ring-stone-700">
            @foreach ($locales as $lang)
                <a href="{{ route('davet.show', array_filter(['token' => $event->token, 'dil' => $lang === 'tr' ? null : $lang])) }}"
                   class="rounded-full px-2.5 py-1 font-medium uppercase transition {{ $locale === $lang ? 'bg-emerald-600 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-200' }}">{{ $lang }}</a>
            @endforeach
        </div>

        <div class="w-full max-w-lg rounded-3xl border p-8 shadow-lg sm:p-10 {{ $theme['card'] }}">
            {{-- Süs + tür --}}
            <div class="text-center">
                <div class="text-3xl">{{ $theme['ornament'] }}</div>
                <div class="mt-3 text-sm font-medium uppercase tracking-[0.25em] {{ $theme['accent'] }}">
                    {{ $event->type->emoji() }} {{ $event->type->getLabel() }}
                </div>
                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-4xl">{{ $event->title }}</h1>
            </div>

            {{-- Tarih & mekan --}}
            <div class="mt-8 space-y-3 text-center text-sm">
                <div>
                    <div class="text-xs uppercase tracking-wider opacity-60">{{ __('davet.tarih') }}</div>
                    <div class="mt-0.5 text-lg font-semibold {{ $theme['accent'] }}">{{ $event->starts_at->translatedFormat('j F Y l') }}</div>
                    <div class="font-medium opacity-80">{{ __('davet.saat') }} {{ $event->starts_at->format('H:i') }}</div>
                    <a href="{{ route('davet.ics', $event->token) }}" class="mt-1 inline-block text-xs underline-offset-2 opacity-70 hover:underline">{{ __('davet.takvime_ekle') }}</a>
                </div>
                @if ($event->venue_name || $event->venue_address)
                    <div class="pt-2">
                        <div class="text-xs uppercase tracking-wider opacity-60">{{ __('davet.mekan') }}</div>
                        @if ($event->venue_name)<div class="mt-0.5 font-semibold">{{ $event->venue_name }}</div>@endif
                        @if ($event->venue_address)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($event->venue_address) }}" target="_blank" rel="noopener"
                               class="text-sm underline-offset-2 opacity-80 hover:underline">{{ $event->venue_address }} ↗</a>
                        @endif
                    </div>
                @endif
            </div>

            @if ($event->description)
                <p class="mx-auto mt-6 max-w-md whitespace-pre-line text-center text-sm leading-relaxed opacity-90">{{ $event->description }}</p>
            @endif

            {{-- LCV --}}
            <div class="mt-8 border-t pt-6 {{ ($theme['dark_only'] ?? false) ? 'border-stone-700' : 'border-stone-200 dark:border-stone-700' }}">
                @if (session('rsvp_status'))
                    <div class="mb-4 rounded-xl bg-emerald-100 px-4 py-3 text-center text-sm font-medium text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
                        {{ session('rsvp_status') }}
                    </div>
                @endif

                <h2 class="text-center text-sm font-semibold uppercase tracking-wider opacity-70">
                    {{ $myGuest ? __('davet.lcv_guncelle_baslik') : __('davet.lcv_baslik') }}
                </h2>

                <form method="POST" action="{{ route('davet.rsvp', $event->token) }}" class="mt-4 space-y-4">
                    @csrf
                    @if ($locale !== 'tr')<input type="hidden" name="dil" value="{{ $locale }}">@endif
                    <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

                    <div>
                        <label for="name" class="block text-sm font-medium opacity-80">{{ __('davet.ad_soyad') }}</label>
                        <input id="name" name="name" type="text" required minlength="2" maxlength="100"
                               value="{{ old('name', $myGuest?->name) }}" placeholder="{{ __('davet.ad_soyad_ornek') }}"
                               class="mt-1 w-full rounded-xl border-stone-300 bg-white px-3 py-2.5 text-stone-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                        @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="block text-sm font-medium opacity-80">{{ __('davet.katiliyor_musun') }}</span>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach ($statuses as $status)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="{{ $status->value }}" required class="peer sr-only"
                                           @checked(old('status', $myGuest?->status?->value) === $status->value)>
                                    <span class="block rounded-xl border border-stone-300 px-2 py-2.5 text-center text-sm font-medium transition peer-checked:border-emerald-500 peer-checked:bg-emerald-600 peer-checked:text-white dark:border-stone-600 dark:peer-checked:bg-emerald-500 dark:peer-checked:text-stone-900">
                                        {{ __('davet.'.match ($status->value) { 'geliyor' => 'geliyorum', 'belki' => 'belki', default => 'gelemiyorum' }) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('status') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="party_size" class="block text-sm font-medium opacity-80">{{ __('davet.kac_kisi') }} <span class="opacity-60">{{ __('davet.sen_dahil') }}</span></label>
                            <input id="party_size" name="party_size" type="number" min="1" max="20" required
                                   value="{{ old('party_size', $myGuest?->party_size ?? 1) }}"
                                   class="mt-1 w-full rounded-xl border-stone-300 bg-white px-3 py-2.5 text-stone-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                            @error('party_size') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="note" class="block text-sm font-medium opacity-80">{{ __('davet.not') }} <span class="opacity-60">{{ __('davet.ops') }}</span></label>
                            <input id="note" name="note" type="text" maxlength="255"
                                   value="{{ old('note', $myGuest?->note) }}" placeholder="{{ __('davet.not_ornek') }}"
                                   class="mt-1 w-full rounded-xl border-stone-300 bg-white px-3 py-2.5 text-stone-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                            @error('note') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-xl px-4 py-3 font-semibold transition {{ $theme['button'] }}">
                        {{ $myGuest ? __('davet.yanit_guncelle') : __('davet.yanit_gonder') }}
                    </button>
                    <p class="text-center text-xs opacity-60">{{ __('davet.gizlilik_notu') }}</p>
                </form>
            </div>
        </div>

        {{-- Anı akışı: etkinlik gününden itibaren ortak albüm --}}
        @if ($media !== null)
            <div class="mt-8 w-full max-w-3xl rounded-3xl border p-6 shadow-lg sm:p-8 {{ $theme['card'] }}">
                <h2 class="text-center text-sm font-semibold uppercase tracking-wider opacity-70">📸 {{ __('davet.aki_baslik') }}</h2>
                <p class="mt-1 text-center text-xs opacity-60">{{ __('davet.aki_aciklama') }}</p>

                @if (session('media_status'))
                    <div class="mt-4 rounded-xl bg-emerald-100 px-4 py-3 text-center text-sm font-medium text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
                        {{ session('media_status') }}
                    </div>
                @endif
                @error('files')
                    <div class="mt-4 rounded-xl bg-red-100 px-4 py-3 text-center text-sm font-medium text-red-800 dark:bg-red-900/50 dark:text-red-200">{{ $message }}</div>
                @enderror
                @error('files.*')
                    <div class="mt-4 rounded-xl bg-red-100 px-4 py-3 text-center text-sm font-medium text-red-800 dark:bg-red-900/50 dark:text-red-200">{{ $message }}</div>
                @enderror

                {{-- Yükleme --}}
                @if ($myGuest || $isHost)
                    <form method="POST" action="{{ route('davet.media.store', $event->token) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center justify-center gap-2">
                        @csrf
                        <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                        <input name="files[]" type="file" multiple required accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                               class="max-w-xs text-xs opacity-80 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-xs file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/40 dark:file:text-emerald-300">
                        <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $theme['button'] }}">{{ __('davet.paylas') }}</button>
                        <p class="w-full text-center text-[11px] opacity-50">{{ __('davet.yukleme_kurali') }}</p>
                    </form>
                @else
                    <p class="mt-4 rounded-xl bg-stone-100 px-4 py-3 text-center text-sm text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                        {{ __('davet.once_lcv') }}
                    </p>
                @endif

                {{-- Akış ızgarası --}}
                @if ($media->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($media as $item)
                            <figure class="group relative overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-800">
                                @if ($item->type === 'image')
                                    <a href="{{ Storage::disk(\App\Models\EventMedia::DISK)->url($item->path_large) }}" target="_blank" rel="noopener">
                                        <img src="{{ Storage::disk(\App\Models\EventMedia::DISK)->url($item->path_medium) }}"
                                             alt="" loading="lazy" decoding="async"
                                             class="aspect-square w-full object-cover transition group-hover:scale-105">
                                    </a>
                                @else
                                    <video controls preload="metadata" class="aspect-square w-full object-cover"
                                           src="{{ Storage::disk(\App\Models\EventMedia::DISK)->url($item->path) }}"></video>
                                @endif

                                @if ($item->status === 'beklemede')
                                    <span class="absolute left-1.5 top-1.5 rounded bg-amber-500/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ __('davet.onay_bekliyor') }}</span>
                                @endif

                                <figcaption class="flex items-center justify-between gap-1 px-2 py-1.5 text-[11px] opacity-70">
                                    <span class="truncate">{{ $item->event_guest_id ? $item->uploaderName() : __('davet.ev_sahibi') }}</span>
                                    @if ($isHost || ($myGuest && $item->event_guest_id === $myGuest->id))
                                        <form method="POST" action="{{ route('davet.media.destroy', [$event->token, $item]) }}"
                                              onsubmit="return confirm('{{ __('davet.sil_onay') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-500 hover:text-red-600">{{ __('davet.sil') }}</button>
                                        </form>
                                    @endif
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $media->links() }}</div>
                @else
                    <p class="mt-6 text-center text-sm opacity-60">{{ __('davet.henuz_paylasim_yok') }}</p>
                @endif
            </div>
        @endif

        {{-- Büyüme döngüsü: davetiyeyi gören herkes Nisoya'yı görür --}}
        <a href="{{ url('/panel/etkinlikler') }}" class="mt-6 text-center text-xs text-stone-400 underline-offset-2 hover:underline dark:text-stone-500">
            {{ __('davet.nisoya_footer_prefix') }} <span class="font-semibold text-emerald-600 dark:text-emerald-400">Nisoya</span> {{ __('davet.nisoya_footer_suffix') }}
        </a>
    </div>
</body>
</html>

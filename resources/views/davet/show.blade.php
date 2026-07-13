<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
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
                    <div class="text-xs uppercase tracking-wider opacity-60">Tarih</div>
                    <div class="mt-0.5 text-lg font-semibold {{ $theme['accent'] }}">{{ $event->starts_at->translatedFormat('j F Y l') }}</div>
                    <div class="font-medium opacity-80">saat {{ $event->starts_at->format('H:i') }}</div>
                </div>
                @if ($event->venue_name || $event->venue_address)
                    <div class="pt-2">
                        <div class="text-xs uppercase tracking-wider opacity-60">Mekan</div>
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
                    {{ $myGuest ? 'Yanıtını güncelle' : 'Katılımını bildir (LCV)' }}
                </h2>

                <form method="POST" action="{{ route('davet.rsvp', $event->token) }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

                    <div>
                        <label for="name" class="block text-sm font-medium opacity-80">Adın Soyadın</label>
                        <input id="name" name="name" type="text" required minlength="2" maxlength="100"
                               value="{{ old('name', $myGuest?->name) }}" placeholder="ör. Fatma Yılmaz"
                               class="mt-1 w-full rounded-xl border-stone-300 bg-white px-3 py-2.5 text-stone-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                        @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="block text-sm font-medium opacity-80">Katılıyor musun?</span>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach ($statuses as $status)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="{{ $status->value }}" required class="peer sr-only"
                                           @checked(old('status', $myGuest?->status?->value) === $status->value)>
                                    <span class="block rounded-xl border border-stone-300 px-2 py-2.5 text-center text-sm font-medium transition peer-checked:border-emerald-500 peer-checked:bg-emerald-600 peer-checked:text-white dark:border-stone-600 dark:peer-checked:bg-emerald-500 dark:peer-checked:text-stone-900">
                                        {{ $status->getLabel() }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('status') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="party_size" class="block text-sm font-medium opacity-80">Kaç kişisiniz? <span class="opacity-60">(sen dahil)</span></label>
                            <input id="party_size" name="party_size" type="number" min="1" max="20" required
                                   value="{{ old('party_size', $myGuest?->party_size ?? 1) }}"
                                   class="mt-1 w-full rounded-xl border-stone-300 bg-white px-3 py-2.5 text-stone-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                            @error('party_size') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="note" class="block text-sm font-medium opacity-80">Not <span class="opacity-60">(ops.)</span></label>
                            <input id="note" name="note" type="text" maxlength="255"
                                   value="{{ old('note', $myGuest?->note) }}" placeholder="ör. çocuklu geliyoruz"
                                   class="mt-1 w-full rounded-xl border-stone-300 bg-white px-3 py-2.5 text-stone-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">
                            @error('note') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-xl px-4 py-3 font-semibold transition {{ $theme['button'] }}">
                        {{ $myGuest ? 'Yanıtımı Güncelle' : 'Yanıtımı Gönder' }}
                    </button>
                    <p class="text-center text-xs opacity-60">Yanıtın yalnızca etkinlik sahibine iletilir; bu sayfada isimler gösterilmez.</p>
                </form>
            </div>
        </div>

        {{-- Büyüme döngüsü: davetiyeyi gören herkes Nisoya'yı görür --}}
        <a href="{{ url('/panel/etkinlikler') }}" class="mt-6 text-center text-xs text-stone-400 underline-offset-2 hover:underline dark:text-stone-500">
            Bu davetiye <span class="font-semibold text-emerald-600 dark:text-emerald-400">Nisoya</span> ile hazırlandı — sen de ücretsiz oluştur 💌
        </a>
    </div>
</body>
</html>

<x-layouts.app title="Nisoya Nabzı — Nisoya">
    <div class="mx-auto max-w-4xl px-4 py-12">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            Nisoya Nabzı
        </span>
        <h1 class="mt-2 text-3xl font-bold text-stone-900 dark:text-stone-50">Topluluk birlikte büyüyor</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-300">
            Nisoya'yı büyüten sizsiniz — her davet, her ilan, her mesaj topluluğu bir adım öne taşıyor. Aşağıda bu ayın hedefini ve en çok katkı veren şehirleri görebilirsin.
        </p>

        @if (session('status'))
            <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        @if ($goal)
            <div class="mt-8 rounded-3xl border border-emerald-200 bg-emerald-50/50 p-6 dark:border-emerald-900/40 dark:bg-emerald-950/20 sm:p-8">
                <h2 class="text-xl font-bold text-stone-900 dark:text-stone-50">{{ $goal['baslik'] }}: {{ $goal['mevcut'] }} / {{ $goal['hedef'] }}</h2>
                @if ($goal['odul'])
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ $goal['odul'] }}</p>
                @endif
                <div class="mt-4 h-4 w-full overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <div class="h-full rounded-full bg-emerald-600 transition-all duration-700 dark:bg-emerald-500" style="width: {{ $goal['yuzde'] }}%"></div>
                </div>
                <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">%{{ $goal['yuzde'] }} tamamlandı</p>
            </div>
        @else
            <div class="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400">
                Şu an aktif bir topluluk hedefi yok.
            </div>
        @endif

        <h2 class="mt-10 text-xl font-bold text-stone-900 dark:text-stone-50">🏅 Şehir Elçileri</h2>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Bu ay her şehirden en çok arkadaşını davet eden kişi.</p>

        @if ($ambassadors->isNotEmpty())
            <ol class="mt-4 space-y-2">
                @foreach ($ambassadors as $i => $ambassador)
                    <li class="flex items-center gap-3 rounded-xl border border-stone-200 bg-white p-3 dark:border-stone-800 dark:bg-stone-900">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <span class="font-semibold text-stone-800 dark:text-stone-100">{{ $ambassador->name }}</span>
                            <span class="text-sm text-stone-500 dark:text-stone-400"> · {{ $ambassador->city }}@if ($ambassador->country_code), {{ $ambassador->country_code }}@endif</span>
                        </div>
                        <span class="shrink-0 text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ $ambassador->referral_count }} davet</span>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400">
                Bu ay henüz kimse davet etmemiş — ilk şehir elçisi sen olabilirsin!
            </div>
        @endif

        @if ($storyWallEnabled)
            <h2 class="mt-10 text-xl font-bold text-stone-900 dark:text-stone-50">📖 Gurbet Günlüğü</h2>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Yurt dışında yaşamanın gerçek hikayeleri — tamamen anonim olarak paylaşılır, kimse yazarını göremez.</p>

            @if ($stories->isNotEmpty())
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($stories as $story)
                        <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                            <p class="text-sm leading-relaxed text-stone-700 dark:text-stone-200">{{ $story->body }}</p>
                            <p class="mt-2 text-xs text-stone-600 dark:text-stone-400">Anonim · {{ $story->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400">
                    Henüz onaylanmış bir hikaye yok — ilk hikayeyi sen paylaşabilirsin!
                </div>
            @endif

            <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-5 dark:border-stone-800 dark:bg-stone-800/50">
                @auth
                    @if ($myStoryStatus?->value === 'beklemede')
                        <p class="text-sm text-stone-600 dark:text-stone-300">✅ Hikayen alındı ve inceleniyor. Onaylanınca burada tamamen anonim olarak yayınlanacak.</p>
                    @else
                        @if ($myStoryStatus?->value === 'reddedildi')
                            <p class="text-sm text-stone-600 dark:text-stone-300">Son hikayen yayınlanmadı, ama yeni bir hikaye paylaşabilirsin:</p>
                        @else
                            <p class="text-sm font-medium text-stone-700 dark:text-stone-200">Sen de gurbetteki deneyimini anonim olarak paylaş:</p>
                        @endif

                        @if ($errors->has('body'))
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $errors->first('body') }}</p>
                        @endif

                        <form method="POST" action="{{ route('nabiz.stories.store') }}" class="mt-3">
                            @csrf
                            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                            <textarea name="body" rows="3" maxlength="600" minlength="20" required
                                placeholder="Örn: İlk yıl çok zorlandım ama Nisoya'da bulduğum bir tamirci sayesinde..."
                                class="w-full rounded-xl border border-stone-300 bg-white p-3 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100">{{ old('body') }}</textarea>
                            <button type="submit" class="mt-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800">
                                Paylaş
                            </button>
                        </form>
                    @endif
                @else
                    <p class="text-sm text-stone-600 dark:text-stone-300">
                        Hikayeni paylaşmak için <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">giriş yap</a>.
                    </p>
                @endauth
            </div>
        @endif

        <div class="mt-10 rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 p-6 text-center text-white sm:p-8">
            <h2 class="text-xl font-bold">Sen de topluluğu büyüt</h2>
            <p class="mx-auto mt-2 max-w-lg text-emerald-50">Arkadaşlarını davet et, şehrinin elçisi ol.</p>
            <a href="{{ auth()->check() ? route('panel.invite') : route('register') }}" class="mt-4 inline-block rounded-lg bg-white px-5 py-2.5 font-semibold text-emerald-700 transition hover:-translate-y-0.5 hover:shadow-lg">
                {{ auth()->check() ? 'Arkadaşını Davet Et' : 'Hemen Katıl' }}
            </a>
        </div>
    </div>
</x-layouts.app>

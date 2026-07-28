<x-layouts.app title="İletişim — Nisoya" description="Sorun, önerin veya bir şikayetin mi var? Nisoya ekibine buradan ulaş.">
    <div class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-50">İletişim</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-400">Soru, öneri veya bir sorun mu var? Aşağıdaki formu doldur, sana en kısa sürede dönüş yapalım.</p>

        <div class="mt-10 grid gap-8 lg:grid-cols-5">
            {{-- Form --}}
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
                    @if (session('status'))
                        <div class="mb-6 flex items-start gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                            <span aria-hidden="true">✅</span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
                        @csrf
                        @include('partials.honeypot')

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ad Soyad</label>
                                <input id="name" name="name" type="text" required maxlength="255"
                                       value="{{ old('name', auth()->user()->name ?? '') }}"
                                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300">E-posta</label>
                                <input id="email" name="email" type="email" required maxlength="255"
                                       value="{{ old('email', auth()->user()->email ?? '') }}"
                                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Telefon <span class="text-stone-600 dark:text-stone-400">(ops.)</span></label>
                                <input id="phone" name="phone" type="tel" maxlength="50" value="{{ old('phone') }}"
                                       class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                @error('phone') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Konu</label>
                                <select id="category" name="category" required
                                        class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    @foreach (\App\Enums\ContactCategory::cases() as $category)
                                        <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->getLabel() }}</option>
                                    @endforeach
                                </select>
                                @error('category') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mesajın</label>
                            <textarea id="message" name="message" rows="6" required minlength="10" maxlength="5000"
                                      placeholder="Nasıl yardımcı olabiliriz?"
                                      class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">{{ old('message') }}</textarea>
                            @error('message') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 sm:w-auto dark:bg-emerald-500 dark:text-stone-900">
                            Mesajı Gönder
                        </button>
                    </form>
                </div>
            </div>

            {{-- İletişim bilgileri --}}
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="text-2xl">✉️</div>
                    <h2 class="mt-2 font-semibold text-stone-800 dark:text-stone-100">E-posta</h2>
                    <a href="mailto:{{ setting('iletisim.eposta') }}" class="mt-1 block text-sm text-emerald-700 hover:underline dark:text-emerald-400">{{ setting('iletisim.eposta') }}</a>
                </div>

                @if (setting('iletisim.telefon'))
                    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="text-2xl">📞</div>
                        <h2 class="mt-2 font-semibold text-stone-800 dark:text-stone-100">Telefon</h2>
                        <a href="tel:{{ setting('iletisim.telefon') }}" class="mt-1 block text-sm text-emerald-700 hover:underline dark:text-emerald-400">{{ setting('iletisim.telefon') }}</a>
                    </div>
                @endif

                @if (setting('iletisim.adres'))
                    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="text-2xl">📍</div>
                        <h2 class="mt-2 font-semibold text-stone-800 dark:text-stone-100">Adres</h2>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ setting('iletisim.adres') }}</p>
                    </div>
                @endif

                @if (setting('iletisim.calisma_saatleri'))
                    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="text-2xl">🕐</div>
                        <h2 class="mt-2 font-semibold text-stone-800 dark:text-stone-100">Yanıt süresi</h2>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ setting('iletisim.calisma_saatleri') }}</p>
                    </div>
                @endif

                <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <div class="text-2xl">🛟</div>
                    <h2 class="mt-2 font-semibold text-stone-800 dark:text-stone-100">Yardım</h2>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Sık sorulan sorulara <a href="{{ url('/sss') }}" class="text-emerald-700 hover:underline dark:text-emerald-400">SSS sayfasından</a> göz atabilirsin.</p>
                </div>

                @if (setting('footer.sosyal_instagram') || setting('footer.sosyal_facebook') || setting('footer.sosyal_whatsapp'))
                    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="font-semibold text-stone-800 dark:text-stone-100">Sosyal medya</h2>
                        <div class="mt-2 flex items-center gap-3 text-xl">
                            @if (setting('footer.sosyal_instagram'))
                                <a href="{{ setting('footer.sosyal_instagram') }}" target="_blank" rel="noopener noreferrer" class="hover:opacity-70" aria-label="Instagram">📷</a>
                            @endif
                            @if (setting('footer.sosyal_facebook'))
                                <a href="{{ setting('footer.sosyal_facebook') }}" target="_blank" rel="noopener noreferrer" class="hover:opacity-70" aria-label="Facebook">📘</a>
                            @endif
                            @if (setting('footer.sosyal_whatsapp'))
                                <a href="{{ setting('footer.sosyal_whatsapp') }}" target="_blank" rel="noopener noreferrer" class="hover:opacity-70" aria-label="WhatsApp">💬</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>

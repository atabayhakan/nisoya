<x-layouts.app>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-emerald-50 to-stone-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                    🌍 Yurt dışındaki Türkler için
                </span>
                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-stone-900 sm:text-5xl">
                    Yeteneğini paraya dönüştür,<br>
                    <span class="text-emerald-600">kendi insanından</span> hizmet al
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-stone-600">
                    İngilizce ders mi veriyorsun, taşınmada mı yardım ediyorsun, ev yemeği mi yapıyorsun?
                    Nisoya'da yeteneğini ilan et; bulunduğun ülkedeki Türklerle güvenle buluş.
                </p>

                {{-- Arama kutusu (M4'te işlevsel olacak) --}}
                <form action="{{ url('/ilanlar') }}" method="GET" class="mx-auto mt-8 flex max-w-2xl flex-col gap-2 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-stone-200 sm:flex-row">
                    <input type="text" name="q" placeholder="Ne arıyorsun? (ör. İngilizce öğretmeni)"
                           class="flex-1 rounded-xl border-0 bg-transparent px-4 py-3 text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <select name="ulke" class="rounded-xl border-0 bg-stone-50 px-4 py-3 text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Tüm ülkeler</option>
                        <option value="DE">🇩🇪 Almanya</option>
                        <option value="NL">🇳🇱 Hollanda</option>
                        <option value="GB">🇬🇧 İngiltere</option>
                        <option value="FR">🇫🇷 Fransa</option>
                        <option value="AT">🇦🇹 Avusturya</option>
                        <option value="BE">🇧🇪 Belçika</option>
                        <option value="SE">🇸🇪 İsveç</option>
                        <option value="CH">🇨🇭 İsviçre</option>
                        <option value="US">🇺🇸 ABD</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:bg-emerald-700">
                        Ara
                    </button>
                </form>
                <p class="mt-3 text-sm text-stone-400">Popüler: dil dersi · taşınma · ev yemeği · web tasarım · tercüme</p>
            </div>
        </div>
    </section>

    {{-- Kategoriler --}}
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="flex items-end justify-between">
            <h2 class="text-2xl font-bold text-stone-900">Kategoriler</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">Tümünü gör →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $cat)
                <a href="{{ route('listings.category', $cat->slug) }}"
                   class="group flex flex-col items-center gap-2 rounded-2xl border border-stone-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                    <span class="text-3xl">{{ $cat->icon }}</span>
                    <span class="text-sm font-medium text-stone-700 group-hover:text-emerald-700">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Yeni ilanlar --}}
    @if ($latestListings->isNotEmpty())
        <section class="bg-white py-14">
            <div class="mx-auto max-w-6xl px-4">
                <div class="flex items-end justify-between">
                    <h2 class="text-2xl font-bold text-stone-900">Yeni ilanlar</h2>
                    <a href="{{ route('listings.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">Tümünü gör →</a>
                </div>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($latestListings as $listing)
                        @include('partials.listing-card', ['listing' => $listing])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Nasıl çalışır --}}
    <section class="bg-white py-14">
        <div class="mx-auto max-w-6xl px-4">
            <h2 class="text-center text-2xl font-bold text-stone-900">Nasıl çalışır?</h2>
            <div class="mt-10 grid gap-8 md:grid-cols-3">
                @php
                    $adimlar = [
                        ['no' => '1', 'baslik' => 'Ücretsiz kayıt ol', 'metin' => 'Birkaç dakikada hesabını oluştur, bulunduğun ülke ve şehri seç.'],
                        ['no' => '2', 'baslik' => 'İlanını ver veya ara', 'metin' => 'Yeteneğini/hizmetini ilan et ya da ihtiyacın olan hizmeti ara.'],
                        ['no' => '3', 'baslik' => 'Mesajlaş, anlaş', 'metin' => 'Karşı tarafla mesajlaş, güvenle anlaş. Ödeme aranızda.'],
                    ];
                @endphp
                @foreach ($adimlar as $a)
                    <div class="text-center">
                        <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-600 text-lg font-bold text-white">{{ $a['no'] }}</div>
                        <h3 class="mt-4 text-lg font-semibold text-stone-900">{{ $a['baslik'] }}</h3>
                        <p class="mt-2 text-sm text-stone-600">{{ $a['metin'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-6xl px-4 py-14">
        <div class="rounded-3xl bg-emerald-600 px-6 py-12 text-center text-white sm:px-12">
            <h2 class="text-2xl font-bold sm:text-3xl">Bir yeteneğin mutlaka vardır.</h2>
            <p class="mx-auto mt-3 max-w-xl text-emerald-50">Hadi onu paraya dönüştür. İlan vermek tamamen ücretsiz.</p>
            <a href="{{ url('/kayit') }}" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-semibold text-emerald-700 transition hover:bg-emerald-50">Hemen Başla</a>
        </div>
    </section>
</x-layouts.app>

{{--
    Rehber — işlem detay sayfası (/de/koeln/vekaletname). Rehberin asıl
    değer ürettiği yer: evrak listesi + süre + ücret + resmî kaynak.

    K7 sözleşmesi görünür hâlde: "son doğrulama" tarihi ve resmî kaynak
    birincil CTA — kullanıcı bilgiye güvenmeden önce yaşını görebilmeli.
--}}
<x-layouts.app
    :title="$islemKaydi->islemTuru->ad.' — '.$temsilcilik->ad.' — '.setting('genel.site_adi')"
    :description="$temsilcilik->ad.' '.$islemKaydi->islemTuru->ad.' işlemi: gerekli evraklar, süre, ücret ve resmî kaynak.'"
>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:py-10">
        <nav class="text-sm text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:underline">Ana sayfa</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('rehber.ulke', strtolower($country->code)) }}" class="hover:underline">{{ $country->name_tr }}</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('rehber.temsilcilik', [strtolower($country->code), $temsilcilik->slug]) }}" class="hover:underline">{{ $temsilcilik->sehir }}</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700 dark:text-stone-200">{{ $islemKaydi->islemTuru->ad }}</span>
        </nav>

        <x-rehber.ulke-secici :aktif="$country" class="mt-3" />

        <h1 class="mt-4 text-3xl font-bold text-stone-900 dark:text-stone-50">{{ $islemKaydi->islemTuru->ad }}</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-300">{{ $temsilcilik->ad }}</p>

        <div class="mt-6 flex flex-wrap gap-3 text-sm">
            @if ($islemKaydi->sure_metni)
                <span class="rounded-full bg-stone-100 px-3 py-1.5 text-stone-700 dark:bg-stone-800 dark:text-stone-200">⏱ Süre: {{ $islemKaydi->sure_metni }}</span>
            @endif
            @if ($islemKaydi->ucret_metni)
                <span class="rounded-full bg-stone-100 px-3 py-1.5 text-stone-700 dark:bg-stone-800 dark:text-stone-200">💶 Ücret: {{ $islemKaydi->ucret_metni }}</span>
            @endif
            <span class="rounded-full bg-stone-100 px-3 py-1.5 text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                Son doğrulama:
                {{ $islemKaydi->dogrulanma_tarihi?->translatedFormat('d F Y') ?? 'henüz doğrulanmadı' }}
            </span>
        </div>

        @if (! empty($islemKaydi->evraklar))
            <h2 class="mt-10 text-xl font-semibold text-stone-900 dark:text-stone-50">Gerekli evraklar</h2>
            <ul class="mt-4 space-y-3">
                @foreach ($islemKaydi->evraklar as $evrak)
                    <li class="flex gap-3 rounded-xl border border-stone-200 bg-white p-4 dark:border-stone-700 dark:bg-stone-800/50">
                        <span class="mt-0.5 text-emerald-700 dark:text-emerald-400" aria-hidden="true">✔</span>
                        <span>
                            {{-- `?? ''` YOK, bilerek: modeldeki düzenleyici
                                 adı boş olan maddeyi zaten atıyor. Buraya
                                 yedek koymak, veri şekli yine bozulursa onu
                                 SESSİZCE boş satıra çevirirdi — canlıda tam
                                 olarak bu oldu (bkz. TemsilcilikIslemi). --}}
                            <span class="font-medium text-stone-900 dark:text-stone-50">{{ $evrak['ad'] }}</span>
                            @if (! empty($evrak['not']))
                                <span class="mt-0.5 block text-sm text-stone-500 dark:text-stone-400">{{ $evrak['not'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($islemKaydi->notlar)
            <h2 class="mt-8 text-xl font-semibold text-stone-900 dark:text-stone-50">Bilmen gerekenler</h2>
            <p class="mt-3 whitespace-pre-line leading-relaxed text-stone-700 dark:text-stone-200">{{ $islemKaydi->notlar }}</p>
        @endif

        {{-- Resmî kaynak BİRİNCİL eylemdir (K7): biz özetiz, kaynak orası. --}}
        <div class="mt-10 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-800 dark:bg-emerald-950/40">
            <p class="font-semibold text-stone-900 dark:text-stone-50">İşleme gitmeden önce resmî kaynaktan teyit et</p>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">Evrak listeleri, ücretler ve süreler değişebilir. Bu sayfa bilgilendirme amaçlıdır.</p>
            <a href="{{ $islemKaydi->resmi_kaynak_url }}" target="_blank" rel="noopener nofollow"
                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 font-medium text-white transition hover:bg-emerald-800">
                Resmî kaynağı aç ↗
            </a>
        </div>

        {{-- "Güncel mi?" — rehberin bağışıklık sistemi. Anonim; honeypot + rate limit rotada. --}}
        <div class="mt-10 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-700 dark:bg-stone-800/50">
            @if (session('rehber_tesekkur'))
                <p class="font-medium text-emerald-700 dark:text-emerald-400">{{ session('rehber_tesekkur') }}</p>
            @else
                <p class="font-semibold text-stone-900 dark:text-stone-50">Bu bilgi güncel mi?</p>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Bu işlemi yakın zamanda yaptıysan ve bir şey değiştiyse haber ver — herkes için düzeltelim.</p>

                <form method="POST" action="{{ route('rehber.geribildirim', $islemKaydi) }}" class="mt-4 space-y-3">
                    @csrf
                    @include('partials.honeypot')

                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\RehberGeriBildirimi::TURLER as $deger => $etiket)
                            <label class="cursor-pointer">
                                <input type="radio" name="tur" value="{{ $deger }}" class="peer sr-only" @checked($loop->first)>
                                <span class="inline-block rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 dark:border-stone-600 dark:text-stone-300 dark:peer-checked:border-emerald-500 dark:peer-checked:bg-emerald-950/40 dark:peer-checked:text-emerald-300">{{ $etiket }}</span>
                            </label>
                        @endforeach
                    </div>

                    <textarea name="metin" rows="2" maxlength="1000"
                        placeholder="İstersen kısaca anlat (zorunlu değil)"
                        class="block w-full rounded-xl border-stone-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"></textarea>
                    @error('tur')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('metin')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                    <button type="submit"
                        class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-800">
                        Gönder
                    </button>
                </form>
            @endif
        </div>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Rehberi', 'item' => route('rehber.ulke', strtolower($country->code))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $temsilcilik->ad, 'item' => route('rehber.temsilcilik', [strtolower($country->code), $temsilcilik->slug])],
            ['@type' => 'ListItem', 'position' => 4, 'name' => $islemKaydi->islemTuru->ad, 'item' => route('rehber.islem', [strtolower($country->code), $temsilcilik->slug, $islemKaydi->islemTuru->slug])],
        ],
    ]" />

    <x-json-ld type="GovernmentService" :data="[
        'name' => $islemKaydi->islemTuru->ad,
        'serviceType' => $islemKaydi->islemTuru->ad,
        'provider' => ['@type' => 'GovernmentOrganization', 'name' => $temsilcilik->ad],
        'areaServed' => ['@type' => 'Country', 'name' => $country->name_tr],
        'url' => route('rehber.islem', [strtolower($country->code), $temsilcilik->slug, $islemKaydi->islemTuru->slug]),
    ]" />
</x-layouts.app>

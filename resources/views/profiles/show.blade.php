<x-layouts.app
    :title="$user->name.' — Nisoya'"
    :description="$user->bio ? \Illuminate\Support\Str::limit(strip_tags($user->bio), 150) : ($user->jobCategory ? $user->jobCategory->name.' — '.$user->name.' Nisoya üzerinde hizmet veriyor.' : $user->name.' — Nisoya üzerinde yetenek ve hizmet sunuyor.')"
    :ogImage="$user->avatarDisplayPath() ? \Illuminate\Support\Facades\Storage::url($user->avatarDisplayPath()) : null"
    :noindex="$noindex"
>
    {{-- ÖRNEK PROFİL BANDI.

         Sayfadaki her sayı (aktif ilan, ★ değerlendirme, tamamlanan anlaşma)
         bu üyenin kendi verisinden türüyor; örnek bir üyede hepsi örnektir.
         Kartların üstündeki rozet tek tek doğru söylüyordu ama SAYILAR onu
         okumuyordu — sayfayı bütün olarak etiketlemek bu boşluğu kapatır,
         ilan detayındaki bandın yaptığı işin aynısı. --}}
    @if ($user->is_demo)
        <div class="mx-auto max-w-6xl px-4 pt-6">
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-950/50">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Bu bir ÖRNEK profildir</p>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    Nisoya demo verisidir; gerçek bir kişiye ait değildir. Sayfadaki ilanlar, puanlar ve
                    anlaşma sayıları örnektir ve mesaj gönderilemez.
                </p>
            </div>
        </div>
    @endif

    {{-- JSON-LD: Person — ÖRNEK üyede HİÇ basılmaz.

         Yapılandırılmış veri arama motoruna "bu gerçek bir kişi, puanı şu"
         diye bildirim yapar; uydurma bir kişi için AggregateRating yayınlamak
         noindex ile bile savunulabilir değil (zengin sonuç beslemeleri sayfa
         etiketinden bağımsız toplanabiliyor). Kaynağında kesilir. --}}
    @unless ($user->is_demo)
    <x-json-ld type="Person" :data="array_filter([
        'name' => $user->name,
        'description' => $user->bio ? \Illuminate\Support\Str::limit(strip_tags($user->bio), 300) : null,
        'image' => $user->avatarDisplayPath() ? \Illuminate\Support\Facades\Storage::url($user->avatarDisplayPath()) : null,
        'url' => route('profiles.show', $user->username),
        'jobTitle' => $user->jobCategory?->name,
        'address' => $user->city || $user->country_code ? array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => $user->city,
            'addressCountry' => $user->country_code,
        ]) : null,
        'aggregateRating' => $rating['count'] > 0 ? [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating['avg'],
            'reviewCount' => $rating['count'],
        ] : null,
    ])" />
    @endunless

    @php
        $guven = $user->trustProfile();
        $anlasma = $guven['completed_deals'] ?? 0;
    @endphp

    {{-- ---------------------------------------------------------------------
         DÜZEN (2026-08-04 yeniden tasarım)

         Önceki hâlde her şey TEK bir kartın içindeydi: kimlik, mesaj kutusu,
         ödeme kanalları, güvenlik uyarısı, paylaşım düğmeleri ve dolandırıcılık
         ihbarı — ~170 satır, hepsi aynı görsel ağırlıkta. Ziyaretçi "bu kim ve
         ona nasıl ulaşırım" sorusunun cevabını gürültünün içinde arıyordu.

         Yeni düzen: kapak bandı + üstüne binen kimlik kartı, altında gerçek
         sayılardan oluşan üç kart, sonra iki sütun. Sol sütun KİŞİ (kim olduğu,
         ne yaptığı, işleri), sağ sütun EYLEM ve KANIT (ona yaz, ilanları,
         değerlendirmeler).

         DEĞİŞTİRİLMEYEN KURAL — mesaj → ödeme → uyarı sırası. Bu sıra bir
         güvenlik kararıdır (K-A/K-D): ödeme kanalları uyarı kartından ÖNCE ve
         ondan KOPUK durursa ziyaretçi "PayPal'da Mal ve Hizmetler seç"
         uyarısını görmeden satıcının ödeme sayfasına çıkabiliyor. Üçü aynı
         sütunda ve bu sırada duruyor; ödeme kanallarını sol sütuna almak
         görsel olarak daha dengeli olurdu ama o bitişikliği kırardı.

         Kapak görseli YÜKLENMİYOR: kullanıcıda kapak alanı yok ve bunun için
         yeni bir yükleme akışı açmak bu turun işi değil. Bant, marka renginden
         türeyen bir degrade — sahte bir fotoğraf değil.
    --------------------------------------------------------------------- --}}

    <div class="mx-auto max-w-6xl px-4 pb-12">
        {{-- Kapak bandı --}}
        <div class="h-32 rounded-b-3xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-400 sm:h-44 dark:from-emerald-800 dark:via-emerald-700 dark:to-teal-700"></div>

        {{-- Kimlik kartı — kapağın üstüne biniyor --}}
        <div class="-mt-14 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:-mt-16 sm:p-6 dark:border-stone-800 dark:bg-stone-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="shrink-0 rounded-full ring-4 ring-white dark:ring-stone-900">
                    <x-avatar :user="$user" size="h-24 w-24" text="text-4xl" />
                </div>

                <div class="min-w-0 flex-1">
                    {{-- Rozetler h1'in DIŞINDA: içerideyken ekran okuyucu başlığı
                         "Hakan Güvenilir Doğrulanmış" diye tek parça okuyordu.
                         Başlık kişinin adıdır; rozetler onun yanındaki ayrı
                         bilgilerdir. --}}
                    <h1 class="text-2xl font-bold text-stone-900 sm:text-3xl dark:text-stone-50">{{ $user->name }}</h1>

                    @if ($user->jobCategory)
                        <p class="mt-0.5 text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ $user->jobCategory->name }}</p>
                    @endif

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <x-trust-badge :user="$user" />
                        @if ($user->is_verified)
                            <span class="inline-flex items-center gap-1 text-sm text-emerald-700 dark:text-emerald-400">
                                <x-verified-badge size="base" /> Doğrulanmış
                            </span>
                        @endif
                    </div>

                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-stone-500 dark:text-stone-400">
                        @if ($user->city)<span>{{ $user->city }}</span><span aria-hidden="true">·</span>@endif
                        <span>{{ $user->country_code }}</span>
                        <span aria-hidden="true">·</span>
                        {{-- "2026'dan beri üye" gibi bir ifade DENENDİ ve geri alındı:
                             Türkçe ek ünlü uyumuna göre değişiyor (2026 → altı → "dan",
                             2025 → beş → "ten") ve yıla göre yanlış ek basılıyordu.
                             "Üyelik:" öneki bu tuzağı tamamen atlatıyor. --}}
                        <span>Üyelik: {{ $user->created_at->translatedFormat('F Y') }}</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Sayı kartları. Hepsi GERÇEK veriden türüyor; ilanı/değerlendirmesi
             olmayan kart hiç basılmıyor — sıfır gösteren bir kart kişinin
             aleyhine tanıklık eder. --}}
        {{-- DİKKAT: burada `$listings->total()` KULLANILMAZ. Sekme "geçmiş"e
             alındığında o sayı geçmiş ilanları sayar ama etiket "aktif ilan"
             der — sayfa sessizce yanlış bir şey söylemiş olurdu. Kart her
             zaman GÜNCEL sayıyı gösterir, hangi sekmede olursak olalım. --}}
        @php
            $kartlar = array_filter([
                $ilanSayilari['guncel'] > 0 ? ['deger' => $ilanSayilari['guncel'], 'etiket' => 'aktif ilan'] : null,
                $rating['count'] > 0 ? ['deger' => '★ '.$rating['avg'], 'etiket' => $rating['count'].' değerlendirme'] : null,
                $anlasma > 0 ? ['deger' => $anlasma, 'etiket' => 'tamamlanan anlaşma'] : null,
            ]);
        @endphp

        @if ($kartlar !== [])
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($kartlar as $kart)
                    <div class="rounded-2xl border border-stone-200 bg-white px-4 py-3 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="text-xl font-bold text-stone-900 dark:text-stone-50">{{ $kart['deger'] }}</div>
                        <div class="text-xs text-stone-500 dark:text-stone-400">{{ $kart['etiket'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- ---------------- SOL SÜTUN: kişi ---------------- --}}
            <div class="space-y-6 lg:col-span-1">
                @if ($user->bio || $user->skills)
                    <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="font-semibold text-stone-800 dark:text-stone-100">Hakkında</h2>

                        @if ($user->bio)
                            <p class="mt-2 text-sm leading-relaxed text-stone-600 dark:text-stone-300">{{ $user->bio }}</p>
                        @endif

                        @if ($user->skills)
                            <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-stone-100 pt-3 dark:border-stone-800">
                                @foreach ($user->skills as $skill)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif

                @if ($user->portfolioItems->isNotEmpty())
                    <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="font-semibold text-stone-800 dark:text-stone-100">Portfolyo</h2>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            @foreach ($user->portfolioItems as $item)
                                <a href="{{ $item->url('large') }}" target="_blank" rel="noopener" class="group relative overflow-hidden rounded-xl border border-stone-200 dark:border-stone-800">
                                    <img src="{{ $item->url('medium') }}" alt="{{ $item->caption }}" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105">
                                    @if ($item->caption)
                                        <div class="absolute inset-x-0 bottom-0 truncate bg-stone-900/60 px-2 py-1 text-2xs text-white">{{ $item->caption }}</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="font-semibold text-stone-800 dark:text-stone-100">Profili paylaş</h2>
                    <div class="mt-3">
                        @include('partials.share-buttons', ['shareUrl' => route('profiles.show', $user->username), 'shareText' => $user->name.' — Nisoya'])
                    </div>

                    @auth
                        @if (auth()->id() !== $user->id)
                            <details class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800" @if ($errors->has('note')) open @endif>
                                <summary class="cursor-pointer list-none text-xs text-stone-600 hover:text-red-600 dark:text-stone-400 dark:hover:text-red-400">
                                    🚩 Bu kullanıcıyı dolandırıcılık için bildir
                                </summary>
                                <form method="POST" action="{{ route('users.report-fraud', $user->username) }}" class="mt-2">
                                    @csrf
                                    @include('partials.honeypot')
                                    <label for="fraud-note" class="sr-only">Açıklama</label>
                                    <textarea id="fraud-note" name="note" rows="3" required minlength="10" maxlength="1000"
                                        placeholder="Ne oldu? Ödeme detayı, mesaj, tarih... (en az 10 karakter)"
                                        class="w-full rounded-lg border-stone-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('note') }}</textarea>
                                    @error('note')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                    <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">Bildirimin gizli tutulur; ekibimiz inceler. Asılsız ihbar kötüye kullanımdır.</p>
                                    <button type="submit" class="mt-2 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Bildir</button>
                                </form>
                            </details>
                        @endif
                    @endauth
                </section>
            </div>

            {{-- ---------------- SAĞ SÜTUN: eylem ve kanıt ---------------- --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- BİRİNCİL EYLEM: doğrudan mesaj.

                     Bu sayfada eylem çağrısı HİÇ YOKTU. Mesajlaşmanın tek yolu
                     bir İLAN üzerindendi, dolayısıyla aktif ilanı olmayan bir
                     yetenek /adaylar listesinde görünüyor, profiline
                     girilebiliyor ama kendisine ULAŞILAMIYORDU. --}}
                @auth
                    @if (auth()->id() !== $user->id)
                        <form method="POST" action="{{ route('messages.startWithUser', $user->username) }}"
                              class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                            @csrf
                            {{-- Honeypot: rota 'honeypot' middleware'i kullanıyor
                                 (bkz. HoneypotMiddleware docblock — alan adı "website"). --}}
                            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                            <label for="profil-mesaj" class="block font-semibold text-stone-800 dark:text-stone-100">
                                {{ \Illuminate\Support\Str::before($user->name, ' ') }} kişisine yaz
                            </label>
                            <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">Türkçe yaz, doğrudan kendisine ulaşsın.</p>
                            <textarea id="profil-mesaj" name="body" rows="3" required maxlength="2000"
                                      placeholder="Merhaba, ..."
                                      class="mt-2 w-full rounded-lg border-stone-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('body') }}</textarea>
                            @error('body')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                    class="mt-2 inline-flex min-h-11 items-center rounded-lg bg-emerald-700 px-5 text-sm font-semibold text-white hover:bg-emerald-800 md:min-h-0 md:py-2.5 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                                Mesaj gönder
                            </button>
                        </form>
                    @endif
                @else
                    <div class="rounded-2xl border border-stone-200 bg-white p-5 text-sm shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Giriş yap</a>
                        <span class="text-stone-600 dark:text-stone-300">— {{ \Illuminate\Support\Str::before($user->name, ' ') }} kişisine doğrudan yazabilmek için.</span>
                    </div>
                @endauth

                {{-- ÖDEME KANALLARI — MESAJDAN SONRA, UYARIDAN HEMEN ÖNCE.

                     Bu sıra projenin KENDİ deseni ve bir güvenlik kararıdır:
                       listings/show.blade.php        mesaj → ödeme → uyarı
                       vitrin/listings/show.blade.php mesaj → ödeme → uyarı
                     Ödeme kanallarını sol sütuna almak görsel olarak daha
                     dengeli olurdu ama uyarı kartıyla bitişikliği kırardı ve
                     ziyaretçi uyarıyı görmeden satıcının ödeme sayfasına
                     çıkabilirdi — ön ödeme dolandırıcılığının istediği akış.

                     DOM sırası = görsel sıra: bu bölümde `order-*` sınıfı yok. --}}
                @if ($user->paymentLinks->isNotEmpty())
                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-stone-600 dark:text-stone-400">Kabul ettiği ödeme yöntemleri:</span>
                            @foreach ($user->paymentLinks as $link)
                                @if ($link->detailIsLink())
                                    <a href="{{ $link->detail }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — kendi ödeme sayfasına git">
                                        <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} ↗
                                    </a>
                                @elseif ($link->qr_path)
                                    <a href="{{ Storage::url($link->qr_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — QR kodu gör">
                                        <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} 🔳
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                        <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }}@if ($link->detail) — {{ $link->detail }}@endif
                                    </span>
                                @endif
                            @endforeach
                        </div>

                        <div class="mt-3">
                            @include('partials.payment-safety-card', ['seller' => $user])
                        </div>
                    </div>
                @endif

                {{-- İlanları --}}
                <section class="scroll-mt-20" id="ilanlar">
                    <h2 class="text-lg font-bold text-stone-900 dark:text-stone-50">{{ $user->name }} kullanıcısının ilanları</h2>

                    {{-- GÜNCEL / GEÇMİŞ sekmesi. Geçmiş sekmesi yalnız gerçekten
                         geçmiş ilan varsa basılır: boş bir sekme, tıklandığında
                         hiçbir şey olmayacak bir söz verirdi. --}}
                    @if ($ilanSayilari['gecmis'] > 0)
                        <div class="mt-3 inline-flex rounded-xl border border-stone-200 bg-white p-1 dark:border-stone-800 dark:bg-stone-900" role="tablist">
                            <a href="{{ route('profiles.show', $user->username) }}#ilanlar"
                               @if ($durum === 'guncel') aria-current="page" @endif
                               class="rounded-lg px-3.5 py-1.5 text-sm font-bold transition {{ $durum === 'guncel' ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800' }}">
                                Güncel ({{ $ilanSayilari['guncel'] }})
                            </a>
                            <a href="{{ route('profiles.show', ['user' => $user->username, 'durum' => 'gecmis']) }}#ilanlar"
                               @if ($durum === 'gecmis') aria-current="page" @endif
                               class="rounded-lg px-3.5 py-1.5 text-sm font-bold transition {{ $durum === 'gecmis' ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800' }}">
                                Geçmiş ({{ $ilanSayilari['gecmis'] }})
                            </a>
                        </div>
                    @endif

                    @if ($durum === 'gecmis')
                        <p class="mt-3 text-sm font-medium text-stone-500 dark:text-stone-400">
                            Bu ilanlar yayından kalkmış. Açılabilirler ama mesaj gönderilemez.
                        </p>
                    @endif

                    @if ($listings->isNotEmpty())
                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            @foreach ($listings as $listing)
                                @include('partials.listing-card', ['listing' => $listing])
                            @endforeach
                        </div>
                        <div class="mt-6">{{ $listings->links() }}</div>
                    @else
                        <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center text-stone-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400">
                            {{ $durum === 'gecmis' ? 'Bu üyenin yayından kalkmış ilanı yok.' : 'Bu üyenin şu an aktif ilanı yok.' }}
                        </div>
                    @endif
                </section>

                {{-- Değerlendirmeler --}}
                <section class="scroll-mt-20" id="degerlendir">
                    <h2 class="text-lg font-bold text-stone-900 dark:text-stone-50">
                        Değerlendirmeler
                        @if ($rating['count'] > 0)<span class="text-amber-500 dark:text-amber-400">★ {{ $rating['avg'] }}</span> <span class="text-sm font-normal text-stone-600 dark:text-stone-400">({{ $rating['count'] }})</span>@endif
                    </h2>

                    @if (session('status'))
                        <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
                    @endif

                    @auth
                        @if ($canReview)
                            <form method="POST" action="{{ route('reviews.store', $user->username) }}" class="mt-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                                @csrf
                                <p class="text-sm font-medium text-stone-700 dark:text-stone-300">{{ $myReview ? 'Değerlendirmeni güncelle' : 'Bu üyeyi değerlendir' }}</p>
                                <div class="mt-2 flex flex-wrap items-end gap-3">
                                    <div>
                                        <label for="rating" class="block text-sm text-stone-600 dark:text-stone-300">Puan</label>
                                        <select id="rating" name="rating" class="mt-1 rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                            @for ($i = 5; $i >= 1; $i--)
                                                <option value="{{ $i }}" @selected(($myReview?->rating ?? 5) === $i)>{{ $i }} yıldız</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <textarea name="comment" rows="3" placeholder="Deneyimini paylaş (ops.)" class="mt-2 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">{{ $myReview?->comment }}</textarea>
                                @error('rating') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                <button type="submit" class="mt-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">{{ $myReview ? 'Güncelle' : 'Gönder' }}</button>
                            </form>
                        @elseif (auth()->id() !== $user->id)
                            <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-5 text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300">
                                {{-- Metin ReviewController'daki kapı kuralıyla aynı: değerlendirme,
                                     gerçekleşmiş bir etkileşimin beyanıdır. --}}
                                Değerlendirme bırakabilmek için aranızda iki tarafın da yazdığı bir konuşma ya da tamamlanmış bir anlaşma olması gerekir.
                                @if ($listings->isNotEmpty())
                                    @php($firstListing = $listings->first())
                                    <a href="{{ route('listings.show', [$firstListing, $firstListing->slug]) }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">Bir ilanına mesaj gönder</a>
                                @endif
                            </div>
                        @endif
                    @endauth

                    <div class="mt-4 space-y-3">
                        @forelse ($reviews as $review)
                            <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex flex-wrap items-center gap-1.5 font-medium text-stone-800 dark:text-stone-100">
                                        {{ $review->reviewer->name }}
                                        @if ($review->deal_id)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-1.5 py-0.5 text-2xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-900/40 dark:text-emerald-300" title="Bu değerlendirme, tamamlanmış bir anlaşmaya dayanıyor.">✓ Doğrulanmış işlem</span>
                                        @endif
                                    </span>
                                    <span class="shrink-0 text-amber-500 dark:text-amber-400">{{ str_repeat('★', $review->rating) }}<span class="text-stone-300 dark:text-stone-400">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                                </div>
                                @if ($review->comment)<p class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ $review->comment }}</p>@endif
                                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">{{ $review->created_at->translatedFormat('j F Y') }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-stone-500 dark:text-stone-400">Henüz değerlendirme yok. İlk değerlendiren sen ol!</p>
                        @endforelse

                        {{-- `yorum` adlı paginator: ilanlar varsayılan `page`
                             parametresini kullanıyor, ikisi aynı adı paylaşsaydı yorum
                             sayfasını çevirmek ilan listesini de kaydırırdı. --}}
                        @if ($reviews->hasPages())
                            <div class="mt-2">{{ $reviews->onEachSide(1)->links() }}</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layouts.app>

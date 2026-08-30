<x-layouts.app title="Nasıl Çalışır? — Nisoya">
    @php
        // Tek kaynak: hem görünür SSS bölümü hem FAQPage JSON-LD buradan üretilir.
        $faqs = [
            [
                'Nisoya ücretsiz mi?',
                'Evet. Nisoya\'da hesap oluşturmak, ilan vermek, iş ilanı paylaşmak ve hizmet aramak tamamen ücretsizdir. Nisoya kullanıcılarından hiçbir komisyon, listeleme veya üyelik ücreti talep etmez.'
            ],
            [
                'Ödemeyi Nisoya mı alıyor?',
                'Hayır. Nisoya bir ilan, kariyer ve iletişim platformudur; ödemelere doğrudan aracılık etmez veya emanet hesabı tutmaz. Anlaştığınız kişi veya işletmeyle ödeme yöntemini (Banka Havalesi, PayPal, Kaspi, MBANK, Zelle, Nakit vb.) doğrudan kendiniz belirlersiniz.'
            ],
            [
                'Nisoya kimler için?',
                'Yurt dışında yaşayan, çalışan, okuyan veya seyahat eden tüm Türkler için tasarlandı. Kendi dilinizde güvenilir bir usta, doktor, avukat, nakliyeci, kiralık ev veya ikinci el eşya bulabilir; aynı zamanda kendi yetenek ve hizmetlerinizi topluluğa sunabilirsiniz.'
            ],
            [
                'İlan vermek için ne gerekiyor?',
                'Yalnızca 1 dakikada ücretsiz bir hesap açıp e-posta adresinizi doğrulamanız yeterlidir. Ardından "+ İlan Ver" butonuna tıklayarak başlık, detaylı açıklama, fotoğraflar, konum ve kabul ettiğiniz ödeme yöntemlerini ekleyip ilanınızı anında yayınlayabilirsiniz.'
            ],
            [
                'Konsolosluk ve Yaşam Rehberi nedir?',
                'Yaşadığınız ülkedeki T.C. Büyükelçilik ve Başkonsolosluk işlemlerine (Pasaport, Vekaletname, Askerlik, Noter, Cenaze/Vefat vb.) ait güncel harçlar, randevu adımları ve gerekli evrakları resmi kaynaklardan derleyerek Türkçe ve anlaşılır rehberler olarak sunan bilgi merkezidir.'
            ],
            [
                'Dolandırıcılıktan nasıl korunurum?',
                'Hizmet tamamlanmadan veya ürün teslim alınmadan tüm ücreti peşin ödemekten kaçının. İlk kez çalıştığınız kişilerde aşamalı ödemeyi tercih edin. Elden teslim alımlarında halka açık güvenli noktalarda buluşun. Şüpheli bir durum sezdiğinizde ilanı veya profili "Şikayet Et" butonu ile anında bize bildirin.'
            ],
            [
                'Hangi ülkelerde kullanılıyor?',
                'Nisoya belirli bir ülkeyle sınırlı değildir. Almanya, Hollanda, Fransa, Avusturya, Belçika, İngiltere başta olmak üzere Avrupa genelinde, Kırgızistan, Kazakistan, Özbekistan, Azerbaycan gibi Türk dünyasında, ABD ve Körfez ülkelerinde aktif olarak kullanılmaktadır.'
            ],
        ];
    @endphp

    {{-- JSON-LD: FAQPage (Google zengin sonuçlar + AI asistanları için) --}}
    <x-json-ld type="FAQPage" :data="[
        'mainEntity' => collect($faqs)->map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ])->all(),
    ]" />

    <div class="mx-auto max-w-5xl px-4 py-12 sm:py-16">
        {{-- Başlık & Hero Alanı --}}
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200/90 bg-emerald-50/80 px-3.5 py-1 text-xs font-bold text-emerald-800 shadow-2xs dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                <span class="text-sm">🌍</span>
                <span>Yurt Dışındaki Türklerin Yaşam & Dayanışma Ekosistemi</span>
            </div>
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-stone-900 sm:text-4xl lg:text-5xl dark:text-stone-50">
                Nisoya Nasıl Çalışır?
            </h1>
            <p class="mt-4 text-base sm:text-lg leading-relaxed text-stone-600 dark:text-stone-300">
                Dünyanın dört bir yanındaki Türkleri bir araya getiren; aracısız, komisyonsuz ve güvenli ilan, kariyer, yaşam rehberi ve iletişim platformu.
            </p>
        </div>

        {{-- 3 Temel Değer Önerisi --}}
        <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-3.5 rounded-2xl border border-stone-200/90 bg-white p-4 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <x-heroicon-o-banknotes class="h-5 w-5" />
                </span>
                <div>
                    <div class="text-sm font-bold text-stone-900 dark:text-stone-100">%0 Komisyon</div>
                    <div class="text-xs text-stone-500 dark:text-stone-400">Ödemeler doğrudan taraflar arasındadır</div>
                </div>
            </div>
            <div class="flex items-center gap-3.5 rounded-2xl border border-stone-200/90 bg-white p-4 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                    <x-heroicon-o-shield-check class="h-5 w-5" />
                </span>
                <div>
                    <div class="text-sm font-bold text-stone-900 dark:text-stone-100">Doğrulanmış Topluluk</div>
                    <div class="text-xs text-stone-500 dark:text-stone-400">Puanlar, yorumlar ve onaylı rozetler</div>
                </div>
            </div>
            <div class="flex items-center gap-3.5 rounded-2xl border border-stone-200/90 bg-white p-4 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                    <x-heroicon-o-globe-alt class="h-5 w-5" />
                </span>
                <div>
                    <div class="text-sm font-bold text-stone-900 dark:text-stone-100">Küresel & Yerel</div>
                    <div class="text-xs text-stone-500 dark:text-stone-400">Bulunduğun şehirde Türkçe hizmet</div>
                </div>
            </div>
        </div>

        {{-- 2 Ana Akış: Hizmet Sunanlar vs Hizmet Arayanlar --}}
        <div class="mt-12">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-50">
                    İki Taraf İçin de Çok Kolay
                </h2>
                <p class="mt-1.5 text-sm text-stone-500 dark:text-stone-400">İster profesyonel bir hizmet sunun, ister aradığınız desteğe hemen ulaşın.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Kart 1: Hizmet/Ürün Sunuyorsan --}}
                <div class="flex flex-col justify-between rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-8 shadow-sm transition hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-2xl dark:bg-emerald-950/60">
                                🙋
                            </span>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                Hizmet Verenler İçin
                            </span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-stone-900 dark:text-stone-100">Hizmet / Ürün Sunuyorsan</h3>
                        <p class="mt-1.5 text-xs text-stone-500 dark:text-stone-400">Yeteneklerinizi, ürünlerinizi veya işletmenizi binlerce gurbetçiye duyurun.</p>

                        <ol class="mt-6 space-y-4">
                            <li class="flex items-start gap-3.5">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-700 text-xs font-bold text-white dark:bg-emerald-500 dark:text-stone-900">1</span>
                                <div class="text-sm leading-snug">
                                    <strong class="text-stone-900 dark:text-stone-100">Ücretsiz Kayıt Ol & Doğrula:</strong>
                                    <p class="mt-0.5 text-xs text-stone-600 dark:text-stone-400">1 dakikada hesabını oluştur, profil bilgilerini ve rozetlerini tamamla.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3.5">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-700 text-xs font-bold text-white dark:bg-emerald-500 dark:text-stone-900">2</span>
                                <div class="text-sm leading-snug">
                                    <strong class="text-stone-900 dark:text-stone-100">İlanını Yayınla:</strong>
                                    <p class="mt-0.5 text-xs text-stone-600 dark:text-stone-400">Hizmetini, fiyatını, konumunu ve kabul ettiğin ödeme yöntemlerini (IBAN, Kaspi, PayPal vb.) ekle.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3.5">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-700 text-xs font-bold text-white dark:bg-emerald-500 dark:text-stone-900">3</span>
                                <div class="text-sm leading-snug">
                                    <strong class="text-stone-900 dark:text-stone-100">Doğrudan Anlaş:</strong>
                                    <p class="mt-0.5 text-xs text-stone-600 dark:text-stone-400">Gelen mesajlara hızlıca yanıt ver, müşterilerinle aracısız anlaş ve kazancını doğrudan al.</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div class="mt-8 pt-5 border-t border-stone-100 dark:border-stone-800">
                        <a href="{{ route('panel.listings.create') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-brand transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                            <x-heroicon-o-plus class="h-4 w-4" />
                            <span>Hemen İlan Ver</span>
                        </a>
                    </div>
                </div>

                {{-- Kart 2: Hizmet/Ürün Arıyorsan --}}
                <div class="flex flex-col justify-between rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-8 shadow-sm transition hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-2xl dark:bg-blue-950/60">
                                🔍
                            </span>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                Hizmet Arayanlar İçin
                            </span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-stone-900 dark:text-stone-100">Hizmet / Ürün Arıyorsan</h3>
                        <p class="mt-1.5 text-xs text-stone-500 dark:text-stone-400">Gurbette ihtiyacınız olan güvenilir desteğe kendi dilinizde kolayca ulaşın.</p>

                        <ol class="mt-6 space-y-4">
                            <li class="flex items-start gap-3.5">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-700 text-xs font-bold text-white dark:bg-blue-500 dark:text-stone-900">1</span>
                                <div class="text-sm leading-snug">
                                    <strong class="text-stone-900 dark:text-stone-100">Filtrele & Haritada Ara:</strong>
                                    <p class="mt-0.5 text-xs text-stone-600 dark:text-stone-400">Ülke, şehir veya kategoriye göre arama yap; yakınındaki Türk uzmanları listele.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3.5">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-700 text-xs font-bold text-white dark:bg-blue-500 dark:text-stone-900">2</span>
                                <div class="text-sm leading-snug">
                                    <strong class="text-stone-900 dark:text-stone-100">Puan ve Yorumları İncele:</strong>
                                    <p class="mt-0.5 text-xs text-stone-600 dark:text-stone-400">İlan detaylarını, satıcının onay rozetlerini ve daha önceki müşteri değerlendirmelerini gör.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3.5">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-700 text-xs font-bold text-white dark:bg-blue-500 dark:text-stone-900">3</span>
                                <div class="text-sm leading-snug">
                                    <strong class="text-stone-900 dark:text-stone-100">Güvenle İletişime Geç:</strong>
                                    <p class="mt-0.5 text-xs text-stone-600 dark:text-stone-400">Satıcıya platform üzerinden tek tıkla mesaj at, şartları belirle ve güvenle anlaş.</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div class="mt-8 pt-5 border-t border-stone-100 dark:border-stone-800">
                        <a href="{{ url('/ilanlar') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-stone-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-stone-800 dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white">
                            <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                            <span>İlanları Keşfet</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nisoya'nın 4 Ana Ekosistem Bölümü --}}
        <div class="mt-16">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-50">
                    Nisoya Ekosistemi Neler Sunar?
                </h2>
                <p class="mt-1.5 text-sm text-stone-500 dark:text-stone-400">Yurt dışındaki yaşamınızı kolaylaştıracak tüm araçlar tek çatı altında.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-stone-200/90 bg-white p-5 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <x-heroicon-o-shopping-bag class="h-5 w-5" />
                    </span>
                    <h4 class="mt-3 text-sm font-bold text-stone-900 dark:text-stone-100">İlanlar & Pazar Yeri</h4>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Hizmet, ürün, kiralık oda/ev, vasıta ve ikinci el alım-satım ilanları.</p>
                </div>
                <div class="rounded-2xl border border-stone-200/90 bg-white p-5 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                        <x-heroicon-o-briefcase class="h-5 w-5" />
                    </span>
                    <h4 class="mt-3 text-sm font-bold text-stone-900 dark:text-stone-100">İş & Kariyer Havuzu</h4>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Şirketler için açık iş ilanları, yetenekler için profesyonel profil vitrini.</p>
                </div>
                <div class="rounded-2xl border border-stone-200/90 bg-white p-5 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                        <x-heroicon-o-book-open class="h-5 w-5" />
                    </span>
                    <h4 class="mt-3 text-sm font-bold text-stone-900 dark:text-stone-100">Konsolosluk & Yaşam Rehberi</h4>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Vekalet, pasaport, randevu, harçlar ve gurbette yaşam kılavuzları.</p>
                </div>
                <div class="rounded-2xl border border-stone-200/90 bg-white p-5 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">
                        <x-heroicon-o-map-pin class="h-5 w-5" />
                    </span>
                    <h4 class="mt-3 text-sm font-bold text-stone-900 dark:text-stone-100">Hizmet Haritası</h4>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Yaşadığın lokasyonda Türkçe konuşan esnaf ve uzmanları haritada bul.</p>
                </div>
            </div>
        </div>

        {{-- Ödemeler ve Güvenli Alışveriş Rehberi --}}
        <div class="mt-16 rounded-3xl border border-amber-200/90 bg-gradient-to-br from-amber-50/90 via-amber-50/50 to-white p-6 sm:p-8 dark:border-amber-900/60 dark:from-amber-950/30 dark:via-stone-900 dark:to-stone-900">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-amber-100 text-xl text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                    💳
                </span>
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-amber-950 dark:text-amber-200">Ödemeler ve Güvenlik Nasıl İşler?</h3>
                    <p class="text-xs text-amber-800/80 dark:text-amber-300/80">Aracısız, doğrudan ve şeffaf ödeme prensibi</p>
                </div>
            </div>

            <p class="mt-4 text-sm leading-relaxed text-stone-700 dark:text-stone-300">
                Nisoya bir ilan ve iletişim platformudur; ödemeyi kendi havuzunda toplamaz, komisyon veya gizli masraf kesmez. Anlaştığınız kişiyle ödeme yöntemini doğrudan kendiniz belirlersiniz. Üye profillerinde satıcının kabul ettiği yerel ve uluslararası ödeme rozetlerini inceleyebilirsiniz:
            </p>

            {{-- Ödeme Yöntemi Rozetleri --}}
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs dark:border-amber-900/60 dark:bg-stone-800 dark:text-stone-300">
                    🏦 Banka Havalesi / SEPA IBAN
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs dark:border-amber-900/60 dark:bg-stone-800 dark:text-stone-300">
                    🅿️ PayPal
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs dark:border-amber-900/60 dark:bg-stone-800 dark:text-stone-300">
                    🇰🇿 🇺🇿 🇰🇬 Kaspi / MBANK / Click / Payme
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs dark:border-amber-900/60 dark:bg-stone-800 dark:text-stone-300">
                    🇺🇸 Zelle / Venmo
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-white px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs dark:border-amber-900/60 dark:bg-stone-800 dark:text-stone-300">
                    💵 Elden Nakit Teslimat
                </span>
            </div>

            {{-- Güvenlik Kuralları Listesi --}}
            <div class="mt-6 rounded-2xl bg-white/80 p-4 sm:p-5 border border-amber-200/70 dark:bg-stone-900/80 dark:border-amber-900/40">
                <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900 dark:text-amber-300">💡 Güvenli Alışveriş İçin Altın Kurallar</h4>
                <ul class="mt-3 space-y-2 text-xs sm:text-sm text-stone-700 dark:text-stone-300">
                    <li class="flex items-start gap-2">
                        <x-heroicon-s-check-circle class="h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400 mt-0.5" />
                        <span><strong>Aşamalı Ödeme:</strong> Hizmet tamamlanmadan tüm ücreti peşin ödemeyin; mümkünse iş bitiminde veya aşamalı ödeyin.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-heroicon-s-check-circle class="h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400 mt-0.5" />
                        <span><strong>Küçük Adımlarla Güven:</strong> İlk defa çalıştığınız kişilerle büyük bütçeli işlerde temkinli olun, önceden yapılan işleri inceleyin.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-heroicon-s-check-circle class="h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400 mt-0.5" />
                        <span><strong>Güvenli Noktalarda Buluşma:</strong> Elden ürün alışverişlerinde halka açık, aydınlık ve merkezi yerleri tercih edin.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-heroicon-s-check-circle class="h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400 mt-0.5" />
                        <span><strong>Şikayet Mekanizması:</strong> Şüpheli veya kural dışı bir durum sezerseniz ilanı veya kullanıcıyı "Şikayet Et" ile anında bildirin.</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Sıkça Sorulan Sorular (Accordion) --}}
        <div class="mt-16">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-50">
                    Sıkça sorulan sorular
                </h2>
                <p class="mt-1.5 text-sm text-stone-500 dark:text-stone-400">Aklınıza takılan tüm soruların yanıtları burada.</p>
            </div>

            <div class="divide-y divide-stone-200/90 overflow-hidden rounded-3xl border border-stone-200/90 bg-white shadow-2xs dark:divide-stone-800 dark:border-stone-800 dark:bg-stone-900">
                @foreach ($faqs as [$soru, $cevap])
                    <details class="group">
                        <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 sm:px-6 sm:py-5 text-sm sm:text-base font-bold text-stone-900 marker:content-none hover:bg-stone-50 dark:text-stone-100 dark:hover:bg-stone-800/50">
                            <span>{{ $soru }}</span>
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-stone-100 text-stone-600 transition duration-200 group-open:rotate-180 group-open:bg-emerald-50 group-open:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:group-open:bg-emerald-950/60 dark:group-open:text-emerald-300">
                                <x-heroicon-o-chevron-down class="h-4 w-4" />
                            </span>
                        </summary>
                        <div class="px-5 pb-5 sm:px-6 sm:pb-6 text-xs sm:text-sm leading-relaxed text-stone-600 dark:text-stone-300 border-t border-stone-100 dark:border-stone-800/60 pt-3 bg-stone-50/40 dark:bg-stone-900/40">
                            {{ $cevap }}
                        </div>
                    </details>
                @endforeach
            </div>
        </div>

        {{-- Alt Eylem & Güven Alanı (CTA) --}}
        <div class="mt-16 rounded-3xl border border-emerald-200/90 bg-gradient-to-br from-emerald-50 via-white to-emerald-50/40 p-8 sm:p-10 text-center shadow-xs dark:border-emerald-900/50 dark:from-emerald-950/40 dark:via-stone-900 dark:to-stone-900">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-emerald-700 text-white shadow-brand dark:bg-emerald-500 dark:text-stone-900">
                <x-heroicon-s-sparkles class="h-6 w-6" />
            </div>
            <h2 class="mt-4 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-50">
                Gurbette Yalnız Değilsiniz
            </h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-stone-600 dark:text-stone-300">
                İster yeteneğinizi kazanca dönüştürün, ister ihtiyacınız olan güvenilir hizmete kendi dilinizde tek tıkla ulaşın.
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('panel.listings.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-3 text-sm font-bold text-white shadow-brand transition hover:-translate-y-0.5 hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                    <x-heroicon-o-plus class="h-4 w-4 stroke-2" />
                    <span>Ücretsiz İlan Ver</span>
                </a>
                <a href="{{ url('/ilanlar') }}" class="inline-flex items-center gap-2 rounded-xl border border-stone-300 bg-white px-6 py-3 text-sm font-bold text-stone-700 shadow-2xs transition hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                    <span>İlanları İncele</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>

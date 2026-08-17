{{--
    GÖRÜNÜM VE TEMA — 2026-08-06'da beş ajanlı denetimle yeniden yazıldı.

    ---------------------------------------------------------------------------
    NEDEN YENİDEN YAZILDI

    Sayfanın en büyük sorunu görünüm değil DOĞRULUKtu: kodun yapmadığı şeyleri
    anlatıyordu. "Mat gece siyahı zemin" diyordu, zemine dokunulmuyordu;
    "0.5px zarif hatlar" diyordu, öyle bir kenarlık yoktu; "Modern (14px)"
    diyordu, gerçek değer 12px'ti; "Başlık Yazı Tipi" diyordu, sitenin TÜM
    fontunu değiştiriyordu. Bu sayfa sahibin tasarım kararlarını verdiği yer;
    yanlış bilgi veren bir kumanda paneli, kumandanın kendisinden kötüdür.

    ---------------------------------------------------------------------------
    KORUNAN İLKELER

    · Metinler artık PRESETLER sabitinden türer — vaat değil, özet.
    · Önizleme köşe ölçeğini TemaJetonlari::koseOlcegi()'nden okur; ayrı bir
      kopya tutmak ikisinin sessizce ayrışmasına yol açıyordu (ve açmıştı).
    · Sayfanın tamamı tek "aktif tema" kaynağı kullanır ($aktifTema). Oturum
      önizlemesi ayrı ve açıkça belirtilir.
    · Aktiflik/seçim rengi primary-* jetonlarından gelir; sabit emerald,
      marka rengi değiştiğinde panelin geri kalanıyla çelişiyordu.
--}}
<x-filament-panels::page>

    {{-- ŞU AN NE YAYINDA — sayfanın en önemli olgusu, en üstte ve METİN olarak.
         Eskiden burada üç ayrı süs başlık vardı ("2027 UI/UX Vizyonu · Ultra
         Komuta Merkezi", "Nisoya Marka & Tasarım Mimarisi") ve hiçbiri hangi
         temanın açık olduğunu söylemiyordu; sahip iki kart ızgarasını tarayıp
         yeşil halka aramak zorundaydı. --}}
    <x-filament::section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-950 dark:text-white">
                    Şu an yayında:
                    <span class="text-primary-600 dark:text-primary-400">{{ $aktifTema === 'vitrin' ? 'Vitrin' : 'Klasik' }}</span>
                    @if ($aktifTema === 'klasik')
                        · <span class="text-primary-600 dark:text-primary-400">{{ \App\Filament\Pages\TasarimAyarlari::PRESETLER[$aktifMod]['ad'] ?? 'Özel' }}</span>
                    @endif
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Sitenin rengini, yazı tipini ve köşe yuvarlatmasını buradan değiştirirsin.
                </p>
            </div>

            {{-- flex-wrap ŞART: iki ikonlu düğme ~368px eder, 375px'lik
                 telefonda sayfa dolgusundan sonra taşıyordu. --}}
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::button
                    color="gray"
                    icon="heroicon-o-arrow-path"
                    wire:click="sifirla"
                    wire:confirm="Renk, yazı tipi, köşe ve efektler fabrika ayarına dönecek ve canlı sitede anında geçerli olacak. Tema seçimin değişmez. Emin misin?"
                >
                    Varsayılana sıfırla
                </x-filament::button>
                <x-filament::button color="primary" icon="heroicon-o-check" wire:click="kaydetCustom">
                    Değişiklikleri kaydet
                </x-filament::button>
            </div>
        </div>

        {{-- ÖNİZLEME UYARISI — sayfa iki farklı "aktif tema" kaynağı
             kullanıyordu ve kendisiyle çelişebiliyordu: kart "Klasik ✓ Aktif"
             derken alttaki bant "Şu an Vitrin açık" diyordu. İkisi de doğruydu
             ama farklı soruların cevabıydı. Artık ayrımı sayfa söylüyor. --}}
        @if (session('tema_onizleme'))
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-300 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-950/40">
                <p class="text-sm text-blue-900 dark:text-blue-200">
                    Şu an <strong>yalnız sen</strong> {{ session('tema_onizleme') === 'vitrin' ? 'Vitrin' : 'Klasik' }} önizlemesindesin.
                    Ziyaretçiler <strong>{{ $aktifTema === 'vitrin' ? 'Vitrin' : 'Klasik' }}</strong> görüyor.
                </p>
                <x-filament::button tag="a" size="sm" color="gray" href="{{ url('/?tema_onizleme=kapat') }}">
                    Önizlemeyi kapat
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>

    {{-- 1. SİTE TEMASI --}}
    <x-filament::section>
        {{-- ADIM MERDİVENİ. Sayfa aslında sıralı bir akış (önce tema, sonra
             paket, sonra ince ayar) ama bu sıra yalnız başlıktaki "1." "2."
             rakamlarında yaşıyordu ve göze çarpmıyordu. Numaralı rozet sırayı
             görsel hâle getirir; okumadan da anlaşılır. --}}
        <x-slot name="heading">
            <span class="flex items-center gap-2.5">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary-700 text-xs font-bold text-white">1</span>
                Site teması
            </span>
        </x-slot>
        <x-slot name="description">
            Sitenin tamamının hangi tasarımla sunulacağını seçer. <strong>Etkinleştirdiğin anda tüm ziyaretçiler görür.</strong>
            Geri dönüş de tek tık; hiçbir ayarın kaybolmaz.
        </x-slot>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ([
                'klasik' => [
                    'ad' => 'Klasik',
                    'renk' => '#059669',
                    'aciklama' => 'Bugünkü tasarım. Aşağıdaki hazır paketler ve ince ayarlar yalnız bu temada çalışır.',
                    'rozet' => null,
                ],
                'vitrin' => [
                    'ad' => 'Vitrin',
                    'renk' => '#3E63F0',
                    'aciklama' => 'Mavi renk paleti, Plus Jakarta Sans yazı tipi, kutu düzeninde üst alan. Karşılığı hazır olmayan sayfalar klasik görünümle sunulur.',
                    'rozet' => 'Bazı sayfalarda klasik',
                ],
            ] as $anahtar => $tema)
                @php $secili = $aktifTema === $anahtar; @endphp
                {{-- Ölçek merdiveni: tema kartı p-5/rounded-xl, paket kartı
                     p-3.5/rounded-lg. Aynı görünüm iki farklı ağırlıktaki
                     kararı eşitliyordu. --}}
                <div @class([
                    'relative flex flex-col justify-between rounded-xl border bg-white p-5 transition dark:bg-gray-800',
                    'border-primary-500 ring-2 ring-primary-500/30' => $secili,
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => ! $secili,
                ])>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="h-4 w-4 shrink-0 rounded-full" style="background: {{ $tema['renk'] }}"></span>
                            <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ $tema['ad'] }}</h4>

                            {{-- Rozet YALNIZ aktif değilken. Eskiden koşulsuzdu ve
                                 Vitrin açıkken kart aynı anda "✓ Aktif" ve
                                 "Hazırlanıyor" gösteriyordu. --}}
                            @if ($tema['rozet'] && ! $secili)
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $tema['rozet'] }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $tema['aciklama'] }}</p>
                    </div>

                    @if ($secili)
                        {{-- Aktif kartta ÖLÜ DÜĞME YOK. Devre dışı gri düğme görsel
                             dilde "bu kart kapalı" demektir, "yürürlükte" değil;
                             üstelik sekme sırasından da düşüyordu. --}}
                        <div class="mt-4 w-full rounded-lg bg-primary-50 py-2 text-center text-xs font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                            Yürürlükte
                        </div>
                    @else
                        <div class="mt-4 flex flex-col gap-2">
                            <x-filament::button
                                color="gray"
                                class="w-full"
                                wire:click="secTema('{{ $anahtar }}')"
                                wire:confirm="Site {{ $tema['ad'] }} temasına geçecek ve bunu TÜM ziyaretçiler anında görecek. Emin misin?"
                            >
                                Etkinleştir
                            </x-filament::button>
                            <a href="{{ url('/?tema_onizleme='.$anahtar) }}" target="_blank" rel="noopener"
                               class="text-center text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                Önce sadece bana göster →
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- 2. HAZIR PAKETLER — Klasik'in ALTINDA yaşarlar, kardeşi değil.
         Girintili ve nötr zeminli kapsayıcı bu bağımlılığı anlatır; eskiden
         iki ızgara birebir aynı sınıfları kullandığı için ilişkiyi ancak
         metinle özür dileyerek anlatabiliyordu. --}}
    <x-filament::section>
        <x-slot name="heading">
            <span class="flex items-center gap-2.5">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary-700 text-xs font-bold text-white">2</span>
                Hazır tasarım paketleri
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Klasik temaya ait</span>
            </span>
        </x-slot>
        <x-slot name="description">
            Renk, yazı tipi, köşe ve efektleri birlikte değiştirir.
            <strong>"Etkinleştir" der demez canlıya geçer</strong> — aşağıdaki tek tek ayarlardan farklı olarak kaydetmen gerekmez.
        </x-slot>

        @if ($aktifTema === 'vitrin')
            <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-900/30">
                <p class="text-sm text-amber-900 dark:text-amber-200">
                    <strong>Vitrin</strong> teması açıkken bu paketler ve aşağıdaki ince ayarlar siteye yansımaz.
                    Seçtiğin ayar kaydedilir ve Klasik'e dönünce geçerli olur.
                </p>
            </div>
        @endif

        {{-- Vitrin'de kontroller GERÇEKTEN kapatılır. Eskiden yalnız
             `opacity-50` uygulanıyordu: metin 1.98:1'e düşüyor ama düğmeler
             çalışmaya devam ediyordu — "kapalı görünen ama çalışan kontrol",
             hem yanıltıcı hem okunmaz. --}}
        {{-- PAKETLER KLASİK'İN ALTINDA YAŞAR, KARDEŞİ DEĞİL.
             Eskiden iki ızgara birebir aynı kart sınıflarını kullanıyordu ve
             bağımlılık yalnız metinle anlatılabiliyordu. Nötr zeminli girintili
             kapsayıcı (Filament'in ikincil yüzeyi) + bir kademe küçük kartlar
             bunu yerleşimle söyler; metin özür dilemek zorunda kalmaz. --}}
        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
        <fieldset @disabled($aktifTema === 'vitrin') class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 disabled:opacity-70">
            @foreach (\App\Filament\Pages\TasarimAyarlari::PRESETLER as $anahtar => $paket)
                @php
                    $secili = $aktifTema !== 'vitrin' && $aktifMod === $anahtar;
                    $onizlemeRenk = $paket['ayarlar']['gorunum.primary_color'];
                    $onizlemeKose = \App\Support\TemaJetonlari::koseOlcegi($paket['ayarlar']['gorunum.border_radius'])['xl'];
                    $onizlemeFont = \App\Support\TemaJetonlari::fontCss($paket['ayarlar']['gorunum.font_family']);
                @endphp

                {{-- Tema kartlarından BİR KADEME küçük (p-3.5/rounded-lg vs
                     p-5/rounded-xl): ölçek farkı bağımlılığı anlatır. --}}
                <div @class([
                    'relative flex flex-col justify-between rounded-lg border bg-white p-3.5 transition dark:bg-gray-800',
                    'border-primary-500 ring-2 ring-primary-500/30' => $secili,
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => ! $secili,
                ])>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="h-3.5 w-3.5 shrink-0 rounded-full" style="background: {{ $onizlemeRenk }}"></span>
                            <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ $paket['ad'] }}</h4>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $paket['ozet'] }}</p>
                    </div>

                    {{-- ÖNİZLEME ŞERİDİ PAKETTEN TÜRER. Eskiden dördü de sabit
                         `rounded-xl` idi ve üçü paketin rengini hiç
                         göstermiyordu; Obsidyen şeridi ise paketin yazmadığı bir
                         mono font ile koyu zemin vaat ediyordu. Şerit artık
                         paketin gerçekten yazdığı renk/köşe/font ile çizilir,
                         yani motorla ayrışamaz. --}}
                    <div class="mt-3 border border-gray-200 px-3 py-2.5 text-sm font-bold dark:border-gray-700"
                         style="border-radius: {{ $onizlemeKose }}; font-family: {{ $onizlemeFont }}; color: {{ $onizlemeRenk }}">
                        Ne İş Olursa Yaparız
                    </div>

                    @if ($secili)
                        <div class="mt-3 w-full rounded-lg bg-primary-50 py-2 text-center text-xs font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                            Yürürlükte
                        </div>
                    @else
                        <x-filament::button color="gray" class="mt-3 w-full" wire:click="secPreset('{{ $anahtar }}')">
                            Etkinleştir
                        </x-filament::button>
                    @endif
                </div>
            @endforeach
        </fieldset>
        </div>
    </x-filament::section>

    {{-- 3. İNCE AYARLAR + ÖNİZLEME --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <fieldset @disabled($aktifTema === 'vitrin') class="space-y-6 disabled:opacity-70 xl:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    <span class="flex items-center gap-2.5">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary-700 text-xs font-bold text-white">3</span>
                        Vurgu rengi
                    </span>
                </x-slot>
                <x-slot name="description">
                    Butonlarda, vurgularda ve rozetlerde kullanılan renk.
                    <strong>"Değişiklikleri kaydet"e basana kadar yayına girmez.</strong>
                </x-slot>

                <div class="flex flex-wrap items-center gap-3">
                    <label for="tasarim-renk-secici" class="sr-only">Vurgu rengi seçici</label>
                    <input id="tasarim-renk-secici" type="color" wire:model.live="primaryColor"
                           class="h-11 w-16 cursor-pointer rounded-lg border-0 bg-transparent p-0 shadow" />

                    <label for="tasarim-renk-hex" class="sr-only">Vurgu rengi HEX kodu</label>
                    <input id="tasarim-renk-hex" type="text" wire:model.live="primaryColor"
                           class="w-32 rounded-lg border-gray-300 text-sm focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />

                    {{-- Yuvarlaklar: erişilebilir adı, basılı durumu ve 44px
                         dokunma payı olmadan yalnız renk halkasıyla
                         gösteriliyordu — renk körü bir kullanıcı için hangisinin
                         seçili olduğu okunamıyordu. --}}
                    <div role="group" aria-label="Hazır vurgu renkleri"
                         class="flex basis-full items-center gap-1.5 sm:basis-auto sm:border-l sm:border-gray-200 sm:pl-3 sm:dark:border-gray-700">
                        @foreach ([
                            '#059669' => 'Zümrüt',
                            '#0f5c42' => 'Koyu yeşil',
                            '#10b981' => 'Parlak zümrüt',
                            '#0f172a' => 'Lacivert',
                            '#2563eb' => 'Mavi',
                            '#7c3aed' => 'Mor',
                            '#c1440e' => 'Kiremit',
                        ] as $hex => $renkAdi)
                            @php $renkSecili = strtolower($primaryColor) === strtolower($hex); @endphp
                            <button type="button"
                                    wire:click="$set('primaryColor', '{{ $hex }}')"
                                    aria-label="{{ $renkAdi }}"
                                    aria-pressed="{{ $renkSecili ? 'true' : 'false' }}"
                                    class="-m-2 grid place-items-center p-2">
                                <span style="background: {{ $hex }}"
                                      @class([
                                          'grid h-7 w-7 place-items-center rounded-full text-white shadow-sm transition',
                                          'ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-gray-900' => $renkSecili,
                                      ])>
                                    @if ($renkSecili)
                                        <x-heroicon-s-check class="h-4 w-4" />
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <span class="flex items-center gap-2.5">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary-700 text-xs font-bold text-white">4</span>
                        Yazı tipi, köşe ve efektler
                    </span>
                </x-slot>
                <x-slot name="description"><strong>"Değişiklikleri kaydet"e basana kadar yayına girmez.</strong></x-slot>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        {{-- "Başlık Yazı Tipi" DEĞİL: seçim --font-sans'a yazılıyor,
                             yani sitenin gövde metni dâhil tamamını değiştiriyor.
                             Etiket yanlış bir sınırlama vaat ediyordu. --}}
                        <label for="tasarim-font" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Yazı tipi</label>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Başlıklar ve gövde metni dâhil sitenin tamamı.</p>
                        <select id="tasarim-font" wire:model.live="fontFamily"
                                class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            {{-- Seçenekler tek kaynaktan (TemaJetonlari::FONTLAR) gelir:
                                 orada YALNIZ self-host edilen aileler bulunur. Buraya elle
                                 seçenek eklemek, sahibin seçip hiçbir şeyin değişmediği ölü
                                 bir kontrol üretir — 'Inter'/'Outfit' tam olarak öyleydi. --}}
                            @foreach (\App\Support\TemaJetonlari::fontSecenekleri() as $fontAnahtar => $fontEtiket)
                                <option value="{{ $fontAnahtar }}">{{ $fontEtiket }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tasarim-kose" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Köşe yuvarlatma</label>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Kart, buton ve kutuların köşeleri.</p>
                        <select id="tasarim-kose" wire:model.live="borderRadius"
                                class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            {{-- Etiketlerde piksel YOK: eski "Modern (14px)" hiçbir
                                 katmanla eşleşmiyordu ve ölçek değişince sessizce
                                 yalancı oluyordu (bkz. TemaJetonlari::koseSecenekleri). --}}
                            @foreach (\App\Support\TemaJetonlari::koseSecenekleri() as $koseAnahtar => $koseEtiket)
                                <option value="{{ $koseAnahtar }}">{{ $koseEtiket }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-4 border-t border-gray-100 pt-4 sm:flex-row sm:items-start sm:justify-between dark:border-gray-700/50">
                    <label class="flex cursor-pointer items-start gap-3 py-2">
                        {{-- fi-checkbox-input ŞART: bu projede @tailwindcss/forms YOK,
                             o yüzden `rounded text-primary-600` sınıfları bir onay
                             kutusuna hiçbir şey yapmıyordu (ölü sınıf) ve kutu
                             tarayıcının ~13px yerel kutusu olarak çıkıyordu. --}}
                        <input type="checkbox" wire:model.live="glassmorphism" class="fi-checkbox-input mt-0.5" />
                        <span>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Cam efekti</span>
                            {{-- "Uygular" DEĞİL: açık konum sitenin mevcut buzlu cam
                                 yüzeylerini korur, kapalı konum onları söker. Açık
                                 konum tek başına yeni bir efekt eklemez. --}}
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Açıkken kart ve menülerdeki buzlu cam yüzeyler korunur; kapatınca hepsi opaklaşır.</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 py-2">
                        <input type="checkbox" wire:model.live="smoothAnimations" class="fi-checkbox-input mt-0.5" />
                        <span>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Yumuşak geçişler</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Kapatınca sitedeki tüm geçiş ve animasyonlar anında olur.</span>
                        </span>
                    </label>
                </div>
            </x-filament::section>
        </fieldset>

        {{-- ÖNİZLEME --}}
        <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
            <x-filament::section>
                <x-slot name="heading">Önizleme</x-slot>
                <x-slot name="description">Renk, yazı tipi, köşe ve cam efektini anında gösterir.</x-slot>

                @php
                    // Köşe ölçeği motorla AYNI kaynaktan; ayrı bir kopya
                    // tutulduğu sürece sessizce ayrışmıştı (modern 14px↔12px).
                    $simKose = \App\Support\TemaJetonlari::koseOlcegi($borderRadius)['xl'];
                    $simFont = \App\Support\TemaJetonlari::fontCss($fontFamily);
                    // Beyaz metin için zemin koyultulur: düz $primaryColor ile
                    // beyaz 3.77:1 kalıyordu (AA 4.5 ister). Aynı türetme motorun
                    // --color-emerald-700 satırıyla birebir aynı.
                    $simKoyu = 'color-mix(in srgb, '.$primaryColor.' 80%, black)';
                @endphp

                <div @class([
                        'overflow-hidden border border-gray-200 p-5 transition dark:border-gray-700',
                        'bg-white/70 backdrop-blur-md dark:bg-gray-900/70' => $glassmorphism,
                        'bg-stone-50 dark:bg-stone-900' => ! $glassmorphism,
                    ])
                    style="border-radius: {{ $simKose }}">

                    <span style="background: color-mix(in srgb, {{ $primaryColor }} 12%, white); color: {{ $simKoyu }}; border-radius: {{ $simKose }}"
                          class="inline-block px-2.5 py-1 text-xs font-bold">
                        Yurt dışındaki Türkler için
                    </span>

                    {{-- GERÇEK METİN. Eskiden burada sabit yazılmış "2027 Gurbetçi
                         Vitrini" ve eski bir slogan duruyordu; sahip tipografi
                         kararını sitede hiç kullanılmayan bir cümle üzerinde
                         veriyordu. Artık Hero Yöneticisi'ndeki metnin aynısı. --}}
                    <h4 style="font-family: {{ $simFont }}" class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">
                        {{ \App\Support\Hero::baslik() }}
                    </h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ \App\Support\Hero::altBaslik() }}</p>

                    <button type="button" tabindex="-1" aria-hidden="true"
                            style="background: {{ $simKoyu }}; border-radius: {{ $simKose }}"
                            @class([
                                'mt-4 w-full py-2.5 text-sm font-bold text-white shadow-md',
                                'transition hover:opacity-90' => $smoothAnimations,
                            ])>
                        İlanlara göz at
                    </button>
                </div>

                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Bu kutu bir örnektir, gerçek bir ilan değildir.
                </p>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>

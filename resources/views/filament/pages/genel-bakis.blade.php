<x-filament-panels::page>
    {{-- Yazdırma stilleri. Belge tarayıcının Yazdır → PDF'iyle alınır;
         sunucuda PDF kütüphanesi YOK (gerekçe: GenelBakis sınıf docblock'u). --}}
    <style>
        @media print {
            /* Panel kabuğu basılmaz: yalnız belge kalır. */
            .fi-sidebar, .fi-topbar, .fi-header, .fi-footer, .yazdirma-disi { display: none !important; }
            .fi-main, .fi-page { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            .belge-bolum { break-inside: avoid; page-break-inside: avoid; }
            body { background: #fff !important; }
        }
    </style>

    <div class="yazdirma-disi flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
        <div class="text-sm text-gray-600 dark:text-gray-300">
            Bu belge <strong>içeriye</strong> bakar: ürünün bugünkü hâlini anlatır.
            Yatırımcı memosu ayrı bir belgedir.
        </div>
        <x-filament::button icon="heroicon-o-printer" onclick="window.print()">
            Yazdır / PDF olarak kaydet
        </x-filament::button>
    </div>

    <div class="belge-bolum">
        <x-filament::section>
            <x-slot name="heading">Nisoya nedir</x-slot>

            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p>
                    Nisoya, yurt dışında yaşayan Türklerin kendi dillerinde hizmet, ürün ve iş
                    bulabildiği <strong>ücretsiz</strong> bir pazaryeri. Site ödeme işlemez,
                    komisyon almaz; üyeler birbirine doğrudan ulaşır ve doğrudan öder.
                </p>
                <p>
                    Pazaryerinin yanında bir <strong>ülke rehberi</strong> katmanı var:
                    konsolosluk işlemlerinin resmî kaynaktan doğrulanmış anlatımı. Bu katman
                    pazaryeri dolmadan da değer üretir.
                </p>
            </div>
        </x-filament::section>
    </div>

    <div class="belge-bolum">
        <x-filament::section>
            <x-slot name="heading">Bugünkü gerçek envanter</x-slot>
            <x-slot name="description">
                Örnek (demo) kayıtlar sayılmaz — Kâhya teşhisiyle aynı kural.
            </x-slot>

            <div class="grid gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Aktif ilan', $this->envanter()['ilan']],
                    ['Benzersiz satıcı', $this->envanter()['satici']],
                    ['İlanı olan şehir', $this->envanter()['sehir']],
                    ['İlanı olan ülke', $this->envanter()['ulke']],
                ] as [$etiket, $deger])
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $deger }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $etiket }}</div>
                    </div>
                @endforeach
            </div>

            @if ($this->envanterZayifMi())
                <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    <strong>Darboğaz açıkça burada:</strong> üçüncü taraf arzı henüz oluşmadı.
                    Ürün tarafı çalışıyor; eksik olan satıcı tedariki. Bu sayıyı gizlemek
                    yerine yazmak, belgeyi okuyanın ilk soracağı soruyu öne almaktır.
                </p>
            @endif
        </x-filament::section>
    </div>

    <div class="belge-bolum">
        <x-filament::section>
            <x-slot name="heading">İlan yaşam döngüsü</x-slot>
            <x-surec-seridi :adimlar="$this->surecAdimlari()" />
        </x-filament::section>
    </div>

    <div class="belge-bolum">
        <x-filament::section>
            <x-slot name="heading">Üretilen içerik ve üye tabanı</x-slot>

            <div class="grid gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Yayında rehber içeriği', $this->icerikSayilari()['rehber_icerik']],
                    ['Rehber kapsanan ülke', $this->icerikSayilari()['rehber_ulke']],
                    ['Yayında sayfa', $this->icerikSayilari()['sayfa']],
                    ['Doğrulanmış üye', $this->uyeler()['dogrulanmis']],
                ] as [$etiket, $deger])
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $deger }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $etiket }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>

    <div class="belge-bolum">
        <x-filament::section>
            <x-slot name="heading">Açık modüller</x-slot>
            <x-slot name="description">Ürünün kapsamı — sayıyla değil adla.</x-slot>

            <div class="flex flex-wrap gap-2">
                @forelse ($this->acikModuller() as $modul)
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700 dark:bg-white/10 dark:text-gray-200">{{ $modul }}</span>
                @empty
                    <span class="text-sm text-gray-500">Açık modül yok.</span>
                @endforelse
            </div>
        </x-filament::section>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Veri kesim tarihi: {{ $this->kesimMetni() }} ·
        Bu belgedeki her sayı canlı veritabanından üretildi.
    </p>
</x-filament-panels::page>

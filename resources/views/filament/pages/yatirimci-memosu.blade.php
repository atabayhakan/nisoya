<x-filament-panels::page>
    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, .fi-footer, .yazdirma-disi { display: none !important; }
            .fi-main, .fi-page { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            .memo-bolum { break-inside: avoid; page-break-inside: avoid; }
            body { background: #fff !important; }
        }
    </style>

    <div class="yazdirma-disi flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
        <div class="text-sm text-gray-600 dark:text-gray-300">
            <strong>İki sayfa.</strong> Bu belge özellik saymaz — riski öldürür.
            Özellik listesi yatırımcıya traction değil, traction yokluğu olarak okunur.
        </div>
        <div class="flex gap-2">
            <x-filament::button color="gray" wire:click="anligiKaydet" icon="heroicon-o-bookmark-square">
                Rakamları deftere yaz
            </x-filament::button>
            <x-filament::button icon="heroicon-o-printer" onclick="window.print()">
                Yazdır / PDF
            </x-filament::button>
        </div>
    </div>

    @if ($this->eksikMetinler())
        <div class="yazdirma-disi rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
            <strong>Bu bölümler henüz yazılmadı</strong> ve belgede basılmıyor:
            {{ implode(' · ', $this->eksikMetinler()) }}.
            Ayarlar → <code>memo.problem</code>, <code>memo.koridor</code>, <code>memo.ask</code>.
            <div class="mt-1 text-xs opacity-80">
                Sayılar canlı veriden gelir ve düzenlenemez; anlatı senin sözün olmalı.
            </div>
        </div>
    @endif

    {{-- 1. PROBLEM --}}
    @if ($this->metin('problem'))
        <div class="memo-bolum">
            <x-filament::section>
                <x-slot name="heading">1 · Problem ve bugünkü ikame</x-slot>
                <div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-line">{{ $this->metin('problem') }}</div>
            </x-filament::section>
        </div>
    @endif

    {{-- 2. ARZIN DÜRÜST RAKAMI --}}
    <div class="memo-bolum">
        <x-filament::section>
            <x-slot name="heading">2 · Arz: bugünkü gerçek rakam</x-slot>
            <x-slot name="description">Örnek (demo) kayıtlar sayılmaz.</x-slot>

            <div class="grid gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Aktif ilan', $this->envanter()['ilan']],
                    ['Benzersiz satıcı', $this->envanter()['satici']],
                    ['Şehir', $this->envanter()['sehir']],
                    ['Ülke', $this->envanter()['ulke']],
                ] as [$etiket, $deger])
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $deger }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $etiket }}</div>
                    </div>
                @endforeach
            </div>

            <p class="mt-4 rounded-lg bg-gray-100 p-3 text-sm text-gray-700 dark:bg-white/5 dark:text-gray-300">
                <strong>Darboğaz burada ve bilinçli olarak yazıyor:</strong> üçüncü taraf arzı
                henüz oluşmadı. Ürün, ödeme dışı bütün akışı çalışır durumda taşıyor; eksik olan
                satıcı tedariki. Bu rakamı gizleyen bir belge, okuyanın ilk yapacağı kontrolde
                güvenilirliğini kaybeder.
            </p>
        </x-filament::section>
    </div>

    {{-- 3. TALEP KANITI --}}
    <div class="memo-bolum">
        <x-filament::section>
            <x-slot name="heading">3 · Talep sinyali: huni</x-slot>
            <x-slot name="description">
                Kuzey yıldızı metriği: <strong>karşılıklı ilk temas</strong> — iki yabancının
                gerçekten konuşmaya başlaması. Tek taraflı mesaj sayılmaz.
            </x-slot>

            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ([
                    ['Başlatılan konuşma', $this->huni()['konusma']],
                    ['Karşılıklı konuşma', $this->huni()['karsilikli']],
                    ['Tamamlanan anlaşma', $this->huni()['anlasma']],
                ] as [$etiket, $deger])
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $deger }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $etiket }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Son 8 hafta — karşılıklı ilk temas</div>
                <div class="mt-2 flex items-end gap-1" style="height: 60px">
                    @foreach ($this->kuzeyYildizi() as $h)
                        <div class="flex-1" title="{{ $h['hafta'] }}: {{ $h['adet'] }}">
                            <div class="w-full rounded-t bg-primary-400 dark:bg-primary-600"
                                 style="height: {{ max(2, $h['adet'] * 12) }}px"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Çubuklar gerçek veriden; sıfır hafta sıfır görünür. Uydurma eğri yok.
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- 4. KORİDOR --}}
    @if ($this->metin('koridor'))
        <div class="memo-bolum">
            <x-filament::section>
                <x-slot name="heading">4 · Tek koridor ve doyurma ölçüsü</x-slot>
                <div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-line">{{ $this->metin('koridor') }}</div>
            </x-filament::section>
        </div>
    @endif

    {{-- 5. SERMAYE VERİMLİLİĞİ --}}
    <div class="memo-bolum">
        <x-filament::section>
            <x-slot name="heading">5 · Sermaye verimliliği</x-slot>

            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p>
                    Ürün <strong>tek kişi</strong> tarafından, geliştirici olmayan bir kurucuyla,
                    <strong>{{ $this->aySayisi() }} ayda</strong> canlıya alındı. Bugün yayında:
                    {{ $this->icerikSayilari()['rehber_icerik'] }} doğrulanmış rehber içeriği
                    ({{ $this->icerikSayilari()['rehber_ulke'] }} ülke),
                    {{ $this->icerikSayilari()['sayfa'] }} yayında sayfa.
                </p>
                <p>
                    Özellik listesi bu belgede yok — özellikler traction değildir. Buradaki tek
                    iddia şu: <em>aynı sermayeyle ne kadar yol alındığı</em>.
                </p>
            </div>
        </x-filament::section>
    </div>

    {{-- 6. ASK --}}
    @if ($this->metin('ask'))
        <div class="memo-bolum">
            <x-filament::section>
                <x-slot name="heading">6 · Ask ve kilometre taşları</x-slot>
                <div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-line">{{ $this->metin('ask') }}</div>
            </x-filament::section>
        </div>
    @endif

    {{-- Kanıt defteri --}}
    @if ($this->gecmisAnliklar())
        <div class="memo-bolum">
            <x-filament::section>
                <x-slot name="heading">Kanıt defteri</x-slot>
                <x-slot name="description">Daha önce hangi rakamı verdik — büyüme iki ölçüm arasındaki farktır.</x-slot>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                            <th class="pb-2">Tarih</th><th class="pb-2">Aktif ilan</th><th class="pb-2">Karşılıklı konuşma</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->gecmisAnliklar() as $satir)
                            <tr class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-2 text-gray-600 dark:text-gray-300">{{ $satir['tarih'] }}</td>
                                <td class="py-2 font-medium text-gray-950 dark:text-white">{{ $satir['ilan'] }}</td>
                                <td class="py-2 font-medium text-gray-950 dark:text-white">{{ $satir['karsilikli'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
        </div>
    @endif

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Veri kesim tarihi: {{ $this->kesimMetni() }} · Bu belgedeki her sayı canlı veritabanından üretildi.
        Demo kayıtlar sayılmadı.
    </p>
</x-filament-panels::page>

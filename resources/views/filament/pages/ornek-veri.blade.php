@php
    $partiler = $this->getPartiler();
    $artik = $this->getArtikSayisi();
@endphp

<x-filament-panels::page>
    {{-- Ne olduğunu ve neyin olmadığını en başta söyle. --}}
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/40">
        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Bu veri gerçek değildir</p>
        <ul class="mt-2 space-y-1 text-sm text-amber-800 dark:text-amber-200">
            <li>· Üye adresleri <code>@demo.invalid</code> — hiçbir yere posta gitmez.</li>
            <li>· İlan başlıkları <strong>[ÖRNEK]</strong> önekli, görseller apaçık yer tutucu.</li>
            <li>· Kâhya teşhisi bu kayıtları <strong>saymaz</strong>; "gerçek envanter" sayısı şişmez.</li>
            <li>· Örnek ilanlara <strong>mesaj gönderilemez</strong> — kimse cevapsız kalmaz.</li>
        </ul>
    </div>

    @if ($artik > 0)
        <div class="rounded-xl border border-danger-300 bg-danger-50 p-4 dark:border-danger-700 dark:bg-danger-950/40">
            <p class="text-sm font-semibold text-danger-700 dark:text-danger-300">
                Defterde karşılığı olmayan {{ $artik }} işaretli kayıt var
            </p>
            <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">
                Bir silme yarım kalmış ya da defter dışında veri üretilmiş olabilir. Aşağıdaki
                düğmeler bunlara dokunamaz — sunucuda elle bakılmalı.
            </p>
        </div>
    @endif

    {{-- ÜRETİM --}}
    <x-filament::section>
        <x-slot name="heading">Yeni parti üret</x-slot>
        <x-slot name="description">
            Üye, ilan, görsel, sohbet, anlaşma ve değerlendirme birlikte üretilir — sitenin dolu
            hâlini gerçekçi biçimde görebilmek için.
        </x-slot>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="uyeSayisi" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Üye sayısı</label>
                <input id="uyeSayisi" type="number" min="1" max="50" wire:model="uyeSayisi"
                       class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div>
                <label for="ilanSayisi" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Üye başına ilan</label>
                <input id="ilanSayisi" type="number" min="0" max="10" wire:model="ilanSayisi"
                       class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>
            <div class="flex flex-col justify-end gap-2">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" wire:model="gorunur" class="rounded border-gray-300 dark:border-gray-600" />
                    Sitede görünsün
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" wire:model="aiGorsel" class="rounded border-gray-300 dark:border-gray-600" />
                    AI fotoğraf (filigranlı)
                </label>
            </div>
        </div>

        {{-- Görünür kip ayrı bir karardır ve ne getirdiği açıkça yazılmalı. --}}
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            İşaretlemezsen ilanlar <strong>taslak</strong> doğar ve sitede hiç görünmez —
            üretimde hiçbir şey değişmez. İşaretlersen yayınlanırlar ama her yerde
            <strong>"ÖRNEK"</strong> işareti taşırlar.
            "AI fotoğraf" ilan görsellerini yapay zekâyla gerçekçi fotoğraf olarak üretir
            (görsel başına küçük bir API maliyeti; üzerine tüm görsele yayılan yarı saydam
            <strong>ÖRNEK</strong> filigranı basılır — anahtar yoksa/hata olursa grafik tuvale düşer, üretim uzayabilir).
        </p>

        @if ($this->uretimMi())
            <p class="mt-2 text-sm font-medium text-warning-700 dark:text-warning-400">
                Burası CANLI sunucu. Üretilen her şey geri alınabilir ama önce iki kez düşün.
            </p>
        @endif

        <div class="mt-4">
            <x-filament::button type="button" wire:click="uret" wire:loading.attr="disabled"
                                wire:confirm="Örnek veri üretilecek. Her şey deftere yazılır ve tek tıkla geri alınabilir. Devam edilsin mi?">
                <span wire:loading.remove wire:target="uret">Örnek veri üret</span>
                <span wire:loading wire:target="uret">Üretiliyor…</span>
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- YAPAY ZEKÂ KAPISI --}}
    <x-filament::section>
        <x-slot name="heading">Yapay zekâ örnek veri üretebilsin mi?</x-slot>
        <x-slot name="description">
            Açıkken MCP araçları (<code>demo-uret</code>, <code>demo-sil</code>) asistanın araç
            listesinde görünür. Kapalıyken hiç görünmezler.
        </x-slot>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-gray-700 dark:text-gray-200">
                Şu an:
                <strong>{{ $this->mcpAcikMi() ? 'AÇIK' : 'kapalı' }}</strong>
            </p>

            <x-filament::button type="button" :color="$this->mcpAcikMi() ? 'danger' : 'primary'"
                                wire:click="mcpKapisiniDegistir"
                                wire:confirm="{{ $this->mcpAcikMi() ? 'Kapatılsın mı? Araçlar listeden düşer.' : 'Açılsın mı? Yapay zekâ örnek veri üretebilecek — üretilen her şey geri alınabilir.' }}">
                {{ $this->mcpAcikMi() ? 'Kapat' : 'Aç' }}
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- PARTİLER --}}
    <x-filament::section>
        <x-slot name="heading">Üretilmiş partiler</x-slot>
        <x-slot name="description">
            Her parti tek tıkla geri alınır: kayıtlar ve diskteki dosyalar birlikte silinir.
        </x-slot>

        @forelse ($partiler as $parti)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 py-3 first:border-0 first:pt-0 dark:border-gray-800">
                <div class="min-w-0">
                    <p class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $parti['parti'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $parti['adet'] }} kayıt ·
                        @foreach ($parti['dokum'] as $ad => $adet)
                            {{ $ad }} {{ $adet }}@if (! $loop->last), @endif
                        @endforeach
                    </p>
                </div>

                <x-filament::button type="button" size="sm" color="danger" outlined
                                    wire:click="partiSil('{{ $parti['parti'] }}')"
                                    wire:confirm="{{ $parti['parti'] }} partisi ve diskteki dosyaları silinecek. Devam edilsin mi?">
                    Geri al
                </x-filament::button>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Henüz örnek veri üretilmedi.</p>
        @endforelse

        @if ($partiler !== [])
            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                <x-filament::button type="button" color="danger" wire:click="hepsiniSil"
                                    wire:confirm="BÜTÜN örnek veri silinecek ({{ count($partiler) }} parti). Devam edilsin mi?">
                    Hepsini geri al
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>

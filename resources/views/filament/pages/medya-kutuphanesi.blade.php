<x-filament-panels::page>
    @php
        $breakdown = $this->getTypeBreakdown();
        $usage = $this->getUsage();
        $files = $this->getFiles();
        $human = \App\Services\BackupService::humanSize(...);
    @endphp

    {{-- Uyarı --}}
    <x-filament::section>
        <div class="flex items-start gap-3 text-sm">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 shrink-0 text-warning-500" />
            <span class="text-gray-700 dark:text-gray-300">
                Bir dosyayı silmeden önce emin ol: bir <strong>ilana, sayfaya veya profile ait</strong> bir görseli/videoyu
                silersen orada içerik kırılır. Yalnızca artık kullanılmadığından emin olduğun dosyaları sil.
            </span>
        </div>
    </x-filament::section>

    {{-- 📱 iOS Tarzı Canlı Depolama Dağılım Grafiği (Storage Distribution Bar) --}}
    <x-filament::section>
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Depolama Alanı Dağılımı</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sunucudaki medyanın kategorilere göre canlı doluluk oranı</p>
                </div>
                <div class="text-right">
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $human($breakdown['total_size']) }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">/ {{ number_format($breakdown['total_count']) }} dosya</span>
                </div>
            </div>

            {{-- Canlı Depolama Çubuğu --}}
            <div class="relative flex h-5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700 shadow-inner">
                @if ($breakdown['images']['percent'] > 0)
                    <div
                        style="width: {{ $breakdown['images']['percent'] }}%"
                        class="h-full bg-emerald-500 transition-all duration-500 hover:opacity-90"
                        title="Görseller: {{ $human($breakdown['images']['size']) }} ({{ $breakdown['images']['percent'] }}%)"
                    ></div>
                @endif
                @if ($breakdown['videos']['percent'] > 0)
                    <div
                        style="width: {{ $breakdown['videos']['percent'] }}%"
                        class="h-full bg-sky-500 transition-all duration-500 hover:opacity-90"
                        title="Videolar: {{ $human($breakdown['videos']['size']) }} ({{ $breakdown['videos']['percent'] }}%)"
                    ></div>
                @endif
                @if ($breakdown['documents']['percent'] > 0)
                    <div
                        style="width: {{ $breakdown['documents']['percent'] }}%"
                        class="h-full bg-amber-500 transition-all duration-500 hover:opacity-90"
                        title="Dokümanlar: {{ $human($breakdown['documents']['size']) }} ({{ $breakdown['documents']['percent'] }}%)"
                    ></div>
                @endif
                @if ($breakdown['others']['percent'] > 0)
                    <div
                        style="width: {{ $breakdown['others']['percent'] }}%"
                        class="h-full bg-slate-400 transition-all duration-500 hover:opacity-90"
                        title="Diğer: {{ $human($breakdown['others']['size']) }} ({{ $breakdown['others']['percent'] }}%)"
                    ></div>
                @endif
            </div>

            {{-- Lejant (Renkli Etiketler) --}}
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-5 text-xs font-medium pt-1">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                    <span class="text-gray-700 dark:text-gray-300">Görseller</span>
                    <span class="text-gray-400 ml-auto">{{ $human($breakdown['images']['size']) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-sky-500"></span>
                    <span class="text-gray-700 dark:text-gray-300">Videolar</span>
                    <span class="text-gray-400 ml-auto">{{ $human($breakdown['videos']['size']) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                    <span class="text-gray-700 dark:text-gray-300">Dokümanlar</span>
                    <span class="text-gray-400 ml-auto">{{ $human($breakdown['documents']['size']) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-slate-400"></span>
                    <span class="text-gray-700 dark:text-gray-300">Diğer</span>
                    <span class="text-gray-400 ml-auto">{{ $human($breakdown['others']['size']) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span class="text-gray-700 dark:text-gray-300">Boş Disk</span>
                    <span class="text-gray-400 ml-auto">{{ $human($breakdown['free_space']) }}</span>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- 📁 Klasör Yönetimi ve Yeni Klasör Ekleme --}}
    <x-filament::section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Klasörler & Gruplar</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Dosyalarınızı gruplandırmak için yeni bir klasör açın veya seçin</p>
            </div>

            {{-- Yeni Klasör Oluşturma Formu --}}
            <form wire:submit.prevent="createFolder" class="flex items-center gap-2">
                <input
                    type="text"
                    wire:model="newFolderName"
                    placeholder="Klasör adı (ör. tanitim-videolari)"
                    class="rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
                <x-filament::button type="submit" size="sm" icon="heroicon-o-folder-plus">
                    Klasör Ekle
                </x-filament::button>
            </form>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($usage['dirs'] as $name => $stat)
                <button
                    type="button"
                    wire:click="selectDir(@js($name))"
                    @class([
                        'flex items-center gap-2 rounded-xl border px-3 py-2 text-left text-sm transition shadow-sm',
                        'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300 font-semibold ring-2 ring-primary-500/20' => $dir === $name,
                        'border-gray-200 hover:border-gray-300 bg-white dark:bg-gray-800 dark:border-gray-700 dark:hover:border-gray-600 text-gray-700 dark:text-gray-300' => $dir !== $name,
                    ])
                >
                    <x-filament::icon icon="heroicon-o-folder" class="h-4 w-4 shrink-0 text-primary-500" />
                    <span>{{ $name }}</span>
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        {{ number_format($stat['count']) }} dosya · {{ $human($stat['size']) }}
                    </span>
                </button>
            @empty
                <div class="text-sm text-gray-500">Henüz klasör oluşturulmadı.</div>
            @endforelse
        </div>
    </x-filament::section>

    {{-- 📤 Seçili Klasöre Dosya Yükleme Paneli --}}
    @if ($dir !== '')
        <x-filament::section>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        "<span class="text-primary-600 dark:text-primary-400">{{ $dir }}</span>" Klasörüne Dosya Yükle
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Görsel, MP4 video veya doküman seçip yükleyebilirsiniz</p>
                </div>

                <form wire:submit.prevent="uploadFiles" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <input
                        type="file"
                        wire:model="newFiles"
                        multiple
                        class="block text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-800 dark:file:text-primary-400"
                    />
                    <x-filament::button type="submit" size="sm" icon="heroicon-o-cloud-arrow-up" :disabled="empty($newFiles)">
                        Yüklemeyi Başlat
                    </x-filament::button>
                </form>
            </div>
        </x-filament::section>

        {{-- 🔍 Filtreleme, Arama & Medya Izgarası --}}
        <x-filament::section>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                {{-- Tür Filtre Sekmeleri --}}
                <div class="flex items-center gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                    <button
                        type="button"
                        wire:click="setFilterType('all')"
                        @class([
                            'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                            'bg-white text-gray-950 shadow dark:bg-gray-700 dark:text-white' => $filterType === 'all',
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400' => $filterType !== 'all',
                        ])
                    >
                        Tümü
                    </button>
                    <button
                        type="button"
                        wire:click="setFilterType('image')"
                        @class([
                            'rounded-lg px-3 py-1.5 text-xs font-semibold transition flex items-center gap-1',
                            'bg-white text-gray-950 shadow dark:bg-gray-700 dark:text-white' => $filterType === 'image',
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400' => $filterType !== 'image',
                        ])
                    >
                        🖼️ Görseller
                    </button>
                    <button
                        type="button"
                        wire:click="setFilterType('video')"
                        @class([
                            'rounded-lg px-3 py-1.5 text-xs font-semibold transition flex items-center gap-1',
                            'bg-white text-gray-950 shadow dark:bg-gray-700 dark:text-white' => $filterType === 'video',
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400' => $filterType !== 'video',
                        ])
                    >
                        🎥 Videolar
                    </button>
                    <button
                        type="button"
                        wire:click="setFilterType('document')"
                        @class([
                            'rounded-lg px-3 py-1.5 text-xs font-semibold transition flex items-center gap-1',
                            'bg-white text-gray-950 shadow dark:bg-gray-700 dark:text-white' => $filterType === 'document',
                            'text-gray-500 hover:text-gray-700 dark:text-gray-400' => $filterType !== 'document',
                        ])
                    >
                        📄 Dokümanlar
                    </button>
                </div>

                {{-- Arama Kutusu --}}
                <div class="relative min-w-48">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Dosya adı ara..."
                        class="w-full rounded-xl border-gray-300 pl-8 text-xs focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-gray-400" />
                </div>
            </div>

            {{-- Dosya Kartları Izgarası --}}
            <div class="mt-4">
                @if (count($files['items']) === 0)
                    <div class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        Bu klasörde kriterlerinize uygun dosya bulunamadı.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                        @foreach ($files['items'] as $item)
                            <div
                                x-data="{ copied: false }"
                                class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                            >
                                {{-- Önizleme Alanı --}}
                                <div class="relative aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
                                    @if ($item['is_image'])
                                        <img src="{{ $item['url'] }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @elseif ($item['is_video'])
                                        <video src="{{ $item['url'] }}" class="h-full w-full object-cover" muted preload="metadata" playsinline></video>
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                            <span class="grid h-10 w-10 place-items-center rounded-full bg-white/80 text-gray-900 shadow">
                                                ▶
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center justify-center p-2 text-center">
                                            <x-filament::icon icon="heroicon-o-document-text" class="h-10 w-10 text-gray-400" />
                                            <span class="mt-1 uppercase text-2xs font-bold text-gray-500">
                                                {{ pathinfo($item['path'], PATHINFO_EXTENSION) }}
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Üst Rozet (Tür) --}}
                                    <span class="absolute left-1.5 top-1.5 rounded bg-black/60 px-1.5 py-0.5 text-2xs font-medium text-white backdrop-blur">
                                        @if ($item['is_image']) Görsel @elseif ($item['is_video']) Video @else Doküman @endif
                                    </span>
                                </div>

                                {{-- Dosya Detayı & Butonlar --}}
                                <div class="flex flex-col justify-between p-2.5 gap-2 flex-1">
                                    <div>
                                        <div class="truncate text-xs font-semibold text-gray-800 dark:text-gray-200" title="{{ $item['path'] }}">
                                            {{ basename($item['path']) }}
                                        </div>
                                        <div class="mt-0.5 text-2xs text-gray-400">
                                            {{ $human($item['size']) }} · {{ $item['mtime']->format('d.m.Y') }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 pt-1 border-t border-gray-100 dark:border-gray-700/50">
                                        {{-- 🔗 Tek Tıkla URL Kopyala Butonu --}}
                                        <button
                                            type="button"
                                            @click="
                                                navigator.clipboard.writeText('{{ $item['url'] }}');
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="flex-1 inline-flex items-center justify-center gap-1 rounded-lg bg-emerald-50 px-2 py-1 text-2xs font-medium text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/40 transition"
                                        >
                                            <template x-if="!copied">
                                                <span class="flex items-center gap-1">🔗 Link Kopyala</span>
                                            </template>
                                            <template x-if="copied">
                                                <span class="flex items-center gap-1 text-emerald-600 font-bold">✓ Kopyalandı!</span>
                                            </template>
                                        </button>

                                        {{-- Sil Butonu --}}
                                        <button
                                            type="button"
                                            wire:click="deleteFile(@js($item['path']))"
                                            wire:confirm="Bu dosyayı kalıcı olarak silmek istediğinizden emin misiniz? Sitede kullanılıyorsa görsel/video kırılacaktır."
                                            class="rounded-lg p-1 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition"
                                            title="Dosyayı Sil"
                                        >
                                            <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Sayfalandırma --}}
                    @if ($files['pages'] > 1)
                        <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                            <x-filament::button size="sm" color="gray" wire:click="prevPage" :disabled="$files['page'] <= 1">
                                ← Önceki
                            </x-filament::button>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Sayfa {{ $files['page'] }} / {{ $files['pages'] }} (Toplam {{ number_format($files['total']) }} dosya)
                            </span>
                            <x-filament::button size="sm" color="gray" wire:click="nextPage" :disabled="$files['page'] >= $files['pages']">
                                Sonraki →
                            </x-filament::button>
                        </div>
                    @endif
                @endif
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

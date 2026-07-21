<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $backups = $this->getBackups();
        $isMysql = in_array($stats['driver'], ['mysql', 'mariadb'], true);
        $human = \App\Services\BackupService::humanSize(...);
    @endphp

    {{-- Ne olduğu + tek tık yedek --}}
    <x-filament::section>
        <x-slot name="heading">Tam yedek al</x-slot>
        <x-slot name="description">
            Tek bir .zip içinde <strong>veritabanı</strong> (tüm ilanlar, üyeler, mesajlar, ayarlar)
            ve <strong>yüklenen tüm medya</strong> (fotoğraflar, logo) toplanır. Dosyayı indirip
            güvenli bir yerde (bilgisayarın, harici disk, bulut) saklayabilirsin.
        </x-slot>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Yedek alma birkaç saniye sürebilir; büyük medya varsa biraz daha uzun.
            </p>

            <x-filament::button
                wire:click="createBackup"
                wire:target="createBackup"
                wire:loading.attr="disabled"
                icon="heroicon-o-circle-stack"
                size="lg"
            >
                <span wire:loading.remove wire:target="createBackup">Şimdi Yedek Al</span>
                <span wire:loading wire:target="createBackup">Yedek alınıyor…</span>
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- Sağlık kartları --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-filament::section class="text-center">
            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($stats['count']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Yedek sayısı</div>
        </x-filament::section>

        <x-filament::section class="text-center">
            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $human($stats['total_size']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam boyut</div>
        </x-filament::section>

        <x-filament::section class="text-center">
            <div class="text-2xl font-bold text-gray-950 dark:text-white">
                {{ $stats['latest'] ? $stats['latest']->diffForHumans() : '—' }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Son yedek
                @if ($stats['latest'])
                    <span class="block text-xs">{{ $stats['latest']->format('d.m.Y H:i') }}</span>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section class="text-center">
            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $human($stats['free_space']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Boş disk alanı</div>
        </x-filament::section>
    </div>

    {{-- Son yedek uyarısı (7 günden eskiyse / hiç yoksa) --}}
    @if (! $stats['latest'])
        <x-filament::section>
            <div class="flex items-start gap-3 text-sm">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 shrink-0 text-warning-500" />
                <span class="text-gray-700 dark:text-gray-300">
                    Henüz hiç yedek yok. Düzenli yedek, sunucuda bir sorun olduğunda siteni
                    kurtarabilmenin tek yoludur — yukarıdan bir yedek almanı öneririz.
                </span>
            </div>
        </x-filament::section>
    @elseif ($stats['latest']->lt(now()->subDays(7)))
        <x-filament::section>
            <div class="flex items-start gap-3 text-sm">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 shrink-0 text-warning-500" />
                <span class="text-gray-700 dark:text-gray-300">
                    En son yedek 7 günden eski. Otomatik günlük yedekleme açık olsa da, güncel
                    bir yedek almanı öneririz.
                </span>
            </div>
        </x-filament::section>
    @endif

    {{-- MySQL'de mysqldump erişilemiyorsa uyar --}}
    @if ($isMysql && count($backups) === 0 && ! app(\App\Services\BackupService::class)->mysqldumpAvailable())
        <x-filament::section>
            <div class="flex items-start gap-3 text-sm">
                <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5 shrink-0 text-danger-500" />
                <span class="text-gray-700 dark:text-gray-300">
                    Sunucuda <code>mysqldump</code> bulunamadı; veritabanı yedeği alınamayabilir.
                    Yolu <code>config/backup.php</code> (<code>MYSQLDUMP_PATH</code>) ile ayarlayabilirsin.
                </span>
            </div>
        </x-filament::section>
    @endif

    {{-- Yedek listesi --}}
    <x-filament::section>
        <x-slot name="heading">Mevcut yedekler</x-slot>
        <x-slot name="description">Günlük otomatik yedekleme çalışır; en yeni yedek her zaman korunur.</x-slot>

        @if (count($backups) === 0)
            <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Henüz yedek yok.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th class="py-2 pr-4 font-medium">Dosya</th>
                            <th class="py-2 pr-4 font-medium">Boyut</th>
                            <th class="py-2 pr-4 font-medium">Tarih</th>
                            <th class="py-2 pr-4 font-medium text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($backups as $b)
                            <tr>
                                <td class="py-3 pr-4 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $b['name'] }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $human($b['size']) }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    {{ $b['created_at']->format('d.m.Y H:i') }}
                                </td>
                                <td class="py-3 pr-0">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-filament::button
                                            tag="a"
                                            href="{{ route('admin.backup.download', ['name' => $b['name']]) }}"
                                            icon="heroicon-o-arrow-down-tray"
                                            size="xs"
                                            color="gray"
                                        >
                                            İndir
                                        </x-filament::button>

                                        <x-filament::button
                                            wire:click="deleteBackup('{{ $b['name'] }}')"
                                            wire:confirm="Bu yedeği kalıcı olarak silmek istediğine emin misin?"
                                            icon="heroicon-o-trash"
                                            size="xs"
                                            color="danger"
                                        >
                                            Sil
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Rehberli geri yükleme --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">Yedekten nasıl geri dönülür?</x-slot>
        <x-slot name="description">Sunucuda bir sorun olduğunda izlenecek adımlar.</x-slot>

        <div class="prose prose-sm max-w-none dark:prose-invert">
            <p>Her yedek .zip şunları içerir:</p>
            <ul>
                <li><code>database/</code> — veritabanının tam dökümü</li>
                <li><code>media/</code> — yüklenen tüm dosyalar (<code>storage/app/public</code>)</li>
                <li><code>manifest.json</code> — yedek tarihi, veritabanı türü ve geri yükleme notu</li>
            </ul>
            <p><strong>Geri yükleme:</strong></p>
            <ol>
                <li>İlgili yedeği bu sayfadan <strong>indir</strong> ve .zip'i aç.</li>
                <li><code>database/</code> içindeki dökümü veritabanına içe aktar
                    (MySQL: <code>mysql</code> ile; SQLite: dosyayı <code>database/database.sqlite</code> ile değiştir).</li>
                <li><code>media/</code> klasörünün içeriğini <code>storage/app/public</code> altına kopyala.</li>
            </ol>
            <p class="text-gray-500 dark:text-gray-400">
                Not: Panel içinden <strong>tek tık otomatik geri yükleme</strong>, veritabanının üzerine
                yazdığı için önce bir kopya üzerinde denenmesi gereken riskli bir işlemdir; bir sonraki
                adımda güvenlik korumalarıyla eklenecektir. O zamana kadar geri yükleme yukarıdaki
                adımlarla yapılır.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>

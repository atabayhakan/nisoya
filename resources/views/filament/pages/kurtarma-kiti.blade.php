<x-filament-panels::page>
    @php
        $adminCount = $this->adminCount();
        $ikiFaktorlu = $this->ikiFaktorluAdminCount();
        $remaining = $this->remainingCodes();
    @endphp

    {{-- 1) En az iki yönetici güvencesi --}}
    <x-filament::section>
        <x-slot name="heading">Yönetici hesapları</x-slot>
        <x-slot name="description">
            Tek yöneticili bir sitede o hesaba erişimini kaybedersen panele kilitlenirsin.
            En az iki yönetici olması bu riski ortadan kaldırır.
        </x-slot>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="text-3xl font-bold text-gray-950 dark:text-white">{{ $adminCount }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">aktif yönetici</span>
            </div>

            <x-filament::button tag="a" :href="$this->createUserUrl()" icon="heroicon-o-user-plus" color="gray">
                Yeni yönetici ekle
            </x-filament::button>
        </div>

        @if ($adminCount < 2)
            <div class="mt-4 flex items-start gap-3 rounded-lg bg-warning-50 p-3 text-sm dark:bg-warning-500/10">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 shrink-0 text-warning-500" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>Tek yöneticin var.</strong> Güvenilir bir kişiyi (veya kendine ait ikinci bir
                    e-postayı) ikinci yönetici olarak eklemeni öneririz — "Yeni yönetici ekle" ile ekleyip
                    rolünü <em>Yönetici</em> seç.
                </span>
            </div>
        @endif

        {{-- 2026-08-05: panel artık 2FA olmadan açılmıyor. "İki yönetici var"
             tek başına güvence değil — 2FA'sı kurulmamış ikinci yönetici,
             birincisi kilitlendiği gün kurulum ekranına düşer ve giremez.
             Kağıt üzerindeki yedekle gerçek yedeği ayıran satır bu. --}}
        @if ($ikiFaktorlu < $adminCount)
            <div class="mt-3 flex items-start gap-3 rounded-lg bg-warning-50 p-3 text-sm dark:bg-warning-500/10">
                <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-5 w-5 shrink-0 text-warning-500" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>{{ $adminCount - $ikiFaktorlu }} yöneticinin iki faktörlü doğrulaması kurulu değil.</strong>
                    Yönetim paneli 2FA olmadan açılmıyor, yani o hesaplar şu an <em>yedek sayılmaz</em> —
                    panele girmeye çalıştıklarında kurulum ekranına düşerler. Kurulumdaki 8 yedek kodu
                    saklamaları önemli: telefon kaybolursa panele dönüşün yolu onlar.
                </span>
            </div>
        @endif
    </x-filament::section>

    {{-- 2) Hesap kurtarma kodları --}}
    <x-filament::section>
        <x-slot name="heading">Hesap kurtarma kodları</x-slot>
        <x-slot name="description">
            Parolanı unutur ve e-posta (SMTP) da çalışmıyorsa, bu tek-kullanımlık kodlardan biriyle
            <span class="font-mono">/hesap-kurtar</span> sayfasından parolanı e-postasız sıfırlayabilirsin.
            Kodları yazdır veya bir parola yöneticisinde sakla.
        </x-slot>

        {{-- OKUNAMAYAN KODLAR — sessizce "0" göstermek yanıltıcı olurdu.
             Alan dolu ama çözülemiyorsa (APP_KEY değişmiş, satır bozulmuş)
             sahip "hiç üretmemişim" sanar; oysa elindeki kodlar artık
             işlemiyor. Olmayan bir güvenceye güvenmek, güvencesiz olduğunu
             bilmekten kötüdür. --}}
        @if (! $this->kodlarOkunabilirMi())
            <div class="mb-4 flex items-start gap-3 rounded-lg bg-danger-50 p-3 text-sm dark:bg-danger-500/10">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-danger-500" />
                <span class="text-gray-700 dark:text-gray-300">
                    <strong>Kayıtlı kurtarma kodların okunamıyor.</strong>
                    Kodlar şifreli saklanır; şifreleme anahtarı değiştiyse eski kodlar çözülemez.
                    Elindeki yazılı kodlar <em>artık çalışmaz</em>. Aşağıdan yeni kod üret ve
                    eskilerini at.
                </span>
            </div>
        @endif

        @if ($generatedCodes !== [])
            {{-- Yeni üretilen kodlar — yalnızca şimdi görünür --}}
            <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                <div class="flex items-center gap-2 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                    <x-filament::icon icon="heroicon-o-key" class="h-5 w-5" />
                    Kurtarma kodların (bu ekrandan çıkınca bir daha gösterilmez)
                </div>

                <div id="kurtarma-kodlari" class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                    @foreach ($generatedCodes as $code)
                        <div class="rounded bg-white px-3 py-2 text-center tracking-wider text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100">{{ $code }}</div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-printer"
                        x-on:click="window.print()"
                    >
                        Yazdır
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-clipboard-document"
                        x-data="{ kopyalandi: false }"
                        x-on:click="navigator.clipboard.writeText(@js(implode(PHP_EOL, $generatedCodes))); kopyalandi = true; setTimeout(() => kopyalandi = false, 1500)"
                    >
                        <span x-show="!kopyalandi">Kopyala</span>
                        <span x-show="kopyalandi" x-cloak>Kopyalandı ✓</span>
                    </x-filament::button>
                </div>
            </div>
        @endif

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if ($remaining > 0)
                    Kullanılabilir <strong>{{ $remaining }}</strong> kurtarma kodun var.
                @else
                    Henüz kurtarma kodun yok.
                @endif
            </p>

            @if ($remaining > 0 || $generatedCodes !== [])
                <x-filament::button
                    wire:click="generateCodes"
                    wire:confirm="Yeni kodlar üretilirse eski kurtarma kodların GEÇERSİZ olur. Devam edilsin mi?"
                    icon="heroicon-o-arrow-path"
                    color="warning"
                >
                    Kodları yenile
                </x-filament::button>
            @else
                <x-filament::button wire:click="generateCodes" icon="heroicon-o-key">
                    Kurtarma kodları oluştur
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>

    {{-- 3) Cam kır — son çare --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">Son çare: "cam kır" (sunucu erişimi)</x-slot>
        <x-slot name="description">Parola, 2FA ve kurtarma kodlarının hepsi kaybolduğunda.</x-slot>

        <div class="prose prose-sm max-w-none dark:prose-invert">
            <p>
                Yukarıdakilerin hiçbiri işe yaramazsa ve sunucuya (SSH) erişimin varsa, bir yöneticinin
                parolasını doğrudan sıfırlayıp hesabı yönetici + aktif yapan son çare komut:
            </p>
            <pre><code>php artisan admin:recover eposta@ornek.com</code></pre>
            <ul>
                <li>Parola verilmezse rastgele güçlü bir parola üretilip ekrana yazılır.</li>
                <li>Belirli bir parola için: <code>--password=YeniParola123</code></li>
                <li>Mevcut yöneticileri (ve 2FA durumlarını) görmek için: <code>php artisan admin:recover --list</code></li>
                <li>
                    <strong>Telefonunu ve yedek kodlarını birlikte kaybettiysen</strong> parolayı sıfırlamak
                    yetmez — panel 2FA ister. İkisini birden temizlemek için:
                    <code>--iki-faktor-sifirla</code>. Girişten sonra 2FA'yı yeniden kurman istenir.
                </li>
            </ul>
            <p class="text-gray-500 dark:text-gray-400">Her kurtarma işlemi İşlem Geçmişi'ne kaydedilir.</p>
        </div>
    </x-filament::section>
</x-filament-panels::page>

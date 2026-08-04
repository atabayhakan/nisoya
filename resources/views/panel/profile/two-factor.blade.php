<x-layouts.app title="İki Faktörlü Doğrulama — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <x-panel.back-link :href="route('panel.profile.edit')" label="Profil Ayarları" />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">İki Faktörlü Kimlik Doğrulama (2FA)</h1>
        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
            Google Authenticator, Authy veya 1Password gibi TOTP uygulamalarıyla hesabına ekstra güvenlik kat.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if ($enabled)
            <section class="mt-6 space-y-4 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 dark:border-emerald-800 dark:bg-emerald-950/20">
                <header>
                    <h2 class="font-semibold text-emerald-900 dark:text-emerald-200">✓ 2FA Etkin</h2>
                    <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                        Hesabın her girişte 6 haneli doğrulama kodu soracak.
                    </p>
                </header>

                <form method="POST" action="{{ route('panel.profile.2fa.disable') }}" class="space-y-3 border-t border-emerald-200 pt-4 dark:border-emerald-800">
                    @csrf
                    <div>
                        <label for="disable_password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mevcut şifren</label>
                        <x-password-input id="disable_password" name="current_password" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100" />
                        @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="disable_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Doğrulama kodu (6 haneli)</label>
                        <input id="disable_code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400 dark:text-stone-900">
                        2FA'yı kapat
                    </button>
                </form>
            </section>
        @else
            <section class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                <header>
                    <h2 class="font-semibold text-stone-900 dark:text-stone-100">2FA Kurulumu</h2>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        "Kur" düğmesine bas, çıkan QR kodu authenticator uygulamanla okut,
                        uygulamanın gösterdiği 6 haneli kodu gir.
                    </p>
                </header>

                @if ($qrSvg)
                    {{-- EKRAN GÖRÜNTÜSÜ UYARISI — 2026-08-05'te gerçekten yaşandı.
                         Sayfa QR basmayıp gizli anahtarı düz metin gösterdiği için
                         sahip ekranı paylaştı ve anahtar yakıldı. Anahtarı bilen
                         herkes geçerli kod üretebilir; yani ikinci faktör hiç
                         yokmuş gibi olur. Uyarı bu yüzden EN ÜSTTE. --}}
                    <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                        <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                            <strong>Bu ekranın görüntüsünü kimseyle paylaşma.</strong>
                            Aşağıdaki kod hesabının anahtarıdır — gören biri senin adına
                            geçerli doğrulama kodu üretebilir. Paylaştıysan "Yeni QR üret"e bas.
                        </span>
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-700 dark:bg-stone-800/50">
                        <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">1. QR kodu okut</h3>
                        <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">
                            Authenticator uygulamanı aç, "hesap ekle" de ve bu kodu okut.
                        </p>

                        {{-- QR beyaz zemin ŞART: koyu modda koyu zemine basılan QR
                             birçok telefon kamerasında okunmaz. --}}
                        <div class="mt-3 flex justify-center">
                            <div class="rounded-xl bg-white p-3 shadow-sm">
                                {!! $qrSvg !!}
                            </div>
                        </div>

                        {{-- Elle giriş İKİNCİL: sırrı ekranda sürekli açık tutmak,
                             omuz üstünden bakan biri ve ekran görüntüsü için
                             gereksiz bir risk. Kamerası çalışmayan kullanıcı için
                             yine de bir yol lazım — katlanmış hâlde duruyor. --}}
                        <details class="mt-3 group">
                            <summary class="cursor-pointer text-xs font-medium text-stone-600 underline decoration-dotted underline-offset-2 hover:text-emerald-700 dark:text-stone-400 dark:hover:text-emerald-400">
                                QR okutamıyorum — kodu elle gireyim
                            </summary>
                            <div class="mt-2 rounded-lg bg-stone-100 p-3 dark:bg-stone-800">
                                <p class="text-xs text-stone-600 dark:text-stone-400">
                                    Uygulamanda "kurulum anahtarını elle gir" seçeneğini kullan:
                                </p>
                                <code class="mt-1.5 block break-all font-mono text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $bekleyenSecret }}</code>
                            </div>
                        </details>
                    </div>
                @endif

                @if ($qrSvg)
                    <form method="POST" action="{{ route('panel.profile.2fa.confirm') }}" class="space-y-3 border-t border-stone-200 pt-4 dark:border-stone-800">
                        @csrf
                        <div>
                            <label for="confirm_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">2. Doğrulama kodunu gir</label>
                            <input id="confirm_code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                                Etkinleştir
                            </button>
                        </div>
                    </form>

                    {{-- Anahtarı bilerek değiştirmenin yolu. QR artık kalıcı
                         olduğu için "Kur"a tekrar basmak diye bir kaza yok;
                         yenileme bilinçli bir eylem oldu. --}}
                    <form method="POST" action="{{ route('panel.profile.2fa.setup') }}"
                          onsubmit="return confirm('Yeni bir anahtar üretilecek ve şu anki QR geçersiz olacak. Uygulamana eklediysen o kaydı silmen gerekir. Devam edilsin mi?')">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-stone-600 underline decoration-dotted underline-offset-2 hover:text-red-600 dark:text-stone-400 dark:hover:text-red-400">
                            Yeni QR üret (anahtarı paylaştıysan)
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('panel.profile.2fa.setup') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                            Kur (QR Kod Üret)
                        </button>
                    </form>
                @endif
            </section>
        @endif

        {{-- KURTARMA KODLARI — @if($enabled) DIŞINDA olmak ZORUNDA.

             Eskiden kurulum bloğunun içindeydi ve HİÇ GÖRÜNMÜYORDU: confirm()
             başarılı olunca two_factor_confirmed_at yazılır, sayfa yeniden
             yüklendiğinde $enabled true olur ve o dal artık basılmaz. Yani 8
             kod üretiliyor, veritabanına yazılıyor, flash'a konuyor —
             kullanıcı hiçbir zaman göremiyordu.

             Panel 2FA olmadan açılmadığı için bu kodlar telefonu kaybeden
             yöneticinin kullanıcı-tarafındaki TEK geri dönüş yolu.

             Ayrıca eskiden "kaydet" diyordu ama kaydetmenin bir yolunu
             sunmuyor ve tek seferlik olduğunu söylemiyordu. İki test artık
             bunları mühürlüyor. --}}
        @if (session('recovery_codes'))
            <div class="mt-6"><div x-data="{ kopyalandi: false, kodlar: @js(session('recovery_codes')) }"
                     class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/20">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
                        <x-heroicon-o-key class="h-4 w-4" />
                        Yedek kurtarma kodların
                    </h3>
                    <p class="mt-1 text-xs font-semibold text-amber-900 dark:text-amber-200">
                        Bu kodlar bir daha gösterilmeyecek. Şimdi kaydet.
                    </p>
                    <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                        Telefonunu kaybedersen yönetim paneline yalnız bunlarla girebilirsin.
                        Her kod bir kez çalışır.
                    </p>

                    <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm">
                        @foreach (session('recovery_codes') as $code)
                            <code class="rounded bg-white px-2 py-1 text-center font-semibold text-stone-900 dark:bg-stone-800 dark:text-stone-100">{{ $code }}</code>
                        @endforeach
                    </div>

                    <button type="button"
                            @click="navigator.clipboard.writeText(kodlar.join('\n')).then(() => { kopyalandi = true; setTimeout(() => kopyalandi = false, 2500) })"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 dark:border-amber-700 dark:bg-stone-800 dark:text-amber-200 dark:hover:bg-stone-700">
                        <x-heroicon-o-clipboard-document class="h-4 w-4" />
                        <span x-text="kopyalandi ? 'Kopyalandı ✓' : 'Kodları kopyala'"></span>
                    </button>
                </div>
            @endif

        {{-- Passkey yönetimi (Faz M2; laravel/passkeys'e geçiş 2026-08-02 —
             uçlar paketin varsayılanları /user/passkeys*). WebAuthn
             desteklemeyen tarayıcıda ekleme düğmesi gizlenir ama mevcut
             liste görünür kalır. --}}
        <section
            x-data="passkeyManage()"
            class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none"
        >
            <header>
                <h2 class="flex items-center gap-2 font-semibold text-stone-900 dark:text-stone-100">
                    <x-heroicon-o-finger-print class="h-5 w-5 text-emerald-700 dark:text-emerald-400" />
                    Passkey (Parmak İzi / Yüz Tanıma ile Giriş)
                </h2>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Şifre yazmadan, telefonunun kilidini açar gibi giriş yap. Passkey'in bu cihazda
                    (veya cihaz hesabında) güvenle saklanır — Nisoya şifreni hiç görmez.
                </p>
                <p class="mt-2 flex items-start gap-1.5 text-xs text-amber-700 dark:text-amber-400">
                    <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" />
                    Bu sayfayı bir e-posta/uygulama içi tarayıcıda (ör. Outlook, Gmail) açtıysan Face ID/parmak izi çalışmaz. Safari veya Chrome'da doğrudan aç, ya da Nisoya'yı ana ekranına ekleyip oradan kullan.
                </p>
            </header>

            @if ($passkeys->isNotEmpty())
                <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200 dark:divide-stone-800 dark:border-stone-700">
                    @foreach ($passkeys as $passkey)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-stone-800 dark:text-stone-100">
                                    {{ $passkey->name ?: 'Passkey' }}
                                </div>
                                <div class="text-xs text-stone-600 dark:text-stone-400">
                                    Eklendi: {{ $passkey->created_at->format('d.m.Y') }}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('passkey.destroy', $passkey) }}" onsubmit="return confirm('Bu passkey silinsin mi? Bu cihazla şifresiz giriş yapamazsın.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-2 text-stone-600 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Passkey'i sil">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div x-show="supported" x-cloak class="space-y-3 border-t border-stone-100 pt-4 dark:border-stone-800">
                <div>
                    <label for="passkey_alias" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Cihaz adı (isteğe bağlı)</label>
                    <input id="passkey_alias" x-model="alias" type="text" maxlength="50" placeholder="örn. Telefonum"
                           class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>
                <button
                    type="button"
                    @click="add()"
                    :disabled="busy"
                    class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-50 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900"
                >
                    <span x-text="busy ? 'Cihaz doğrulanıyor...' : 'Bu cihazı ekle'"></span>
                </button>
                <p x-show="error" x-text="error" class="text-sm text-red-600" x-cloak></p>
            </div>
            <p x-show="!supported" class="border-t border-stone-100 pt-4 text-sm text-stone-600 dark:border-stone-800 dark:text-stone-400" x-cloak>
                Bu tarayıcı passkey desteklemiyor — telefonundan veya güncel bir tarayıcıdan deneyebilirsin.
            </p>
        </section>
    </div>
</x-layouts.app>
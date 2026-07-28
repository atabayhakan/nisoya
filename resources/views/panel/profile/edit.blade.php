<x-layouts.app title="Profilim — Nisoya">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <x-panel.back-link />
        <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">Profil Ayarları</h1>

        {{-- Profil bilgileri --}}
        <form method="POST" action="{{ route('panel.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            @csrf
            @method('PUT')

            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            @if ($user->avatar_path)
                {{-- Hizalama ayrı bir modalda: kaydır + yakınlaştır (WhatsApp tarzı).
                     Küçük daire salt önizleme — kayıttan sonra previewUrl ile tazelenir. --}}
                <div
                    x-data="avatarCropModal(
                        { x: {{ $user->avatar_crop_x ?? 'null' }}, y: {{ $user->avatar_crop_y ?? 'null' }}, size: {{ $user->avatar_crop_size ?? 'null' }} },
                        { x: {{ $user->avatar_focal_x }}, y: {{ $user->avatar_focal_y }} },
                        '{{ route('panel.profile.avatar-align') }}'
                    )"
                    class="flex items-center gap-4"
                >
                    {{-- img `absolute inset-0` şart — gerekçe için bkz. components/avatar.blade.php --}}
                    <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                        <img
                            :src="previewUrl || '{{ Storage::url($user->avatarDisplayPath()) }}'"
                            src="{{ Storage::url($user->avatarDisplayPath()) }}"
                            alt=""
                            class="absolute inset-0 h-full w-full object-cover"
                            @if ($user->avatarUsesLegacyFocal()) style="object-position: {{ $user->avatarObjectPosition() }}" @endif
                        >
                    </div>
                    {{-- min-w-0 + w-full İKİSİ BİRDEN gerekli — ölçüldü, tek başına hiçbiri
                         yetmiyor. input[type=file] native genişliği 257px'te sabittir (dosya adı
                         uzasa bile büyümez, içeride kısaltılır) ve bu blok 96px avatarın flex
                         kardeşi olduğu için 393px'lik telefonda 17px yatay taşma yapıyordu.
                         min-w-0 flex öğesinin varsayılan `min-width:auto` kilidini açar — yoksa
                         öğe içeriğinin altına inemez; w-full ise girdiyi artık küçülebilen
                         kolona bağlar. Biri eksikse taşma geri gelir. --}}
                    <div class="min-w-0">
                        <label for="avatar" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Profil fotoğrafı</label>
                        <input id="avatar" name="avatar" type="file" accept="image/*" class="mt-1 w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                        @error('avatar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="button" @click="openModal()" class="mt-2 flex items-center gap-1 text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                            <span aria-hidden="true">✏️</span> Profil fotoğrafını düzenle
                        </button>
                    </div>

                    <template x-teleport="body">
                        <div
                            x-show="open"
                            x-transition.opacity.duration.150ms
                            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4"
                            @click.self="cancel()"
                            @keydown.escape.window="open && cancel()"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="avatar-align-title"
                            x-cloak
                        >
                            {{-- DİKKAT: x-transition.opacity ŞART (scale DEĞİL). openModal() bu panel
                                 içindeki çerçeveyi getBoundingClientRect() ile ölçüyor; varsayılan Alpine
                                 geçişi scale(0.95→1) da uygulardı ve ölçüm geçiş bitmeden alınınca çerçeve
                                 %5 küçük ölçülüp kaydedilen kırpım gerçekte görünenden dar/kaymış çıkardı
                                 (2026-07-17 karşıt inceleme raporu). Sadece opacity, transform'u hiç
                                 etkilemez — ölçüm ne zaman yapılırsa yapılsın doğru çıkar. --}}
                            <div
                                x-show="open"
                                x-transition.opacity.duration.150ms
                                class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800"
                            >
                                <div class="flex items-center justify-between gap-4 border-b border-stone-100 px-5 py-4 dark:border-stone-800">
                                    <h2 id="avatar-align-title" class="font-semibold text-stone-800 dark:text-stone-100">Profil fotoğrafını hizala</h2>
                                    <button type="button" @click="cancel()" class="rounded-full p-1.5 text-stone-600 transition hover:bg-stone-100 dark:hover:bg-stone-800" aria-label="Kapat">
                                        <x-heroicon-o-x-mark class="h-5 w-5" />
                                    </button>
                                </div>
                                <div class="flex flex-col items-center gap-3 px-5 py-6">
                                    {{-- Fotoğraf çerçeveden BÜYÜK çizilir (translate + piksel genişlik);
                                         daire overflow-hidden ile pencere görevi görür. object-position
                                         DEĞİL transform kullanılır — bkz. app.js avatarCropModal. --}}
                                    {{-- x-init ile eleman kaydı: $refs teleport dışından (düzenle butonu)
                                         teleport içindeki ref'lere erişemiyor — bkz. app.js avatarCropModal. --}}
                                    {{-- w-64 + aspect-square (h-64 DEĞİL): dar ekranlarda (<328px) max-w-full
                                         genişliği kısıtlar ama sabit h-64 yüksekliği kısıtlamazdı — çerçeve
                                         oval olur, kırpım karesi (frameW tabanlı) görünen alt/yan şeridi
                                         dışarıda bırakırdı (2026-07-17 karşıt inceleme raporu). aspect-square
                                         yüksekliği HER ZAMAN gerçek genişliğe eşitler. --}}
                                    <div
                                        x-init="registerFrame($el)"
                                        @pointerdown="startDrag($event)"
                                        @wheel.prevent="onWheel($event)"
                                        @dragstart.prevent="null"
                                        class="focal-drag-frame relative aspect-square w-64 max-w-full cursor-move touch-none select-none overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-900/40"
                                    >
                                        <img
                                            x-init="registerPhoto($el)"
                                            src="{{ Storage::url($user->avatar_path) }}"
                                            alt=""
                                            draggable="false"
                                            class="absolute left-0 top-0 origin-top-left"
                                            :style="imgStyle"
                                            x-show="ready"
                                        >
                                    </div>
                                    <label class="flex w-full max-w-64 items-center gap-2 text-stone-600 dark:text-stone-400">
                                        <x-heroicon-o-magnifying-glass-minus class="h-4 w-4 shrink-0" />
                                        <input
                                            type="range"
                                            min="1"
                                            max="3"
                                            step="0.01"
                                            :value="zoom"
                                            @input="onSlider($event.target.value)"
                                            class="w-full accent-emerald-600"
                                            aria-label="Yakınlaştırma"
                                        >
                                        <x-heroicon-o-magnifying-glass-plus class="h-4 w-4 shrink-0" />
                                    </label>
                                    <p class="text-center text-xs text-stone-600 dark:text-stone-400">Sürükleyerek kaydır; iki parmakla sıkıştırarak veya kaydırıcıyla yakınlaştır.</p>
                                    <p x-show="error" x-text="error" class="text-center text-xs font-medium text-red-600 dark:text-red-400"></p>
                                </div>
                                <div class="flex items-center justify-end gap-2 border-t border-stone-100 px-5 py-3 dark:border-stone-800">
                                    <button type="button" @click="cancel()" class="rounded-lg px-4 py-2 text-sm font-semibold text-stone-600 transition hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800">İptal</button>
                                    <button type="button" @click="save()" :disabled="saving" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                                        <span x-show="!saving">Kaydet</span>
                                        <span x-show="saving">Kaydediliyor...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @else
                <div class="flex items-center gap-4">
                    <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-full bg-emerald-100 text-3xl font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                    {{-- min-w-0 + w-full: yukarıdaki avatarlı daldaki notun aynısı. İki dal da
                         96px'lik bir daire + dosya girdisi barındıran aynı flex satırı olduğu
                         için taşma ikisinde de oluşur; düzeltme ikisine de uygulanmalı. --}}
                    <div class="min-w-0">
                        <label for="avatar" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Profil fotoğrafı</label>
                        <input id="avatar" name="avatar" type="file" accept="image/*" class="mt-1 w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                        @error('avatar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ad Soyad</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Kullanıcı adı</label>
                    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="bio" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Hakkında <span class="text-stone-600">(ops.)</span></label>
                <textarea id="bio" name="bio" rows="3" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('bio', $user->bio) }}</textarea>
                @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="skills" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yetenekler <span class="text-stone-600">(ops.)</span></label>
                <input id="skills" name="skills" type="text" value="{{ old('skills', $user->skills ? implode(', ', $user->skills) : '') }}" placeholder="ör. İngilizce, Web Tasarım, Photoshop" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">Virgülle ayırarak yaz, profilinde rozet olarak görünür.</p>
                @error('skills') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="country_code" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Ülke</label>
                    <select id="country_code" name="country_code" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @foreach ($countries as $country)
                            <option value="{{ $country->code }}" @selected(old('country_code', $user->country_code) === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Şehir</label>
                    <input id="city" name="city" type="text" value="{{ old('city', $user->city) }}" list="city-options" autocomplete="off" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    @include('partials.city-datalist')
                </div>
                <div>
                    <label for="preferred_currency" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Para birimi</label>
                    <select id="preferred_currency" name="preferred_currency" required class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->code }}" @selected(old('preferred_currency', $user->preferred_currency) === $currency->code)>{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-800 dark:bg-emerald-950/20">
                <label class="flex items-start gap-3">
                    <input type="hidden" name="is_searchable" value="0">
                    <input type="checkbox" id="is_searchable" name="is_searchable" value="1" @checked(old('is_searchable', $user->is_searchable)) class="mt-0.5 rounded border-stone-300 text-emerald-700 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                    <span class="text-sm text-stone-700 dark:text-stone-300">
                        <span class="font-medium">💼 Yetenek Havuzu'nda görün</span> — işverenler seni <a href="{{ route('candidates.index') }}" class="text-emerald-700 underline dark:text-emerald-400">Yetenek Havuzu</a>'nda arayıp bulabilsin (isim, bio, yetenekler ve şehir/ülke bilgin görünür).
                    </span>
                </label>
                <div class="mt-3">
                    <label for="job_category_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Uzmanlık alanı <span class="text-stone-600">(ops.)</span></label>
                    <select id="job_category_id" name="job_category_id" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        <option value="">Seçiniz</option>
                        @foreach ($jobCategories as $jobCategory)
                            <option value="{{ $jobCategory->id }}" @selected((int) old('job_category_id', $user->job_category_id) === $jobCategory->id)>{{ $jobCategory->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Profili Kaydet</button>
        </form>

        {{-- Ödeme yöntemleri --}}
        <section class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <header>
                <h2 class="font-semibold text-stone-800 dark:text-stone-100">💳 Ödeme yöntemlerim</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Nisoya ödemeleri işlemez — burada eklediğin kendi ödeme linkin veya QR kodun, diğer üyelere doğrudan sana ödeme yapmalarını kolaylaştırır. Ülkene göre önerilen yöntemler: {{ collect($suggestedPaymentMethods)->map->getLabel()->join(', ') }}.
                </p>
            </header>

            @if ($paymentLinks->isNotEmpty())
                <ul class="space-y-2">
                    @foreach ($paymentLinks as $link)
                        <li class="flex items-center gap-3 rounded-lg border border-stone-200 p-3 dark:border-stone-700">
                            @if ($link->qr_path)
                                <img src="{{ Storage::url($link->qr_path) }}" alt="QR kod" class="h-12 w-12 rounded object-cover">
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-stone-800 dark:text-stone-100"><span aria-hidden="true">{{ $link->method->icon() }}</span> {{ $link->method->getLabel() }}</div>
                                @if ($link->detail)
                                    <div class="truncate text-xs text-stone-500 dark:text-stone-400">{{ $link->detail }}</div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('panel.payment-links.destroy', $link) }}" onsubmit="return confirm('Bu ödeme yöntemini kaldırmak istediğine emin misin?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/30">Kaldır</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($availablePaymentMethods->isNotEmpty())
                <form method="POST" action="{{ route('panel.payment-links.store') }}" enctype="multipart/form-data" class="space-y-3 border-t border-stone-100 pt-4 dark:border-stone-800">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="pl_method" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yöntem ekle</label>
                            <select id="pl_method" name="method" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                @foreach ($availablePaymentMethods as $method)
                                    <option value="{{ $method->value }}" @selected(old('method') === $method->value)>
                                        {{ $method->icon() }} {{ $method->getLabel() }}@if (in_array($method, $suggestedPaymentMethods, true)) (önerilen)@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('method') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="pl_detail" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Bağlantı veya bilgi <span class="text-stone-600">(ops.)</span></label>
                            <input id="pl_detail" name="detail" type="text" value="{{ old('detail') }}" placeholder="ör. paypal.me/kullaniciadi veya IBAN" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @error('detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="pl_qr" class="block text-sm font-medium text-stone-700 dark:text-stone-300">QR kod görseli <span class="text-stone-600">(ops.)</span></label>
                        <input id="pl_qr" name="qr" type="file" accept="image/*" class="mt-1 text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                        @error('qr') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">+ Ödeme yöntemi ekle</button>
                </form>
            @endif
        </section>

        {{-- Portfolyo --}}
        <section class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <header>
                <h2 class="font-semibold text-stone-800 dark:text-stone-100">🖼️ Portfolyo</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Geçmiş iş örneklerinden fotoğraflar ekle, profilini ziyaret edenler yeteneklerini görsün. En fazla {{ \App\Http\Controllers\PortfolioItemController::MAX_ITEMS }} görsel.
                </p>
            </header>

            @error('image') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            @if ($portfolioItems->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($portfolioItems as $item)
                        <div class="group relative overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700">
                            <img src="{{ $item->url('medium') }}" alt="{{ $item->caption }}" class="aspect-square w-full object-cover">
                            @if ($item->caption)
                                <div class="absolute inset-x-0 bottom-0 truncate bg-stone-900/60 px-2 py-1 text-xs text-white">{{ $item->caption }}</div>
                            @endif
                            <form method="POST" action="{{ route('panel.portfolio.destroy', $item) }}" class="absolute right-1 top-1" onsubmit="return confirm('Bu görseli kaldırmak istediğine emin misin?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="grid h-6 w-6 place-items-center rounded-full bg-stone-900/70 text-white opacity-0 transition group-hover:opacity-100" aria-label="Kaldır">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($portfolioItems->count() < \App\Http\Controllers\PortfolioItemController::MAX_ITEMS)
                <form method="POST" action="{{ route('panel.portfolio.store') }}" enctype="multipart/form-data" class="space-y-3 border-t border-stone-100 pt-4 dark:border-stone-800">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="pf_image" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Görsel</label>
                            <input id="pf_image" name="image" type="file" accept="image/*" class="mt-1 text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:text-stone-400 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                        </div>
                        <div>
                            <label for="pf_caption" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Açıklama <span class="text-stone-600">(ops.)</span></label>
                            <input id="pf_caption" name="caption" type="text" value="{{ old('caption') }}" placeholder="ör. Mutfak tadilatı, 2026" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        </div>
                    </div>
                    <button type="submit" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">+ Portfolyo görseli ekle</button>
                </form>
            @endif
        </section>

        {{-- Şifre değiştir --}}
        <form method="POST" action="{{ route('panel.profile.password') }}" class="mt-6 space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            @csrf
            @method('PUT')

            <h2 class="font-semibold text-stone-800 dark:text-stone-100">Şifre Değiştir</h2>

            @if (session('status_password'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status_password') }}</div>
            @endif

            <div>
                <label for="current_password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Mevcut şifre</label>
                <x-password-input id="current_password" name="current_password" autocomplete="current-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100" />
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yeni şifre</label>
                    <x-password-input id="new_password" name="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100" />
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Yeni şifre (tekrar)</label>
                    <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="mt-1 w-full rounded-lg border-stone-300 px-3 py-2 pr-10 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100" />
                </div>
            </div>

            <button type="submit" class="rounded-lg border border-stone-300 px-5 py-2.5 font-semibold text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800">Şifreyi Güncelle</button>
        </form>

        {{-- KVKK: Verilerini indir / Hesabı sil --}}
        <section class="mt-6 space-y-4 rounded-2xl border border-amber-200 bg-amber-50/50 p-6 dark:border-amber-800 dark:bg-amber-950/20">
            <header>
                <h2 class="font-semibold text-amber-900 dark:text-amber-200">🔐 Güvenlik</h2>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                    Hesabını iki faktörlü kimlik doğrulama ile koru.
                </p>
            </header>

            <a
                href="{{ route('panel.profile.2fa') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-50 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50"
            >
                <span aria-hidden="true">🔑</span>
                İki Faktörlü Doğrulama (2FA) Yönet
            </a>
        </section>

        {{-- KVKK: Verilerini indir / Hesabı sil --}}
        <section class="mt-6 space-y-4 rounded-2xl border border-blue-200 bg-blue-50/50 p-6 dark:border-blue-800 dark:bg-blue-950/20">
            <header>
                <h2 class="font-semibold text-blue-900 dark:text-blue-200">Kişisel verilerin (KVKK)</h2>
                <p class="mt-1 text-sm text-blue-800 dark:text-blue-300">
                    6698 sayılı KVKK kapsamında verilerine erişme, dışa aktarma ve silme haklarına sahipsin.
                </p>
            </header>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a
                    href="{{ route('panel.profile.export') }}"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-blue-300 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200 dark:hover:bg-blue-900/50"
                >
                    <span aria-hidden="true">⬇️</span>
                    Verilerimi indir (JSON)
                </a>
            </div>
        </section>

        {{-- Hesabı Sil --}}
        <section
            x-data="{ open: false }"
            class="mt-6 space-y-4 rounded-2xl border border-red-200 bg-red-50/50 p-6 dark:border-red-800 dark:bg-red-950/20"
        >
            <header>
                <h2 class="font-semibold text-red-900 dark:text-red-200">Hesabı sil</h2>
                <p class="mt-1 text-sm text-red-800 dark:text-red-300">
                    Hesabın silindiğinde tüm ilanların, yorumların ve kişisel verilerin kalıcı olarak temizlenir.
                    Bu işlem geri alınamaz.
                </p>
            </header>

            <button
                type="button"
                @click="open = !open"
                class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200 dark:hover:bg-red-900/50"
            >
                Hesabımı silmek istiyorum
            </button>

            <form
                x-show="open"
                x-transition.opacity
                x-cloak
                method="POST"
                action="{{ route('panel.profile.destroy') }}"
                class="space-y-3 border-t border-red-200 pt-4 dark:border-red-800"
                onsubmit="return confirm('Bu işlem geri alınamaz. Hesabınız silinecek. Emin misiniz?');"
            >
                @csrf
                @method('DELETE')

                <div>
                    <label for="delete_current_password" class="block text-sm font-medium text-red-800 dark:text-red-200">Mevcut şifren</label>
                    <x-password-input id="delete_current_password" name="current_password" required class="mt-1 w-full rounded-lg border-red-300 px-3 py-2 pr-10 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-red-700 dark:bg-red-950/40 dark:text-red-100" />
                    @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="delete_confirm_text" class="block text-sm font-medium text-red-800 dark:text-red-200">
                        Onaylamak için tam olarak <code class="rounded bg-red-100 px-1.5 py-0.5 font-mono text-red-700 dark:bg-red-900/40 dark:text-red-200">HESABIMI SİL</code> yaz
                    </label>
                    <input id="delete_confirm_text" name="confirm_text" type="text" required autocomplete="off" placeholder="HESABIMI SİL" class="mt-1 w-full rounded-lg border-red-300 px-3 py-2 font-mono text-sm uppercase shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-red-700 dark:bg-red-950/40 dark:text-red-100">
                    @error('confirm_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400 dark:text-stone-900">
                    Hesabımı kalıcı olarak sil
                </button>
            </form>
        </section>
    </div>
</x-layouts.app>

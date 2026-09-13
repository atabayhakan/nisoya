<x-layouts.app>
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-100 pb-4 dark:border-stone-800">
                <a href="{{ route('football.teams.show', ['city' => \Illuminate\Support\Str::slug($team->city), 'team' => $team->slug]) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← Takım Profiline Dön
                </a>
                <h1 class="mt-1 text-2xl font-bold text-stone-900 dark:text-stone-100">
                    Takım Bilgilerini Düzenle
                </h1>
            </div>

            <form method="POST" action="{{ route('football.teams.update', $team) }}" enctype="multipart/form-data" id="teamForm" class="mt-6 space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="ai_logo_path" id="ai_logo_path" value="{{ old('ai_logo_path') }}">

                @if ($errors->any())
                    <div class="rounded-2xl bg-rose-50 p-4 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- EA Sports FC Club Creator Header & AI Crest Showcase --}}
                <div class="overflow-hidden rounded-2xl border border-amber-400/40 bg-gradient-to-br from-stone-900 via-stone-800 to-amber-950/40 p-5 text-white shadow-lg">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        {{-- Live Crest Box --}}
                        <div class="relative flex-shrink-0">
                            <div id="crestPreviewBox" class="flex h-36 w-32 items-center justify-center rounded-2xl border-2 border-amber-400/80 bg-stone-950/90 p-2 shadow-inner shadow-amber-500/20">
                                @if ($team->logo_url)
                                    <div id="crestPlaceholder" class="h-full w-full flex items-center justify-center">
                                        <img src="{{ $team->logo_url }}" alt="{{ $team->name }}" class="max-h-full max-w-full object-contain">
                                    </div>
                                    <div id="crestSvgContainer" class="hidden h-full w-full flex items-center justify-center"></div>
                                @else
                                    <div id="crestPlaceholder" class="text-center">
                                        <div class="text-3xl">🛡️</div>
                                        <span class="mt-1 block text-[10px] font-bold uppercase tracking-wider text-amber-300">EA FC Arma</span>
                                        <span class="text-[9px] text-stone-400">Yapay zeka ile üret</span>
                                    </div>
                                    <div id="crestSvgContainer" class="hidden h-full w-full flex items-center justify-center"></div>
                                @endif
                            </div>
                            <span class="absolute -bottom-2 -right-2 rounded-full bg-amber-500 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-stone-950 shadow">
                                FC 26
                            </span>
                        </div>

                        {{-- AI Crest Controls --}}
                        <div class="flex-1 text-center sm:text-left">
                            <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 px-2.5 py-0.5 text-[11px] font-bold text-amber-300">
                                <span>⚡ AI Crest Generator</span>
                            </div>
                            <h3 class="mt-1 text-base font-extrabold tracking-tight text-white">
                                Kulüp Armasını Yenile / Üret
                            </h3>
                            <p class="mt-1 text-xs text-stone-300 leading-relaxed">
                                Mevcut veya yeni forma renklerinize göre metalik kalkan, kulüp monogramı ve maskot içeren profesyonel EA FC arması oluşturun.
                            </p>

                            <div class="mt-3.5 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-300">Maskot / Sembol</label>
                                    <select id="aiSymbolSelect" class="mt-1 w-full rounded-lg border border-stone-700 bg-stone-900/90 px-2.5 py-1.5 text-xs text-stone-100 focus:border-amber-400 focus:outline-none">
                                        @foreach ($availableSymbols ?? [] as $symKey => $symLabel)
                                            <option value="{{ $symKey }}">{{ $symLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-300">Kalkan Stili</label>
                                    <select id="aiStyleSelect" class="mt-1 w-full rounded-lg border border-stone-700 bg-stone-900/90 px-2.5 py-1.5 text-xs text-stone-100 focus:border-amber-400 focus:outline-none">
                                        @foreach ($availableStyles ?? [] as $stKey => $stLabel)
                                            <option value="{{ $stKey }}">{{ $stLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <button type="button" id="btnGenerateAiCrest" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-xs font-black text-stone-950 shadow-md transition hover:from-amber-400 hover:to-amber-500 active:scale-95">
                                    <span id="aiBtnSpinner" class="hidden animate-spin">🌀</span>
                                    <span id="aiBtnText">✨ Yeni Armayı Üret</span>
                                </button>
                                <span id="aiSuccessBadge" class="hidden text-xs font-semibold text-emerald-400">
                                    ✓ Arma başarıyla üretildi!
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Takım Adı --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Adı *</label>
                    <input type="text" name="name" id="teamNameInput" value="{{ old('name', $team->name) }}" required
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                </div>

                {{-- Şehir & Ülke --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Şehir *</label>
                        <input type="text" name="city" id="teamCityInput" value="{{ old('city', $team->city) }}" required
                               class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Ülke *</label>
                        <select name="country_code" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            @foreach ($countries as $country)
                                <option value="{{ $country->code }}" @selected(old('country_code', $team->country_code) === $country->code)>
                                    {{ $country->emoji }} {{ $country->name_tr }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Takım Seviyesi --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Seviyesi *</label>
                    <select name="level" required class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        <option value="baslangic" @selected(old('level', $team->level->value) === 'baslangic')>🟢 Başlangıç (Hobi / Keyif)</option>
                        <option value="orta" @selected(old('level', $team->level->value) === 'orta')>🔵 Orta (Düzenli oynayanlar)</option>
                        <option value="iyi" @selected(old('level', $team->level->value) === 'iyi')>🟣 İyi (Tempolu / Teknik)</option>
                        <option value="ileri" @selected(old('level', $team->level->value) === 'ileri')>🔴 İleri (Eski lisanslı / Turnuva takımı)</option>
                    </select>
                </div>

                {{-- Forma Renkleri & Canlı Kit Barı --}}
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-900/50">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Forma Renkleri</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] text-stone-500 dark:text-stone-400">Canlı Kit:</span>
                            <div id="kitLivePreview" class="flex h-5 w-12 overflow-hidden rounded-md border border-stone-300 shadow-sm dark:border-stone-700">
                                <div id="kitPrimaryBar" class="w-1/2 bg-red-600 transition-colors"></div>
                                <div id="kitSecondaryBar" class="w-1/2 bg-stone-900 transition-colors"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-stone-600 dark:text-stone-400">Birincil Forma Rengi</label>
                            <input type="text" name="primary_kit_color" id="primaryKitInput" value="{{ old('primary_kit_color', $team->primary_kit_color ?? 'Kırmızı') }}" placeholder="Örn: Kırmızı, Bordo, #dc2626"
                                   class="mt-1 w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-stone-600 dark:text-stone-400">İkincil Forma Rengi</label>
                            <input type="text" name="secondary_kit_color" id="secondaryKitInput" value="{{ old('secondary_kit_color', $team->secondary_kit_color ?? 'Beyaz') }}" placeholder="Örn: Beyaz, Siyah, #0f172a"
                                   class="mt-1 w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                        </div>
                    </div>

                    {{-- Hızlı Renk Paleti --}}
                    <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px]">
                        <span class="text-stone-400">Hızlı Renkler:</span>
                        <button type="button" class="quick-color-btn rounded-md border border-stone-300 px-2 py-0.5 dark:border-stone-700 hover:bg-stone-200 dark:hover:bg-stone-800" data-p="Kırmızı" data-s="Beyaz">🔴⚪ Kırmızı-Beyaz</button>
                        <button type="button" class="quick-color-btn rounded-md border border-stone-300 px-2 py-0.5 dark:border-stone-700 hover:bg-stone-200 dark:hover:bg-stone-800" data-p="Sarı" data-s="Lacivert">🟡🔵 Sarı-Lacivert</button>
                        <button type="button" class="quick-color-btn rounded-md border border-stone-300 px-2 py-0.5 dark:border-stone-700 hover:bg-stone-200 dark:hover:bg-stone-800" data-p="Sarı" data-s="Kırmızı">🟡🔴 Sarı-Kırmızı</button>
                        <button type="button" class="quick-color-btn rounded-md border border-stone-300 px-2 py-0.5 dark:border-stone-700 hover:bg-stone-200 dark:hover:bg-stone-800" data-p="Siyah" data-s="Beyaz">⚫⚪ Siyah-Beyaz</button>
                        <button type="button" class="quick-color-btn rounded-md border border-stone-300 px-2 py-0.5 dark:border-stone-700 hover:bg-stone-200 dark:hover:bg-stone-800" data-p="Bordo" data-s="Mavi">🟣🔵 Bordo-Mavi</button>
                    </div>
                </div>

                {{-- Logo Güncelleme --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Yeni Özel Logo Yükle</label>
                    <input type="file" name="logo" accept="image/*"
                           class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">Yapay zeka armasını kullandıysanız dosya seçmenize gerek yoktur.</p>
                </div>

                {{-- Açıklama --}}
                <div>
                    <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Takım Açıklaması</label>
                    <textarea name="description" rows="3"
                              class="mt-1.5 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">{{ old('description', $team->description) }}</textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 py-3.5 text-sm font-bold text-white shadow-md transition hover:from-emerald-500 hover:to-teal-600 active:scale-[0.99]">
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const teamNameInput = document.getElementById('teamNameInput');
            const teamCityInput = document.getElementById('teamCityInput');
            const primaryKitInput = document.getElementById('primaryKitInput');
            const secondaryKitInput = document.getElementById('secondaryKitInput');
            const aiSymbolSelect = document.getElementById('aiSymbolSelect');
            const aiStyleSelect = document.getElementById('aiStyleSelect');
            const btnGenerateAiCrest = document.getElementById('btnGenerateAiCrest');
            const aiBtnSpinner = document.getElementById('aiBtnSpinner');
            const aiBtnText = document.getElementById('aiBtnText');
            const aiSuccessBadge = document.getElementById('aiSuccessBadge');
            const crestPlaceholder = document.getElementById('crestPlaceholder');
            const crestSvgContainer = document.getElementById('crestSvgContainer');
            const aiLogoPathInput = document.getElementById('ai_logo_path');
            const kitPrimaryBar = document.getElementById('kitPrimaryBar');
            const kitSecondaryBar = document.getElementById('kitSecondaryBar');

            const colorMap = {
                'kirmizi': '#dc2626', 'beyaz': '#f8fafc', 'siyah': '#0f172a',
                'mavi': '#2563eb', 'sari': '#eab308', 'yesil': '#16a34a',
                'lacivert': '#1e3a8a', 'bordo': '#881337', 'turuncu': '#ea580c'
            };

            function updateKitBars() {
                const pVal = (primaryKitInput.value || '').toLowerCase().trim();
                const sVal = (secondaryKitInput.value || '').toLowerCase().trim();

                for (const [name, hex] of Object.entries(colorMap)) {
                    if (pVal.includes(name)) kitPrimaryBar.style.backgroundColor = hex;
                    if (sVal.includes(name)) kitSecondaryBar.style.backgroundColor = hex;
                }
            }

            primaryKitInput.addEventListener('input', updateKitBars);
            secondaryKitInput.addEventListener('input', updateKitBars);
            updateKitBars();

            document.querySelectorAll('.quick-color-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    primaryKitInput.value = btn.dataset.p;
                    secondaryKitInput.value = btn.dataset.s;
                    updateKitBars();
                });
            });

            btnGenerateAiCrest.addEventListener('click', async () => {
                const teamName = teamNameInput.value.trim();
                if (!teamName) {
                    alert('Lütfen önce bir takım adı yazın.');
                    teamNameInput.focus();
                    return;
                }

                aiBtnSpinner.classList.remove('hidden');
                aiBtnText.textContent = 'Arma Üretiliyor...';
                btnGenerateAiCrest.disabled = true;

                try {
                    const response = await fetch('{{ route("football.teams.ai-logo") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name: teamName,
                            city: teamCityInput.value.trim() || 'İSTANBUL',
                            primary_color: primaryKitInput.value.trim(),
                            secondary_color: secondaryKitInput.value.trim(),
                            symbol: aiSymbolSelect.value,
                            style: aiStyleSelect.value
                        })
                    });

                    const data = await response.json();
                    if (data.success && data.svg) {
                        crestPlaceholder.classList.add('hidden');
                        crestSvgContainer.innerHTML = data.svg;
                        crestSvgContainer.classList.remove('hidden');
                        aiLogoPathInput.value = data.path;
                        aiSuccessBadge.classList.remove('hidden');
                    } else {
                        alert(data.message || 'Arma üretilirken bir hata oluştu.');
                    }
                } catch (e) {
                    alert('Bağlantı hatası: Arma üretilemedi.');
                } finally {
                    aiBtnSpinner.classList.add('hidden');
                    aiBtnText.textContent = '✨ Yeniden Üret';
                    btnGenerateAiCrest.disabled = false;
                }
            });
        });
    </script>
    @endpush
        </div>
    </div>
</x-layouts.app>

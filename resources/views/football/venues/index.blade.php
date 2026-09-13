<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← {{ $currentCity }} Futbol Ana Sayfası
                </a>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-100">
                    {{ $currentCity }} Halı Sahaları & Tesisler
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Şehrindeki en iyi halı sahaları, zemin ve tesis özelliklerini, fiyatları ve oyuncu yorumlarını incele.
                </p>
            </div>
            <a href="{{ route('football.venues.create') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                + Halı Saha Ekle
            </a>
        </div>

        {{-- Üst Aksiyon & AI Kaşif Barı --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-stone-200 bg-gradient-to-r from-stone-900 via-stone-800 to-emerald-950 p-4 text-white shadow-md">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="btnFindNearestVenues" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2.5 text-xs font-black text-stone-950 shadow transition hover:from-emerald-400 hover:to-teal-400 active:scale-95">
                    <span id="geoSpinner" class="hidden animate-spin">🌀</span>
                    <span id="geoIcon">📍</span>
                    <span id="geoText">En Yakın Halı Sahaları Bul</span>
                </button>

                <button type="button" id="btnOpenAiScoutModal" class="inline-flex items-center gap-2 rounded-2xl border border-amber-400/60 bg-amber-500/20 px-4 py-2.5 text-xs font-bold text-amber-300 transition hover:bg-amber-500/30">
                    <span>🤖</span>
                    <span>AI Halı Saha Kaşifi (Akıllı Öneri)</span>
                </button>
            </div>

            @if ($userLat && $userLng)
                <div class="flex items-center gap-2 text-xs text-emerald-300">
                    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                    <span>Konumunuza göre en yakından sıralandı</span>
                    <a href="{{ route('football.venues.index', \Illuminate\Support\Str::slug($currentCity)) }}" class="rounded-lg bg-stone-800 px-2 py-1 text-2xs text-stone-300 hover:text-white">
                        ✕ Sıfırla
                    </a>
                </div>
            @endif
        </div>

        {{-- Arama & Filtreleme --}}
        <form method="GET" class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            @if ($userLat && $userLng)
                <input type="hidden" name="lat" value="{{ $userLat }}">
                <input type="hidden" name="lng" value="{{ $userLng }}">
            @endif
            <div class="min-w-[200px] flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Saha adı veya semt/adres ara..."
                       class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            </div>
            <div>
                <select name="pitch_type" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Tüm Saha Tipleri</option>
                    <option value="kapali" @selected(request('pitch_type') === 'kapali')>Kapalı Saha</option>
                    <option value="acik" @selected(request('pitch_type') === 'acik')>Açık Saha</option>
                    <option value="yari_acik" @selected(request('pitch_type') === 'yari_acik')>Yarı Açık</option>
                </select>
            </div>
            <div>
                <select name="surface_type" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Tüm Zeminler</option>
                    <option value="suni_cim" @selected(request('surface_type') === 'suni_cim')>Suni Çim</option>
                    <option value="dogal_cim" @selected(request('surface_type') === 'dogal_cim')>Doğal Çim</option>
                    <option value="parke" @selected(request('surface_type') === 'parke')>Parke / Salon</option>
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-800 dark:bg-stone-700 dark:hover:bg-stone-600">
                Filtrele
            </button>
        </form>

        @if ($venues->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-stone-200 p-12 text-center text-stone-500 dark:border-stone-800 dark:text-stone-400">
                <p class="text-base font-semibold">Bu şehirde aradığınız kriterlere uygun halı saha bulunamadı.</p>
                <a href="{{ route('football.venues.create') }}" class="mt-3 inline-block font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    Bildiğin halı sahayı hemen ekle!
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($venues as $venue)
                    @php
                        $dist = ($userLat && $userLng) ? $venue->distanceFrom($userLat, $userLng) : null;
                    @endphp
                    <div class="flex flex-col justify-between rounded-3xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900">
                        <div>
                            @if ($venue->cover_image_path)
                                <div class="relative h-40 w-full overflow-hidden rounded-2xl bg-stone-100">
                                    <img src="{{ Storage::url($venue->cover_image_path) }}" alt="{{ $venue->name }}" class="h-full w-full object-cover">
                                    @if ($dist !== null)
                                        <span class="absolute top-2 right-2 rounded-xl bg-emerald-600/90 backdrop-blur-sm px-2.5 py-1 text-2xs font-extrabold text-white shadow">
                                            📍 {{ $dist }} km
                                        </span>
                                    @endif
                                </div>
                            @elseif ($dist !== null)
                                <div class="mb-2">
                                    <span class="inline-flex items-center gap-1 rounded-xl bg-emerald-50 px-2.5 py-1 text-2xs font-extrabold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        📍 Konumunuza {{ $dist }} km mesafede
                                    </span>
                                </div>
                            @endif

                            <div class="mt-3 flex items-start justify-between gap-2">
                                <div>
                                    <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100">
                                        <a href="{{ route('football.venues.show', ['city' => \Illuminate\Support\Str::slug($venue->city), 'venue' => $venue->slug]) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">
                                            {{ $venue->name }}
                                        </a>
                                    </h2>
                                    <p class="mt-1 text-xs text-stone-500 line-clamp-1 dark:text-stone-400">
                                        📍 {{ $venue->address }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-xl bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                    ⭐ {{ number_format($venue->rating, 1) }}
                                </span>
                            </div>

                            {{-- Harita Rota Butonları --}}
                            <div class="mt-2.5 flex items-center gap-1.5 text-[11px]">
                                @if ($venue->getGoogleMapsUrl())
                                    <a href="{{ $venue->getGoogleMapsUrl() }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-stone-50 px-2 py-1 font-semibold text-stone-700 transition hover:bg-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                                        <span>🗺️</span>
                                        <span>Google Rota</span>
                                    </a>
                                @endif
                                @if ($venue->getYandexMapsUrl())
                                    <a href="{{ $venue->getYandexMapsUrl() }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-stone-50 px-2 py-1 font-semibold text-stone-700 transition hover:bg-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                                        <span>🟡</span>
                                        <span>Yandex</span>
                                    </a>
                                @endif
                                @if ($venue->getAppleMapsUrl())
                                    <a href="{{ $venue->getAppleMapsUrl() }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-stone-50 px-2 py-1 font-semibold text-stone-700 transition hover:bg-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                                        <span>🍎</span>
                                        <span>Apple</span>
                                    </a>
                                @endif
                            </div>

                            {{-- Özellik Etiketleri --}}
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span class="rounded-lg bg-stone-100 px-2 py-0.5 text-3xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    {{ \App\Models\FootballVenue::PITCH_TYPES[$venue->pitch_type] ?? $venue->pitch_type }}
                                </span>
                                <span class="rounded-lg bg-stone-100 px-2 py-0.5 text-3xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    {{ \App\Models\FootballVenue::SURFACE_TYPES[$venue->surface_type] ?? $venue->surface_type }}
                                </span>
                                @if (! empty($venue->features) && is_array($venue->features))
                                    @foreach (array_slice($venue->features, 0, 3) as $feat)
                                        <span class="rounded-lg bg-emerald-50 px-2 py-0.5 text-3xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            {{ \App\Models\FootballVenue::FEATURE_OPTIONS[$feat] ?? $feat }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>

                            @if ($venue->price_info)
                                <p class="mt-3 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                    💰 {{ $venue->price_info }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800">
                            <a href="{{ route('football.venues.show', ['city' => \Illuminate\Support\Str::slug($venue->city), 'venue' => $venue->slug]) }}"
                               class="flex w-full items-center justify-center rounded-xl bg-stone-50 py-2 text-xs font-bold text-stone-800 transition hover:bg-emerald-50 hover:text-emerald-800 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">
                                Saha Detayı & Yorumlar ({{ $venue->reviews_count }}) →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $venues->links() }}
            </div>
        @endif
    </div>

    {{-- AI Pitch Scout Modal --}}
    <div id="aiScoutModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-stone-950/70 p-4 backdrop-blur-sm">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-3xl border border-stone-200 bg-white p-6 shadow-2xl dark:border-stone-800 dark:bg-stone-900">
            <div class="flex items-center justify-between border-b border-stone-100 pb-4 dark:border-stone-800">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🤖</span>
                    <div>
                        <h3 class="text-base font-extrabold text-stone-900 dark:text-stone-100">AI Halı Saha Kaşifi</h3>
                        <p class="text-xs text-stone-500 dark:text-stone-400">Yapay zeka kriterlerinize göre en iyi 3 tesisi analiz edip önerir</p>
                    </div>
                </div>
                <button type="button" id="btnCloseAiScoutModal" class="rounded-xl p-1.5 text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800">
                    ✕
                </button>
            </div>

            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">Saha Tipi</label>
                        <select id="scoutPitchType" class="mt-1 w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Fark Etmez</option>
                            <option value="kapali">Kapalı Saha (Yağmur Geçirmez)</option>
                            <option value="acik">Açık Saha</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">Zemin Tipi</label>
                        <select id="scoutSurfaceType" class="mt-1 w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                            <option value="">Fark Etmez</option>
                            <option value="suni_cim">Yeni Nesil Suni Çim</option>
                            <option value="dogal_cim">Doğal Çim</option>
                            <option value="parke">Salon / Parke</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">Öncelikli Tesis Olanakları</label>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 dark:border-stone-800">
                            <input type="checkbox" value="otopark" class="scout-feature-chk rounded text-emerald-600">
                            <span>🚗 Otopark</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 dark:border-stone-800">
                            <input type="checkbox" value="dus" class="scout-feature-chk rounded text-emerald-600">
                            <span>🚿 Sıcak Duş & Soyunma</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 dark:border-stone-800">
                            <input type="checkbox" value="gece_aydinlatmasi" class="scout-feature-chk rounded text-emerald-600">
                            <span>💡 Gece Aydınlatması</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 dark:border-stone-800">
                            <input type="checkbox" value="kafe" class="scout-feature-chk rounded text-emerald-600">
                            <span>☕ Kafe & Çay Ocağı</span>
                        </label>
                    </div>
                </div>

                <button type="button" id="btnExecuteScout" class="w-full rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 py-3 text-xs font-black text-white shadow-md transition hover:from-emerald-500 hover:to-teal-600 active:scale-95">
                    <span id="scoutSpinner" class="hidden animate-spin">🌀</span>
                    <span id="scoutBtnText">⚡ Yapay Zekadan En İyi Sahaları Öner</span>
                </button>

                {{-- AI Recommendations Container --}}
                <div id="aiScoutResults" class="hidden space-y-3 pt-2"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnFindNearest = document.getElementById('btnFindNearestVenues');
            const geoSpinner = document.getElementById('geoSpinner');
            const geoIcon = document.getElementById('geoIcon');
            const geoText = document.getElementById('geoText');

            if (btnFindNearest) {
                btnFindNearest.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        alert('Tarayıcınız konum servisini desteklemiyor.');
                        return;
                    }

                    geoSpinner.classList.remove('hidden');
                    geoIcon.classList.add('hidden');
                    geoText.textContent = 'Konumunuz alınıyor...';
                    btnFindNearest.disabled = true;

                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            const url = new URL(window.location.href);
                            url.searchParams.set('lat', lat.toFixed(5));
                            url.searchParams.set('lng', lng.toFixed(5));
                            window.location.href = url.toString();
                        },
                        (err) => {
                            geoSpinner.classList.add('hidden');
                            geoIcon.classList.remove('hidden');
                            geoText.textContent = 'En Yakın Halı Sahaları Bul';
                            btnFindNearest.disabled = false;
                            alert('Konum izni alınamadı: ' + err.message);
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                    );
                });
            }

            // AI Scout Modal Controls
            const btnOpenAiScoutModal = document.getElementById('btnOpenAiScoutModal');
            const btnCloseAiScoutModal = document.getElementById('btnCloseAiScoutModal');
            const aiScoutModal = document.getElementById('aiScoutModal');
            const btnExecuteScout = document.getElementById('btnExecuteScout');
            const scoutSpinner = document.getElementById('scoutSpinner');
            const scoutBtnText = document.getElementById('scoutBtnText');
            const aiScoutResults = document.getElementById('aiScoutResults');

            if (btnOpenAiScoutModal && aiScoutModal) {
                btnOpenAiScoutModal.addEventListener('click', () => aiScoutModal.classList.remove('hidden'));
                btnCloseAiScoutModal.addEventListener('click', () => aiScoutModal.classList.add('hidden'));

                btnExecuteScout.addEventListener('click', async () => {
                    scoutSpinner.classList.remove('hidden');
                    scoutBtnText.textContent = 'Yapay Zeka Tesisleri Analiz Ediyor...';
                    btnExecuteScout.disabled = true;
                    aiScoutResults.classList.add('hidden');
                    aiScoutResults.innerHTML = '';

                    const features = Array.from(document.querySelectorAll('.scout-feature-chk:checked')).map(c => c.value);

                    try {
                        const response = await fetch('{{ route("football.venues.ai-recommend") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                city: '{{ $currentCity }}',
                                pitch_type: document.getElementById('scoutPitchType').value || null,
                                surface_type: document.getElementById('scoutSurfaceType').value || null,
                                features: features,
                                lat: '{{ $userLat }}' || null,
                                lng: '{{ $userLng }}' || null
                            })
                        });

                        const data = await response.json();
                        if (data.success && data.recommendations && data.recommendations.length > 0) {
                            let html = '';
                            data.recommendations.forEach(rec => {
                                html += `
                                    <div class="rounded-2xl border border-emerald-500/40 bg-emerald-50/50 p-4 dark:bg-emerald-950/20">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h4 class="font-extrabold text-sm text-stone-900 dark:text-stone-100">${rec.name}</h4>
                                                <p class="text-[11px] text-stone-500 dark:text-stone-400">📍 ${rec.address}</p>
                                            </div>
                                            <span class="rounded-lg bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">⭐ ${rec.rating}</span>
                                        </div>
                                        <p class="mt-2 rounded-xl bg-white/80 p-2 text-xs font-semibold text-emerald-900 shadow-sm dark:bg-stone-800 dark:text-emerald-300">
                                            ${rec.ai_comment}
                                        </p>
                                        <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-3xs font-semibold">
                                            <span class="rounded-md bg-stone-200/70 px-2 py-0.5 text-stone-700 dark:bg-stone-800 dark:text-stone-300">${rec.pitch_type_label}</span>
                                            <span class="rounded-md bg-stone-200/70 px-2 py-0.5 text-stone-700 dark:bg-stone-800 dark:text-stone-300">${rec.surface_type_label}</span>
                                            ${rec.distance_text ? `<span class="rounded-md bg-emerald-600 px-2 py-0.5 text-white">📍 ${rec.distance_text}</span>` : ''}
                                        </div>
                                        <div class="mt-3 flex items-center gap-2 text-xs font-bold">
                                            <a href="${rec.url}" class="flex-1 rounded-xl bg-emerald-700 py-2 text-center text-white transition hover:bg-emerald-600">Saha Detayı →</a>
                                            ${rec.google_maps_url ? `<a href="${rec.google_maps_url}" target="_blank" class="rounded-xl border border-stone-300 bg-white px-3 py-2 text-stone-700 hover:bg-stone-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200">🗺️ Rota</a>` : ''}
                                        </div>
                                    </div>
                                `;
                            });
                            aiScoutResults.innerHTML = html;
                            aiScoutResults.classList.remove('hidden');
                        } else {
                            aiScoutResults.innerHTML = '<p class="text-xs text-center text-stone-500 py-4">Kriterlere uygun saha bulunamadı. Lütfen filtreleri genişletin.</p>';
                            aiScoutResults.classList.remove('hidden');
                        }
                    } catch (e) {
                        alert('Yapay zeka önerisi alınırken bir hata oluştu.');
                    } finally {
                        scoutSpinner.classList.add('hidden');
                        scoutBtnText.textContent = '⚡ Yapay Zekadan En İyi Sahaları Öner';
                        btnExecuteScout.disabled = false;
                    }
                });
            }
        });
    </script>
    @endpush
</x-layouts.app>

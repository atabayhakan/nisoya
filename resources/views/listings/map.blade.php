<x-layouts.app title="Haritada Keşfet — Nisoya">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

    <style>
        /* Leaflet Özel Marker ve Popup Stilleri */
        .custom-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 0 0 2px #ffffff;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            font-weight: 700;
            white-space: nowrap;
        }
        .custom-pin:hover, .custom-pin.is-active {
            transform: scale(1.15) translateY(-2px);
            z-index: 999 !important;
            box-shadow: 0 8px 20px rgba(4, 120, 87, 0.35), 0 0 0 3px #10b981;
        }
        .leaflet-popup-content-wrapper {
            padding: 0;
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(229, 231, 235, 0.8);
        }
        .dark .leaflet-popup-content-wrapper {
            background: #1c1917;
            border-color: #292524;
            color: #f5f5f4;
        }
        .leaflet-popup-content {
            margin: 0;
            line-height: 1.4;
        }
        .leaflet-popup-tip {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .dark .leaflet-popup-tip {
            background: #1c1917;
        }
        .leaflet-container {
            font-family: inherit;
        }
    </style>

    <div class="mx-auto max-w-7xl px-4 py-4 sm:py-6"
         x-data="mapExplorer(@js($points))"
         x-init="initMap()">

        {{-- Üst Kontrol & Filtre Başlığı --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <x-heroicon-o-map-pin class="h-5 w-5" />
                    </span>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                        Haritada Keşfet
                    </h1>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300"
                          x-text="filteredPoints.length + ' İlan Yayında'">
                        {{ $points->count() }} İlan Yayında
                    </span>
                </div>
                <p class="mt-1 text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                    Bulunduğun şehirde veya hedef ülkede Türkçe hizmet veren esnaf, ürün ve kariyer fırsatları
                </p>
            </div>

            {{-- Kategori & Tip Filtreleri --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center rounded-xl bg-stone-100 p-1 dark:bg-stone-800/80">
                    <a href="{{ route('listings.map') }}"
                       class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $tip === '' ? 'bg-white text-emerald-700 shadow-xs dark:bg-stone-900 dark:text-emerald-400' : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100' }}">
                        🌐 Tümü
                    </a>
                    <a href="{{ route('listings.map', ['tip' => 'hizmet']) }}"
                       class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $tip === 'hizmet' ? 'bg-white text-emerald-700 shadow-xs dark:bg-stone-900 dark:text-emerald-400' : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100' }}">
                        🧰 Hizmet
                    </a>
                    <a href="{{ route('listings.map', ['tip' => 'urun']) }}"
                       class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $tip === 'urun' ? 'bg-white text-emerald-700 shadow-xs dark:bg-stone-900 dark:text-emerald-400' : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100' }}">
                        📦 Ürün
                    </a>
                    <a href="{{ route('listings.map', ['tip' => 'is']) }}"
                       class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $tip === 'is' ? 'bg-white text-emerald-700 shadow-xs dark:bg-stone-900 dark:text-emerald-400' : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100' }}">
                        💼 İş İlanı
                    </a>
                </div>

                {{-- Liste Görünümüne Geçiş Butonu --}}
                <a href="{{ $tip === 'is' ? route('jobs.index') : route('listings.index', $tip ? ['tip' => $tip] : []) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-white px-3.5 py-1.5 text-xs font-bold text-stone-700 shadow-2xs transition hover:border-stone-300 hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700">
                    <x-heroicon-o-list-bullet class="h-4 w-4" />
                    <span>Liste →</span>
                </a>
            </div>
        </div>

        {{-- Mobil Görünüm Değiştirici (Harita / Liste Tab) --}}
        <div class="mt-3 flex rounded-xl border border-stone-200 bg-white p-1 shadow-2xs md:hidden dark:border-stone-800 dark:bg-stone-900">
            <button type="button"
                    @click="mobileTab = 'map'"
                    :class="mobileTab === 'map' ? 'bg-emerald-700 text-white shadow-xs' : 'text-stone-600 dark:text-stone-400'"
                    class="flex-1 rounded-lg py-2 text-center text-xs font-bold transition">
                🗺️ Harita
            </button>
            <button type="button"
                    @click="mobileTab = 'list'"
                    :class="mobileTab === 'list' ? 'bg-emerald-700 text-white shadow-xs' : 'text-stone-600 dark:text-stone-400'"
                    class="flex-1 rounded-lg py-2 text-center text-xs font-bold transition">
                📋 Liste (<span x-text="filteredPoints.length"></span>)
            </button>
        </div>

        {{-- Ana Split Alanı (Sol: İlan Listesi, Sağ: İnteraktif Harita) --}}
        <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-stretch md:h-[calc(100vh-210px)] min-h-[600px]">

            {{-- Sol Panel: Arama ve İlan Kartları Listesi --}}
            <div class="flex flex-col md:w-80 lg:w-96 shrink-0 rounded-2xl border border-stone-200/90 bg-white shadow-2xs dark:border-stone-800 dark:bg-stone-900 overflow-hidden"
                 :class="mobileTab === 'list' ? 'block' : 'hidden md:flex'">

                {{-- Liste İçi Arama Çubuğu --}}
                <div class="p-3 border-b border-stone-100 dark:border-stone-800/80 bg-stone-50/70 dark:bg-stone-900">
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-500 dark:text-stone-400" />
                        <input type="text"
                               x-model="searchQuery"
                               @input="filterPoints()"
                               placeholder="Başlık, kategori veya şehir ara..."
                               class="w-full rounded-xl border border-stone-200 bg-white py-2 pl-9 pr-8 text-xs text-stone-900 placeholder:text-stone-500 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder:text-stone-500">
                        <button type="button"
                                x-show="searchQuery"
                                @click="searchQuery = ''; filterPoints()"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200"
                                aria-label="Temizle">
                            <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                {{-- Kaydırılabilir İlan Kartları Listesi --}}
                <div class="flex-1 overflow-y-auto p-3 space-y-2.5 divide-y divide-stone-100 dark:divide-stone-800/60">
                    <template x-for="p in filteredPoints" :key="p.id + '-' + p.type">
                        <div @click="focusMarker(p)"
                             @mouseenter="highlightMarker(p.id)"
                             @mouseleave="unhighlightMarker(p.id)"
                             :class="activePointId === p.id ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 ring-1 ring-emerald-500' : 'border-stone-200/80 hover:border-emerald-300 hover:bg-stone-50/70 dark:border-stone-800 dark:hover:bg-stone-800/50'"
                             class="group relative flex gap-3 rounded-xl border p-2.5 transition cursor-pointer pt-3">

                            {{-- Küçük Kapak Görseli veya İkon --}}
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-stone-100 dark:bg-stone-800 relative">
                                <template x-if="p.image">
                                    <img :src="p.image" :alt="p.title" class="h-full w-full object-cover group-hover:scale-105 transition duration-200" loading="lazy">
                                </template>
                                <template x-if="!p.image">
                                    <div class="grid h-full w-full place-items-center text-lg">
                                        <span x-text="p.type === 'is' ? '💼' : (p.type === 'urun' ? '📦' : '🧰')"></span>
                                    </div>
                                </template>
                                <span class="absolute bottom-1 right-1 rounded bg-stone-900/70 px-1 text-[9px] font-bold text-white uppercase"
                                      x-text="p.type === 'is' ? 'İş' : (p.type === 'urun' ? 'Ürün' : 'Hizmet')"></span>
                            </div>

                            {{-- Kart Detayı --}}
                            <div class="min-w-0 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 truncate" x-text="p.category || 'Genel'"></span>
                                        <span class="text-xs font-bold text-stone-900 dark:text-stone-100" x-text="p.price"></span>
                                    </div>
                                    <h4 class="text-xs font-bold text-stone-800 group-hover:text-emerald-700 dark:text-stone-200 dark:group-hover:text-emerald-300 line-clamp-1 mt-0.5" x-text="p.title"></h4>
                                </div>

                                <div class="mt-2 flex items-center justify-between text-[11px] text-stone-500 dark:text-stone-400">
                                    <span class="flex items-center gap-1 truncate">
                                        <x-heroicon-o-map-pin class="h-3 w-3 shrink-0 text-stone-500 dark:text-stone-400" />
                                        <span x-text="p.city ? (p.city + (p.country_code ? ', ' + p.country_code : '')) : (p.country_name || 'Konum Belirtilmemiş')"></span>
                                    </span>
                                    <a :href="p.url"
                                       @click.stop
                                       class="font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 inline-flex items-center gap-0.5">
                                        Git →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Sonuç Bulunamadı Durumu --}}
                    <div x-show="filteredPoints.length === 0" class="py-12 text-center">
                        <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                            <x-heroicon-o-map-pin class="h-6 w-6" />
                        </div>
                        <h4 class="mt-3 text-xs font-bold text-stone-800 dark:text-stone-200">Aramaya uygun ilan bulunamadı</h4>
                        <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">Farklı bir kelime veya kategori aramayı deneyebilirsin.</p>
                        <button type="button"
                                @click="searchQuery = ''; filterPoints()"
                                class="mt-3 inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300">
                            Filtreyi Temizle
                        </button>
                    </div>
                </div>
            </div>

            {{-- Sağ Panel: Tam Yükseklikte Leaflet Harita Alanı --}}
            <div class="relative flex-1 rounded-2xl border border-stone-200/90 bg-stone-100 shadow-2xs dark:border-stone-800 dark:bg-stone-900 overflow-hidden isolate"
                 :class="mobileTab === 'map' ? 'block' : 'hidden md:block'">

                {{-- Harita Konteyneri --}}
                <div id="harita" class="h-[500px] sm:h-[600px] md:h-full w-full"></div>

                {{-- Harita Üstü Hızlı Eylem Butonları --}}
                <div class="absolute right-3 top-3 z-[400] flex flex-col gap-2">
                    {{-- Konumumu Bul Butonu --}}
                    <button type="button"
                            @click="locateUser()"
                            :disabled="locating"
                            class="grid h-10 w-10 place-items-center rounded-xl border border-stone-200/90 bg-white text-stone-700 shadow-md transition hover:bg-stone-50 hover:text-emerald-700 disabled:opacity-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:text-emerald-400"
                            title="Konumumu Bul"
                            aria-label="Konumumu Bul">
                        <template x-if="!locating">
                            <x-heroicon-o-viewfinder-circle class="h-5 w-5" />
                        </template>
                        <template x-if="locating">
                            <svg class="h-5 w-5 animate-spin text-emerald-700 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                    </button>

                    {{-- Tüm Marker'ları Kapsa (Fit Bounds) --}}
                    <button type="button"
                            @click="fitAllMarkers()"
                            class="grid h-10 w-10 place-items-center rounded-xl border border-stone-200/90 bg-white text-stone-700 shadow-md transition hover:bg-stone-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:text-emerald-400"
                            title="Tüm İlanları Göster"
                            aria-label="Tüm İlanları Göster">
                        <x-heroicon-o-arrows-pointing-out class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mapExplorer', (initialPoints) => ({
                points: initialPoints || [],
                filteredPoints: initialPoints || [],
                searchQuery: '',
                mobileTab: 'map',
                activePointId: null,
                locating: false,
                mapInstance: null,
                markerMap: {},

                initMap() {
                    const esc = (s) => {
                        const d = document.createElement('div');
                        d.textContent = s ?? '';
                        return d.innerHTML;
                    };

                    this.mapInstance = L.map('harita', {
                        zoomControl: true,
                    }).setView([50.5, 9.0], 4);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap katkıda bulunanlar',
                    }).addTo(this.mapInstance);

                    this.renderMarkers();
                },

                renderMarkers() {
                    // Mevcut marker'ları temizle
                    Object.values(this.markerMap).forEach(m => this.mapInstance.removeLayer(m));
                    this.markerMap = {};

                    const esc = (s) => {
                        const d = document.createElement('div');
                        d.textContent = s ?? '';
                        return d.innerHTML;
                    };

                    const markerGroup = [];

                    this.filteredPoints.forEach((p) => {
                        if (!p.lat || !p.lng) return;

                        const tipIcon = p.type === 'is' ? '💼' : (p.type === 'urun' ? '📦' : '🧰');
                        const bgClass = p.type === 'is' ? 'bg-blue-700 text-white' : (p.type === 'urun' ? 'bg-amber-600 text-white' : 'bg-emerald-700 text-white');

                        // Özel L.divIcon rozet pini
                        const icon = L.divIcon({
                            className: 'custom-pin-wrapper',
                            html: `<div class="custom-pin ${bgClass} px-2.5 py-1 text-xs" id="pin-${p.id}">
                                    <span class="mr-1 text-[11px]">${tipIcon}</span>
                                    <span>${esc(p.price)}</span>
                                   </div>`,
                            iconSize: [80, 30],
                            iconAnchor: [40, 15],
                        });

                        const marker = L.marker([p.lat, p.lng], { icon: icon }).addTo(this.mapInstance);

                        // Modern Zengin Popup İçeriği
                        const popupHtml = `
                            <div class="w-64 overflow-hidden rounded-2xl bg-white dark:bg-stone-900">
                                ${p.image ? `<div class="h-28 w-full overflow-hidden bg-stone-100 dark:bg-stone-800"><img src="${p.image}" alt="" class="h-full w-full object-cover"></div>` : ''}
                                <div class="p-3.5">
                                    <div class="flex items-center justify-between gap-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                        <span>${esc(p.category || 'Genel')}</span>
                                        <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold">${esc(p.price)}</span>
                                    </div>
                                    <h4 class="mt-1 text-xs font-bold text-stone-900 dark:text-stone-100 line-clamp-2">${esc(p.title)}</h4>
                                    ${p.city ? `<div class="mt-1.5 flex items-center gap-1 text-[11px] text-stone-500 dark:text-stone-400">📍 ${esc(p.city)}${p.country_code ? ', ' + esc(p.country_code) : ''}</div>` : ''}
                                    <div class="mt-3 pt-2.5 border-t border-stone-100 dark:border-stone-800 flex items-center justify-between">
                                        <a href="${p.url}" class="inline-flex items-center gap-1 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white shadow-2xs transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900">
                                            İlanı Aç →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;

                        marker.bindPopup(popupHtml, { maxWidth: 280 });

                        marker.on('click', () => {
                            this.activePointId = p.id;
                        });

                        this.markerMap[p.id] = marker;
                        markerGroup.push(marker);
                    });

                    if (markerGroup.length > 0) {
                        this.mapInstance.fitBounds(L.featureGroup(markerGroup).getBounds().pad(0.2));
                    }
                },

                filterPoints() {
                    const q = this.searchQuery.trim().toLowerCase();
                    if (!q) {
                        this.filteredPoints = this.points;
                    } else {
                        this.filteredPoints = this.points.filter((p) => {
                            return (p.title && p.title.toLowerCase().includes(q)) ||
                                   (p.city && p.city.toLowerCase().includes(q)) ||
                                   (p.category && p.category.toLowerCase().includes(q)) ||
                                   (p.country_name && p.country_name.toLowerCase().includes(q));
                        });
                    }
                    this.renderMarkers();
                },

                focusMarker(p) {
                    this.activePointId = p.id;
                    this.mobileTab = 'map';
                    if (this.mapInstance && p.lat && p.lng) {
                        this.mapInstance.flyTo([p.lat, p.lng], 14, { duration: 0.8 });
                        const marker = this.markerMap[p.id];
                        if (marker) {
                            marker.openPopup();
                        }
                    }
                },

                highlightMarker(id) {
                    const el = document.getElementById('pin-' + id);
                    if (el) el.classList.add('is-active');
                },

                unhighlightMarker(id) {
                    const el = document.getElementById('pin-' + id);
                    if (el) el.classList.remove('is-active');
                },

                fitAllMarkers() {
                    const markers = Object.values(this.markerMap);
                    if (markers.length && this.mapInstance) {
                        this.mapInstance.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));
                    }
                },

                locateUser() {
                    if (!navigator.geolocation) {
                        alert('Tarayıcınız konum servisini desteklemiyor.');
                        return;
                    }

                    this.locating = true;
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.locating = false;
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            if (this.mapInstance) {
                                this.mapInstance.flyTo([lat, lng], 13, { duration: 1 });
                                L.circleMarker([lat, lng], {
                                    radius: 8,
                                    color: '#059669',
                                    fillColor: '#10b981',
                                    fillOpacity: 0.8,
                                    weight: 3
                                }).addTo(this.mapInstance).bindPopup('Buradasınız').openPopup();
                            }
                        },
                        (err) => {
                            this.locating = false;
                            alert('Konum alınamadı: ' + err.message);
                        },
                        { enableHighAccuracy: true, timeout: 8000 }
                    );
                }
            }));
        });
    </script>
</x-layouts.app>

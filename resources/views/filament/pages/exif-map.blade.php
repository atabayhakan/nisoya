<x-filament-panels::page>
    <div
        x-data="exifMapApp()"
        x-init="init()"
        class="space-y-4"
    >
        {{-- Kontrol & Filtre Çubuğu --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer font-medium text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            x-model="sensitiveOnly"
                            @change="loadMarkers()"
                            class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 dark:border-gray-700 dark:bg-gray-900"
                        >
                        <span>Sadece Hassas EXIF</span>
                    </label>

                    <label class="inline-flex items-center gap-2 cursor-pointer font-medium text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            x-model="clusterMode"
                            @change="toggleCluster()"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                        >
                        <span>Kümeleme (Cluster)</span>
                    </label>

                    {{-- Hızlı Bölge Odaklanma Butonları --}}
                    <div class="inline-flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                        <button
                            type="button"
                            @click="focusRegion('world')"
                            class="rounded px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            🌍 Dünya
                        </button>
                        <button
                            type="button"
                            @click="focusRegion('europe')"
                            class="rounded px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            🇪🇺 Avrupa
                        </button>
                        <button
                            type="button"
                            @click="focusRegion('turkey')"
                            class="rounded px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            🇹🇷 Türkiye
                        </button>
                        <button
                            type="button"
                            @click="focusRegion('central_asia')"
                            class="rounded px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            🇰🇬 Orta Asya
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span x-text="statusText">Harita hazırlanıyor...</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Harita Konteyneri --}}
        <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            {{-- Yükleniyor Göstergesi --}}
            <div
                x-show="loading"
                x-transition.opacity
                class="absolute inset-0 z-40 flex flex-col items-center justify-center bg-white/80 backdrop-blur-xs dark:bg-gray-900/80"
            >
                <div class="text-4xl animate-bounce">🛰️</div>
                <div class="mt-2 text-sm font-semibold text-gray-800 dark:text-gray-200">Coğrafi İstihbarat Haritası Yükleniyor...</div>
                <div class="text-xs text-gray-500">Koordinat ve kümeleme verileri taranıyor</div>
            </div>

            {{-- Harita Canvas --}}
            <div
                id="exif-map-canvas"
                style="height: 620px; width: 100%; z-index: 10;"
            ></div>

            {{-- 0 Marker Durumunda Bilgilendirici Alt Bildirim Rozeti --}}
            <div
                x-show="!loading && markersCount === 0"
                x-transition
                class="absolute bottom-4 left-4 right-4 z-20 flex items-center justify-between rounded-xl border border-primary-200 bg-primary-50/95 p-3.5 shadow-lg backdrop-blur-md dark:border-primary-800 dark:bg-primary-950/90"
            >
                <div class="flex items-center gap-2.5 text-xs text-primary-900 dark:text-primary-200">
                    <span class="text-lg">🗺️</span>
                    <div>
                        <strong class="font-semibold">Harita Aktif:</strong>
                        <span>Sistemde henüz GPS koordinatı içeren görsel bulunmuyor veya yüklenen görsellerin GPS verileri KVKK gereği otomatik temizlenmiş durumda.</span>
                    </div>
                </div>
                <a
                    href="{{ \App\Filament\Resources\ListingImages\ListingImageResource::getUrl('index') }}"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-primary-500 transition shrink-0"
                >
                    Görseller Tablosunu Aç
                </a>
            </div>
        </div>

        {{-- Şüpheli Kümeler (Clusters) ve Kopya Noktalar Paneli --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span>🔍 Şüpheli Coğrafi Kümeler & Kopya Noktaları</span>
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300" x-text="clusterCountText">0 Küme</span>
                    </div>
                    <span class="text-xs text-gray-500 font-normal">Aynı koordinattan yüklenen görseller organize sahtekarlık veya bot çetelerine işaret edebilir.</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <template x-for="(c, idx) in clusters" :key="idx">
                    <div
                        @click="flyToCoordinates(c.lat, c.lng)"
                        class="group cursor-pointer rounded-xl border border-gray-200 bg-gray-50 p-3.5 transition hover:border-primary-500 hover:bg-primary-50/40 dark:border-gray-800 dark:bg-gray-900/60 dark:hover:border-primary-600 dark:hover:bg-primary-950/20 shadow-xs"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 group-hover:text-primary-600">📍 Küme #<span x-text="idx + 1"></span></span>
                            <span class="rounded-md bg-rose-100 px-2 py-0.5 text-2xs font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300" x-text="c.count + ' Görsel'"></span>
                        </div>
                        <div class="mt-1.5 text-2xs font-mono text-gray-500" x-text="c.lat.toFixed(4) + ', ' + c.lng.toFixed(4)"></div>
                        <div class="mt-2 flex items-center justify-between text-2xs text-gray-600 dark:text-gray-400">
                            <span x-text="c.listing_ids.length + ' Farklı İlan'"></span>
                            <span class="text-primary-600 font-semibold group-hover:underline">Haritada İncele →</span>
                        </div>
                    </div>
                </template>
                <div x-show="clusters.length === 0" class="col-span-full py-4 text-center text-xs text-gray-500">
                    Yoğunlaşmış şüpheli koordinat kümesi tespit edilmedi. Tüm görseller sağlıklı dağılımda.
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Leaflet Script ve Stil Yükleyicisi --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="" />

    <script>
        function exifMapApp() {
            return {
                map: null,
                markerLayer: null,
                sensitiveOnly: false,
                clusterMode: true,
                loading: true,
                markersCount: 0,
                clusters: [],
                statusText: 'Harita başlatılıyor...',
                clusterCountText: 'Yükleniyor...',

                async init() {
                    await this.loadDependencies();
                    this.initMap();
                    await this.loadMarkers();
                    await this.loadClusters();
                },

                loadDependencies() {
                    return new Promise((resolve) => {
                        if (window.L && window.L.markerClusterGroup) {
                            return resolve();
                        }

                        const loadScript = (src) => {
                            return new Promise((res, rej) => {
                                if (document.querySelector(`script[src="${src}"]`)) {
                                    return res();
                                }
                                const script = document.createElement('script');
                                script.src = src;
                                script.async = true;
                                script.onload = res;
                                script.onerror = rej;
                                document.head.appendChild(script);
                            });
                        };

                        loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js')
                            .then(() => loadScript('https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js'))
                            .then(() => resolve())
                            .catch((err) => {
                                console.error('Harita kütüphaneleri yüklenemedi:', err);
                                resolve();
                            });
                    });
                },

                initMap() {
                    if (!window.L) return;
                    const canvas = document.getElementById('exif-map-canvas');
                    if (!canvas || this.map) return;

                    // Varsayılan odak: Avrupa / Türkiye merkezi
                    this.map = L.map(canvas, {
                        zoomControl: true,
                        attributionControl: true,
                    }).setView([46.0, 20.0], 4);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    }).addTo(this.map);

                    this.markerLayer = L.markerClusterGroup({
                        maxClusterRadius: 50,
                        spiderfyOnMaxZoom: true,
                        showCoverageOnHover: false,
                    });
                    this.map.addLayer(this.markerLayer);
                },

                async loadMarkers() {
                    this.loading = true;
                    this.statusText = 'Veriler çekiliyor...';
                    try {
                        const url = new URL('{{ route('exif-map.images') }}', window.location.origin);
                        if (this.sensitiveOnly) {
                            url.searchParams.set('sensitive', '1');
                        }

                        const response = await fetch(url);
                        const data = await response.json();

                        this.renderMarkers(data);
                    } catch (e) {
                        console.error('Marker yükleme hatası:', e);
                        this.statusText = 'Hata oluştu';
                    } finally {
                        this.loading = false;
                    }
                },

                renderMarkers(data) {
                    if (!this.markerLayer || !this.map) return;
                    this.markerLayer.clearLayers();
                    const allMarkers = [];

                    const markers = data.markers || [];
                    this.markersCount = markers.length;
                    this.statusText = `${this.markersCount} GPS'li görsel haritada`;

                    markers.forEach((m) => {
                        const isSensitive = m.sensitive;
                        const iconHtml = `<div style="background:${isSensitive ? '#e11d48' : '#059669'};color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.35);">📷</div>`;

                        const customIcon = L.divIcon({
                            className: 'exif-map-pin',
                            html: iconHtml,
                            iconSize: [28, 28],
                            iconAnchor: [14, 14],
                        });

                        const marker = L.marker([m.lat, m.lng], { icon: customIcon });

                        // Popup Kartı
                        const imgTag = m.thumb ? `<img src="${m.thumb}" class="w-full h-28 object-cover rounded-lg mb-2 shadow-xs" />` : '';
                        const listingTitle = m.listing ? `<a href="${m.listing.url}" target="_blank" class="font-bold text-sm text-primary-600 hover:underline block mb-1">${m.listing.title}</a>` : '<div class="text-sm font-semibold mb-1">Bağımsız Görsel</div>';
                        const seller = m.user ? `<div class="text-xs text-gray-500 mb-1">👤 Satıcı: <strong class="text-gray-700">${m.user.name}</strong></div>` : '';
                        const camera = (m.camera || m.model) ? `<div class="text-xs text-gray-500 mb-1">📷 Cihaz: ${m.camera || ''} ${m.model || ''}</div>` : '';
                        const statusBadge = isSensitive ? '<span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:bold;">⚠️ Hassas EXIF</span>' : '<span style="background:#d1fae5;color:#065f46;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:bold;">✓ Güvenli</span>';
                        const coord = `<div class="text-2xs font-mono text-gray-400 mt-1">📍 ${m.lat.toFixed(4)}, ${m.lng.toFixed(4)}</div>`;

                        const popupContent = `
                            <div style="min-width:220px; font-family:inherit; padding:4px;">
                                ${imgTag}
                                <div style="margin-bottom:6px;">${statusBadge}</div>
                                ${listingTitle}
                                ${seller}
                                ${camera}
                                ${coord}
                            </div>
                        `;

                        marker.bindPopup(popupContent);
                        this.markerLayer.addLayer(marker);
                        allMarkers.push(marker);
                    });

                    if (allMarkers.length > 0) {
                        const group = L.featureGroup(allMarkers);
                        this.map.fitBounds(group.getBounds(), { padding: [40, 40], maxZoom: 14 });
                    }
                },

                async loadClusters() {
                    try {
                        const response = await fetch('{{ route('exif-map.clusters') }}');
                        const data = await response.json();
                        this.clusters = (data.clusters || []).slice(0, 8);
                        this.clusterCountText = `${data.cluster_count || 0} Küme (${data.total_images || 0} Görsel)`;
                    } catch (e) {
                        console.error('Cluster çekme hatası:', e);
                        this.clusterCountText = '0 Küme';
                    }
                },

                toggleCluster() {
                    if (!this.map || !this.markerLayer) return;
                    if (this.clusterMode) {
                        this.map.addLayer(this.markerLayer);
                    } else {
                        this.map.removeLayer(this.markerLayer);
                    }
                },

                flyToCoordinates(lat, lng) {
                    if (!this.map) return;
                    this.map.flyTo([lat, lng], 13, { duration: 1.2 });
                    window.scrollTo({ top: 320, behavior: 'smooth' });
                },

                focusRegion(region) {
                    if (!this.map) return;
                    switch (region) {
                        case 'europe':
                            this.map.flyTo([51.1657, 10.4515], 5);
                            break;
                        case 'turkey':
                            this.map.flyTo([38.9637, 35.2433], 6);
                            break;
                        case 'central_asia':
                            this.map.flyTo([42.8746, 74.5698], 6);
                            break;
                        case 'world':
                        default:
                            this.map.flyTo([46.0, 20.0], 4);
                            break;
                    }
                }
            };
        }
    </script>
</x-filament-panels::page>

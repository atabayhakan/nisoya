<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span>🛰️ EXIF Haritası</span>
                <span class="text-sm font-normal text-stone-500">
                    GPS koordinatı içeren tüm görseller
                </span>
            </div>
        </x-slot>

        <div wire:id="exif-map" class="space-y-4">
            {{-- Filtre bar --}}
            <div class="flex flex-wrap items-center gap-3 rounded-lg bg-stone-50 p-3 dark:bg-stone-800">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="sensitive-only" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Sadece hassas EXIF</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="cluster-mode" checked class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Cluster göster</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" id="heatmap-mode" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Heatmap modu</span>
                </label>
                <div class="ms-auto text-xs text-stone-500" id="map-stats">
                    <span id="marker-count">0</span> marker
                </div>
            </div>

            {{-- Harita --}}
            <div
                id="exif-map-canvas"
                style="height: 600px; min-height: 400px; border-radius: 0.75rem; overflow: hidden; border: 1px solid rgb(214 211 209);"
                class="relative"
            >
                <div class="absolute inset-0 z-50 flex items-center justify-center bg-stone-50 dark:bg-stone-900" id="map-loading">
                    <div class="text-center">
                        <div class="mb-2 text-3xl">🗺️</div>
                        <p class="text-sm text-stone-500">Harita yükleniyor...</p>
                    </div>
                </div>
            </div>

            {{-- Cluster listesi (yan panel gibi altta) --}}
            <div id="cluster-panel" class="hidden rounded-lg border border-stone-200 bg-white p-4 dark:border-stone-700 dark:bg-stone-800">
                <h3 class="mb-3 text-sm font-semibold text-stone-700 dark:text-stone-300">
                    <span id="cluster-panel-title">Kümeler</span>
                </h3>
                <div id="cluster-list" class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3"></div>
            </div>
        </div>
    </x-filament::section>

    {{-- Leaflet CSS/JS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapEl = document.getElementById('exif-map-canvas');
            const loadingEl = document.getElementById('map-loading');
            const markerCountEl = document.getElementById('marker-count');
            const clusterListEl = document.getElementById('cluster-list');
            const clusterPanel = document.getElementById('cluster-panel');
            const clusterPanelTitle = document.getElementById('cluster-panel-title');

            // Haritayı başlat — varsayılan İstanbul
            const map = L.map(mapEl).setView([41.0082, 28.9784], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap',
            }).addTo(map);

            let markerLayer = L.markerClusterGroup({
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
            });
            let heatLayer = null;
            let allMarkers = [];

            function showMarkerPopup(marker) {
                const m = marker.options.exifData;
                const img = m.thumb ? `<img src="${m.thumb}" alt="" class="mb-2 h-24 w-full rounded object-cover">` : '';
                const sensitive = m.sensitive ? '<span class="badge bg-red-500">Hassas</span>' : '<span class="badge bg-green-500">Temiz</span>';
                const camera = (m.camera || m.model) ? `<div class="text-xs text-stone-500">${(m.camera || '') + ' ' + (m.model || '')}</div>` : '';
                const listing = m.listing ? `<a href="${m.listing.url}" target="_blank" class="text-sm font-medium text-emerald-600 hover:underline">${m.listing.title}</a>` : '';
                const user = m.user ? `<div class="text-xs text-stone-500">👤 ${m.user.name}</div>` : '';
                const date = m.uploaded_at ? new Date(m.uploaded_at).toLocaleDateString('tr-TR') : '';

                return `
                    <div class="min-w-[200px] font-sans">
                        ${img}
                        ${sensitive}
                        <div class="mt-1 font-semibold text-stone-900">${listing}</div>
                        ${user}
                        ${camera}
                        <div class="mt-1 text-xs text-stone-400">${date}</div>
                        <div class="mt-1 text-[10px] text-stone-400">${m.lat.toFixed(4)}, ${m.lng.toFixed(4)}</div>
                    </div>
                `;
            }

            function addMarkers(data) {
                markerLayer.clearLayers();
                allMarkers = [];
                const heatPoints = [];

                data.markers.forEach((m) => {
                    const icon = L.divIcon({
                        className: 'exif-marker',
                        html: `<div style="background:${m.sensitive ? '#dc2626' : '#059669'};color:white;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3)">📷</div>`,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12],
                    });

                    const marker = L.marker([m.lat, m.lng], { icon, exifData: m });
                    marker.bindPopup(showMarkerPopup(marker));
                    markerLayer.addLayer(marker);
                    allMarkers.push(marker);
                    heatPoints.push([m.lat, m.lng, 1]);
                });

                if (heatLayer) {
                    map.removeLayer(heatLayer);
                    heatLayer = null;
                }

                if (data.markers.length === 0) {
                    markerCountEl.textContent = '0';
                    loadingEl.style.display = 'none';
                    return;
                }

                if (data.bounds) {
                    const b = data.bounds;
                    map.fitBounds([[b.south, b.west], [b.north, b.east]], { padding: [30, 30] });
                } else {
                    const group = L.featureGroup(allMarkers);
                    map.fitBounds(group.getBounds(), { padding: [30, 30] });
                }

                heatLayer = L.heatLayer(heatPoints, { radius: 25, blur: 15 });

                markerCountEl.textContent = data.markers.length;
                loadingEl.style.display = 'none';
            }

            function toggleCluster() {
                const enabled = document.getElementById('cluster-mode').checked;
                if (enabled) {
                    map.addLayer(markerLayer);
                    map.removeLayer(heatLayer);
                    document.getElementById('heatmap-mode').checked = false;
                } else {
                    map.removeLayer(markerLayer);
                }
            }

            function toggleHeatmap() {
                const enabled = document.getElementById('heatmap-mode').checked;
                if (enabled && heatLayer) {
                    map.addLayer(heatLayer);
                    map.removeLayer(markerLayer);
                    document.getElementById('cluster-mode').checked = false;
                } else {
                    if (heatLayer) map.removeLayer(heatLayer);
                }
            }

            async function loadMarkers(sensitive = false) {
                loadingEl.style.display = 'flex';
                try {
                    const url = new URL('{{ route('exif-map.images') }}', window.location.origin);
                    if (sensitive) url.searchParams.set('sensitive', 1);
                    const response = await fetch(url);
                    const data = await response.json();
                    addMarkers(data);
                } catch (err) {
                    console.error('Marker yükleme hatası:', err);
                } finally {
                    loadingEl.style.display = 'none';
                }
            }

            async function loadClusters() {
                clusterListEl.innerHTML = '<div class="col-span-full text-sm text-stone-500">Yükleniyor...</div>';
                try {
                    const response = await fetch('{{ route('exif-map.clusters') }}');
                    const data = await response.json();
                    clusterListEl.innerHTML = '';
                    data.clusters.slice(0, 12).forEach((c) => {
                        const el = document.createElement('button');
                        el.type = 'button';
                        el.className = 'rounded-lg border border-stone-200 bg-stone-50 p-3 text-left transition hover:border-emerald-500 hover:bg-emerald-50 dark:border-stone-700 dark:bg-stone-900 dark:hover:bg-emerald-950/30';
                        el.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-stone-900 dark:text-stone-100">${c.count} görsel</div>
                                <div class="text-xs text-stone-500">${c.lat.toFixed(3)}, ${c.lng.toFixed(3)}</div>
                            </div>
                            <div class="mt-1 text-xs text-stone-500">📍 ${c.listing_ids.length} ilan</div>
                        `;
                        el.onclick = () => map.flyTo([c.lat, c.lng], 12);
                        clusterListEl.appendChild(el);
                    });
                    clusterPanelTitle.textContent = `${data.cluster_count} küme (${data.total_images} görsel)`;
                    clusterPanel.classList.remove('hidden');
                } catch (err) {
                    console.error('Cluster yükleme hatası:', err);
                }
            }

            // Event listeners
            document.getElementById('sensitive-only').addEventListener('change', (e) => {
                loadMarkers(e.target.checked);
            });
            document.getElementById('cluster-mode').addEventListener('change', toggleCluster);
            document.getElementById('heatmap-mode').addEventListener('change', toggleHeatmap);

            // İlk yükleme
            loadMarkers();
            loadClusters();
        });
    </script>
</x-filament-panels::page>
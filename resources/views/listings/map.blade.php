<x-layouts.app title="Haritada Keşfet — Nisoya">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-100">Haritada Keşfet</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $points->count() }} ilan haritada</p>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <a href="{{ route('listings.map') }}" class="rounded-lg px-3 py-1.5 font-medium {{ $tip === '' ? 'bg-emerald-700 text-white' : 'text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800' }}">Tümü</a>
                <a href="{{ route('listings.map', ['tip' => 'hizmet']) }}" class="rounded-lg px-3 py-1.5 font-medium {{ $tip === 'hizmet' ? 'bg-emerald-700 text-white' : 'text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800' }}">🧰 Hizmet</a>
                <a href="{{ route('listings.map', ['tip' => 'urun']) }}" class="rounded-lg px-3 py-1.5 font-medium {{ $tip === 'urun' ? 'bg-emerald-700 text-white' : 'text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800' }}">📦 Ürün</a>
                <a href="{{ route('listings.map', ['tip' => 'is']) }}" class="rounded-lg px-3 py-1.5 font-medium {{ $tip === 'is' ? 'bg-emerald-700 text-white' : 'text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800' }}">💼 İş İlanları</a>
                <a href="{{ $tip === 'is' ? route('jobs.index') : route('listings.index', $tip ? ['tip' => $tip] : []) }}" class="rounded-lg border border-stone-300 px-3 py-1.5 font-medium text-stone-700 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800">Liste →</a>
            </div>
        </div>

        {{-- isolate: Leaflet kendi panellerini (z-index:400) ve kontrollerini
             (z-index:1000) kök yığın bağlamına sızdırır; bu, header'ın (z-30)
             "Keşfet" mega-menüsünün haritanın ARKASINDA kalmasına yol açıyordu.
             isolation:isolate haritayı kendi yığın bağlamına hapseder → header
             ve açılır menü haritanın üstünde kalır. --}}
        <div id="harita" class="isolate mt-4 h-[72vh] w-full overflow-hidden rounded-2xl border border-stone-200 shadow-sm dark:border-stone-800"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const points = @json($points);
            const esc = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };

            const map = L.map('harita').setView([50.5, 9.0], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap katkıda bulunanlar',
            }).addTo(map);

            const markers = [];
            points.forEach((p) => {
                if (!p.lat || !p.lng) return;
                const m = L.marker([p.lat, p.lng]).addTo(map);
                const tip = p.type === 'urun' ? '📦' : (p.type === 'is' ? '💼' : '🧰');
                m.bindPopup(
                    `<div style="min-width:160px">
                        <div style="font-weight:600">${tip} ${esc(p.title)}</div>
                        <div style="color:#059669;font-weight:600;margin:2px 0">${esc(p.price)}</div>
                        ${p.city ? `<div style="color:#78716c;font-size:12px">${esc(p.city)}</div>` : ''}
                        <a href="${p.url}" style="color:#047857;font-size:13px">İlanı gör →</a>
                    </div>`
                );
                markers.push(m);
            });

            if (markers.length) {
                map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));
            }
        })();
    </script>
</x-layouts.app>

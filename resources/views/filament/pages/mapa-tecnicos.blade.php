<x-filament-panels::page>
    <div class="tracking-shell">
        <div class="tracking-stage">
            <div id="technicians-map" class="tracking-map"></div>

            <aside class="tracking-sidebar">
                <div class="tracking-sidebar-card">
                    <div class="tracking-head">
                        <div>
                            <div class="tracking-eyebrow">Dispatch tecnico</div>
                            <h2 class="tracking-title">Mapa de tecnicos</h2>
                        </div>
                        <button id="tracking-refresh" type="button" class="tracking-btn tracking-btn-primary">Actualizar</button>
                    </div>

                    <div class="tracking-filters">
                        <select id="tracking-status-filter" class="tracking-input">
                            <option value="">Todos los estados</option>
                            <option value="available">Disponibles</option>
                            <option value="traveling">Viajando</option>
                            <option value="working">Trabajando</option>
                            <option value="offline">Desconectados</option>
                        </select>

                        <label class="tracking-check">
                            <input id="tracking-active-only" type="checkbox">
                            <span>Solo activos</span>
                        </label>
                    </div>

                    <div id="tracking-summary" class="tracking-summary">Cargando tecnicos...</div>
                    <div id="tracking-list" class="tracking-list"></div>
                </div>
            </aside>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
        @verbatim
        <style>
            .tracking-stage { position: relative; min-height: calc(100vh - 8rem); border-radius: 1.25rem; overflow: hidden; background: linear-gradient(180deg, #e0f2fe, #f8fafc); }
            .tracking-map { height: calc(100vh - 8rem); min-height: 720px; }
            .tracking-sidebar { position: absolute; top: 1rem; left: 1rem; width: 390px; max-width: calc(100% - 2rem); z-index: 600; }
            .tracking-sidebar-card { background: rgba(255,255,255,.95); backdrop-filter: blur(12px); border-radius: 1.25rem; padding: 1rem; box-shadow: 0 24px 50px rgba(15,23,42,.18); }
            .tracking-head { display: flex; justify-content: space-between; gap: 1rem; align-items: start; }
            .tracking-eyebrow { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 800; }
            .tracking-title { margin: .2rem 0 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
            .tracking-btn { border: 0; border-radius: 999px; padding: .7rem 1rem; font-weight: 700; cursor: pointer; }
            .tracking-btn-primary { background: #0f172a; color: white; }
            .tracking-filters { display: flex; gap: .75rem; margin-top: 1rem; align-items: center; }
            .tracking-input { flex: 1; border: 1px solid #cbd5e1; border-radius: .9rem; padding: .7rem .9rem; background: white; }
            .tracking-check { display: inline-flex; gap: .4rem; align-items: center; color: #334155; font-size: .85rem; }
            .tracking-summary { margin-top: 1rem; font-size: .86rem; color: #475569; }
            .tracking-list { margin-top: 1rem; display: grid; gap: .75rem; max-height: calc(100vh - 17rem); overflow: auto; }
            .tracking-card { border: 1px solid #e2e8f0; border-radius: 1rem; background: white; padding: .85rem; }
            .tracking-card.is-selected { border-color: #0f172a; box-shadow: 0 0 0 2px rgba(15,23,42,.1); }
            .tracking-card-top { display: flex; justify-content: space-between; gap: .75rem; }
            .tracking-name { font-weight: 800; color: #0f172a; }
            .tracking-meta { margin-top: .2rem; font-size: .8rem; color: #64748b; }
            .tracking-badge { display: inline-flex; align-items: center; padding: .28rem .6rem; border-radius: 999px; color: white; font-size: .72rem; font-weight: 800; }
            .tracking-client { margin-top: .65rem; font-size: .84rem; color: #1e293b; }
            .tracking-actions { margin-top: .75rem; display: flex; gap: .5rem; }
            .tracking-link { text-decoration: none; }
            @media (max-width: 900px) { .tracking-sidebar { position: static; width: 100%; max-width: none; padding: 1rem; } .tracking-map { min-height: 480px; height: 60vh; } }
        </style>
        @endverbatim
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        @verbatim
        <script>
            window.technicianTrackingState = window.technicianTrackingState || {
                map: null,
                markers: {},
                technicians: [],
                filtered: [],
                selectedId: null,
                refreshTimer: null,
                routeLayer: null,
            };

            function trackingStatusBadge(status, color) {
                return `<span class="tracking-badge" style="background:${color};">${status}</span>`;
            }

            function trackingPopupHtml(item) {
                const visit = item.current_visit;
                const pendiente = visit?.pendiente;
                const client = pendiente?.client;

                return `
                    <div style="min-width:260px">
                        <div style="font-weight:800;color:#0f172a">${item.name}</div>
                        <div style="margin-top:6px">${trackingStatusBadge(item.status_label, item.status_color)}</div>
                        <div style="margin-top:8px;color:#334155">Ultima actualizacion: ${item.last_seen_human || 'sin datos'}</div>
                        ${client ? `<div style="margin-top:8px;color:#0f172a"><strong>Cliente:</strong> ${client.name}</div>` : ''}
                        ${pendiente ? `<div style="margin-top:6px;color:#0f172a"><strong>Pendiente:</strong> ${pendiente.description}</div>` : ''}
                        ${item.speed != null ? `<div style="margin-top:6px;color:#334155"><strong>Velocidad:</strong> ${Number(item.speed).toFixed(1)} m/s</div>` : ''}
                        ${visit?.arrival_photo_url ? `<img src="${visit.arrival_photo_url}" style="margin-top:10px;border-radius:12px;max-width:100%;height:auto;" />` : ''}
                    </div>
                `;
            }

            function trackingRenderList() {
                const list = document.getElementById('tracking-list');
                const summary = document.getElementById('tracking-summary');
                const items = window.technicianTrackingState.filtered;

                summary.textContent = `${items.length} tecnico(s) visibles`;

                list.innerHTML = items.map((item) => {
                    const visit = item.current_visit;
                    const pendiente = visit?.pendiente;
                    const client = pendiente?.client;
                    const selected = window.technicianTrackingState.selectedId === item.id ? 'is-selected' : '';

                    return `
                        <div class="tracking-card ${selected}" data-technician-id="${item.id}">
                            <div class="tracking-card-top">
                                <div>
                                    <div class="tracking-name">${item.name}</div>
                                    <div class="tracking-meta">${item.last_seen_human || 'Sin posicion reciente'}</div>
                                </div>
                                ${trackingStatusBadge(item.status_label, item.status_color)}
                            </div>
                            <div class="tracking-client">
                                ${client ? `<strong>${client.name}</strong><br>${client.full_address || ''}` : 'Sin visita activa'}
                            </div>
                            <div class="tracking-meta">
                                ${pendiente ? `Pendiente #${pendiente.id} · ${pendiente.description}` : 'Disponible sin pendiente activa'}
                            </div>
                            <div class="tracking-actions">
                                <button type="button" class="tracking-btn tracking-btn-primary tracking-focus" data-technician-id="${item.id}">Ver</button>
                            </div>
                        </div>
                    `;
                }).join('');

                list.querySelectorAll('.tracking-focus').forEach((button) => {
                    button.addEventListener('click', () => trackingSelectTechnician(Number(button.dataset.technicianId)));
                });
            }

            function trackingApplyFilters() {
                const status = document.getElementById('tracking-status-filter').value;
                const activeOnly = document.getElementById('tracking-active-only').checked;

                window.technicianTrackingState.filtered = window.technicianTrackingState.technicians.filter((item) => {
                    const matchesStatus = !status || item.status === status;
                    const matchesActive = !activeOnly || ['available', 'traveling', 'working'].includes(item.status);
                    return matchesStatus && matchesActive;
                });

                trackingRenderMarkers();
                trackingRenderList();
            }

            function trackingMarkerIcon(item) {
                return L.divIcon({
                    className: 'tracking-marker-wrap',
                    html: `<div style="width:20px;height:20px;border-radius:999px;background:${item.status_color};border:3px solid white;box-shadow:0 10px 18px rgba(15,23,42,.25);"></div>`,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                });
            }

            function trackingRenderMarkers() {
                const state = window.technicianTrackingState;

                Object.values(state.markers).forEach((marker) => state.map.removeLayer(marker));
                state.markers = {};

                state.filtered.forEach((item) => {
                    if (item.last_lat == null || item.last_lng == null) {
                        return;
                    }

                    const marker = L.marker([item.last_lat, item.last_lng], { icon: trackingMarkerIcon(item) })
                        .addTo(state.map)
                        .bindPopup(trackingPopupHtml(item));

                    marker.on('click', () => {
                        state.selectedId = item.id;
                        trackingRenderList();
                    });

                    state.markers[item.id] = marker;
                });
            }

            function trackingSelectTechnician(id) {
                const state = window.technicianTrackingState;
                const item = state.filtered.find((row) => row.id === id) || state.technicians.find((row) => row.id === id);

                if (!item || !state.markers[id]) {
                    return;
                }

                state.selectedId = id;
                state.map.setView([item.last_lat, item.last_lng], 16, { animate: true });
                state.markers[id].openPopup();
                trackingRenderList();
                trackingLoadHistory(id);
            }

            function trackingLoadHistory(id) {
                const state = window.technicianTrackingState;

                fetch(`/tracking/history/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (state.routeLayer) {
                            state.map.removeLayer(state.routeLayer);
                        }

                        const points = Array.isArray(data.points) ? data.points : [];

                        if (points.length < 2) {
                            return;
                        }

                        state.routeLayer = L.polyline(points.map((point) => [point.lat, point.lng]), {
                            color: '#0f172a',
                            weight: 3,
                            opacity: .65,
                        }).addTo(state.map);
                    })
                    .catch((error) => console.error('Error cargando historial del tecnico', error));
            }

            function trackingLoadLive() {
                fetch('/tracking/live', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        window.technicianTrackingState.technicians = Array.isArray(data) ? data : [];
                        trackingApplyFilters();
                    })
                    .catch((error) => console.error('Error cargando tracking live', error));
            }

            function trackingInitMap() {
                const state = window.technicianTrackingState;
                const element = document.getElementById('technicians-map');

                if (!element || state.map) {
                    return;
                }

                state.map = L.map(element, {
                    preferCanvas: true,
                }).setView([-34.6037, -58.3816], 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 20,
                }).addTo(state.map);

                trackingLoadLive();
                state.refreshTimer = window.setInterval(trackingLoadLive, 15000);
            }

            function trackingBoot() {
                trackingInitMap();
                document.getElementById('tracking-refresh')?.addEventListener('click', trackingLoadLive);
                document.getElementById('tracking-status-filter')?.addEventListener('change', trackingApplyFilters);
                document.getElementById('tracking-active-only')?.addEventListener('change', trackingApplyFilters);
            }

            document.addEventListener('DOMContentLoaded', trackingBoot);
            document.addEventListener('livewire:navigated', trackingBoot);
        </script>
        @endverbatim
    @endpush
</x-filament-panels::page>

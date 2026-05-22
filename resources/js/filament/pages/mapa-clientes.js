            window.mapaClientesState = {
                map: null,
                clientes: [],
                filtrados: [],
                markers: [],
                markerByClientId: {},
                markerClusterGroup: null,
                selectedClient: null,
                eventsBound: false,
                defaultCenter: [-34.6037, -58.3816],
                defaultZoom: 10,
                panelCollapsed: false,
                searchOpen: false,
                lastRefreshAt: null,
                canViewTracking: document.querySelector('.map-shell')?.dataset.canViewTracking === '1',
                technicians: [],
                technicianMarkers: {},
                technicianPanelOpen: false,
                technicianIntervalStarted: false,
                selectedTechnicianId: null,
                technicianLayerGroup: null,
                technicianRefreshTimer: null,
                technicianLastRefreshAt: null,
                technicianRequestInFlight: false,
            };

            function mapaClientesEscapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function mapaClientesGetMarkerColor(cliente) {
                return cliente.client_type_color || '#2563eb';
            }

            function mapaClientesGetMarkerStrokeColor(cliente) {
                switch (cliente.coordinates_quality) {
                    case 'verified':
                        return '#10b981';
                    case 'approximate':
                        return '#f59e0b';
                    case 'basic':
                        return '#64748b';
                    default:
                        return '#94a3b8';
                }
            }

            function mapaClientesGetMarkerRadius(cliente) {
                if (cliente.client_type === 'conservation') {
                    return cliente.active_pendientes_count > 0 ? 12 : 10;
                }

                return cliente.active_pendientes_count > 0 ? 11 : 8;
            }

            function mapaClientesToggleSearch(forceOpen = null) {
                const shell = document.getElementById('map-search-shell');
                const results = document.getElementById('map-search-results');
                const input = document.getElementById('map-search');

                if (!shell) {
                    return;
                }

                const nextState = forceOpen === null
                    ? !window.mapaClientesState.searchOpen
                    : forceOpen;

                window.mapaClientesState.searchOpen = nextState;
                shell.classList.toggle('hidden', !nextState);

                if (!nextState && results) {
                    results.classList.add('hidden');
                }

                if (nextState && input) {
                    setTimeout(() => input.focus(), 50);
                }
            }

            function mapaClientesHasActiveFilters() {
                return document.getElementById('map-search')?.value.trim() !== ''
                    || document.getElementById('map-city')?.value !== ''
                    || document.getElementById('map-client-type')?.value !== ''
                    || document.getElementById('map-only-active')?.checked
                    || document.getElementById('map-only-without-coordinates')?.checked;
            }

            function mapaClientesTogglePanel(forceOpen = null) {
                const sidebar = document.getElementById('map-sidebar');

                if (!sidebar) {
                    return;
                }

                const nextState = forceOpen === null
                    ? !window.mapaClientesState.panelCollapsed
                    : !forceOpen;

                window.mapaClientesState.panelCollapsed = nextState;
                sidebar.classList.toggle('is-collapsed', nextState);
            }

            function mapaClientesGetSearchResults() {
                const search = document.getElementById('map-search')?.value.trim().toLowerCase() ?? '';

                if (search.length < 2) {
                    return [];
                }

                return window.mapaClientesState.clientes
                    .map((cliente) => {
                        const haystack = [
                            cliente.name,
                            cliente.company,
                            cliente.full_address,
                            cliente.city,
                            cliente.normalized_address,
                        ]
                            .filter(Boolean)
                            .join(' | ')
                            .toLowerCase();

                        const startsWithName = (cliente.name || '').toLowerCase().startsWith(search);
                        const includesName = (cliente.name || '').toLowerCase().includes(search);
                        const includesOther = haystack.includes(search);

                        return {
                            cliente,
                            score: startsWithName ? 0 : (includesName ? 1 : (includesOther ? 2 : 99)),
                        };
                    })
                    .filter((item) => item.score < 99)
                    .sort((a, b) => a.score - b.score || (a.cliente.name || '').localeCompare(b.cliente.name || ''))
                    .slice(0, 8)
                    .map((item) => item.cliente);
            }

            function mapaClientesRenderSearchResults() {
                const container = document.getElementById('map-search-results');
                const clearButton = document.getElementById('map-clear-search');
                const searchValue = document.getElementById('map-search')?.value.trim() ?? '';

                if (clearButton) {
                    clearButton.classList.toggle('hidden', searchValue === '');
                }

                if (!container) {
                    return;
                }

                const results = mapaClientesGetSearchResults();

                if (searchValue.length < 2) {
                    container.classList.add('hidden');
                    container.innerHTML = '';
                    return;
                }

                if (!results.length) {
                    container.classList.remove('hidden');
                    container.innerHTML = '<div class="px-4 py-4 text-sm text-slate-500">No se encontraron clientes para esa busqueda.</div>';
                    return;
                }

                container.classList.remove('hidden');
                container.innerHTML = results.map((cliente) => `
                    <button
                        type="button"
                        class="map-search-select flex w-full items-start justify-between gap-3 rounded-xl px-4 py-3 text-left hover:bg-slate-50"
                        data-client-id="${cliente.id}"
                    >
                        <div class="min-w-0">
                            <div class="map-search-result-name truncate">${mapaClientesEscapeHtml(cliente.name)}</div>
                            <div class="map-search-result-address truncate">${mapaClientesEscapeHtml(cliente.full_address || 'Sin direccion')}</div>
                        </div>
                        <span class="map-badge" style="background:${cliente.client_type_color}1A;color:${cliente.client_type_color};">
                            ${mapaClientesEscapeHtml(cliente.client_type_label)}
                        </span>
                    </button>
                `).join('');

                container.querySelectorAll('.map-search-select').forEach((button) => {
                    button.addEventListener('click', () => {
                        const clientId = Number(button.dataset.clientId);
                        const cliente = window.mapaClientesState.clientes.find((item) => item.id === clientId);

                        if (!cliente) {
                            return;
                        }

                        document.getElementById('map-search').value = cliente.name || '';
                        mapaClientesRenderSearchResults();
                        mapaClientesApplyFilters();
                        mapaClientesSelectClient(cliente);
                        mapaClientesTogglePanel(true);
                        mapaClientesToggleSearch(false);
                    });
                });
            }

            function mapaClientesPopupHtml(cliente) {
                const typeBadge = `<span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700;background:${cliente.client_type_color}1A;color:${cliente.client_type_color};">${mapaClientesEscapeHtml(cliente.client_type_label)}</span>`;
                const qualityBadge = `<span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:700;background:#f8fafc;color:#334155;border:1px solid #cbd5e1;">${mapaClientesEscapeHtml(cliente.coordinates_quality_label)}</span>`;
                const pendientes = (cliente.active_pendientes || []).slice(0, 3).map((pendiente) => `
                    <a href="${pendiente.url}" style="display:block;margin-top:8px;padding:8px 10px;border:1px solid #e2e8f0;border-radius:12px;text-decoration:none;color:#111827;background:#f8fafc;">
                        <strong style="font-size:12px;">${mapaClientesEscapeHtml(pendiente.description)}</strong><br>
                        <span style="font-size:11px;color:#64748b;">${mapaClientesEscapeHtml(pendiente.status_label)}${pendiente.due_date ? ` · ${mapaClientesEscapeHtml(pendiente.due_date)}` : ''}</span>
                    </a>
                `).join('');

                return `
                    <div style="min-width:270px;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                            <div style="font-weight:800;font-size:14px;color:#0f172a;">${mapaClientesEscapeHtml(cliente.name)}</div>
                            ${typeBadge}
                        </div>
                        <div style="margin-top:4px;font-size:12px;color:#64748b;">${mapaClientesEscapeHtml(cliente.full_address || 'Sin direccion')}</div>
                        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">${qualityBadge}</div>
                        <div style="margin-top:8px;font-size:12px;color:#334155;">
                            Pendientes: <strong>${cliente.pending_count}</strong> · En proceso: <strong>${cliente.in_progress_count}</strong>
                        </div>
                        <div style="margin-top:10px;">
                            <a href="${cliente.url}" style="display:inline-block;padding:8px 12px;background:#0f172a;color:#fff;border-radius:999px;text-decoration:none;font-size:12px;font-weight:700;">Abrir cliente</a>
                        </div>
                        ${pendientes}
                    </div>
                `;
            }

            function mapaClientesRenderSelectedClient(cliente) {
                const card = document.getElementById('selected-client-card');
                const name = document.getElementById('selected-client-name');
                const address = document.getElementById('selected-client-address');
                const badge = document.getElementById('selected-client-badge');
                const tags = document.getElementById('selected-client-tags');
                const link = document.getElementById('selected-client-link');

                if (!cliente) {
                    card.classList.add('hidden');
                    return;
                }

                card.classList.remove('hidden');
                name.textContent = cliente.name || '';
                address.textContent = cliente.full_address || 'Sin direccion';
                link.href = cliente.url || '#';

                badge.textContent = cliente.client_type_label || 'General';
                badge.style.backgroundColor = `${cliente.client_type_color}1A`;
                badge.style.color = cliente.client_type_color;

                const tagsHtml = [];
                tagsHtml.push(`<span class="map-tag map-tag-neutral">${mapaClientesEscapeHtml(cliente.city || 'Sin ciudad')}</span>`);
                tagsHtml.push(`<span class="map-tag map-tag-neutral">${mapaClientesEscapeHtml(cliente.coordinates_quality_label || 'Sin coordenadas')}</span>`);

                if (cliente.pending_count > 0) {
                    tagsHtml.push(`<span class="map-tag map-tag-warning">${cliente.pending_count} pendiente(s)</span>`);
                }

                if (cliente.in_progress_count > 0) {
                    tagsHtml.push(`<span class="map-tag map-tag-success">${cliente.in_progress_count} en proceso</span>`);
                }

                if (cliente.active_conservations_count > 0) {
                    tagsHtml.push(`<span class="map-tag map-tag-orange">${cliente.active_conservations_count} conservacion(es)</span>`);
                }

                if (!cliente.has_coordinates) {
                    tagsHtml.push('<span class="map-tag map-tag-danger">Revisar ubicacion</span>');
                }

                tags.innerHTML = tagsHtml.join('');
            }

            function mapaClientesSyncMarkerSelection() {
                const selectedId = window.mapaClientesState.selectedClient?.id;

                Object.values(window.mapaClientesState.markerByClientId).forEach((marker) => {
                    const cliente = marker.__cliente;
                    const isSelected = cliente?.id === selectedId;

                    marker.setStyle({
                        radius: mapaClientesGetMarkerRadius(cliente) + (isSelected ? 3 : 0),
                        weight: isSelected ? 4 : 2,
                        fillOpacity: isSelected ? 1 : 0.9,
                    });
                });
            }

            function mapaClientesScrollToCard(clientId) {
                const element = document.getElementById(`cliente-card-${clientId}`);

                if (element) {
                    element.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                    });
                }
            }

            function mapaClientesSelectClient(cliente, options = {}) {
                const settings = {
                    openPopup: true,
                    panMap: true,
                    scrollList: true,
                    ...options,
                };

                window.mapaClientesState.selectedClient = cliente;
                mapaClientesRenderSelectedClient(cliente);
                mapaClientesRenderClientList();
                mapaClientesSyncMarkerSelection();

                if (settings.scrollList) {
                    mapaClientesScrollToCard(cliente.id);
                }

                const marker = window.mapaClientesState.markerByClientId[cliente.id];

                if (!marker) {
                    return;
                }

                if (settings.panMap) {
                    window.mapaClientesState.map.setView(marker.getLatLng(), 16, { animate: true });
                }

                if (settings.openPopup) {
                    marker.openPopup();
                }
            }

            function mapaClientesRenderStats() {
                const clientes = window.mapaClientesState.filtrados;

                document.getElementById('stats-total').textContent = clientes.length;
                document.getElementById('stats-with-coordinates').textContent = clientes.filter((cliente) => cliente.has_coordinates).length;
                document.getElementById('stats-conservation').textContent = clientes.filter((cliente) => cliente.client_type === 'conservation').length;
                document.getElementById('stats-operational').textContent = clientes.filter((cliente) => cliente.client_type === 'operational').length;
                document.getElementById('stats-standard').textContent = clientes.filter((cliente) => cliente.client_type === 'standard').length;
                document.getElementById('stats-without-coordinates').textContent = clientes.filter((cliente) => !cliente.has_coordinates).length;
            }

            function mapaClientesRenderStatus(message = null, tone = 'default') {
                const status = document.getElementById('map-status');

                if (!status) {
                    return;
                }

                const baseClass = 'map-status';
                status.className = baseClass;

                if (tone === 'success') {
                    status.style.background = '#ecfdf5';
                    status.style.color = '#047857';
                } else if (tone === 'error') {
                    status.style.background = '#fff1f2';
                    status.style.color = '#be123c';
                } else if (tone === 'loading') {
                    status.style.background = '#eff6ff';
                    status.style.color = '#1d4ed8';
                } else {
                    status.style.background = '#f8fafc';
                    status.style.color = '#475569';
                }

                const suffix = window.mapaClientesState.lastRefreshAt
                    ? ` · Actualizado ${window.mapaClientesState.lastRefreshAt}`
                    : '';

                status.textContent = `${message ?? 'Mapa operativo listo.'}${suffix}`;
            }

            function mapaClientesRenderMissingClients() {
                const list = document.getElementById('map-missing-list');
                const summary = document.getElementById('map-missing-summary');

                if (!list || !summary) {
                    return;
                }

                const missing = window.mapaClientesState.clientes
                    .filter((cliente) => !cliente.has_coordinates)
                    .sort((a, b) => (a.name || '').localeCompare(b.name || ''));

                if (!missing.length) {
                    summary.textContent = 'Todos los clientes cargados actualmente tienen coordenadas válidas.';
                    list.innerHTML = '<div class="map-empty-state">No hay clientes pendientes de ubicación.</div>';
                    return;
                }

                summary.textContent = `${missing.length} cliente(s) necesitan coordenadas o corrección de dirección.`;
                list.innerHTML = missing.slice(0, 25).map((cliente) => `
                    <div class="map-missing-card">
                        <div class="min-w-0">
                            <div class="map-missing-name">${mapaClientesEscapeHtml(cliente.name)}</div>
                            <div class="map-missing-meta">${mapaClientesEscapeHtml(cliente.full_address || 'Sin direccion')}</div>
                            <div class="map-missing-actions">
                                <button type="button" class="map-mini-btn map-mini-btn-primary map-missing-search" data-client-id="${cliente.id}">
                                    Buscar
                                </button>
                                <a href="${cliente.url}" class="map-mini-btn map-mini-btn-secondary">
                                    Abrir cliente
                                </a>
                            </div>
                        </div>
                        <span class="map-tag map-tag-danger">Sin coord.</span>
                    </div>
                `).join('');

                list.querySelectorAll('.map-missing-search').forEach((button) => {
                    button.addEventListener('click', () => {
                        const clientId = Number(button.dataset.clientId);
                        const cliente = window.mapaClientesState.clientes.find((item) => item.id === clientId);

                        if (!cliente) {
                            return;
                        }

                        document.getElementById('map-search').value = cliente.name || '';
                        mapaClientesApplyFilters();
                        mapaClientesTogglePanel(true);
                    });
                });
            }

            function mapaClientesRenderClientList() {
                const list = document.getElementById('clients-list');
                const empty = document.getElementById('clients-empty-state');
                const idle = document.getElementById('clients-idle-state');
                const selectedId = window.mapaClientesState.selectedClient?.id;
                const hasActiveFilters = mapaClientesHasActiveFilters();

                if (!hasActiveFilters && !selectedId) {
                    list.innerHTML = '';
                    empty.classList.add('hidden');
                    idle.classList.remove('hidden');
                    return;
                }

                idle.classList.add('hidden');

                if (!window.mapaClientesState.filtrados.length) {
                    list.innerHTML = '';
                    empty.classList.remove('hidden');
                    return;
                }

                empty.classList.add('hidden');

                const visibleClients = hasActiveFilters
                    ? window.mapaClientesState.filtrados.slice(0, 40)
                    : window.mapaClientesState.filtrados.filter((cliente) => cliente.id === selectedId);

                list.innerHTML = visibleClients.map((cliente) => {
                    const isSelected = selectedId === cliente.id;
                    const typeStyle = `background:${cliente.client_type_color}1A;color:${cliente.client_type_color};`;
                    const pendientes = (cliente.active_pendientes || []).slice(0, 3).map((pendiente) => `
                        <a href="${pendiente.url}" class="map-pendiente-link">
                            <div class="map-pendiente-title">${mapaClientesEscapeHtml(pendiente.description)}</div>
                            <div class="map-pendiente-meta">
                                <span>${mapaClientesEscapeHtml(pendiente.status_label)}</span>
                                ${pendiente.due_date ? `<span>${mapaClientesEscapeHtml(pendiente.due_date)}</span>` : ''}
                            </div>
                            ${pendiente.notes ? `<div class="map-pendiente-notes">${mapaClientesEscapeHtml(pendiente.notes)}</div>` : ''}
                        </a>
                    `).join('');

                    return `
                        <div id="cliente-card-${cliente.id}" class="map-result-card ${isSelected ? 'is-selected' : ''}">
                            <div class="map-result-top">
                                <button type="button" class="map-result-select map-client-select" data-client-id="${cliente.id}">
                                    <div class="map-result-name">${mapaClientesEscapeHtml(cliente.name)}</div>
                                    <div class="map-result-address">${mapaClientesEscapeHtml(cliente.full_address || 'Sin direccion')}</div>
                                    ${cliente.normalized_address && cliente.normalized_address !== cliente.full_address ? `<div class="map-result-address">${mapaClientesEscapeHtml(cliente.normalized_address)}</div>` : ''}
                                </button>
                                <span class="map-badge" style="${typeStyle}">
                                    ${mapaClientesEscapeHtml(cliente.client_type_label)}
                                </span>
                            </div>

                            <div class="map-result-tags">
                                <span class="map-tag map-tag-neutral">${mapaClientesEscapeHtml(cliente.city || 'Sin ciudad')}</span>
                                <span class="map-tag ${cliente.has_coordinates ? 'map-tag-success' : 'map-tag-danger'}">${cliente.has_coordinates ? mapaClientesEscapeHtml(cliente.coordinates_quality_label) : 'Sin coord.'}</span>
                                ${cliente.pending_count > 0 ? `<span class="map-tag map-tag-warning">${cliente.pending_count} pendiente(s)</span>` : ''}
                                ${cliente.in_progress_count > 0 ? `<span class="map-tag map-tag-success">${cliente.in_progress_count} en proceso</span>` : ''}
                                ${cliente.active_conservations_count > 0 ? `<span class="map-tag map-tag-orange">${cliente.active_conservations_count} conservacion(es)</span>` : ''}
                            </div>

                            ${pendientes ? `<div class="map-pendientes">${pendientes}</div>` : ''}

                            <div class="map-result-actions">
                                <button type="button" class="map-primary-btn map-client-select" data-client-id="${cliente.id}">Ver en mapa</button>
                                <a href="${cliente.url}" class="map-secondary-btn">Abrir cliente</a>
                            </div>
                        </div>
                    `;
                }).join('');

                list.querySelectorAll('.map-client-select').forEach((button) => {
                    button.addEventListener('click', () => {
                        const clientId = Number(button.dataset.clientId);
                        const cliente = window.mapaClientesState.clientes.find((item) => item.id === clientId);

                        if (cliente) {
                            mapaClientesSelectClient(cliente);
                        }
                    });
                });
            }

            function mapaClientesFitVisible() {
                const bounds = window.mapaClientesState.filtrados
                    .filter((cliente) => cliente.has_coordinates)
                    .map((cliente) => [cliente.latitud, cliente.longitud]);

                if (!bounds.length || !window.mapaClientesState.map) {
                    return;
                }

                window.mapaClientesState.map.fitBounds(bounds, { padding: [40, 40] });
            }

            function mapaClientesResetView() {
                if (!window.mapaClientesState.map) {
                    return;
                }

                window.mapaClientesState.map.setView(
                    window.mapaClientesState.defaultCenter,
                    window.mapaClientesState.defaultZoom,
                    { animate: true }
                );
            }

            function mapaClientesShowAllMapped() {
                document.getElementById('map-search').value = '';
                document.getElementById('map-city').value = '';
                document.getElementById('map-client-type').value = '';
                document.getElementById('map-only-active').checked = false;
                document.getElementById('map-only-without-coordinates').checked = false;
                mapaClientesApplyFilters();
                mapaClientesFitVisible();
                mapaClientesTogglePanel(true);
            }

            function mapaClientesRefreshAllData() {
                mapaClientesLoadClients();
                mapaClientesLoadTechnicians();
            }

            function mapaClientesGetTechnicianColor(status) {
                switch (status) {
                    case 'available':
                        return '#2563eb';
                    case 'traveling':
                        return '#f97316';
                    case 'working':
                        return '#16a34a';
                    case 'paused':
                        return '#eab308';
                    case 'finished':
                        return '#8b5cf6';
                    case 'offline':
                    default:
                        return '#64748b';
                }
            }

            function mapaClientesGetTechnicianStatusLabel(status) {
                switch (status) {
                    case 'available':
                        return 'Disponible';
                    case 'traveling':
                        return 'En camino';
                    case 'working':
                        return 'Trabajando';
                    case 'paused':
                        return 'Pausado';
                    case 'finished':
                        return 'Finalizado';
                    case 'offline':
                    default:
                        return 'Offline';
                }
            }

            function mapaClientesTechnicianPopupHtml(item) {
                const visit = item.current_visit;
                const pendiente = visit?.pendiente;
                const client = pendiente?.client;
                const status = item.status || item.technician_status || 'offline';
                const statusLabel = item.status_label || mapaClientesGetTechnicianStatusLabel(status);
                const currentAddress = item.current_client_address
                    || pendiente?.service_address
                    || client?.full_address
                    || 'Sin direccion disponible';
                const currentService = pendiente?.service_order_number
                    || pendiente?.description
                    || (item.current_visit_id ? `Visita #${item.current_visit_id}` : 'Sin servicio activo');

                return `
                    <div style="min-width:250px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <div style="font-weight:800;font-size:14px;color:#0f172a;">${mapaClientesEscapeHtml(item.name)}</div>
                            <span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:800;background:${mapaClientesGetTechnicianColor(status)};color:#fff;">
                                ${mapaClientesEscapeHtml(statusLabel)}
                            </span>
                        </div>
                        <div style="margin-top:6px;font-size:12px;color:#475569;">
                            Ultima conexion: ${mapaClientesEscapeHtml(item.last_seen_at || item.last_seen_human || 'Sin datos recientes')}
                        </div>
                        <div style="margin-top:8px;font-size:12px;color:#334155;">
                            <strong>Lat/Lng:</strong> ${Number(item.last_lat).toFixed(6)}, ${Number(item.last_lng).toFixed(6)}
                        </div>
                        ${client ? `
                            <div style="margin-top:10px;font-size:12px;color:#0f172a;">
                                <strong>Cliente:</strong> ${mapaClientesEscapeHtml(client.name)}
                            </div>
                            <div style="margin-top:4px;font-size:12px;color:#64748b;">
                                ${mapaClientesEscapeHtml(currentAddress)}
                            </div>
                        ` : ''}
                        ${(pendiente || item.current_visit_id) ? `
                            <div style="margin-top:8px;font-size:12px;color:#334155;">
                                <strong>Servicio actual:</strong> ${mapaClientesEscapeHtml(currentService)}
                            </div>
                        ` : ''}
                        ${item.current_operational_zone_label ? `
                            <div style="margin-top:6px;font-size:12px;color:#334155;">
                                <strong>Zona:</strong> ${mapaClientesEscapeHtml(item.current_operational_zone_label)}
                            </div>
                        ` : ''}
                        ${item.speed != null ? `
                            <div style="margin-top:8px;font-size:12px;color:#334155;">
                                <strong>Velocidad:</strong> ${Number(item.speed).toFixed(1)} m/s
                            </div>
                        ` : ''}
                    </div>
                `;
            }

            function mapaClientesTechnicianIcon(item, isSelected = false) {
                const color = mapaClientesGetTechnicianColor(item.status || item.technician_status || 'offline');
                const size = isSelected ? 28 : 22;
                const innerInset = isSelected ? 6 : 5;
                const ring = isSelected
                    ? '0 0 0 4px rgba(15,23,42,.12), 0 18px 28px rgba(15,23,42,.28)'
                    : '0 14px 24px rgba(15,23,42,.26)';

                return L.divIcon({
                    className: 'map-technician-marker',
                    html: `
                        <div style="position:relative;width:${size}px;height:${size}px;">
                            <div style="position:absolute;inset:0;border-radius:999px;background:${color};border:3px solid rgba(255,255,255,.96);box-shadow:${ring};"></div>
                            <div style="position:absolute;inset:${innerInset}px;border-radius:999px;background:rgba(255,255,255,.22);"></div>
                        </div>
                    `,
                    iconSize: [size, size],
                    iconAnchor: [Math.round(size / 2), Math.round(size / 2)],
                    popupAnchor: [0, -Math.round(size / 2)],
                });
            }

            function mapaClientesRenderTechnicians() {
                const state = window.mapaClientesState;

                if (!state.map) {
                    return;
                }

                if (!state.canViewTracking) {
                    if (state.technicianLayerGroup) {
                        state.technicianLayerGroup.clearLayers();
                    }

                    state.technicianMarkers = {};
                    mapaClientesRenderTechnicianPanel();
                    return;
                }

                if (!state.technicianLayerGroup) {
                    state.technicianLayerGroup = L.layerGroup().addTo(state.map);
                }

                const activeMarkerIds = new Set();

                state.technicians.forEach((item) => {
                    if (item.last_lat == null || item.last_lng == null) {
                        return;
                    }

                    const technicianId = Number(item.id);
                    const latLng = [Number(item.last_lat), Number(item.last_lng)];
                    const isSelected = state.selectedTechnicianId === technicianId;

                    activeMarkerIds.add(String(technicianId));

                    if (!state.technicianMarkers[technicianId]) {
                        const marker = L.marker(latLng, {
                            icon: mapaClientesTechnicianIcon(item, isSelected),
                            pane: 'techniciansPane',
                            keyboard: false,
                        });

                        marker.on('click', () => {
                            state.selectedTechnicianId = technicianId;
                            state.technicianPanelOpen = true;
                            mapaClientesRenderTechnicianPanel();
                            mapaClientesRenderTechnicians();
                        });

                        marker.bindPopup(mapaClientesTechnicianPopupHtml(item));
                        state.technicianLayerGroup.addLayer(marker);
                        state.technicianMarkers[technicianId] = marker;
                    }

                    const marker = state.technicianMarkers[technicianId];
                    marker.setLatLng(latLng);
                    marker.setIcon(mapaClientesTechnicianIcon(item, isSelected));
                    marker.setPopupContent(mapaClientesTechnicianPopupHtml(item));
                    marker.__technician = item;
                });

                Object.keys(state.technicianMarkers).forEach((technicianId) => {
                    if (activeMarkerIds.has(technicianId)) {
                        return;
                    }

                    const marker = state.technicianMarkers[technicianId];

                    if (marker) {
                        state.technicianLayerGroup.removeLayer(marker);
                    }

                    delete state.technicianMarkers[technicianId];
                });

                if (
                    state.selectedTechnicianId !== null
                    && !state.technicians.some((item) => Number(item.id) === Number(state.selectedTechnicianId))
                ) {
                    state.selectedTechnicianId = null;
                }

                mapaClientesRenderTechnicianPanel();
            }

            function mapaClientesLoadTechnicians() {
                const state = window.mapaClientesState;

                if (!state.canViewTracking || !state.map || state.technicianRequestInFlight) {
                    return;
                }

                state.technicianRequestInFlight = true;

                fetch('/tracking/live', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => {
                        if (response.status === 403) {
                            state.canViewTracking = false;
                            state.technicians = [];
                            mapaClientesRenderTechnicians();
                            return [];
                        }

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        return response.json();
                    })
                    .then((data) => {
                        state.technicians = Array.isArray(data) ? data : [];
                        state.technicianLastRefreshAt = new Date().toLocaleTimeString('es-AR', {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                        });
                        mapaClientesRenderTechnicians();
                    })
                    .catch((error) => console.error('Error cargando tracking live en mapa de clientes:', error))
                    .finally(() => {
                        state.technicianRequestInFlight = false;
                    });
            }

            function mapaClientesRenderTechnicianPanel() {
                const state = window.mapaClientesState;
                const panel = document.getElementById('map-technicians-panel');
                const list = document.getElementById('map-technicians-list');
                const toggleButton = document.getElementById('map-toggle-technicians');
                const refreshLabel = document.getElementById('map-technicians-last-refresh');

                if (!panel || !list || !toggleButton) {
                    return;
                }

                if (!state.canViewTracking) {
                    toggleButton.classList.add('hidden');
                    panel.classList.remove('is-open');
                    list.innerHTML = '';
                    return;
                }

                toggleButton.classList.remove('hidden');
                panel.classList.toggle('is-open', state.technicianPanelOpen);

                const technicians = [...state.technicians].sort((a, b) => {
                    const statusRank = {
                        working: 0,
                        traveling: 1,
                        available: 2,
                        paused: 3,
                        finished: 4,
                        offline: 5,
                    };

                    return (statusRank[a.status || a.technician_status] ?? 99) - (statusRank[b.status || b.technician_status] ?? 99)
                        || (a.name || '').localeCompare(b.name || '');
                });

                const counts = technicians.reduce((carry, item) => {
                    const status = item.status || item.technician_status || 'offline';

                    if (status !== 'offline') {
                        carry.online += 1;
                    }

                    if (status === 'traveling') {
                        carry.traveling += 1;
                    }

                    if (status === 'working') {
                        carry.working += 1;
                    }

                    if (status === 'offline') {
                        carry.offline += 1;
                    }

                    return carry;
                }, {
                    online: 0,
                    traveling: 0,
                    working: 0,
                    offline: 0,
                });

                document.getElementById('map-tech-online-pill').textContent = String(counts.online);
                document.getElementById('map-tech-count-online').textContent = String(counts.online);
                document.getElementById('map-tech-count-traveling').textContent = String(counts.traveling);
                document.getElementById('map-tech-count-working').textContent = String(counts.working);
                document.getElementById('map-tech-count-offline').textContent = String(counts.offline);

                refreshLabel.textContent = state.technicianLastRefreshAt
                    ? `Actualizado ${state.technicianLastRefreshAt}`
                    : 'Sin actualizar';

                if (!technicians.length) {
                    list.innerHTML = '<div class="map-technician-empty">No hay tecnicos con posicion disponible en este momento.</div>';
                    return;
                }

                list.innerHTML = technicians.map((item) => {
                    const status = item.status || item.technician_status || 'offline';
                    const badgeColor = mapaClientesGetTechnicianColor(status);
                    const currentClientName = item.current_client_name || item.current_visit?.pendiente?.client?.name || 'Sin visita activa';
                    const currentAddress = item.current_client_address
                        || item.current_visit?.pendiente?.service_address
                        || item.current_visit?.pendiente?.client?.full_address
                        || 'Sin direccion asignada';
                    const currentVisitStatus = item.current_visit_status_label
                        || item.current_visit?.status_label
                        || null;
                    const serviceLabel = item.current_pendiente_type_label
                        || item.current_visit?.pendiente?.type_label
                        || null;
                    const serviceDetail = item.current_visit?.pendiente?.service_order_number
                        || item.current_visit?.pendiente?.description
                        || (item.current_visit_id ? `Visita #${item.current_visit_id}` : 'Sin servicio activo');
                    const zoneLabel = item.current_operational_zone_label
                        || item.current_visit?.pendiente?.operational_zone_label
                        || null;
                    const selected = Number(state.selectedTechnicianId) === Number(item.id) ? 'is-selected' : '';
                    const onlineLabel = status === 'offline' ? 'Offline' : 'Online';
                    const hasCoordinates = item.last_lat != null && item.last_lng != null;

                    return `
                        <div class="map-technician-card ${selected}" data-technician-id="${item.id}">
                            <div class="map-technician-card-top">
                                <div>
                                    <div class="map-technician-name">${mapaClientesEscapeHtml(item.name)}</div>
                                    <div class="map-technician-live-row">
                                        <span class="map-technician-live-dot" style="background:${badgeColor};"></span>
                                        <span>${mapaClientesEscapeHtml(onlineLabel)} · ${mapaClientesEscapeHtml(item.last_seen_human || item.last_seen_at || 'Sin registro reciente')}</span>
                                    </div>
                                </div>
                                <span class="map-technician-badge" style="background:${badgeColor};">
                                    ${mapaClientesEscapeHtml(item.status_label || mapaClientesGetTechnicianStatusLabel(status))}
                                </span>
                            </div>
                            <div class="map-technician-meta">
                                <strong>${mapaClientesEscapeHtml(currentClientName)}</strong><br>
                                ${mapaClientesEscapeHtml(serviceDetail)}
                                ${serviceLabel ? `<br>${mapaClientesEscapeHtml(serviceLabel)}` : ''}
                                ${currentVisitStatus ? `<br>Estado visita: ${mapaClientesEscapeHtml(currentVisitStatus)}` : ''}
                            </div>
                            <div class="map-technician-zone">
                                ${mapaClientesEscapeHtml(currentAddress)}
                                ${zoneLabel ? `<br>Zona: ${mapaClientesEscapeHtml(zoneLabel)}` : ''}
                            </div>
                            <div class="map-technician-actions">
                                <button type="button" class="map-technician-focus-btn" data-technician-focus="${item.id}" ${hasCoordinates ? '' : 'disabled'}>
                                    ${hasCoordinates ? 'Ver en mapa' : 'Sin ubicacion'}
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                list.querySelectorAll('[data-technician-focus]').forEach((button) => {
                    button.addEventListener('click', () => {
                        mapaClientesFocusTechnician(Number(button.dataset.technicianFocus));
                    });
                });
            }

            function mapaClientesFocusTechnician(id) {
                const state = window.mapaClientesState;
                const technician = state.technicians.find((item) => Number(item.id) === Number(id));
                const marker = state.technicianMarkers[id];

                if (!technician || !marker || !state.map) {
                    return;
                }

                state.selectedTechnicianId = Number(id);
                state.technicianPanelOpen = true;

                mapaClientesRenderTechnicianPanel();
                mapaClientesRenderTechnicians();

                state.map.setView([Number(technician.last_lat), Number(technician.last_lng)], 16, {
                    animate: true,
                });

                marker.openPopup();
            }

            function mapaClientesToggleTechnicianPanel(forceOpen = null) {
                const state = window.mapaClientesState;

                if (!state.canViewTracking) {
                    return;
                }

                state.technicianPanelOpen = forceOpen === null
                    ? !state.technicianPanelOpen
                    : forceOpen;

                mapaClientesRenderTechnicianPanel();
            }

            function mapaClientesRenderMarkers() {
                const state = window.mapaClientesState;

                if (state.markerClusterGroup) {
                    state.map.removeLayer(state.markerClusterGroup);
                }

                state.markers = [];
                state.markerByClientId = {};
                state.markerClusterGroup = L.markerClusterGroup({
                    showCoverageOnHover: false,
                    spiderfyOnMaxZoom: true,
                    disableClusteringAtZoom: 16,
                    maxClusterRadius: 44,
                    iconCreateFunction(cluster) {
                        const children = cluster.getAllChildMarkers().map((marker) => marker.__cliente).filter(Boolean);
                        const conservation = children.filter((cliente) => cliente.client_type === 'conservation').length;
                        const operational = children.filter((cliente) => cliente.client_type === 'operational').length;
                        const standard = children.length - conservation - operational;
                        const dominantColor = conservation >= operational && conservation >= standard
                            ? '#f97316'
                            : (operational >= standard ? '#0f766e' : '#2563eb');

                        return L.divIcon({
                            html: `<div style="background:${dominantColor};color:#fff;border:4px solid rgba(255,255,255,.92);box-shadow:0 16px 34px rgba(15,23,42,.24);" class="flex h-12 w-12 items-center justify-center rounded-full text-sm font-extrabold">${cluster.getChildCount()}</div>`,
                            className: 'map-client-cluster',
                            iconSize: [48, 48],
                        });
                    },
                });

                const bounds = [];

                state.filtrados.forEach((cliente) => {
                    if (!cliente.has_coordinates) {
                        return;
                    }

                    const marker = L.circleMarker([cliente.latitud, cliente.longitud], {
                        radius: mapaClientesGetMarkerRadius(cliente),
                        color: mapaClientesGetMarkerStrokeColor(cliente),
                        fillColor: mapaClientesGetMarkerColor(cliente),
                        fillOpacity: 0.92,
                        weight: 2.5,
                    });

                    marker.__cliente = cliente;
                    marker.bindPopup(mapaClientesPopupHtml(cliente));
                    marker.on('click', () => {
                        mapaClientesSelectClient(cliente, { openPopup: false, panMap: false });
                        mapaClientesScrollToCard(cliente.id);
                        mapaClientesTogglePanel(true);
                    });

                    state.markers.push(marker);
                    state.markerByClientId[cliente.id] = marker;
                    state.markerClusterGroup.addLayer(marker);
                    bounds.push([cliente.latitud, cliente.longitud]);
                });

                state.map.addLayer(state.markerClusterGroup);
                mapaClientesSyncMarkerSelection();

                if (bounds.length > 0 && mapaClientesHasActiveFilters()) {
                    state.map.fitBounds(bounds, { padding: [40, 40] });
                }
            }

            function mapaClientesApplyFilters() {
                const search = document.getElementById('map-search').value.trim().toLowerCase();
                const city = document.getElementById('map-city').value;
                const clientType = document.getElementById('map-client-type').value;
                const onlyActive = document.getElementById('map-only-active').checked;
                const onlyWithoutCoordinates = document.getElementById('map-only-without-coordinates').checked;

                window.mapaClientesState.filtrados = window.mapaClientesState.clientes.filter((cliente) => {
                    const matchesSearch = search === ''
                        || (cliente.name || '').toLowerCase().includes(search)
                        || (cliente.company || '').toLowerCase().includes(search)
                        || (cliente.full_address || '').toLowerCase().includes(search)
                        || (cliente.city || '').toLowerCase().includes(search)
                        || (cliente.normalized_address || '').toLowerCase().includes(search);

                    const matchesCity = city === '' || cliente.city === city;
                    const matchesType = clientType === '' || cliente.client_type === clientType;
                    const matchesPendientes = !onlyActive || cliente.active_pendientes_count > 0;
                    const matchesCoordinates = !onlyWithoutCoordinates || !cliente.has_coordinates;

                    return matchesSearch && matchesCity && matchesType && matchesPendientes && matchesCoordinates;
                }).sort((a, b) => {
                    const typeRank = {
                        conservation: 0,
                        operational: 1,
                        standard: 2,
                    };

                    return (typeRank[a.client_type] ?? 99) - (typeRank[b.client_type] ?? 99)
                        || Number(b.has_coordinates) - Number(a.has_coordinates)
                        || b.active_pendientes_count - a.active_pendientes_count
                        || (a.name || '').localeCompare(b.name || '');
                });

                mapaClientesRenderStats();
                mapaClientesRenderSearchResults();
                mapaClientesRenderClientList();
                mapaClientesRenderMarkers();
            }

            function mapaClientesPopulateCities() {
                const select = document.getElementById('map-city');
                const currentValue = select.value;
                const cities = [...new Set(window.mapaClientesState.clientes.map((cliente) => cliente.city).filter(Boolean))].sort();

                select.innerHTML = '<option value="">Todas las ciudades</option>';

                cities.forEach((city) => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    select.appendChild(option);
                });

                select.value = currentValue;
            }

            function mapaClientesLoadClients() {
                mapaClientesRenderStatus('Actualizando clientes...', 'loading');

                fetch('/mapa/clientes', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        window.mapaClientesState.clientes = Array.isArray(data) ? data : [];
                        window.mapaClientesState.lastRefreshAt = new Date().toLocaleTimeString('es-AR', {
                            hour: '2-digit',
                            minute: '2-digit',
                        });
                        mapaClientesPopulateCities();
                        mapaClientesApplyFilters();
                        mapaClientesRenderSearchResults();
                        mapaClientesRenderMissingClients();

                        const selectedId = window.mapaClientesState.selectedClient?.id;
                        if (selectedId) {
                            const refreshedSelected = window.mapaClientesState.clientes.find((cliente) => cliente.id === selectedId);
                            if (refreshedSelected) {
                                window.mapaClientesState.selectedClient = refreshedSelected;
                                mapaClientesRenderSelectedClient(refreshedSelected);
                                mapaClientesSyncMarkerSelection();
                            }
                        }

                        mapaClientesRenderStatus(`${window.mapaClientesState.clientes.length} cliente(s) cargado(s)`, 'success');
                    })
                    .catch((error) => {
                        console.error('Error cargando clientes del mapa:', error);
                        mapaClientesRenderStatus('No se pudieron cargar los clientes del mapa.', 'error');
                    });
            }

            function mapaClientesInitMap() {
                if (!window.L) {
                    setTimeout(mapaClientesInitMap, 150);
                    return;
                }

                const mapElement = document.getElementById('clients-map');

                if (!mapElement) {
                    return;
                }

                if (window.mapaClientesState.map) {
                    window.mapaClientesState.map.invalidateSize();
                    mapaClientesRefreshAllData();
                    return;
                }

                window.mapaClientesState.map = L.map(mapElement, {
                    zoomControl: true,
                    preferCanvas: true,
                }).setView(window.mapaClientesState.defaultCenter, window.mapaClientesState.defaultZoom);

                const techniciansPane = window.mapaClientesState.map.createPane('techniciansPane');
                techniciansPane.style.zIndex = '650';

                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap &copy; CARTO',
                    maxZoom: 20,
                }).addTo(window.mapaClientesState.map);

                setTimeout(() => window.mapaClientesState.map.invalidateSize(), 250);
                mapaClientesRefreshAllData();
            }

            function mapaClientesBindEvents() {
                if (window.mapaClientesState.eventsBound) {
                    return;
                }

                document.getElementById('map-search')?.addEventListener('input', mapaClientesApplyFilters);
                document.getElementById('map-search')?.addEventListener('focus', mapaClientesRenderSearchResults);
                document.getElementById('map-city')?.addEventListener('change', mapaClientesApplyFilters);
                document.getElementById('map-client-type')?.addEventListener('change', mapaClientesApplyFilters);
                document.getElementById('map-only-active')?.addEventListener('change', mapaClientesApplyFilters);
                document.getElementById('map-only-without-coordinates')?.addEventListener('change', mapaClientesApplyFilters);
                document.getElementById('map-clear-search')?.addEventListener('click', () => {
                    document.getElementById('map-search').value = '';
                    mapaClientesApplyFilters();
                });
                document.getElementById('map-open-search')?.addEventListener('click', () => mapaClientesToggleSearch(true));
                document.getElementById('map-close-search')?.addEventListener('click', () => mapaClientesToggleSearch(false));
                document.getElementById('map-refresh-data')?.addEventListener('click', mapaClientesRefreshAllData);
                document.getElementById('selected-client-focus')?.addEventListener('click', () => {
                    if (window.mapaClientesState.selectedClient) {
                        mapaClientesSelectClient(window.mapaClientesState.selectedClient);
                    }
                });
                document.getElementById('map-fit-visible')?.addEventListener('click', mapaClientesFitVisible);
                document.getElementById('map-show-all')?.addEventListener('click', mapaClientesShowAllMapped);
                document.getElementById('map-reset-view')?.addEventListener('click', mapaClientesResetView);
                document.getElementById('map-filter-missing')?.addEventListener('click', () => {
                    document.getElementById('map-search').value = '';
                    document.getElementById('map-city').value = '';
                    document.getElementById('map-client-type').value = '';
                    document.getElementById('map-only-active').checked = false;
                    document.getElementById('map-only-without-coordinates').checked = true;
                    mapaClientesApplyFilters();
                    mapaClientesTogglePanel(true);
                });
                document.getElementById('map-toggle-panel')?.addEventListener('click', () => mapaClientesTogglePanel());
                document.getElementById('map-close-panel')?.addEventListener('click', () => mapaClientesTogglePanel(false));
                document.getElementById('map-toggle-technicians')?.addEventListener('click', () => mapaClientesToggleTechnicianPanel());
                document.getElementById('map-close-technicians')?.addEventListener('click', () => mapaClientesToggleTechnicianPanel(false));

                document.addEventListener('click', (event) => {
                    const search = document.getElementById('map-search');
                    const results = document.getElementById('map-search-results');

                    if (!search || !results) {
                        return;
                    }

                    const shell = document.getElementById('map-search-shell');

                    if (shell && !shell.contains(event.target) && event.target.id !== 'map-open-search') {
                        results.classList.add('hidden');
                    }
                });

                window.mapaClientesState.eventsBound = true;
            }

            function mapaClientesBoot() {
                mapaClientesBindEvents();
                mapaClientesRenderTechnicianPanel();
                mapaClientesInitMap();

                if (
                    window.mapaClientesState.canViewTracking
                    && !window.mapaClientesState.technicianIntervalStarted
                ) {
                    window.mapaClientesState.technicianIntervalStarted = true;
                    window.mapaClientesState.technicianRefreshTimer = window.setInterval(
                        mapaClientesLoadTechnicians,
                        5000,
                    );
                }
            }

            document.addEventListener('DOMContentLoaded', mapaClientesBoot);
            document.addEventListener('livewire:navigated', mapaClientesBoot);

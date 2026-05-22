<x-filament-panels::page>
    <div class="map-shell" data-can-view-tracking="{{ auth()->user()?->can('tracking.view') ? '1' : '0' }}">
        <div class="map-stage">
            <div id="clients-map" wire:ignore class="map-canvas"></div>

            <div class="map-topbar">
                <div class="map-toolbar-floating">
                    <button id="map-open-search" type="button" class="map-round-action" title="Buscar clientes">
                        ⌕
                    </button>

                    <button id="map-refresh-data" type="button" class="map-toolbar-btn" title="Volver a leer clientes y coordenadas">
                        Actualizar mapa
                    </button>

                    <button id="map-toggle-technicians" type="button" class="map-toolbar-btn map-toolbar-btn-tech" title="Mostrar tecnicos activos">
                        Tecnicos
                        <span id="map-tech-online-pill" class="map-toolbar-counter">0</span>
                    </button>

                    <button id="map-toggle-panel" type="button" class="map-toolbar-btn map-toolbar-btn-strong">
                        Panel
                    </button>
                </div>

                <div id="map-search-shell" class="map-search-shell hidden">
                    <div class="map-search-row">
                        <div class="map-search-input-wrap">
                            <span class="map-search-icon">⌕</span>
                            <input
                                id="map-search"
                                type="text"
                                placeholder="Buscar cliente, direccion o ciudad"
                                class="map-search-input"
                                autocomplete="off"
                            />
                            <button id="map-clear-search" type="button" class="map-clear-btn hidden">Limpiar</button>
                        </div>

                        <button id="map-close-search" type="button" class="map-toolbar-btn">
                            Cerrar
                        </button>
                    </div>

                    <div id="map-search-results" class="map-search-results hidden"></div>

                    <div class="map-filter-chips">
                        <select id="map-city" class="map-select">
                            <option value="">Todas las ciudades</option>
                        </select>

                        <select id="map-client-type" class="map-select">
                            <option value="">Todos los tipos</option>
                            <option value="conservation">Conservacion</option>
                            <option value="operational">Operativos</option>
                            <option value="standard">Generales</option>
                        </select>

                        <label class="map-chip-check">
                            <input id="map-only-active" type="checkbox">
                            <span>Solo activos</span>
                        </label>

                        <label class="map-chip-check">
                            <input id="map-only-without-coordinates" type="checkbox">
                            <span>Sin coordenadas</span>
                        </label>
                    </div>
                </div>
            </div>

            <aside id="map-sidebar" class="map-sidebar">
                <div class="map-sidebar-inner">
                    <div class="map-sidebar-head">
                        <div>
                            <div class="map-eyebrow">Mapa operativo</div>
                            <h2 class="map-title">Clientes y cobertura</h2>
                        </div>
                        <button id="map-close-panel" type="button" class="map-icon-btn">×</button>
                    </div>

                    <div id="map-status" class="map-status">
                        Cargando clientes...
                    </div>

                    <div class="map-stats-grid">
                        <div class="map-stat-card">
                            <span class="map-stat-label">Clientes</span>
                            <strong id="stats-total" class="map-stat-value">0</strong>
                        </div>
                        <div class="map-stat-card map-stat-card-blue">
                            <span class="map-stat-label">En mapa</span>
                            <strong id="stats-with-coordinates" class="map-stat-value">0</strong>
                        </div>
                        <div class="map-stat-card map-stat-card-orange">
                            <span class="map-stat-label">Conservacion</span>
                            <strong id="stats-conservation" class="map-stat-value">0</strong>
                        </div>
                        <div class="map-stat-card map-stat-card-teal">
                            <span class="map-stat-label">Operativos</span>
                            <strong id="stats-operational" class="map-stat-value">0</strong>
                        </div>
                        <div class="map-stat-card map-stat-card-sky">
                            <span class="map-stat-label">Generales</span>
                            <strong id="stats-standard" class="map-stat-value">0</strong>
                        </div>
                        <div class="map-stat-card map-stat-card-rose">
                            <span class="map-stat-label">Sin coord.</span>
                            <strong id="stats-without-coordinates" class="map-stat-value">0</strong>
                        </div>
                    </div>

                    <div class="map-missing-panel">
                        <div class="map-section-head">
                            <div>
                                <div class="map-section-eyebrow">Pendientes de ubicacion</div>
                                <div class="map-section-title">Clientes sin coordenadas</div>
                            </div>
                            <button id="map-filter-missing" type="button" class="map-inline-btn">
                                Ver solo estos
                            </button>
                        </div>

                        <div id="map-missing-summary" class="map-missing-summary">
                            Revisá estos clientes para completar direccion o corregir coordenadas.
                        </div>

                        <div id="map-missing-list" class="map-missing-list"></div>
                    </div>

                    <div id="selected-client-card" class="map-selected hidden">
                        <div class="map-selected-top">
                            <div class="map-selected-copy">
                                <div id="selected-client-name" class="map-selected-name"></div>
                                <div id="selected-client-address" class="map-selected-address"></div>
                            </div>
                            <div id="selected-client-badge" class="map-selected-badge"></div>
                        </div>

                        <div id="selected-client-tags" class="map-tags"></div>

                        <div class="map-selected-actions">
                            <button id="selected-client-focus" type="button" class="map-primary-btn">Ver marcador</button>
                            <a id="selected-client-link" href="#" class="map-secondary-btn">Abrir cliente</a>
                        </div>
                    </div>

                    <div class="map-legend">
                        <div class="map-legend-title">Leyenda</div>
                        <div class="map-legend-group">
                            <div class="map-legend-item">
                                <span class="map-dot map-dot-orange"></span>
                                <span>Conservacion</span>
                            </div>
                            <div class="map-legend-item">
                                <span class="map-dot map-dot-teal"></span>
                                <span>Operativo</span>
                            </div>
                            <div class="map-legend-item">
                                <span class="map-dot map-dot-blue"></span>
                                <span>General</span>
                            </div>
                        </div>
                        <div class="map-legend-group map-legend-group-subtle">
                            <div class="map-legend-item">
                                <span class="map-ring map-ring-emerald"></span>
                                <span>Geocodificada</span>
                            </div>
                            <div class="map-legend-item">
                                <span class="map-ring map-ring-amber"></span>
                                <span>Aproximada</span>
                            </div>
                            <div class="map-legend-item">
                                <span class="map-ring map-ring-slate"></span>
                                <span>Basica</span>
                            </div>
                        </div>
                    </div>

                    <div class="map-results-wrap">
                        <div id="clients-idle-state" class="map-empty-state">
                            Empezá escribiendo en el buscador o usá filtros para trabajar el mapa de forma puntual.
                        </div>

                        <div id="clients-empty-state" class="map-empty-state hidden">
                            No hay clientes para mostrar con los filtros actuales.
                        </div>

                        <div id="clients-list" class="map-results-list"></div>
                    </div>
                </div>
            </aside>

            <aside id="map-technicians-panel" class="map-technicians-panel">
                <div class="map-technicians-card">
                    <div class="map-technicians-head">
                        <div>
                            <div class="map-eyebrow">Tracking live</div>
                            <h3 class="map-technicians-title">Tecnicos activos</h3>
                        </div>
                        <button id="map-close-technicians" type="button" class="map-icon-btn">×</button>
                    </div>

                    <div class="map-technicians-summary">
                        Seguimiento visual de cuadrillas y visitas en curso.
                    </div>

                    <div class="map-technicians-stats">
                        <div class="map-technicians-stat">
                            <span class="map-technicians-stat-label">Online</span>
                            <strong id="map-tech-count-online" class="map-technicians-stat-value">0</strong>
                        </div>
                        <div class="map-technicians-stat map-technicians-stat-traveling">
                            <span class="map-technicians-stat-label">En camino</span>
                            <strong id="map-tech-count-traveling" class="map-technicians-stat-value">0</strong>
                        </div>
                        <div class="map-technicians-stat map-technicians-stat-working">
                            <span class="map-technicians-stat-label">Trabajando</span>
                            <strong id="map-tech-count-working" class="map-technicians-stat-value">0</strong>
                        </div>
                        <div class="map-technicians-stat map-technicians-stat-offline">
                            <span class="map-technicians-stat-label">Offline</span>
                            <strong id="map-tech-count-offline" class="map-technicians-stat-value">0</strong>
                        </div>
                    </div>

                    <div id="map-technicians-last-refresh" class="map-technicians-last-refresh">
                        Sin actualizar
                    </div>

                    <div id="map-technicians-list" class="map-technicians-list"></div>
                </div>
            </aside>

            <div class="map-actions">
                <button id="map-fit-visible" type="button" class="map-fab" title="Ver resultados visibles">⌖</button>
                <button id="map-show-all" type="button" class="map-fab" title="Mostrar todos los clientes con coordenadas">◎</button>
                <button id="map-reset-view" type="button" class="map-fab" title="Volver a vista inicial">↺</button>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
        @vite('resources/css/filament/pages/mapa-clientes.css')
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
        @vite('resources/js/filament/pages/mapa-clientes.js')
    @endpush
</x-filament-panels::page>

<x-filament-panels::page>
    <div class="erp-dashboard">
        <section class="erp-dashboard-hero">
            <div class="erp-dashboard-hero-grid">
                <div>
                    <span class="erp-dashboard-eyebrow">Centro de control</span>
                    <h1 class="erp-dashboard-title">Operacion clara, prioridades visibles y gestion mas rapida.</h1>
                    <p class="erp-dashboard-subtitle">
                        Vista ejecutiva para coordinar el frente comercial y operativo sin ruido visual.
                        El objetivo es que el equipo lea el estado del negocio en segundos y actue desde accesos directos concretos.
                    </p>

                    <div class="erp-dashboard-meta">
                        @foreach ($this->heroStats as $stat)
                            <div class="erp-dashboard-meta-card">
                                <p class="erp-dashboard-meta-label">{{ $stat['label'] }}</p>
                                <p class="erp-dashboard-meta-value">{{ $stat['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="erp-dashboard-panel">
                    <h3>Jornada actual</h3>
                    <p>{{ $this->getDashboardDate() }}</p>

                    <div class="erp-dashboard-actions">
                        @foreach ($this->quickActions as $action)
                            <a href="{{ $action['url'] }}" class="erp-dashboard-action">
                                <strong>{{ $action['title'] }}</strong>
                                <span>{{ $action['description'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </aside>
            </div>
        </section>

        <section class="erp-dashboard-stats">
            @foreach ($this->getDashboardKpis() as $kpi)
                <article class="erp-dashboard-kpi">
                    <p class="erp-dashboard-kpi-label">{{ $kpi['label'] }}</p>
                    <div class="erp-dashboard-kpi-value">{{ number_format($kpi['value'], 0, ',', '.') }}</div>
                    <p class="erp-dashboard-kpi-note">{{ $kpi['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="erp-dashboard-grid">
            <article class="erp-dashboard-panel">
                <h3>Areas de enfoque</h3>
                <p>Atajos estructurados para navegar por los frentes con mayor impacto operativo.</p>

                <div class="erp-dashboard-focus-list">
                    @foreach ($this->focusAreas as $area)
                        <a href="{{ $area['url'] }}" class="erp-dashboard-focus-card">
                            <h3>{{ $area['title'] }}</h3>
                            <p>{{ $area['description'] }}</p>
                            <span class="erp-dashboard-focus-metric">{{ $area['metric'] }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            <aside class="erp-dashboard-panel">
                <h3>Alertas operativas</h3>
                <p>Indicadores rapidos para detectar carga, atraso y urgencia.</p>

                @if (count($this->operationalItems))
                    <div class="erp-dashboard-list">
                        @foreach ($this->operationalItems as $item)
                            <div class="erp-dashboard-list-item">
                                <div>
                                    <strong>{{ $item['title'] }}</strong>
                                    <span>{{ $item['detail'] }}</span>
                                </div>
                                <span class="erp-dashboard-pill {{ $item['tone'] }}">
                                    {{ number_format($item['value'], 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="erp-dashboard-empty">No hay alertas cargadas para mostrar.</div>
                @endif
            </aside>
        </section>
    </div>
</x-filament-panels::page>

@php
    $report = $this->getReportData();
    $presetOptions = $this->getPresetOptions();
    $commercialOptions = $this->getCommercialOptions();
@endphp

<x-filament-panels::page>
    <div wire:loading.class="opacity-70" class="erp-metrics space-y-6 transition-opacity duration-200">
        <section class="erp-metrics-hero">
            <div class="erp-metrics-hero-grid">
                <div class="space-y-4 min-w-0">
                    <div>
                        <span class="erp-metrics-kicker">{{ $report['hero']['eyebrow'] }}</span>
                        <h1 class="erp-metrics-title">{{ $report['hero']['title'] }}</h1>
                        <p class="erp-metrics-subtitle">{{ $report['hero']['subtitle'] }}</p>
                    </div>

                    <div class="erp-metrics-chip-grid">
                        @foreach ($report['hero']['chips'] as $chip)
                            <div class="erp-metrics-chip">
                                <span class="erp-metrics-chip-label">{{ $chip['label'] }}</span>
                                <strong class="erp-metrics-chip-value">{{ $chip['value'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="erp-metrics-filter-panel min-w-0">
                    <div class="space-y-2">
                        <p class="erp-muted-label">Lectura activa</p>
                        <h3 class="erp-strong-text text-lg">Periodo y ownership comercial</h3>
                        <p class="erp-copy">Filtro pensado para analizar cartera completa o una vendedora puntual sin salir del tablero.</p>
                    </div>

                    <div class="erp-metrics-hero-notes">
                        <div>
                            <span class="erp-metrics-note-label">Periodo</span>
                            <strong>{{ $report['hero']['period_label'] }}</strong>
                        </div>
                        <div>
                            <span class="erp-metrics-note-label">Cobertura</span>
                            <strong>{{ $report['hero']['scope_label'] }}</strong>
                        </div>
                    </div>

                    <div class="erp-metrics-pills">
                        @foreach ($presetOptions as $value => $label)
                            <button
                                type="button"
                                wire:click="setPeriodPreset('{{ $value }}')"
                                class="erp-metrics-pill {{ $periodPreset === $value ? 'is-active' : '' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div class="erp-metrics-form-grid">
                        <label class="erp-metrics-field">
                            <span>Desde</span>
                            <input type="date" wire:model.live="fromDate" class="erp-metrics-input">
                        </label>

                        <label class="erp-metrics-field">
                            <span>Hasta</span>
                            <input type="date" wire:model.live="untilDate" class="erp-metrics-input">
                        </label>

                        <label class="erp-metrics-field erp-metrics-field-full">
                            <span>Responsable comercial</span>
                            <select wire:model.live="commercialId" class="erp-metrics-input">
                                <option value="">Toda la cartera visible</option>
                                @foreach ($commercialOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <button type="button" wire:click="resetFilters" class="erp-metrics-reset">
                        Reiniciar filtros
                    </button>

                    <div class="grid gap-2 md:grid-cols-2">
                        <a href="{{ $this->getExportUrl('xlsx') }}" class="erp-link-button" target="_blank" rel="noreferrer">
                            Exportar Excel
                        </a>
                        <a href="{{ $this->getExportUrl('pdf') }}" class="erp-link-button" target="_blank" rel="noreferrer">
                            Exportar PDF
                        </a>
                    </div>
                </aside>
            </div>
        </section>

        <section class="erp-metrics-overview-grid grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($report['overview_cards'] as $card)
                <article class="erp-metrics-stat erp-metrics-tone-{{ $card['tone'] }}">
                    <p class="erp-metrics-stat-label">{{ $card['label'] }}</p>
                    <div class="erp-metrics-stat-value">{{ $card['value'] }}</div>
                    <p class="erp-metrics-stat-note">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="erp-metrics-layout grid gap-5 xl:grid-cols-[minmax(0,1.7fr),minmax(0,1fr)]">
            <article class="erp-surface-card min-w-0 p-5">
                <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                    <span class="erp-step-label">Embudo del periodo</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Como progresa la cartera desde ingreso hasta cierre.</h2>
                    <p class="erp-copy">Cada etapa muestra volumen acumulado y conversion respecto de la instancia previa.</p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($report['funnel'] as $stage)
                        <article class="erp-metrics-stage">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="erp-metrics-stage-label">{{ $stage['label'] }}</p>
                                    <p class="erp-metrics-stage-share">{{ $stage['share'] }} del total</p>
                                </div>

                                <span class="erp-metrics-stage-count">{{ $stage['count'] }}</span>
                            </div>

                            <div class="erp-metrics-stage-bar">
                                <span class="erp-metrics-stage-bar-fill tone-{{ $stage['tone'] }}" style="width: {{ $stage['width'] }}%"></span>
                            </div>

                            <div class="erp-metrics-stage-footer">
                                <span>{{ $stage['step_conversion'] }}</span>
                                @if ($stage['drop_off'] > 0)
                                    <strong>{{ number_format($stage['drop_off'], 0, ',', '.') }} quedan fuera</strong>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </article>

            <div class="space-y-5 min-w-0">
                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Lectura ejecutiva</span>
                        <h2 class="text-xl font-semibold erp-strong-text">Insights automaticos</h2>
                        <p class="erp-copy">Resumen rapido de lo mas rentable, lo que se cae y lo que conviene atacar primero.</p>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($report['insights'] as $insight)
                            <article class="erp-metrics-insight erp-metrics-insight-{{ $insight['tone'] }}">
                                <strong>{{ $insight['title'] }}</strong>
                                <p>{{ $insight['description'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </article>

                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Riesgos</span>
                        <h2 class="text-xl font-semibold erp-strong-text">Alertas operativas</h2>
                        <p class="erp-copy">Indicadores para ordenar prioridad comercial antes de que se enfrie la oportunidad.</p>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($report['alerts'] as $alert)
                            <div class="erp-metrics-alert">
                                <div>
                                    <strong>{{ $alert['title'] }}</strong>
                                    <p>{{ $alert['detail'] }}</p>
                                </div>

                                <span class="erp-dashboard-pill {{ $alert['tone'] }}">
                                    {{ $alert['value'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </section>

        <section class="erp-surface-card min-w-0 p-5">
            <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                <span class="erp-step-label">Objetivos mensuales</span>
                <h2 class="text-xl font-semibold erp-strong-text">Desvio contra meta del mes {{ $report['goal_scoreboard']['month_label'] }}</h2>
                <p class="erp-copy">Comparacion entre objetivo cargado y resultado real mensual por vendedora para corregir desvio antes del cierre.</p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($report['goal_scoreboard']['summary_cards'] as $card)
                    <article class="erp-metrics-stage">
                        <p class="erp-metrics-stage-label">{{ $card['label'] }}</p>
                        <div class="erp-metrics-stage-count text-2xl">{{ $card['value'] }}</div>
                        <p class="erp-metrics-stage-share">{{ $card['note'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="erp-metrics-table">
                    <thead>
                        <tr>
                            <th>Comercial</th>
                            <th>Meta leads</th>
                            <th>Real leads</th>
                            <th>Desvio leads</th>
                            <th>Meta contactos</th>
                            <th>Real contactos</th>
                            <th>Desvio contactos</th>
                            <th>Meta cotizaciones</th>
                            <th>Real cotizaciones</th>
                            <th>Desvio cotizaciones</th>
                            <th>Meta ventas</th>
                            <th>Real ventas</th>
                            <th>Desvio ventas</th>
                            <th>Meta facturacion</th>
                            <th>Real facturacion</th>
                            <th>Desvio facturacion</th>
                            <th>Cumplimiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['goal_scoreboard']['rows'] as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['name'] }}</strong>
                                    @if ($row['notes'])
                                        <span>{{ $row['notes'] }}</span>
                                    @endif
                                </td>
                                <td>{{ $row['goal_leads'] }}</td>
                                <td>{{ $row['actual_leads'] }}</td>
                                <td>{{ $row['lead_delta'] }}</td>
                                <td>{{ $row['goal_contacts'] }}</td>
                                <td>{{ $row['actual_contacts'] }}</td>
                                <td>{{ $row['contact_delta'] }}</td>
                                <td>{{ $row['goal_quotes'] }}</td>
                                <td>{{ $row['actual_quotes'] }}</td>
                                <td>{{ $row['quote_delta'] }}</td>
                                <td>{{ $row['goal_sales'] }}</td>
                                <td>{{ $row['actual_sales'] }}</td>
                                <td>{{ $row['sales_delta'] }}</td>
                                <td>{{ $row['goal_revenue'] }}</td>
                                <td>{{ $row['actual_revenue'] }}</td>
                                <td>{{ $row['revenue_delta'] }}</td>
                                <td>{{ $row['attainment'] }}</td>
                                <td>
                                    <span class="erp-dashboard-pill {{ $row['status_tone'] }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="erp-metrics-empty-row">
                                    No hay objetivos cargados para el mes. Cargalos desde el recurso "Objetivos" para empezar a medir desvio.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-metrics-layout grid gap-5 xl:grid-cols-[minmax(0,1.5fr),minmax(0,1fr)]">
            <article class="erp-surface-card min-w-0 p-5">
                <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                    <span class="erp-step-label">Performance del equipo</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Desempeno por vendedora</h2>
                    <p class="erp-copy">Lectura comparativa de volumen, avance, conversion, ingresos y cartera que ya pide seguimiento.</p>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="erp-metrics-table">
                        <thead>
                            <tr>
                                <th>Comercial</th>
                                <th>Leads</th>
                                <th>Contactados</th>
                                <th>Cotizados</th>
                                <th>Ganados</th>
                                <th>Perdidos</th>
                                <th>Sin ritmo</th>
                                <th>Conversion</th>
                                <th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['team_performance'] as $row)
                                <tr>
                                    <td>
                                        <strong>{{ $row['name'] }}</strong>
                                        <span>{{ $row['contact_rate'] }} de contacto</span>
                                    </td>
                                    <td>{{ $row['leads'] }}</td>
                                    <td>{{ $row['contacted'] }}</td>
                                    <td>{{ $row['quoted'] }}</td>
                                    <td>{{ $row['won'] }}</td>
                                    <td>{{ $row['lost'] }}</td>
                                    <td>{{ $row['stale'] }}</td>
                                    <td>{{ $row['conversion'] }}</td>
                                    <td>{{ $row['revenue'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="erp-metrics-empty-row">No hay registros comerciales para este filtro.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="erp-surface-card min-w-0 p-5">
                <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                    <span class="erp-step-label">Perdidas</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Motivos de perdida</h2>
                    <p class="erp-copy">Donde mas se fuga el cierre para poder ajustar propuesta, precio o manejo de objeciones.</p>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse ($report['loss_reasons'] as $reason)
                        <article class="erp-metrics-loss-item">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ $reason['reason'] }}</strong>
                                    <p>{{ $reason['share'] }} de las oportunidades perdidas</p>
                                </div>

                                <span>{{ $reason['count'] }}</span>
                            </div>

                            <div class="erp-metrics-stage-bar">
                                <span class="erp-metrics-stage-bar-fill tone-danger" style="width: {{ $reason['width'] }}%"></span>
                            </div>
                        </article>
                    @empty
                        <div class="erp-empty-copy">No hay perdidas registradas en el periodo filtrado.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="erp-surface-card min-w-0 p-5">
            <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                <span class="erp-step-label">Origen comercial</span>
                <h2 class="text-xl font-semibold erp-strong-text">Rendimiento por canal</h2>
                <p class="erp-copy">Compara que fuente trae mejor calidad de lead, mas cierres y mejor retorno para el esfuerzo de captacion.</p>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="erp-metrics-table">
                    <thead>
                        <tr>
                            <th>Canal</th>
                            <th>Leads</th>
                            <th>Contacto</th>
                            <th>Ganados</th>
                            <th>Conversion</th>
                            <th>Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['source_breakdown'] as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['channel'] }}</strong>
                                </td>
                                <td>{{ $row['leads'] }}</td>
                                <td>{{ $row['contact_rate'] }}</td>
                                <td>{{ $row['won'] }}</td>
                                <td>{{ $row['conversion'] }}</td>
                                <td>{{ $row['revenue'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="erp-metrics-empty-row">No hay leads suficientes para evaluar fuentes en este periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>

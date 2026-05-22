@php
    $report = $this->getReportData();
    $presetOptions = $this->getPresetOptions();
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
                        <h3 class="erp-strong-text text-lg">Periodo de analisis</h3>
                        <p class="erp-copy">Las metricas de throughput respetan el rango filtrado. Los atrasos, pendientes y saldos abiertos muestran el estado actual del ERP para leer salud operativa real.</p>
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
                    </div>

                    <button type="button" wire:click="resetFilters" class="erp-metrics-reset">
                        Reiniciar filtros
                    </button>
                </aside>
            </div>
        </section>

        <section class="erp-metrics-overview-grid grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
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
                    <span class="erp-step-label">Pulso general</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Semaforo por area</h2>
                    <p class="erp-copy">Lectura rapida para entender donde esta la presion real de la empresa en este momento.</p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($report['company_pulse'] as $item)
                        <article class="erp-metrics-stage">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="erp-metrics-stage-label">{{ $item['label'] }}</p>
                                    <p class="erp-metrics-stage-share">{{ $item['value'] }}</p>
                                </div>

                                <span class="erp-dashboard-pill {{ $item['tone'] }}">
                                    {{ $item['status_label'] }}
                                </span>
                            </div>

                            <div class="erp-metrics-stage-bar">
                                <span class="erp-metrics-stage-bar-fill tone-{{ $item['tone'] === 'danger' ? 'danger' : ($item['tone'] === 'warning' ? 'warning' : 'success') }}" style="width: {{ $item['width'] }}%"></span>
                            </div>

                            <div class="erp-metrics-stage-footer">
                                <span>{{ $item['note'] }}</span>
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
                        <p class="erp-copy">Observaciones sintetizadas a partir del comportamiento del ERP en el rango filtrado.</p>
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
                        <span class="erp-step-label">Alertas transversales</span>
                        <h2 class="text-xl font-semibold erp-strong-text">Riesgos que piden accion</h2>
                        <p class="erp-copy">Lo mas urgente para corregir ritmo, caja, backlog o cobertura de servicio.</p>
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
                <span class="erp-step-label">Cartera de clientes</span>
                <h2 class="text-xl font-semibold erp-strong-text">Base activa, recurrencia y clientes bajo presion</h2>
                <p class="erp-copy">Lectura de la cartera real para detectar donde se concentra operacion, postventa, cobranza o carga administrativa.</p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ($report['clients']['summary_cards'] as $card)
                    <article class="erp-metrics-stat erp-metrics-tone-{{ $card['tone'] }}">
                        <p class="erp-metrics-stat-label">{{ $card['label'] }}</p>
                        <div class="erp-metrics-stat-value">{{ $card['value'] }}</div>
                        <p class="erp-metrics-stat-note">{{ $card['note'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.4fr),minmax(0,1fr)]">
                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Top seguimiento</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Clientes con mas frentes activos</h3>
                        <p class="erp-copy">Prioriza donde hay mas roce entre operacion, reclamos, cobranza, habilitaciones o recurrencia contractual.</p>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="erp-metrics-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Operacion</th>
                                    <th>Reclamos</th>
                                    <th>Cobranza</th>
                                    <th>Conservaciones</th>
                                    <th>Habilitaciones</th>
                                    <th>Lectura</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['clients']['portfolio_rows'] as $row)
                                    <tr>
                                        <td><strong>{{ $row['name'] }}</strong></td>
                                        <td>{{ $row['open_pendings'] }}</td>
                                        <td>{{ $row['open_claims'] }}</td>
                                        <td>{{ $row['outstanding'] }}</td>
                                        <td>{{ $row['recurring'] }}</td>
                                        <td>{{ $row['open_habilitations'] }}</td>
                                        <td>
                                            <span class="erp-dashboard-pill {{ $row['focus_tone'] }}">
                                                {{ $row['focus_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="erp-metrics-empty-row">No hay clientes con frentes activos para este corte.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Cobertura</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Distribucion de atencion sobre la base</h3>
                        <p class="erp-copy">Cuantos clientes estan hoy atravesados por gestion operativa, reclamos, cobranza, contratos o habilitaciones.</p>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse ($report['clients']['attention_rows'] as $row)
                            <article class="erp-metrics-loss-item">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <strong>{{ $row['label'] }}</strong>
                                        <p>{{ $row['share'] }} de la base total</p>
                                    </div>

                                    <span>{{ $row['count'] }}</span>
                                </div>

                                <div class="erp-metrics-stage-bar">
                                    <span class="erp-metrics-stage-bar-fill tone-{{ $row['tone'] }}" style="width: {{ $row['width'] }}%"></span>
                                </div>
                            </article>
                        @empty
                            <div class="erp-empty-copy">No hay actividad suficiente para graficar cobertura de cartera.</div>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>

        <section class="erp-surface-card min-w-0 p-5">
            <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                <span class="erp-step-label">Comercial</span>
                <h2 class="text-xl font-semibold erp-strong-text">Embudo, equipo y origen de negocio</h2>
                <p class="erp-copy">La misma lectura de metricas comerciales, integrada ahora dentro del tablero ejecutivo del ERP.</p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($report['commercial']['overview_cards'] as $card)
                    <article class="erp-metrics-stage">
                        <p class="erp-metrics-stage-label">{{ $card['label'] }}</p>
                        <div class="erp-metrics-stage-count text-2xl">{{ $card['value'] }}</div>
                        <p class="erp-metrics-stage-share">{{ $card['note'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.7fr),minmax(0,1fr)]">
                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Embudo del periodo</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Como avanza la cartera</h3>
                        <p class="erp-copy">Desde ingreso hasta cierre, con conversion entre etapas y fuga de volumen.</p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($report['commercial']['funnel'] as $stage)
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

                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Perdidas</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Motivos de fuga</h3>
                        <p class="erp-copy">Lectura rapida de donde se cae el cierre para ajustar propuesta, ritmo o manejo de objeciones.</p>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse ($report['commercial']['loss_reasons'] as $reason)
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
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.5fr),minmax(0,1fr)]">
                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Equipo comercial</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Desempeno por responsable</h3>
                        <p class="erp-copy">Volumen, conversion, ingreso y cartera que ya pide seguimiento.</p>
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
                                @forelse ($report['commercial']['team_performance'] as $row)
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
                        <span class="erp-step-label">Canales</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Origen con mejor respuesta</h3>
                        <p class="erp-copy">Que fuente trae mejor calidad de lead, cierres y retorno.</p>
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
                                @forelse ($report['commercial']['source_breakdown'] as $row)
                                    <tr>
                                        <td><strong>{{ $row['channel'] }}</strong></td>
                                        <td>{{ $row['leads'] }}</td>
                                        <td>{{ $row['contact_rate'] }}</td>
                                        <td>{{ $row['won'] }}</td>
                                        <td>{{ $row['conversion'] }}</td>
                                        <td>{{ $row['revenue'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="erp-metrics-empty-row">No hay leads suficientes para evaluar canales en este periodo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </section>

        <section class="erp-surface-card min-w-0 p-5">
            <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                <span class="erp-step-label">Operacion tecnica</span>
                <h2 class="text-xl font-semibold erp-strong-text">Carga operativa, backlog y visitas de campo</h2>
                <p class="erp-copy">Sirve para ver donde esta el cuello de botella tecnico y como se reparte el trabajo real del equipo.</p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($report['operations']['summary_cards'] as $card)
                    <article class="erp-metrics-stat erp-metrics-tone-{{ $card['tone'] }}">
                        <p class="erp-metrics-stat-label">{{ $card['label'] }}</p>
                        <div class="erp-metrics-stat-value">{{ $card['value'] }}</div>
                        <p class="erp-metrics-stat-note">{{ $card['note'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.4fr),minmax(0,1fr)]">
                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Backlog por tipo</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Que trabajo se esta acumulando</h3>
                        <p class="erp-copy">Open, en proceso, cerrados y vencidos por clase de pendiente.</p>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="erp-metrics-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Abiertos</th>
                                    <th>En proceso</th>
                                    <th>Cerrados</th>
                                    <th>Vencidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['operations']['pending_type_rows'] as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row['label'] }}</strong>
                                        </td>
                                        <td>{{ $row['open'] }}</td>
                                        <td>{{ $row['in_progress'] }}</td>
                                        <td>{{ $row['completed'] }}</td>
                                        <td>{{ $row['overdue'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="erp-metrics-empty-row">No hay pendientes suficientes para medir carga por tipo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Tecnicos</span>
                        <h3 class="text-xl font-semibold erp-strong-text">Carga por tecnico</h3>
                        <p class="erp-copy">Distribucion entre asignadas, aceptadas, en sitio y finalizadas.</p>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="erp-metrics-table">
                            <thead>
                                <tr>
                                    <th>Tecnico</th>
                                    <th>Asignadas</th>
                                    <th>Aceptadas</th>
                                    <th>En sitio</th>
                                    <th>Finalizadas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['operations']['technician_rows'] as $row)
                                    <tr>
                                        <td><strong>{{ $row['name'] }}</strong></td>
                                        <td>{{ $row['assigned'] }}</td>
                                        <td>{{ $row['accepted'] }}</td>
                                        <td>{{ $row['in_progress'] }}</td>
                                        <td>{{ $row['completed'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="erp-metrics-empty-row">No hay visitas suficientes para medir carga tecnica.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </section>

        <section class="erp-metrics-layout grid gap-5 xl:grid-cols-[minmax(0,1fr),minmax(0,1fr)]">
            <article class="erp-surface-card min-w-0 p-5">
                <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                    <span class="erp-step-label">Postventa</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Reclamos y atencion de casos</h2>
                    <p class="erp-copy">Seguimiento de reclamos abiertos, atrasos y cierres del periodo.</p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach ($report['post_sale']['summary_cards'] as $card)
                        <article class="erp-metrics-stage">
                            <p class="erp-metrics-stage-label">{{ $card['label'] }}</p>
                            <div class="erp-metrics-stage-count text-2xl">{{ $card['value'] }}</div>
                            <p class="erp-metrics-stage-share">{{ $card['note'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="space-y-4">
                        @forelse ($report['post_sale']['status_rows'] as $row)
                            <article class="erp-metrics-loss-item">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <strong>{{ $row['label'] }}</strong>
                                    </div>

                                    <span>{{ $row['count'] }}</span>
                                </div>

                                <div class="erp-metrics-stage-bar">
                                    <span class="erp-metrics-stage-bar-fill tone-warning" style="width: {{ $row['width'] }}%"></span>
                                </div>
                            </article>
                        @empty
                            <div class="erp-empty-copy">No hay reclamos registrados.</div>
                        @endforelse
                    </div>

                    <div class="space-y-3">
                        @forelse ($report['post_sale']['queue_rows'] as $row)
                            <div class="erp-metrics-alert">
                                <div>
                                    <strong>{{ $row['title'] }}</strong>
                                    <p>{{ $row['client'] }} / {{ $row['status'] }} / {{ $row['technician'] }}</p>
                                </div>

                                <span class="erp-dashboard-pill {{ $row['tone'] }}">
                                    {{ $row['scheduled'] }}
                                </span>
                            </div>
                        @empty
                            <div class="erp-empty-copy">No hay reclamos abiertos en cola.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="erp-surface-card min-w-0 p-5">
                <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                    <span class="erp-step-label">Habilitaciones</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Estado administrativo y seguimiento</h2>
                    <p class="erp-copy">Visibilidad de documentacion trabada, gestiones vencidas y aprobaciones del periodo.</p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($report['habilitations']['summary_cards'] as $card)
                        <article class="erp-metrics-stage">
                            <p class="erp-metrics-stage-label">{{ $card['label'] }}</p>
                            <div class="erp-metrics-stage-count text-2xl">{{ $card['value'] }}</div>
                            <p class="erp-metrics-stage-share">{{ $card['note'] }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="space-y-4">
                        @forelse ($report['habilitations']['status_rows'] as $row)
                            <article class="erp-metrics-loss-item">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <strong>{{ $row['label'] }}</strong>
                                    </div>

                                    <span>{{ $row['count'] }}</span>
                                </div>

                                <div class="erp-metrics-stage-bar">
                                    <span class="erp-metrics-stage-bar-fill tone-info" style="width: {{ $row['width'] }}%"></span>
                                </div>
                            </article>
                        @empty
                            <div class="erp-empty-copy">No hay habilitaciones cargadas.</div>
                        @endforelse
                    </div>

                    <div class="space-y-3">
                        @forelse ($report['habilitations']['queue_rows'] as $row)
                            <div class="erp-metrics-alert">
                                <div>
                                    <strong>{{ $row['client'] }}</strong>
                                    <p>{{ $row['equipment'] }} / {{ $row['status'] }}</p>
                                </div>

                                <span class="erp-dashboard-pill {{ $row['tone'] }}">
                                    {{ $row['next_step'] }}
                                </span>
                            </div>
                        @empty
                            <div class="erp-empty-copy">No hay gestiones administrativas abiertas.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </section>

        <section class="erp-surface-card min-w-0 p-5">
            <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                <span class="erp-step-label">Conservaciones</span>
                <h2 class="text-xl font-semibold erp-strong-text">Continuidad de contratos y renovaciones</h2>
                <p class="erp-copy">Permite detectar contratos que sostienen recurrencia, visitas por ejecutar y renovaciones que no pueden dormirse.</p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($report['conservations']['summary_cards'] as $card)
                    <article class="erp-metrics-stat erp-metrics-tone-{{ $card['tone'] }}">
                        <p class="erp-metrics-stat-label">{{ $card['label'] }}</p>
                        <div class="erp-metrics-stat-value">{{ $card['value'] }}</div>
                        <p class="erp-metrics-stat-note">{{ $card['note'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr),minmax(0,1fr),minmax(0,1fr)]">
                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Frecuencia</span>
                        <h3 class="text-lg font-semibold erp-strong-text">Mix de contratos activos</h3>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse ($report['conservations']['frequency_rows'] as $row)
                            <article class="erp-metrics-loss-item">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <strong>{{ $row['label'] }}</strong>
                                        <p>{{ $row['share'] }} del total activo</p>
                                    </div>

                                    <span>{{ $row['count'] }}</span>
                                </div>

                                <div class="erp-metrics-stage-bar">
                                    <span class="erp-metrics-stage-bar-fill tone-success" style="width: {{ $row['width'] }}%"></span>
                                </div>
                            </article>
                        @empty
                            <div class="erp-empty-copy">No hay contratos activos para medir frecuencia.</div>
                        @endforelse
                    </div>
                </article>

                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Agenda</span>
                        <h3 class="text-lg font-semibold erp-strong-text">Proximos servicios</h3>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($report['conservations']['upcoming_rows'] as $row)
                            <div class="erp-metrics-alert">
                                <div>
                                    <strong>{{ $row['client'] }}</strong>
                                    <p>{{ $row['progress'] }} / {{ $row['frequency'] }}</p>
                                </div>

                                <span class="erp-dashboard-pill {{ $row['tone'] }}">
                                    {{ $row['next_service'] }}
                                </span>
                            </div>
                        @empty
                            <div class="erp-empty-copy">No hay proximos servicios programados.</div>
                        @endforelse
                    </div>
                </article>

                <article class="erp-surface-card min-w-0 p-5">
                    <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                        <span class="erp-step-label">Renovaciones</span>
                        <h3 class="text-lg font-semibold erp-strong-text">Historial del periodo</h3>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($report['conservations']['renewal_rows'] as $row)
                            <div class="erp-metrics-alert">
                                <div>
                                    <strong>{{ $row['client'] }}</strong>
                                    <p>Ciclo {{ $row['cycle'] }} / {{ $row['new_frequency'] }}</p>
                                </div>

                                <span class="erp-dashboard-pill success">
                                    {{ $row['renewed_at'] }}
                                </span>
                            </div>
                        @empty
                            <div class="erp-empty-copy">No hubo renovaciones en el periodo filtrado.</div>
                        @endforelse
                    </div>
                </article>
            </div>
        </section>

        <section class="erp-metrics-layout grid gap-5 xl:grid-cols-[minmax(0,1fr),minmax(0,1fr)]">
            <article class="erp-surface-card min-w-0 p-5">
                <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                    <span class="erp-step-label">Cobranza y caja</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Facturacion, recupero y mora</h2>
                    <p class="erp-copy">Lectura rapida para ver cuanto se factura, cuanto entra y cuanto queda trabado.</p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($report['billing']['summary_cards'] as $card)
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
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Factura</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Edad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['billing']['receivable_rows'] as $row)
                                <tr>
                                    <td><strong>{{ $row['client'] }}</strong></td>
                                    <td>{{ $row['description'] }}</td>
                                    <td>{{ $row['invoice'] }}</td>
                                    <td>{{ $row['invoice_date'] }}</td>
                                    <td>{{ $row['amount'] }}</td>
                                    <td>
                                        <span class="erp-dashboard-pill {{ $row['tone'] }}">
                                            {{ $row['age'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="erp-metrics-empty-row">No hay facturas pendientes de cobro.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="erp-surface-card min-w-0 p-5">
                <div class="flex flex-col gap-2 border-b pb-4" style="border-color: var(--erp-border);">
                    <span class="erp-step-label">Inventario</span>
                    <h2 class="text-xl font-semibold erp-strong-text">Cobertura de stock y faltantes criticos</h2>
                    <p class="erp-copy">Lo minimo que gerencia necesita para no vender, despachar o reparar a ciegas.</p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($report['inventory']['summary_cards'] as $card)
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
                                <th>Variante</th>
                                <th>Codigo</th>
                                <th>Disponible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report['inventory']['critical_rows'] as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong></td>
                                    <td>{{ $row['code'] }}</td>
                                    <td>
                                        <span class="erp-dashboard-pill {{ $row['tone'] }}">
                                            {{ $row['quantity'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="erp-metrics-empty-row">No hay items criticos detectados en inventario.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>

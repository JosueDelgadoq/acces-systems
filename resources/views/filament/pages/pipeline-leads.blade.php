@php
    use App\Filament\Resources\Leads\LeadResource;
    use App\Models\Lead;

    $columns = $this->getColumns();
    $leadsByStage = $this->getLeads();
    $stageTotals = $this->getStageTotals();
    $canChangeStatus = $this->canChangeStatus();

    $pipelineStyles = [
        'gray' => [
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'dot' => 'bg-slate-400',
        ],
        'info' => [
            'badge' => 'bg-sky-100 text-sky-800 ring-sky-200',
            'dot' => 'bg-sky-500',
        ],
        'primary' => [
            'badge' => 'bg-blue-100 text-blue-800 ring-blue-200',
            'dot' => 'bg-blue-500',
        ],
        'warning' => [
            'badge' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'dot' => 'bg-amber-500',
        ],
        'success' => [
            'badge' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'dot' => 'bg-emerald-500',
        ],
        'danger' => [
            'badge' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'dot' => 'bg-rose-500',
        ],
    ];

    $followUpStyles = [
        'urgente' => [
            'badge' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'label' => 'Urgente',
        ],
        'atencion' => [
            'badge' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'label' => 'Atencion',
        ],
        'ok' => [
            'badge' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'label' => 'Al dia',
        ],
    ];

    $stageStyle = function (string $stage) use ($pipelineStyles): array {
        return $pipelineStyles[Lead::getPipelineColor($stage)] ?? $pipelineStyles['gray'];
    };
@endphp

<x-filament-panels::page>
    <div x-data="pipelineBoard()" class="space-y-6">
        <section class="erp-info-banner">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <span class="erp-step-label">Pipeline comercial</span>
                    <h2 class="text-2xl font-semibold erp-strong-text">Visibilidad por etapa y proxima accion.</h2>
                    <p class="max-w-3xl erp-copy">
                        @if ($canChangeStatus)
                            Arrastra un lead entre columnas para moverlo dentro del pipeline sin salir del tablero.
                        @else
                            Vista de lectura del pipeline comercial con foco en ritmo de gestion y proximos compromisos.
                        @endif
                    </p>
                </div>

                <aside class="erp-surface-muted p-4 lg:max-w-md">
                    <p class="erp-muted-label">Carga por etapa</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($columns as $column)
                            @php
                                $style = $stageStyle($column);
                            @endphp

                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs ring-1 {{ $style['badge'] }}">
                                <span class="h-2 w-2 rounded-full {{ $style['dot'] }}"></span>
                                {{ Lead::getPipelineLabel($column) }}
                                <strong>{{ $stageTotals[$column] ?? 0 }}</strong>
                            </span>
                        @endforeach
                    </div>
                </aside>
            </div>
        </section>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($columns as $column)
                @php
                    $style = $stageStyle($column);
                    $stageLeads = $leadsByStage->get($column, collect());
                    $stageCount = $stageTotals[$column] ?? 0;
                @endphp

                <section
                    class="erp-surface-card p-4"
                    @if ($canChangeStatus)
                        @drop="dropLead('{{ $column }}')"
                        @dragover.prevent
                    @endif
                >
                    <div class="flex items-center justify-between gap-3 border-b pb-3" style="border-color: var(--erp-border);">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full {{ $style['dot'] }}"></span>
                                <h3 class="text-sm font-semibold erp-strong-text">{{ Lead::getPipelineLabel($column) }}</h3>
                            </div>

                            <p class="mt-1 text-xs erp-copy">
                                {{ $stageCount }} {{ $stageCount === 1 ? 'lead' : 'leads' }} en esta etapa
                            </p>
                        </div>

                        <span class="rounded-full px-2.5 py-1 text-xs ring-1 {{ $style['badge'] }}">
                            {{ $stageCount }}
                        </span>
                    </div>

                    <div class="mt-4 min-h-[18rem] space-y-3">
                        @forelse ($stageLeads as $lead)
                            @php
                                $followUp = $lead->nextPendingSeguimiento;
                                $health = $followUpStyles[$lead->estado_semaforo] ?? $followUpStyles['ok'];
                            @endphp

                            <article
                                class="erp-timeline-entry p-4 {{ $canChangeStatus ? 'cursor-move' : '' }}"
                                wire:key="lead-card-{{ $lead->id }}"
                                @if ($canChangeStatus)
                                    draggable="true"
                                    @dragstart="dragLead({{ $lead->id }})"
                                    @dragend="clearLead()"
                                @endif
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="text-sm font-semibold erp-strong-text">{{ $lead->cliente }}</h4>
                                        <p class="mt-1 text-xs erp-copy">{{ $lead->telefono ?: 'Sin telefono' }}</p>
                                    </div>

                                    <span class="rounded-full px-2 py-1 text-[11px] ring-1 {{ $health['badge'] }}">
                                        {{ $health['label'] }}
                                    </span>
                                </div>

                                <div class="mt-3 grid gap-2 text-xs erp-copy">
                                    <div>
                                        <span class="font-semibold erp-strong-text">Comercial:</span>
                                        {{ $lead->comercialAsignado?->name ?? 'Sin asignar' }}
                                    </div>

                                    <div>
                                        <span class="font-semibold erp-strong-text">Proxima accion:</span>
                                        {{ $followUp?->proxima_accion ?? 'Sin accion programada' }}
                                    </div>

                                    <div>
                                        <span class="font-semibold erp-strong-text">Compromiso:</span>
                                        {{ $followUp?->fecha_proxima_accion?->format('d/m/Y') ?? 'Sin fecha' }}
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <a
                                        href="{{ LeadResource::getUrl('edit', ['record' => $lead]) }}"
                                        class="text-xs font-medium text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-200"
                                    >
                                        Abrir lead
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="erp-surface-muted erp-empty-copy rounded-xl border border-dashed p-4">
                                Sin leads en esta etapa.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <script>
        function pipelineBoard() {
            return {
                leadId: null,

                dragLead(id) {
                    this.leadId = id
                },

                clearLead() {
                    this.leadId = null
                },

                async dropLead(status) {
                    if (this.leadId === null) {
                        return
                    }

                    await $wire.moveLead(this.leadId, status)
                    this.clearLead()
                },
            }
        }
    </script>
</x-filament-panels::page>

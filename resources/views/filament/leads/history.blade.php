@php
    use App\Models\Lead;

    $history = ($record?->histories ?? collect())
        ->sortByDesc(fn ($item) => optional($item->changed_at ?? $item->created_at)?->timestamp)
        ->values();

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

    $stageStyle = function (?string $stage) use ($pipelineStyles): array {
        if (blank($stage)) {
            return $pipelineStyles['gray'];
        }

        return $pipelineStyles[Lead::getPipelineColor($stage)] ?? $pipelineStyles['gray'];
    };

    $lastEntry = $history->first();
    $currentStageStyle = $stageStyle($record?->estado_pipeline);
@endphp

<div class="space-y-5">
    <section class="erp-surface-card">
        <div class="border-b px-5 py-4" style="border-color: var(--erp-border);">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold erp-strong-text">Historia comercial del lead</h3>
                <span class="text-xs erp-copy">{{ $history->count() }} movimientos</span>
            </div>
        </div>

        <div class="px-5 py-6">
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Pipeline actual</p>
                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs ring-1 {{ $currentStageStyle['badge'] }}">
                        {{ Lead::getPipelineLabel($record?->estado_pipeline) }}
                    </span>
                </div>

                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Comercial responsable</p>
                    <p class="mt-2 text-sm font-medium erp-strong-text">
                        {{ $record?->comercialAsignado?->name ?? 'Sin asignar' }}
                    </p>
                </div>

                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Ultima novedad</p>
                    <p class="mt-2 text-sm erp-strong-text">
                        {{ $lastEntry?->title ?? 'Sin movimientos registrados' }}
                    </p>
                </div>
            </div>

            @if ($history->isEmpty())
                <div class="erp-empty-copy">No hay historial comercial todavia.</div>
            @else
                <div class="relative">
                    <div class="erp-timeline-line absolute left-3 top-0 h-full w-px"></div>

                    <div class="space-y-6">
                        @foreach ($history as $item)
                            @php
                                $fromStyle = $stageStyle($item->status_from);
                                $toStyle = $stageStyle($item->status_to);
                            @endphp

                            <div class="relative flex gap-4">
                                <div class="z-10 mt-2">
                                    <div class="h-3 w-3 rounded-full {{ $toStyle['dot'] }}"></div>
                                </div>

                                <div class="erp-timeline-entry flex-1 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold erp-strong-text">{{ $item->title }}</div>
                                            <div class="mt-1 text-xs erp-copy">
                                                {{ $item->user?->name ?? 'Sistema' }}
                                            </div>
                                        </div>

                                        <span class="text-xs erp-copy">
                                            {{ optional($item->changed_at ?? $item->created_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    @if (filled($item->status_to))
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            @if (filled($item->status_from) && $item->status_from !== $item->status_to)
                                                <span class="rounded-full px-2 py-1 text-xs ring-1 {{ $fromStyle['badge'] }}">
                                                    {{ Lead::getPipelineLabel($item->status_from) }}
                                                </span>
                                                <span class="text-xs erp-copy">&rarr;</span>
                                            @endif

                                            <span class="rounded-full px-2 py-1 text-xs ring-1 {{ $toStyle['badge'] }}">
                                                {{ Lead::getPipelineLabel($item->status_to) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="erp-timeline-note mt-3 p-3 text-sm">
                                        {{ filled($item->description) ? $item->description : 'Sin detalle adicional.' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>

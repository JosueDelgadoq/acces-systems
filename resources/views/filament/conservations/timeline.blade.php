@php
    $visitEntries = $record->visits()
        ->with('technician:id,name')
        ->orderByDesc('date')
        ->orderByDesc('id')
        ->get()
        ->map(fn ($visit) => [
            'type' => 'visit',
            'sort_date' => $visit->date,
            'payload' => $visit,
        ]);

    $renewalEntries = $record->renewals()
        ->with('renewedBy:id,name')
        ->orderByDesc('renewed_at')
        ->orderByDesc('id')
        ->get()
        ->map(fn ($renewal) => [
            'type' => 'renewal',
            'sort_date' => $renewal->renewed_at,
            'payload' => $renewal,
        ]);

    $entries = $visitEntries
        ->concat($renewalEntries)
        ->sortByDesc('sort_date')
        ->values();
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold erp-strong-text">
            Historial contractual
        </h3>

        <span class="text-xs erp-copy">
            {{ $entries->count() }} evento(s)
        </span>
    </div>

    @if ($entries->isEmpty())
        <div class="erp-surface-muted erp-empty-copy rounded-xl border border-dashed p-4">
            Todavia no hay mantenimientos ni renovaciones registrados.
        </div>
    @else
        <div class="relative space-y-6 border-l-2 pl-6" style="border-color: var(--erp-border);">
            @foreach ($entries as $entry)
                @php
                    $isVisit = $entry['type'] === 'visit';
                    $item = $entry['payload'];
                    $dotClass = $isVisit ? 'bg-green-500' : 'bg-sky-500';
                @endphp

                <div class="relative">
                    <span class="absolute -left-[9px] top-1.5 h-4 w-4 rounded-full {{ $dotClass }}"></span>

                    <div class="erp-timeline-entry p-4">
                        @if ($isVisit)
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-medium erp-strong-text">
                                    {{ $item->date?->format('d/m/Y') }}
                                </div>

                                <span class="erp-surface-muted px-2 py-1 text-xs">
                                    Servicio
                                    {{ str_pad((string) ($item->service_number ?? 0), 2, '0', STR_PAD_LEFT) }}/{{ str_pad((string) ($item->contract_total_services ?? $record->total_services ?? 0), 2, '0', STR_PAD_LEFT) }}
                                    · Ciclo {{ str_pad((string) ($item->contract_cycle_number ?? 1), 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>

                            <div class="mt-1 text-xs erp-copy">
                                Tecnico: {{ $item->technician->name ?? 'Sin asignar' }}
                            </div>

                            @if ($item->notes)
                                <div class="erp-timeline-note mt-3 p-3 text-sm">
                                    {{ $item->notes }}
                                </div>
                            @endif

                            <div class="mt-2 text-xs erp-copy">
                                Remito: {{ $item->remito ?: 'Sin remito' }}
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-medium erp-strong-text">
                                    {{ $item->renewed_at?->format('d/m/Y') }}
                                </div>

                                <span class="erp-surface-muted px-2 py-1 text-xs">
                                    Renovación · Ciclo {{ str_pad((string) $item->new_cycle_number, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>

                            <div class="mt-1 text-xs erp-copy">
                                Renovó: {{ $item->renewedBy->name ?? 'Sin usuario registrado' }}
                            </div>

                            <div class="mt-3 text-sm erp-copy">
                                Vigencia nueva: {{ $item->new_start_date?->format('d/m/Y') }} al {{ $item->new_expiration_date?->format('d/m/Y') }}
                            </div>

                            <div class="mt-1 text-sm erp-copy">
                                Frecuencia: {{ ucfirst($item->new_frequency) }} · Servicios del ciclo: {{ $item->new_total_services }}
                            </div>

                            <div class="mt-2 text-xs erp-copy">
                                Ciclo anterior {{ str_pad((string) $item->previous_cycle_number, 2, '0', STR_PAD_LEFT) }}
                                · {{ $item->previous_completed_services }}/{{ $item->previous_total_services }}
                                completados
                            </div>

                            @if ($item->notes)
                                <div class="erp-timeline-note mt-3 p-3 text-sm">
                                    {{ $item->notes }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

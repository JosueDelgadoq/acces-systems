@php
    use App\Models\Pendiente;
    use Illuminate\Support\Str;

    $currentHistory = ($record?->histories ?? collect())
        ->sortBy(fn ($item) => optional($item->changed_at ?? $item->created_at)?->timestamp)
        ->values();

    $stateColors = [
        Pendiente::STATUS_PENDING => [
            'badge' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'dot' => 'bg-amber-500',
        ],
        Pendiente::STATUS_IN_PROGRESS => [
            'badge' => 'bg-sky-100 text-sky-800 ring-sky-200',
            'dot' => 'bg-sky-500',
        ],
        Pendiente::STATUS_COMPLETED => [
            'badge' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'dot' => 'bg-emerald-500',
        ],
        Pendiente::STATUS_CANCELLED => [
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'dot' => 'bg-slate-400',
        ],
    ];

    $statusStyle = function (?string $status) use ($stateColors): array {
        return $stateColors[$status] ?? [
            'badge' => 'bg-gray-100 text-gray-700 ring-gray-200',
            'dot' => 'bg-gray-400',
        ];
    };

    $lastMove = $currentHistory->last();
@endphp

<div class="space-y-5">
    <section class="erp-surface-card">
        <div class="border-b px-5 py-4" style="border-color: var(--erp-border);">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold erp-strong-text">Historia del pendiente</h3>
                <span class="text-xs erp-copy">{{ $currentHistory->count() }} movimientos</span>
            </div>
        </div>

        <div class="px-5 py-6">
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Estado actual</p>
                    @php
                        $currentStyle = $statusStyle($record?->status);
                    @endphp
                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs {{ $currentStyle['badge'] }}">
                        {{ Pendiente::getStatusLabel($record?->status) }}
                    </span>
                </div>

                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Ultimo movimiento</p>
                    <p class="mt-2 text-sm font-medium erp-strong-text">
                        {{ $lastMove ? Pendiente::getStatusLabel($lastMove->status_to) : 'Sin movimientos' }}
                    </p>
                </div>

                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Ultima novedad</p>
                    <p class="mt-2 text-sm erp-strong-text">
                        {{ filled($record?->notes) ? Str::limit($record->notes, 90) : 'Sin novedad' }}
                    </p>
                </div>
            </div>

            @if ($currentHistory->isEmpty())
                <div class="erp-empty-copy">No hay historial.</div>
            @else
                <div class="relative">
                    <div class="erp-timeline-line absolute left-3 top-0 h-full w-px"></div>

                    <div class="space-y-6">
                        @foreach ($currentHistory as $item)
                            @php
                                $toStyle = $statusStyle($item->status_to);
                                $fromLabel = filled($item->status_from) ? Pendiente::getStatusLabel($item->status_from) : 'Creado';
                                $toLabel = Pendiente::getStatusLabel($item->status_to);
                                $isCreatedEntry = blank($item->status_from);
                                $isSameStatusEntry = filled($item->status_from) && $item->status_from === $item->status_to;
                            @endphp

                            <div class="relative flex gap-4">
                                <div class="z-10 mt-2">
                                    <div class="h-3 w-3 rounded-full {{ $toStyle['dot'] }}"></div>
                                </div>

                                <div class="erp-timeline-entry flex-1 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full px-2 py-1 text-xs {{ $toStyle['badge'] }}">
                                                {{ $toLabel }}
                                            </span>

                                            @if ($isCreatedEntry)
                                                <span class="text-sm font-semibold erp-strong-text">Creado en {{ $toLabel }}</span>
                                            @elseif ($isSameStatusEntry)
                                                <span class="text-sm font-semibold erp-strong-text">Actualizacion en {{ $toLabel }}</span>
                                            @else
                                                <span class="text-sm erp-copy">{{ $fromLabel }}</span>
                                                <span class="erp-copy">&rarr;</span>
                                                <span class="text-sm font-semibold erp-strong-text">{{ $toLabel }}</span>
                                            @endif
                                        </div>

                                        <span class="text-xs erp-copy">
                                            {{ optional($item->changed_at ?? $item->created_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <div class="mt-1 text-xs erp-copy">
                                        {{ $item->user?->name ?? 'Sistema' }}
                                    </div>

                                    <div class="erp-timeline-note mt-3 p-3 text-sm">
                                        {{ filled($item->observation) ? $item->observation : 'Sin detalle' }}
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

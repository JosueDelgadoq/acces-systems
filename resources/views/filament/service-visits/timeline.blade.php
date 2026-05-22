@php
    use App\Models\ServiceVisitEvent;

    $timeline = $record
        ? $record->events()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
        : collect();

    $eventColors = [
        ServiceVisitEvent::TYPE_ACCEPTED => [
            'badge' => 'bg-sky-100 text-sky-800 ring-sky-200',
            'dot' => 'bg-sky-500',
        ],
        ServiceVisitEvent::TYPE_TRAVELING => [
            'badge' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'dot' => 'bg-amber-500',
        ],
        ServiceVisitEvent::TYPE_STARTED => [
            'badge' => 'bg-violet-100 text-violet-800 ring-violet-200',
            'dot' => 'bg-violet-500',
        ],
        ServiceVisitEvent::TYPE_FINISHED => [
            'badge' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'dot' => 'bg-emerald-500',
        ],
    ];

    $eventStyle = function (?string $eventType) use ($eventColors): array {
        return $eventColors[$eventType] ?? [
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'dot' => 'bg-slate-400',
        ];
    };

    $lastEvent = $timeline->last();
@endphp

<div class="space-y-5">
    <section class="erp-surface-card">
        <div class="border-b px-5 py-4" style="border-color: var(--erp-border);">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold erp-strong-text">Timeline de visita</h3>
                <span class="text-xs erp-copy">{{ $timeline->count() }} eventos</span>
            </div>
        </div>

        <div class="px-5 py-6">
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Estado actual</p>
                    <p class="mt-2 text-sm font-semibold erp-strong-text">
                        {{ $record ? \App\Models\ServiceVisit::getStatusLabel($record->status) : 'Sin visita' }}
                    </p>
                </div>

                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Ultimo evento</p>
                    <p class="mt-2 text-sm font-semibold erp-strong-text">
                        {{ $lastEvent ? ServiceVisitEvent::getTypeLabel($lastEvent->event_type) : 'Sin eventos' }}
                    </p>
                </div>

                <div class="erp-surface-muted p-4">
                    <p class="erp-muted-label">Ultima marca</p>
                    <p class="mt-2 text-sm erp-strong-text">
                        {{ optional($lastEvent?->created_at)->format('d/m/Y H:i') ?? 'Sin registro' }}
                    </p>
                </div>
            </div>

            @if ($timeline->isEmpty())
                <div class="erp-empty-copy">Todavia no hay eventos registrados para esta visita.</div>
            @else
                <div class="relative">
                    <div class="erp-timeline-line absolute left-3 top-0 h-full w-px"></div>

                    <div class="space-y-6">
                        @foreach ($timeline as $event)
                            @php
                                $style = $eventStyle($event->event_type);
                            @endphp

                            <div class="relative flex gap-4">
                                <div class="z-10 mt-2">
                                    <div class="h-3 w-3 rounded-full {{ $style['dot'] }}"></div>
                                </div>

                                <div class="erp-timeline-entry flex-1 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full px-2 py-1 text-xs {{ $style['badge'] }}">
                                                {{ ServiceVisitEvent::getTypeLabel($event->event_type) }}
                                            </span>

                                            <span class="text-sm font-semibold erp-strong-text">
                                                {{ $event->description ?: 'Evento operativo registrado.' }}
                                            </span>
                                        </div>

                                        <span class="text-xs erp-copy">
                                            {{ optional($event->created_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <div class="mt-1 text-xs erp-copy">
                                        {{ $event->user?->name ?? 'Sistema' }}
                                    </div>

                                    @if (filled($event->lat) && filled($event->lng))
                                        <div class="erp-timeline-note mt-3 p-3 text-sm">
                                            Coordenadas: {{ number_format((float) $event->lat, 7, '.', '') }}, {{ number_format((float) $event->lng, 7, '.', '') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>

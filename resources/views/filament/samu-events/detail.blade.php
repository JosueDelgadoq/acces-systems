@php
    $payload = $record?->payload ?? [];
    $objections = collect($record?->objections ?? [])->filter()->values();
    $tasks = collect($record?->tasks_detected ?? [])->filter(fn ($task) => is_array($task))->values();
    $record?->loadMissing('notes.user');
    $notes = collect($record?->notes ?? []);
@endphp

<div class="space-y-5">
    <section class="erp-surface-card">
        <div class="border-b px-5 py-4" style="border-color: var(--erp-border);">
            <h3 class="text-sm font-semibold erp-strong-text">Notas internas de revision</h3>
        </div>

        <div class="px-5 py-5">
            @if ($notes->isEmpty())
                <p class="text-sm erp-copy">Todavia no hay notas internas auditadas para este evento.</p>
            @else
                <div class="space-y-3">
                    @foreach ($notes as $note)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <div class="flex flex-wrap items-center gap-3 text-xs erp-copy">
                                <span class="font-semibold erp-strong-text">{{ $note->user?->name ?? 'Sistema' }}</span>
                                <span>{{ optional($note->created_at)->format('d/m/Y H:i') }}</span>
                                @if ($note->is_system)
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-700 ring-1 ring-slate-200">Sistema</span>
                                @endif
                            </div>
                            <p class="mt-3 whitespace-pre-line text-sm erp-copy">{{ $note->note }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="erp-surface-card">
        <div class="border-b px-5 py-4" style="border-color: var(--erp-border);">
            <h3 class="text-sm font-semibold erp-strong-text">Analisis detectado por Samu.ai</h3>
        </div>

        <div class="grid gap-4 px-5 py-5 md:grid-cols-2">
            <div class="erp-surface-muted p-4">
                <p class="erp-muted-label">Objeciones</p>
                @if ($objections->isEmpty())
                    <p class="mt-2 text-sm erp-copy">Sin objeciones detectadas.</p>
                @else
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($objections as $objection)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 ring-1 ring-amber-200">
                                {{ $objection }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="erp-surface-muted p-4">
                <p class="erp-muted-label">Tareas detectadas</p>
                @if ($tasks->isEmpty())
                    <p class="mt-2 text-sm erp-copy">Sin tareas estructuradas en el payload.</p>
                @else
                    <div class="mt-3 space-y-3">
                        @foreach ($tasks as $task)
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-sm font-semibold erp-strong-text">{{ $task['title'] ?? 'Tarea sin titulo' }}</p>
                                <div class="mt-2 flex flex-wrap gap-3 text-xs erp-copy">
                                    <span>Vencimiento: {{ $task['due_date'] ?? 'Sin fecha' }}</span>
                                    <span>Prioridad: {{ $task['priority'] ?? 'Sin definir' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="erp-surface-card">
        <div class="border-b px-5 py-4" style="border-color: var(--erp-border);">
            <h3 class="text-sm font-semibold erp-strong-text">Payload crudo para auditoria</h3>
        </div>

        <div class="px-5 py-5">
            <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </section>
</div>

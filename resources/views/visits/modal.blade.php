@php
    /** @var \App\Models\ServiceVisit|null $visit */
@endphp

<div class="space-y-6">
    @if (! $visit)
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            No hay una visita registrada para este pendiente todavia.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Llegada</h3>
                    <span class="text-xs text-slate-500">
                        {{ optional($visit->arrival_time)->format('d/m/Y H:i') ?: 'Sin horario' }}
                    </span>
                </div>

                @if ($visit->arrival_photo)
                    <img
                        src="{{ asset('storage/' . $visit->arrival_photo) }}"
                        alt="Foto de llegada"
                        class="h-64 w-full rounded-lg object-cover"
                    >
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-sm text-slate-500">
                        Sin foto de llegada.
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Salida</h3>
                    <span class="text-xs text-slate-500">
                        {{ optional($visit->departure_time)->format('d/m/Y H:i') ?: 'Sin horario' }}
                    </span>
                </div>

                @if ($visit->departure_photo)
                    <img
                        src="{{ asset('storage/' . $visit->departure_photo) }}"
                        alt="Foto de salida"
                        class="h-64 w-full rounded-lg object-cover"
                    >
                @else
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-12 text-center text-sm text-slate-500">
                        Sin foto de salida.
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Informe tecnico</h3>
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                    {{ \App\Models\ServiceVisit::getStatusLabel($visit->status) }}
                </span>
            </div>

            <p class="whitespace-pre-line text-sm text-slate-700">
                {{ $visit->report ?: 'Todavia no se registro un informe para esta visita.' }}
            </p>
        </div>
    @endif
</div>

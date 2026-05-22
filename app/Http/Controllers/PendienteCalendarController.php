<?php

namespace App\Http\Controllers;

use App\Models\Pendiente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendienteCalendarController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pendiente.view'), 403);

        $query = Pendiente::query()
            ->with(['client:id,name', 'user:id,name'])
            ->whereNotNull('due_date');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('start')) {
            $query->whereDate('due_date', '>=', $request->string('start')->before('T')->toString());
        }

        if ($request->filled('end')) {
            $query->whereDate('due_date', '<=', $request->string('end')->before('T')->toString());
        }

        return response()->json(
            $query
                ->orderBy('due_date')
                ->get()
                ->map(function (Pendiente $pendiente): array {
                    $client = $pendiente->client?->name ?? 'Sin cliente';
                    $technician = $pendiente->user?->name ?? 'Sin asignar';
                    $type = Pendiente::getTypeOptions()[$pendiente->type ?? ''] ?? 'Tarea';
                    $description = filled($pendiente->description)
                        ? str($pendiente->description)->squish()->limit(42)->toString()
                        : $type;

                    return [
                        'id' => $pendiente->id,
                        'title' => '#' . $pendiente->id . ' - ' . $description,
                        'start' => optional($pendiente->due_date)->format('Y-m-d'),
                        'allDay' => true,
                        'color' => match ($pendiente->priority) {
                            'alta' => '#ef4444',
                            'media' => '#f59e0b',
                            'baja' => '#22c55e',
                            default => '#64748b',
                        },
                        'extendedProps' => [
                            'client' => $client,
                            'technician' => $technician,
                            'type' => $type,
                            'status' => Pendiente::getStatusLabel($pendiente->status),
                            'description' => $pendiente->description,
                        ],
                    ];
                })
                ->values(),
        );
    }

    public function updateDate(Request $request): JsonResponse
    {
        abort_unless($this->canUpdateSchedule($request), 403);

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:pendientes,id'],
            'date' => ['required', 'date'],
        ]);

        $pendiente = Pendiente::findOrFail($validated['id']);
        $pendiente->due_date = $validated['date'];
        $pendiente->save();

        return response()->json([
            'success' => true,
            'id' => $pendiente->id,
            'due_date' => optional($pendiente->due_date)->format('Y-m-d'),
        ]);
    }

    protected function canUpdateSchedule(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('pendiente.update') || $user->can('pendiente.assign');
    }
}

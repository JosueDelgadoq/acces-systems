<?php

namespace App\Http\Controllers;

use App\Models\ServiceVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceVisitMapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('tracking.view'), 403);

        return response()->json(
            ServiceVisit::query()
                ->with([
                    'claim.client',
                    'pendiente.client',
                    'technician',
                ])
                ->whereDate('created_at', now()->toDateString())
                ->get()
                ->map(function (ServiceVisit $visit): array {
                    return [
                        'id' => $visit->id,
                        'status' => $visit->status,
                        'status_label' => ServiceVisit::getStatusLabel($visit->status),
                        'visit_type' => $visit->visit_type,
                        'visit_type_label' => ServiceVisit::getTypeLabel($visit->visit_type),
                        'lat' => $visit->lat_arrival ?? $visit->lat_departure,
                        'lng' => $visit->lng_arrival ?? $visit->lng_departure,
                        'client' => $visit->claim?->client?->name
                            ?? $visit->pendiente?->client?->name
                            ?? 'Sin cliente',
                        'technician' => $visit->technician?->name ?? 'Sin tecnico',
                        'pendiente_id' => $visit->pendiente_id,
                        'service_order_number' => $visit->pendiente?->service_order_number,
                    ];
                })
                ->values(),
        );
    }
}

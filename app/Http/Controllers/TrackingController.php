<?php

namespace App\Http\Controllers;

use App\Models\ServiceVisit;
use App\Models\User;
use App\Services\ServiceVisitEventService;
use App\Services\ServiceVisitWorkflowService;
use App\Services\TechnicianLiveMapService;
use App\Services\TechnicianTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(
        protected TechnicianTrackingService $trackingService,
        protected TechnicianLiveMapService $liveMapService,
        protected ServiceVisitWorkflowService $visitWorkflow,
        protected ServiceVisitEventService $visitEventService,
    ) {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
    }

    public function mobile()
    {
        abort_unless(
            auth()->user()?->can('tracking.mobile') || auth()->user()?->hasRole('admin'),
            403
        );

        return view('tracking.mobile');
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:120'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'is_moving' => ['nullable', 'boolean'],
            'tracked_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $location = $this->trackingService->ingest($user, $data);

        return response()->json([
            'success' => true,
            'tracked_at' => optional($location->tracked_at)->toIso8601String(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:available,traveling,working,paused,offline'],
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $user->update([
            'technician_status' => $data['status'],
            'last_seen_at' => now(),
        ]);

        if ($data['status'] === 'traveling') {
            $this->visitEventService->recordTravelingForTechnician($user->fresh());
        }

        return response()->json([
            'success' => true,
            'status' => $user->technician_status,
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        $viewer = $request->user() ?? auth()->user();
        $canViewOperationalTracking = (bool) ($viewer?->can('tracking.view') || $viewer?->hasRole('admin'));

        if (! $canViewOperationalTracking) {
            $technicians = User::query()
                ->whereNotNull('last_lat')
                ->whereNotNull('last_lng')
                ->where('tracking_enabled', true)
                ->get([
                    'id',
                    'name',
                    'last_lat',
                    'last_lng',
                    'last_seen_at',
                    'technician_status',
                ])
                ->map(function (User $user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'last_lat' => (float) $user->last_lat,
                        'last_lng' => (float) $user->last_lng,
                        'last_seen_at' => optional($user->last_seen_at)->format('d/m/Y H:i:s'),
                        'technician_status' => $this->resolveLiveStatus($user),
                    ];
                })
                ->values();

            return response()->json($technicians);
        }

        $technicians = collect($this->liveMapService->livePayload($viewer))
            ->map(function (array $item): array {
                $currentVisit = $item['current_visit'] ?? null;
                $currentPendiente = $currentVisit['pendiente'] ?? null;
                $currentClient = $currentPendiente['client'] ?? null;

                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'last_lat' => $item['last_lat'],
                    'last_lng' => $item['last_lng'],
                    'last_seen_at' => filled($item['last_seen_at'])
                        ? \Illuminate\Support\Carbon::parse($item['last_seen_at'])->format('d/m/Y H:i:s')
                        : null,
                    'last_seen_at_iso' => $item['last_seen_at'],
                    'last_seen_human' => $item['last_seen_human'],
                    'technician_status' => $item['status'],
                    'status' => $item['status'],
                    'status_label' => $item['status_label'],
                    'status_color' => $item['status_color'],
                    'tracking_enabled' => $item['tracking_enabled'],
                    'accuracy' => $item['accuracy'],
                    'speed' => $item['speed'],
                    'battery_level' => $item['battery_level'],
                    'is_moving' => $item['is_moving'],
                    'is_online' => ($item['status'] ?? null) !== 'offline',
                    'current_visit' => $currentVisit,
                    'current_visit_id' => $currentVisit['id'] ?? null,
                    'current_visit_status' => $currentVisit['status'] ?? null,
                    'current_visit_status_label' => $currentVisit['status_label'] ?? null,
                    'current_pendiente_id' => $currentPendiente['id'] ?? null,
                    'current_pendiente_type' => $currentPendiente['type'] ?? null,
                    'current_pendiente_type_label' => $currentPendiente['type_label'] ?? null,
                    'current_client_name' => $currentClient['name'] ?? null,
                    'current_client_address' => $currentPendiente['service_address']
                        ?? $currentClient['full_address']
                        ?? null,
                    'current_operational_zone' => $currentPendiente['operational_zone'] ?? null,
                    'current_operational_zone_label' => $currentPendiente['operational_zone_label'] ?? null,
                ];
            })
            ->values();

        return response()->json($technicians);
    }

    public function history(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('tracking.view'), 403);

        return response()->json(
            $this->liveMapService->historyPayload(
                $user,
                $request->string('date')->toString() ?: null
            )
        );
    }

    public function myVisits(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        return response()->json(
            $this->visitWorkflow->mobilePayloadForTechnician($user)
        );
    }

    public function acceptVisit(Request $request, ServiceVisit $visit): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $visit = $this->visitWorkflow->accept($visit, $user);

        return response()->json([
            'success' => true,
            'message' => 'Orden aceptada.',
            'visit_id' => $visit->id,
            'status' => $visit->status,
            'visit' => $this->visitWorkflow->serializeForMobile($visit),
        ]);
    }

    public function startVisit(Request $request, ServiceVisit $visit): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $visit = $this->visitWorkflow->start($visit, $user, $data);

        return response()->json([
            'success' => true,
            'message' => 'Servicio iniciado.',
            'visit_id' => $visit->id,
            'status' => $visit->status,
            'visit' => $this->visitWorkflow->serializeForMobile($visit),
        ]);
    }

    public function finishVisit(Request $request, ServiceVisit $visit): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $data = $request->validate([
            'report' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $visit = $this->visitWorkflow->finish($visit, $user, $data);

        return response()->json([
            'success' => true,
            'message' => 'Servicio finalizado.',
            'visit_id' => $visit->id,
            'status' => $visit->status,
            'visit' => $this->visitWorkflow->serializeForMobile($visit),
        ]);
    }

    public function visitEvents(Request $request, ServiceVisit $visit): JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        abort_unless($user && $this->canViewVisitEvents($user, $visit), 403);

        return response()->json([
            'success' => true,
            'visit_id' => $visit->id,
            'pendiente_id' => $visit->pendiente_id,
            'events' => $this->visitEventService->timelinePayload($visit),
        ]);
    }

    private function resolveLiveStatus(User $user): string
    {
        if (! $user->last_seen_at) {
            return 'offline';
        }

        if ($user->last_seen_at->lt(now()->subMinutes(3))) {
            return 'offline';
        }

        return $user->technician_status ?: 'available';
    }

    private function canViewVisitEvents(User $user, ServiceVisit $visit): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ((int) $visit->technician_id === (int) $user->id) {
            return true;
        }

        return $user->can('technical_board.view') || $user->can('tracking.view');
    }
}

<?php

namespace App\Services;

use App\Models\Pendiente;
use App\Models\ServiceVisit;
use App\Models\ServiceVisitEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceVisitWorkflowService
{
    public function __construct(
        protected TechnicianTrackingService $trackingService,
        protected ServiceVisitEventService $eventService,
        protected ProductoUnidadAssignmentService $unitAssignments,
    ) {
    }

    /**
     * @return Collection<int, ServiceVisit>
     */
    public function activeVisitsForTechnician(User $user): Collection
    {
        return ServiceVisit::query()
            ->with([
                'pendiente.client',
                'technician',
            ])
            ->assignedToTechnician($user)
            ->active()
            ->get()
            ->sortBy(function (ServiceVisit $visit): string {
                $statusOrder = match ($visit->status) {
                    ServiceVisit::STATUS_IN_PROGRESS => 0,
                    ServiceVisit::STATUS_ACCEPTED => 1,
                    ServiceVisit::STATUS_PENDING => 2,
                    default => 3,
                };

                $priorityOrder = match ($visit->pendiente?->priority) {
                    'alta' => 0,
                    'media' => 1,
                    'baja' => 2,
                    default => 3,
                };

                $dueTimestamp = $visit->pendiente?->due_date?->getTimestamp() ?? PHP_INT_MAX;

                return sprintf(
                    '%d-%d-%010d-%010d',
                    $statusOrder,
                    $priorityOrder,
                    $dueTimestamp,
                    $visit->id,
                );
            })
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mobilePayloadForTechnician(User $user): array
    {
        return $this->activeVisitsForTechnician($user)
            ->map(fn (ServiceVisit $visit): array => $this->serializeForMobile($visit))
            ->values()
            ->all();
    }

    public function accept(ServiceVisit $visit, User $user): ServiceVisit
    {
        return DB::transaction(function () use ($visit, $user): ServiceVisit {
            $visit = $this->lockVisit($visit);

            $this->assertCanOperate($visit, $user);
            $this->assertNotCompleted($visit);

            if ($visit->status !== ServiceVisit::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'visit' => 'Esta visita no esta pendiente de aceptacion.',
                ]);
            }

            $visit->update([
                'status' => ServiceVisit::STATUS_ACCEPTED,
            ]);

            /*
             * Importante:
             * Usamos withoutEvents para que PendienteObserver no vuelva a sincronizar
             * la visita y no pise el estado accepted nuevamente a pending.
             */
            if ($visit->pendiente) {
                Pendiente::withoutEvents(function () use ($visit, $user): void {
                    $visit->pendiente->update([
                        'technician_acknowledged' => true,
                        'technician_signature_name' => $user->name,
                        'technician_signature_at' => now(),
                        'workflow_stage' => Pendiente::STAGE_ACCEPTED,
                    ]);
                });
            }

            $user->update([
                'technician_status' => 'available',
                'last_seen_at' => now(),
            ]);

            $this->eventService->record(
                $visit,
                $user,
                ServiceVisitEvent::TYPE_ACCEPTED,
                'Visita aceptada por el tecnico.',
                [
                    'status' => ServiceVisit::STATUS_ACCEPTED,
                    'workflow_stage' => $visit->pendiente?->workflow_stage,
                ],
                $user->last_lat,
                $user->last_lng,
            );

            $this->unitAssignments->markAssignedForVisit(
                $visit,
                $user,
                'Unidad confirmada al aceptar la visita.',
            );

            return $visit->fresh(['pendiente.client', 'technician']);
        }, 5);
    }

    public function start(ServiceVisit $visit, User $user, array $payload = []): ServiceVisit
    {
        return DB::transaction(function () use ($visit, $user, $payload): ServiceVisit {
            $visit = $this->lockVisit($visit);

            $this->assertCanOperate($visit, $user);
            $this->assertNotCompleted($visit);

            if (! in_array($visit->status, [
                ServiceVisit::STATUS_PENDING,
                ServiceVisit::STATUS_ACCEPTED,
                ServiceVisit::STATUS_IN_PROGRESS,
            ], true)) {
                throw ValidationException::withMessages([
                    'visit' => 'Esta visita no puede ser iniciada.',
                ]);
            }

            $visit->status = ServiceVisit::STATUS_IN_PROGRESS;
            $visit->arrival_time ??= now();
            $visit->lat_arrival = $payload['lat'] ?? $user->last_lat ?? $visit->lat_arrival;
            $visit->lng_arrival = $payload['lng'] ?? $user->last_lng ?? $visit->lng_arrival;

            if (filled($payload['arrival_photo'] ?? null)) {
                $visit->arrival_photo = $payload['arrival_photo'];
            }

            $visit->save();

            if ($visit->pendiente) {
                $visit->pendiente->update([
                    'status' => Pendiente::STATUS_IN_PROGRESS,
                    'performed_at' => $visit->pendiente->performed_at ?? now(),
                    'workflow_stage' => Pendiente::STAGE_ACCEPTED,
                ]);
            }

            $resolvedLat = isset($payload['lat']) ? (float) $payload['lat'] : $user->last_lat;
            $resolvedLng = isset($payload['lng']) ? (float) $payload['lng'] : $user->last_lng;

            $this->eventService->record(
                $visit,
                $user,
                ServiceVisitEvent::TYPE_STARTED,
                'Visita iniciada en sitio.',
                [
                    'status' => ServiceVisit::STATUS_IN_PROGRESS,
                    'arrival_photo_uploaded' => filled($visit->arrival_photo),
                ],
                $resolvedLat,
                $resolvedLng,
            );

            $this->unitAssignments->markAssignedForVisit(
                $visit,
                $user,
                'Unidad confirmada al iniciar la visita.',
            );

            $visit = $visit->fresh(['technician', 'pendiente.client']);

            $this->trackingService->syncFromVisitStart(
                $visit,
                $resolvedLat,
                $resolvedLng,
            );

            return $visit->fresh(['pendiente.client', 'technician']);
        }, 5);
    }

    public function finish(ServiceVisit $visit, User $user, array $payload = []): ServiceVisit
    {
        return DB::transaction(function () use ($visit, $user, $payload): ServiceVisit {
            $visit = $this->lockVisit($visit);

            $this->assertCanOperate($visit, $user);
            $this->assertNotCompleted($visit);

            if ($visit->status !== ServiceVisit::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'visit' => 'La visita debe estar en proceso para poder finalizarla.',
                ]);
            }

            $visit->status = ServiceVisit::STATUS_COMPLETED;
            $visit->departure_time = $payload['departure_time'] ?? now();
            $visit->lat_departure = $payload['lat'] ?? $user->last_lat ?? $visit->lat_departure;
            $visit->lng_departure = $payload['lng'] ?? $user->last_lng ?? $visit->lng_departure;

            if (array_key_exists('report', $payload) && filled($payload['report'])) {
                $visit->report = $payload['report'];
            }

            if (filled($payload['departure_photo'] ?? null)) {
                $visit->departure_photo = $payload['departure_photo'];
            }

            $visit->save();

            $this->unitAssignments->resolveForVisit(
                $visit,
                $user,
                'Resolucion automatica de unidad al finalizar la visita.',
            );

            if ($visit->pendiente) {
                $visit->pendiente->update([
                    'status' => Pendiente::STATUS_COMPLETED,
                    'completed_at' => $visit->pendiente->completed_at ?? now(),
                    'control_summary' => $visit->report ?? $visit->pendiente->control_summary,
                ]);
            }

            $resolvedLat = isset($payload['lat']) ? (float) $payload['lat'] : $user->last_lat;
            $resolvedLng = isset($payload['lng']) ? (float) $payload['lng'] : $user->last_lng;

            $this->eventService->record(
                $visit,
                $user,
                ServiceVisitEvent::TYPE_FINISHED,
                'Visita finalizada por el tecnico.',
                [
                    'status' => ServiceVisit::STATUS_COMPLETED,
                    'report_present' => filled($visit->report),
                    'departure_photo_uploaded' => filled($visit->departure_photo),
                ],
                $resolvedLat,
                $resolvedLng,
            );

            $visit = $visit->fresh(['technician', 'pendiente.client']);

            $this->trackingService->syncFromVisitFinish(
                $visit,
                $resolvedLat,
                $resolvedLng,
            );

            return $visit->fresh(['pendiente.client', 'technician']);
        }, 5);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeForMobile(ServiceVisit $visit): array
    {
        $pendiente = $visit->pendiente;

        return [
            'id' => $visit->id,
            'status' => $visit->status,
            'status_label' => ServiceVisit::getStatusLabel($visit->status),
            'visit_type' => $visit->visit_type,
            'visit_type_label' => ServiceVisit::getTypeLabel($visit->visit_type),

            'arrival_time' => optional($visit->arrival_time)->toIso8601String(),
            'departure_time' => optional($visit->departure_time)->toIso8601String(),

            'arrival_photo_url' => $visit->arrival_photo
                ? asset('storage/' . $visit->arrival_photo)
                : null,

            'departure_photo_url' => $visit->departure_photo
                ? asset('storage/' . $visit->departure_photo)
                : null,

            'report' => $visit->report,

            'pendiente_id' => $visit->pendiente_id,
            'pendiente_type' => $pendiente?->type,

            'pendiente_type_label' => $pendiente
                ? (Pendiente::getTypeOptions()[$pendiente->type] ?? (string) $pendiente->type)
                : null,

            'pendiente_status' => $pendiente?->status,

            'pendiente_status_label' => $pendiente
                ? Pendiente::getStatusLabel($pendiente->status)
                : null,

            'workflow_stage' => $pendiente?->workflow_stage,

            'workflow_stage_label' => $pendiente
                ? Pendiente::getWorkflowStageLabel($pendiente->workflow_stage)
                : null,

            'service_order_number' => $pendiente?->service_order_number,

            'client_name' => $pendiente?->client?->name ?? 'Sin cliente',

            'client_address' => $pendiente?->service_address
                ?? $pendiente?->client?->full_address
                ?? $pendiente?->client?->address
                ?? 'Sin direccion',

            'client_availability' => $pendiente?->client_availability,
            'description' => $pendiente?->description ?? 'Sin descripcion',
            'priority' => $pendiente?->priority ?? 'media',

            'priority_label' => $pendiente
                ? (Pendiente::getPriorityOptions()[$pendiente->priority] ?? 'Media')
                : 'Media',

            'due_date' => optional($pendiente?->due_date)->toDateString(),
            'assigned_at' => optional($pendiente?->assigned_at)->toIso8601String(),
            'estimated_time' => $pendiente?->estimated_time,
            'review_notes' => $pendiente?->review_notes,
            'work_order' => $pendiente?->work_order,
            'required_tools' => $pendiente?->required_tools ?? [],

            'operational_zone' => $pendiente?->operational_zone,

            'operational_zone_label' => $pendiente
                ? (Pendiente::getOperationalZoneOptions()[$pendiente->operational_zone] ?? null)
                : null,

            'neighborhood' => $pendiente?->neighborhood,

            'can_accept' => $visit->status === ServiceVisit::STATUS_PENDING,

            'can_start' => in_array($visit->status, [
                ServiceVisit::STATUS_PENDING,
                ServiceVisit::STATUS_ACCEPTED,
            ], true),

            'can_finish' => $visit->status === ServiceVisit::STATUS_IN_PROGRESS,
        ];
    }

    protected function lockVisit(ServiceVisit $visit): ServiceVisit
    {
        return ServiceVisit::query()
            ->with(['technician', 'pendiente.client'])
            ->lockForUpdate()
            ->findOrFail($visit->id);
    }

    protected function assertCanOperate(ServiceVisit $visit, User $user): void
    {
        if ($user->hasRole('admin')) {
            return;
        }

        if ((int) $visit->technician_id !== (int) $user->id) {
            throw new AuthorizationException('No autorizado para operar esta visita.');
        }
    }

    protected function assertNotCompleted(ServiceVisit $visit): void
    {
        if ($visit->status === ServiceVisit::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'visit' => 'Esta visita ya fue finalizada.',
            ]);
        }
    }
}

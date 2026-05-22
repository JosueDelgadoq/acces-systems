<?php

namespace App\Services;

use App\Models\Pendiente;
use App\Models\ServiceVisit;
use App\Models\TechnicianLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TechnicianTrackingService
{
    public function __construct(
        protected TechnicianStatusResolver $statusResolver,
    ) {
    }

    public function ingest(User $user, array $payload): TechnicianLocation
    {
        return DB::transaction(function () use ($user, $payload) {
            $trackedAt = isset($payload['tracked_at'])
                ? Carbon::parse($payload['tracked_at'])
                : now();

            $speed = isset($payload['speed']) ? (float) $payload['speed'] : null;
            $isMoving = $payload['is_moving']
                ?? ($speed !== null && $speed >= 1.2);

            $location = TechnicianLocation::query()->create([
                'user_id' => $user->id,
                'lat' => $payload['lat'],
                'lng' => $payload['lng'],
                'accuracy' => $payload['accuracy'] ?? null,
                'speed' => $speed,
                'battery_level' => $payload['battery_level'] ?? null,
                'is_moving' => (bool) $isMoving,
                'tracked_at' => $trackedAt,
            ]);

            $activeVisit = $this->resolveActiveVisit($user);
            $status = $this->statusResolver->resolve($user, $activeVisit, (bool) $isMoving, $trackedAt);

            $user->forceFill([
                'last_lat' => $payload['lat'],
                'last_lng' => $payload['lng'],
                'last_seen_at' => $trackedAt,
                'tracking_enabled' => true,
                'technician_status' => $status,
            ])->save();

            return $location;
        }, 5);
    }

    public function markTrackingHeartbeat(User $user, bool $enabled): void
    {
        $user->forceFill([
            'tracking_enabled' => $enabled,
            'last_seen_at' => now(),
            'technician_status' => $enabled
                ? ($user->technician_status === TechnicianStatusResolver::STATUS_OFFLINE
                    ? TechnicianStatusResolver::STATUS_AVAILABLE
                    : $user->technician_status)
                : TechnicianStatusResolver::STATUS_OFFLINE,
        ])->save();
    }

    public function syncFromVisitStart(ServiceVisit $visit, ?float $lat = null, ?float $lng = null): void
    {
        if (! $visit->technician) {
            return;
        }

        $visit->technician->forceFill([
            'tracking_enabled' => true,
            'technician_status' => TechnicianStatusResolver::STATUS_WORKING,
            'last_seen_at' => now(),
            'last_lat' => $lat ?? $visit->technician->last_lat,
            'last_lng' => $lng ?? $visit->technician->last_lng,
        ])->save();

        if ($visit->pendiente) {
            $payload = [];

            if ($visit->pendiente->status !== Pendiente::STATUS_IN_PROGRESS) {
                $payload['status'] = Pendiente::STATUS_IN_PROGRESS;
            }

            if (! $visit->pendiente->performed_at) {
                $payload['performed_at'] = $visit->arrival_time ?? now();
            }

            if ($payload !== []) {
                $visit->pendiente->update($payload);
            }
        }
    }

    public function syncFromVisitFinish(ServiceVisit $visit, ?float $lat = null, ?float $lng = null): void
    {
        if (! $visit->technician) {
            return;
        }

        $visit->technician->forceFill([
            'tracking_enabled' => true,
            'technician_status' => TechnicianStatusResolver::STATUS_FINISHED,
            'last_seen_at' => now(),
            'last_lat' => $lat ?? $visit->technician->last_lat,
            'last_lng' => $lng ?? $visit->technician->last_lng,
        ])->save();

        if ($visit->pendiente && $visit->pendiente->status !== Pendiente::STATUS_COMPLETED) {
            $payload = [
                'status' => Pendiente::STATUS_COMPLETED,
                'completed_at' => $visit->pendiente->completed_at
                    ?? $visit->departure_time
                    ?? now(),
                'performed_at' => $visit->pendiente->performed_at
                    ?? $visit->arrival_time
                    ?? $visit->departure_time
                    ?? now(),
            ];

            if (Pendiente::hasColumn('control_summary') && filled($visit->report)) {
                $payload['control_summary'] = $visit->report;
            }

            $visit->pendiente->update($payload);
        }
    }

    public function resolveActiveVisit(User $user): ?ServiceVisit
    {
        return ServiceVisit::query()
            ->with(['pendiente.client', 'technician'])
            ->assignedToTechnician($user)
            ->active()
            ->latest('id')
            ->first();
    }
}

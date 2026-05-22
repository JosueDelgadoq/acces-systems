<?php

namespace App\Services;

use App\Models\ServiceVisit;
use App\Models\TechnicianLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TechnicianLiveMapService
{
    public function __construct(
        protected TechnicianStatusResolver $statusResolver,
    ) {
    }

    public function livePayload(?User $viewer = null): array
    {
        return User::query()
            ->where(function ($query) {
                $query->where('tracking_enabled', true)
                    ->orWhereNotNull('last_seen_at');
            })
            ->with([
                'roles:id,name',
                'latestTechnicianLocation',
                'activeServiceVisit.pendiente.client',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->serializeTechnician($user))
            ->values()
            ->all();
    }

    public function historyPayload(User $user, ?string $date = null): array
    {
        $day = $date ? now()->parse($date) : now();

        $points = TechnicianLocation::query()
            ->where('user_id', $user->id)
            ->whereBetween('tracked_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->orderBy('tracked_at')
            ->get();

        return [
            'technician' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'date' => $day->toDateString(),
            'points' => $points->map(fn (TechnicianLocation $point) => [
                'lat' => $point->lat,
                'lng' => $point->lng,
                'accuracy' => $point->accuracy,
                'speed' => $point->speed,
                'battery_level' => $point->battery_level,
                'is_moving' => $point->is_moving,
                'tracked_at' => optional($point->tracked_at)->toIso8601String(),
            ])->values()->all(),
        ];
    }

    protected function serializeTechnician(User $user): array
    {
        $activeVisit = $user->activeServiceVisit;
        $lastPoint = $user->latestTechnicianLocation;
        $status = $this->statusResolver->resolve(
            $user,
            $activeVisit,
            (bool) ($lastPoint?->is_moving ?? false),
            $user->last_seen_at,
        );

        return [
            'id' => $user->id,
            'name' => $user->name,
            'status' => $status,
            'status_label' => $this->statusResolver->label($status),
            'status_color' => $this->statusResolver->color($status),
            'tracking_enabled' => (bool) $user->tracking_enabled,
            'last_lat' => $user->last_lat !== null ? (float) $user->last_lat : null,
            'last_lng' => $user->last_lng !== null ? (float) $user->last_lng : null,
            'last_seen_at' => optional($user->last_seen_at)->toIso8601String(),
            'last_seen_human' => $user->last_seen_at?->diffForHumans(),
            'accuracy' => $lastPoint?->accuracy,
            'speed' => $lastPoint?->speed,
            'battery_level' => $lastPoint?->battery_level,
            'is_moving' => (bool) ($lastPoint?->is_moving ?? false),
            'current_visit' => $this->serializeVisit($activeVisit),
        ];
    }

    protected function serializeVisit(?ServiceVisit $visit): ?array
    {
        if (! $visit) {
            return null;
        }

        return [
            'id' => $visit->id,
            'status' => $visit->status,
            'status_label' => ServiceVisit::getStatusLabel($visit->status),
            'visit_type' => $visit->visit_type,
            'visit_type_label' => ServiceVisit::getTypeLabel($visit->visit_type),
            'arrival_photo_url' => $visit->arrival_photo ? asset('storage/' . $visit->arrival_photo) : null,
            'departure_photo_url' => $visit->departure_photo ? asset('storage/' . $visit->departure_photo) : null,
            'pendiente' => $visit->pendiente ? [
                'id' => $visit->pendiente->id,
                'type' => $visit->pendiente->type,
                'type_label' => \App\Models\Pendiente::getTypeOptions()[$visit->pendiente->type] ?? (string) $visit->pendiente->type,
                'description' => $visit->pendiente->description,
                'status' => $visit->pendiente->status,
                'status_label' => \App\Models\Pendiente::getStatusLabel($visit->pendiente->status),
                'service_order_number' => $visit->pendiente->service_order_number,
                'service_address' => $visit->pendiente->service_address,
                'operational_zone' => $visit->pendiente->operational_zone,
                'operational_zone_label' => $visit->pendiente->operational_zone
                    ? (\App\Models\Pendiente::getOperationalZoneOptions()[$visit->pendiente->operational_zone] ?? null)
                    : null,
                'client' => $visit->pendiente->client ? [
                    'id' => $visit->pendiente->client->id,
                    'name' => $visit->pendiente->client->name,
                    'full_address' => $visit->pendiente->client->full_address,
                ] : null,
            ] : null,
        ];
    }
}

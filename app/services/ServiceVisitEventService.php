<?php

namespace App\Services;

use App\Models\ServiceVisit;
use App\Models\ServiceVisitEvent;
use App\Models\User;

class ServiceVisitEventService
{
    public function record(
        ServiceVisit $visit,
        ?User $user,
        string $eventType,
        ?string $description = null,
        array $metadata = [],
        ?float $lat = null,
        ?float $lng = null,
    ): ServiceVisitEvent {
        return ServiceVisitEvent::query()->create([
            'service_visit_id' => $visit->id,
            'pendiente_id' => $visit->pendiente_id,
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'description' => filled($description) ? trim($description) : null,
            'lat' => $lat,
            'lng' => $lng,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }

    public function resolveTravelingVisitForTechnician(User $user): ?ServiceVisit
    {
        return ServiceVisit::query()
            ->where('technician_id', $user->id)
            ->whereIn('status', [
                ServiceVisit::STATUS_ACCEPTED,
                ServiceVisit::STATUS_IN_PROGRESS,
                ServiceVisit::STATUS_PENDING,
            ])
            ->orderByRaw(
                "case status
                    when ? then 0
                    when ? then 1
                    when ? then 2
                    else 3
                end",
                [
                    ServiceVisit::STATUS_ACCEPTED,
                    ServiceVisit::STATUS_IN_PROGRESS,
                    ServiceVisit::STATUS_PENDING,
                ],
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    public function shouldSkipTravelingEvent(ServiceVisit $visit, int $windowSeconds = 120): bool
    {
        $lastEvent = $visit->events()
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $lastEvent) {
            return false;
        }

        return $lastEvent->event_type === ServiceVisitEvent::TYPE_TRAVELING
            && $lastEvent->created_at?->gte(now()->subSeconds($windowSeconds));
    }

    public function recordTravelingForTechnician(User $user): ?ServiceVisitEvent
    {
        $visit = $this->resolveTravelingVisitForTechnician($user);

        if (! $visit || $this->shouldSkipTravelingEvent($visit)) {
            return null;
        }

        return $this->record(
            $visit,
            $user,
            ServiceVisitEvent::TYPE_TRAVELING,
            'Tecnico en traslado hacia la visita.',
            [
                'source' => 'tracking.status',
                'technician_status' => $user->technician_status,
            ],
            $user->last_lat,
            $user->last_lng,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timelinePayload(ServiceVisit $visit): array
    {
        return $visit->events()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (ServiceVisitEvent $event): array => $this->serialize($event))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(ServiceVisitEvent $event): array
    {
        return [
            'id' => $event->id,
            'service_visit_id' => $event->service_visit_id,
            'pendiente_id' => $event->pendiente_id,
            'user_id' => $event->user_id,
            'technician' => $event->user ? [
                'id' => $event->user->id,
                'name' => $event->user->name,
            ] : null,
            'event_type' => $event->event_type,
            'event_type_label' => ServiceVisitEvent::getTypeLabel($event->event_type),
            'description' => $event->description,
            'lat' => $event->lat,
            'lng' => $event->lng,
            'metadata' => $event->metadata ?? [],
            'created_at' => optional($event->created_at)->toIso8601String(),
            'updated_at' => optional($event->updated_at)->toIso8601String(),
        ];
    }
}

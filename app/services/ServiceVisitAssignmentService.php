<?php

namespace App\Services;

use App\Models\Pendiente;
use App\Models\ServiceVisit;
use Illuminate\Support\Facades\DB;

class ServiceVisitAssignmentService
{
    public function __construct(
        protected ProductoUnidadAssignmentService $unitAssignments,
    ) {
    }

    public function syncFromPendiente(Pendiente $pendiente): ?ServiceVisit
    {
        return DB::transaction(function () use ($pendiente): ?ServiceVisit {
            /** @var Pendiente $lockedPendiente */
            $lockedPendiente = Pendiente::query()
                ->with(['client', 'user'])
                ->lockForUpdate()
                ->findOrFail($pendiente->id);

            $activeVisit = ServiceVisit::query()
                ->with(['pendiente.client', 'technician'])
                ->where('pendiente_id', $lockedPendiente->id)
                ->active()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $this->shouldKeepVisitActive($lockedPendiente)) {
                $closedVisit = $activeVisit
                    ? $this->closeVisit($activeVisit, $lockedPendiente, $this->resolveClosureReason($lockedPendiente))
                    : null;

                $this->unitAssignments->releaseForPendiente(
                    $lockedPendiente,
                    null,
                    $this->resolveClosureReason($lockedPendiente),
                );

                return $closedVisit;
            }

            if ($activeVisit && $this->shouldRolloverVisit($activeVisit, $lockedPendiente)) {
                $this->closeVisit($activeVisit, $lockedPendiente, 'Visita reasignada desde pendiente.');
                $activeVisit = null;
            }

            if (! $activeVisit) {
                $createdVisit = ServiceVisit::query()
                    ->create([
                        'pendiente_id' => $lockedPendiente->id,
                        'technician_id' => $lockedPendiente->user_id,
                        'status' => ServiceVisit::STATUS_PENDING,
                        'visit_type' => ServiceVisit::resolveVisitType($lockedPendiente->type),
                    ])
                    ->load(['pendiente.client', 'technician']);

                $this->unitAssignments->syncOperationalContext($lockedPendiente, $createdVisit);

                return $createdVisit;
            }

            $activeVisit->fill([
                'technician_id' => $lockedPendiente->user_id,
                'visit_type' => ServiceVisit::resolveVisitType($lockedPendiente->type),
            ]);

            if ($activeVisit->isDirty()) {
                $activeVisit->save();
            }

            $this->unitAssignments->syncOperationalContext($lockedPendiente, $activeVisit);

            return $activeVisit->fresh(['pendiente.client', 'technician']);
        }, 5);
    }

    protected function shouldKeepVisitActive(Pendiente $pendiente): bool
    {
        if (! ServiceVisit::requiresOperationalVisit($pendiente->type)) {
            return false;
        }

        if (blank($pendiente->user_id)) {
            return false;
        }

        return ! in_array($pendiente->status, [
            Pendiente::STATUS_COMPLETED,
            Pendiente::STATUS_CANCELLED,
        ], true);
    }

    protected function shouldRolloverVisit(ServiceVisit $visit, Pendiente $pendiente): bool
    {
        if ((int) $visit->technician_id === (int) $pendiente->user_id) {
            return false;
        }

        return filled($visit->arrival_time)
            || filled($visit->arrival_photo)
            || filled($visit->departure_photo)
            || filled($visit->report)
            || $visit->status === ServiceVisit::STATUS_IN_PROGRESS;
    }

    protected function resolveClosureReason(Pendiente $pendiente): string
    {
        return match (true) {
            $pendiente->status === Pendiente::STATUS_COMPLETED => 'Visita cerrada desde pendiente.',
            $pendiente->status === Pendiente::STATUS_CANCELLED => 'Visita cancelada desde pendiente.',
            blank($pendiente->user_id) => 'Visita desasignada desde pendiente.',
            ! ServiceVisit::requiresOperationalVisit($pendiente->type) => 'Visita retirada del circuito tecnico.',
            default => 'Visita cerrada desde pendiente.',
        };
    }

    protected function closeVisit(ServiceVisit $visit, Pendiente $pendiente, string $reason): ServiceVisit
    {
        $report = collect([
            filled($visit->report) ? trim((string) $visit->report) : null,
            filled($pendiente->control_summary) ? trim((string) $pendiente->control_summary) : null,
            filled($pendiente->notes) ? trim((string) $pendiente->notes) : null,
            $reason,
        ])
            ->filter(fn (?string $line): bool => filled($line))
            ->unique()
            ->implode("\n\n");

        $visit->forceFill([
            'status' => ServiceVisit::STATUS_COMPLETED,
            'departure_time' => $visit->departure_time
                ?? $pendiente->completed_at
                ?? $pendiente->performed_at
                ?? now(),
            'report' => $report ?: null,
            'visit_type' => ServiceVisit::resolveVisitType($pendiente->type),
        ])->save();

        return $visit->fresh(['pendiente.client', 'technician']);
    }
}

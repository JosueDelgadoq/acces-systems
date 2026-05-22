<?php

namespace App\Services;

use App\Models\Pendiente;
use App\Models\ProductoUnidad;
use App\Models\ProductoUnidadAssignment;
use App\Models\ServiceVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoUnidadAssignmentService
{
    public function __construct(
        protected ProductoUnidadInventoryService $inventory,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function optionsForPendiente(Pendiente $pendiente): array
    {
        $currentUnitIds = $this->activeAssignmentsQuery()
            ->forPendiente($pendiente)
            ->pluck('producto_unidad_id')
            ->all();

        return ProductoUnidad::query()
            ->with(['variante.producto'])
            ->where(function ($query) use ($currentUnitIds): void {
                $query
                    ->where('estado', ProductoUnidad::STATUS_AVAILABLE)
                    ->when(
                        filled($currentUnitIds),
                        fn ($inner) => $inner->orWhereIn('id', $currentUnitIds),
                    );
            })
            ->orderBy('codigo_barra')
            ->get()
            ->mapWithKeys(fn (ProductoUnidad $unit): array => [
                $unit->id => $this->formatUnitOptionLabel($unit),
            ])
            ->all();
    }

    /**
     * @return EloquentCollection<int, ProductoUnidadAssignment>
     */
    public function activeAssignmentsForPendiente(Pendiente $pendiente): EloquentCollection
    {
        return $this->activeAssignmentsQuery()
            ->forPendiente($pendiente)
            ->with(['unit.variante.producto', 'technician', 'visit'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, int|string|null>  $desiredUnitIds
     * @return EloquentCollection<int, ProductoUnidadAssignment>
     */
    public function syncForPendiente(
        Pendiente $pendiente,
        array $desiredUnitIds,
        ?User $actor = null,
        ?string $notes = null,
    ): EloquentCollection {
        return DB::transaction(function () use ($pendiente, $desiredUnitIds, $actor, $notes): EloquentCollection {
            $lockedPendiente = Pendiente::query()
                ->lockForUpdate()
                ->findOrFail($pendiente->id);

            $activeVisit = ServiceVisit::query()
                ->where('pendiente_id', $lockedPendiente->id)
                ->active()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $desiredIds = collect($desiredUnitIds)
                ->filter(fn ($value): bool => filled($value))
                ->map(fn ($value): int => (int) $value)
                ->unique()
                ->values();

            $currentAssignments = $this->activeAssignmentsQuery()
                ->forPendiente($lockedPendiente)
                ->with('unit.variante.producto')
                ->lockForUpdate()
                ->get()
                ->keyBy('producto_unidad_id');

            $this->releaseRemovedAssignments(
                $currentAssignments,
                $desiredIds,
                $actor,
                $notes ?: 'Unidad retirada de la planificacion tecnica.',
            );

            $conflictingAssignments = $this->activeAssignmentsQuery()
                ->whereIn('producto_unidad_id', $desiredIds)
                ->where(function ($query) use ($lockedPendiente): void {
                    $query
                        ->whereNull('pendiente_id')
                        ->orWhere('pendiente_id', '!=', $lockedPendiente->id);
                })
                ->with(['unit.variante.producto', 'pendiente.client'])
                ->lockForUpdate()
                ->get()
                ->keyBy('producto_unidad_id');

            $units = ProductoUnidad::query()
                ->with(['variante.producto'])
                ->whereIn('id', $desiredIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($desiredIds as $unitId) {
                if ($currentAssignments->has($unitId)) {
                    $this->syncAssignmentContext(
                        $currentAssignments->get($unitId),
                        $lockedPendiente,
                        $activeVisit,
                    );

                    continue;
                }

                if ($conflictingAssignments->has($unitId)) {
                    $assignment = $conflictingAssignments->get($unitId);

                    throw ValidationException::withMessages([
                        'inventory_unit_ids' => 'La unidad ' . $this->describeUnit($assignment->unit) . ' ya esta reservada para otro pendiente.',
                    ]);
                }

                /** @var ProductoUnidad|null $unit */
                $unit = $units->get($unitId);

                if (! $unit) {
                    throw ValidationException::withMessages([
                        'inventory_unit_ids' => 'Una de las unidades seleccionadas ya no existe.',
                    ]);
                }

                if ($unit->estado !== ProductoUnidad::STATUS_AVAILABLE) {
                    throw ValidationException::withMessages([
                        'inventory_unit_ids' => 'La unidad ' . $this->describeUnit($unit) . ' no esta disponible para reservar.',
                    ]);
                }

                $assignment = ProductoUnidadAssignment::query()->create([
                    'producto_unidad_id' => $unit->id,
                    'pendiente_id' => $lockedPendiente->id,
                    'service_visit_id' => $activeVisit?->id,
                    'technician_id' => $activeVisit?->technician_id ?? $lockedPendiente->user_id,
                    'holder_type' => $activeVisit?->technician_id || $lockedPendiente->user_id ? User::class : null,
                    'holder_id' => $activeVisit?->technician_id ?? $lockedPendiente->user_id,
                    'context_type' => $activeVisit ? ServiceVisit::class : Pendiente::class,
                    'context_id' => $activeVisit?->id ?? $lockedPendiente->id,
                    'client_id' => $lockedPendiente->client_id ?? null,
                    'created_by_user_id' => $actor?->id,
                    'status' => $this->resolveOperationalStatus($activeVisit),
                    'notes' => $notes,
                    'reserved_at' => now(),
                    'assigned_at' => $this->resolveOperationalStatus($activeVisit) === ProductoUnidadAssignment::STATUS_ASSIGNED
                        ? now()
                        : null,
                ]);

                $this->inventory->changeState(
                    $unit,
                    $assignment->status === ProductoUnidadAssignment::STATUS_ASSIGNED
                        ? ProductoUnidad::STATUS_ASSIGNED
                        : ProductoUnidad::STATUS_RESERVED,
                    $actor,
                    $assignment->status === ProductoUnidadAssignment::STATUS_ASSIGNED
                        ? 'asignacion_visita'
                        : 'reserva_pendiente',
                    $notes,
                    Pendiente::class,
                    $lockedPendiente->id,
                );

                $currentAssignments->put($unitId, $assignment->fresh(['unit.variante.producto', 'technician', 'visit']));
            }

            return $this->activeAssignmentsForPendiente($lockedPendiente);
        }, 5);
    }

    public function syncOperationalContext(Pendiente $pendiente, ?ServiceVisit $visit = null): void
    {
        DB::transaction(function () use ($pendiente, $visit): void {
            $lockedPendiente = Pendiente::query()
                ->lockForUpdate()
                ->findOrFail($pendiente->id);

            $activeVisit = $visit
                ? ServiceVisit::query()->lockForUpdate()->find($visit->id)
                : ServiceVisit::query()
                    ->where('pendiente_id', $lockedPendiente->id)
                    ->active()
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

            $assignments = $this->activeAssignmentsQuery()
                ->forPendiente($lockedPendiente)
                ->with('unit.variante.producto')
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                $this->syncAssignmentContext($assignment, $lockedPendiente, $activeVisit);
            }
        }, 5);
    }

    public function markAssignedForVisit(ServiceVisit $visit, ?User $actor = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($visit, $actor, $notes): void {
            $lockedVisit = ServiceVisit::query()
                ->with('pendiente')
                ->lockForUpdate()
                ->findOrFail($visit->id);

            $assignments = $this->activeAssignmentsQuery()
                ->forPendiente($lockedVisit->pendiente_id)
                ->with('unit.variante.producto')
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                $assignment->forceFill([
                    'service_visit_id' => $lockedVisit->id,
                    'technician_id' => $lockedVisit->technician_id,
                    'holder_type' => $lockedVisit->technician_id ? User::class : null,
                    'holder_id' => $lockedVisit->technician_id,
                    'context_type' => ServiceVisit::class,
                    'context_id' => $lockedVisit->id,
                    'client_id' => $lockedVisit->pendiente?->client_id,
                    'status' => ProductoUnidadAssignment::STATUS_ASSIGNED,
                    'assigned_at' => $assignment->assigned_at ?? now(),
                ])->save();

                if ($assignment->unit && $assignment->unit->estado !== ProductoUnidad::STATUS_ASSIGNED) {
                    $this->inventory->changeState(
                        $assignment->unit,
                        ProductoUnidad::STATUS_ASSIGNED,
                        $actor,
                        'asignacion_visita',
                        $notes ?: 'Unidad asignada a visita tecnica.',
                        ServiceVisit::class,
                        $lockedVisit->id,
                    );
                }
            }
        }, 5);
    }

    public function resolveForVisit(ServiceVisit $visit, ?User $actor = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($visit, $actor, $notes): void {
            $lockedVisit = ServiceVisit::query()
                ->with('pendiente')
                ->lockForUpdate()
                ->findOrFail($visit->id);

            $assignments = $this->activeAssignmentsQuery()
                ->forPendiente($lockedVisit->pendiente_id)
                ->with('unit.variante.producto')
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                if ($lockedVisit->visit_type === ServiceVisit::TYPE_INSTALLATION) {
                    $this->markInstalledAssignment($assignment, $lockedVisit, $actor, $notes);

                    continue;
                }

                $this->releaseAssignment(
                    $assignment,
                    $actor,
                    $notes ?: 'Unidad liberada al finalizar la visita tecnica.',
                    ServiceVisit::class,
                    $lockedVisit->id,
                );
            }
        }, 5);
    }

    public function releaseForPendiente(Pendiente $pendiente, ?User $actor = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($pendiente, $actor, $notes): void {
            $assignments = $this->activeAssignmentsQuery()
                ->forPendiente($pendiente)
                ->with('unit.variante.producto')
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                $this->releaseAssignment(
                    $assignment,
                    $actor,
                    $notes ?: 'Unidad liberada desde la orden tecnica.',
                    Pendiente::class,
                    $pendiente->id,
                );
            }
        }, 5);
    }

    protected function syncAssignmentContext(
        ProductoUnidadAssignment $assignment,
        Pendiente $pendiente,
        ?ServiceVisit $visit,
    ): void {
        $targetStatus = $this->resolveOperationalStatus($visit);

        $assignment->forceFill([
            'pendiente_id' => $pendiente->id,
            'service_visit_id' => $visit?->id,
            'technician_id' => $visit?->technician_id ?? $pendiente->user_id,
            'holder_type' => $visit?->technician_id || $pendiente->user_id ? User::class : null,
            'holder_id' => $visit?->technician_id ?? $pendiente->user_id,
            'context_type' => $visit ? ServiceVisit::class : Pendiente::class,
            'context_id' => $visit?->id ?? $pendiente->id,
            'client_id' => $pendiente->client_id ?? null,
            'status' => $targetStatus,
            'assigned_at' => $targetStatus === ProductoUnidadAssignment::STATUS_ASSIGNED
                ? ($assignment->assigned_at ?? now())
                : null,
        ])->save();

        if (! $assignment->unit) {
            return;
        }

        $targetUnitState = $targetStatus === ProductoUnidadAssignment::STATUS_ASSIGNED
            ? ProductoUnidad::STATUS_ASSIGNED
            : ProductoUnidad::STATUS_RESERVED;

        if ($assignment->unit->estado !== $targetUnitState) {
            $this->inventory->changeState(
                $assignment->unit,
                $targetUnitState,
                null,
                $targetStatus === ProductoUnidadAssignment::STATUS_ASSIGNED
                    ? 'asignacion_visita'
                    : 'reserva_pendiente',
                'Sincronizacion automatica de unidad asignada.',
                $visit ? ServiceVisit::class : Pendiente::class,
                $visit?->id ?? $pendiente->id,
            );
        }
    }

    /**
     * @param  Collection<int, ProductoUnidadAssignment>  $currentAssignments
     * @param  Collection<int, int>  $desiredIds
     */
    protected function releaseRemovedAssignments(
        Collection $currentAssignments,
        Collection $desiredIds,
        ?User $actor,
        string $notes,
    ): void {
        $idsToRelease = $currentAssignments
            ->keys()
            ->diff($desiredIds)
            ->values();

        foreach ($idsToRelease as $unitId) {
            $assignment = $currentAssignments->get($unitId);

            if (! $assignment) {
                continue;
            }

            $this->releaseAssignment(
                $assignment,
                $actor,
                $notes,
                Pendiente::class,
                $assignment->pendiente_id,
            );

            $currentAssignments->forget($unitId);
        }
    }

    protected function releaseAssignment(
        ProductoUnidadAssignment $assignment,
        ?User $actor,
        string $notes,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        $assignment->forceFill([
            'status' => ProductoUnidadAssignment::STATUS_RELEASED,
            'released_by_user_id' => $actor?->id,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ])->save();

        if (! $assignment->unit) {
            return;
        }

        if (! in_array($assignment->unit->estado, [
            ProductoUnidad::STATUS_INSTALLED,
            ProductoUnidad::STATUS_SOLD,
            ProductoUnidad::STATUS_RETIRED,
        ], true)) {
            $this->inventory->changeState(
                $assignment->unit,
                ProductoUnidad::STATUS_AVAILABLE,
                $actor,
                'liberacion_reserva',
                $notes,
                $referenceType,
                $referenceId,
            );
        }
    }

    protected function markInstalledAssignment(
        ProductoUnidadAssignment $assignment,
        ServiceVisit $visit,
        ?User $actor,
        ?string $notes = null,
    ): void {
        $assignment->forceFill([
            'service_visit_id' => $visit->id,
            'technician_id' => $visit->technician_id,
            'holder_type' => $visit->technician_id ? User::class : null,
            'holder_id' => $visit->technician_id,
            'context_type' => ServiceVisit::class,
            'context_id' => $visit->id,
            'client_id' => $visit->pendiente?->client_id,
            'status' => ProductoUnidadAssignment::STATUS_INSTALLED,
            'released_by_user_id' => $actor?->id,
            'assigned_at' => $assignment->assigned_at ?? now(),
            'installed_at' => now(),
            'resolved_at' => now(),
            'resolution_notes' => $notes ?: 'Unidad instalada al finalizar la visita tecnica.',
        ])->save();

        if (! $assignment->unit) {
            return;
        }

        if ($assignment->unit->estado !== ProductoUnidad::STATUS_INSTALLED) {
            $this->inventory->changeState(
                $assignment->unit,
                ProductoUnidad::STATUS_INSTALLED,
                $actor,
                'instalacion_visita',
                $notes ?: 'Unidad instalada al finalizar la visita tecnica.',
                ServiceVisit::class,
                $visit->id,
            );
        }
    }

    protected function resolveOperationalStatus(?ServiceVisit $visit): string
    {
        if ($visit && in_array($visit->status, [
            ServiceVisit::STATUS_ACCEPTED,
            ServiceVisit::STATUS_IN_PROGRESS,
        ], true)) {
            return ProductoUnidadAssignment::STATUS_ASSIGNED;
        }

        return ProductoUnidadAssignment::STATUS_RESERVED;
    }

    protected function activeAssignmentsQuery()
    {
        return ProductoUnidadAssignment::query()->active();
    }

    protected function formatUnitOptionLabel(ProductoUnidad $unit): string
    {
        return implode(' | ', array_filter([
            $unit->inventory_code ?: $unit->codigo_barra,
            $unit->variante?->producto?->nombre,
            $unit->variante?->modelo,
            $unit->status_label,
        ]));
    }

    protected function describeUnit(?ProductoUnidad $unit): string
    {
        if (! $unit) {
            return 'sin identificar';
        }

        return $unit->inventory_code ?: ($unit->codigo_barra ?: ('#' . $unit->id));
    }
}

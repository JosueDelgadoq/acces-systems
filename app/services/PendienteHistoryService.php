<?php

namespace App\Services;

use App\Models\Pendiente;
use App\Models\User;
use Illuminate\Support\Carbon;

class PendienteHistoryService
{
    protected const TRACKED_COLUMNS = [
        'status',
        'notes',
        'user_id',
        'due_date',
        'priority',
        'review_notes',
    ];

    public function record(
        Pendiente $pendiente,
        ?string $observation = null,
        ?string $statusFrom = null,
        ?string $statusTo = null
    ): void {
        $statusTo ??= $pendiente->status;
        $observation = filled($observation) ? trim($observation) : null;

        if (blank($statusTo)) {
            return;
        }

        if (! $this->shouldPersist($pendiente, $observation, $statusFrom, $statusTo)) {
            return;
        }

        $pendiente->histories()->create([
            'client_id' => $pendiente->client_id,
            'user_id' => auth()->id() ?? $pendiente->user_id,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'observation' => $observation,
            'changed_at' => now(),
        ]);
    }

    public function recordCreated(Pendiente $pendiente): void
    {
        $this->record(
            $pendiente,
            $this->buildCreatedObservation($pendiente),
            null,
            $pendiente->status,
        );
    }

    public function recordUpdated(Pendiente $pendiente): void
    {
        $statusChanged = $pendiente->wasChanged('status');
        $notesChanged = $this->notesChanged($pendiente);
        $structuralChanged = $pendiente->wasChanged([
            'user_id',
            'due_date',
            'priority',
            'review_notes',
        ]);

        if (! $statusChanged && ! $structuralChanged && ! $notesChanged) {
            return;
        }

        $this->record(
            $pendiente,
            $this->buildUpdatedObservation($pendiente, $notesChanged),
            $statusChanged ? $pendiente->getOriginal('status') : $pendiente->status,
            $pendiente->status,
        );
    }

    protected function buildCreatedObservation(Pendiente $pendiente): ?string
    {
        $parts = ['Pendiente creado.'];

        if (filled($pendiente->description)) {
            $parts[] = 'Detalle: ' . trim((string) $pendiente->description);
        }

        if ($pendiente->due_date) {
            $parts[] = 'Fecha limite: ' . $pendiente->due_date->format('d/m/Y') . '.';
        }

        if (filled($pendiente->priority)) {
            $parts[] = 'Prioridad: ' . (Pendiente::getPriorityOptions()[$pendiente->priority] ?? $pendiente->priority) . '.';
        }

        if (filled($pendiente->user?->name)) {
            $parts[] = 'Tecnico asignado: ' . $pendiente->user->name . '.';
        }

        if (filled($pendiente->notes)) {
            $parts[] = 'Novedad inicial: ' . trim((string) $pendiente->notes);
        }

        return trim(implode(' ', array_filter($parts)));
    }

    protected function buildUpdatedObservation(Pendiente $pendiente, bool $notesChanged): ?string
    {
        $parts = [];

        if ($pendiente->wasChanged('status')) {
            $parts[] = 'Estado: '
                . Pendiente::getStatusLabel($pendiente->getOriginal('status'))
                . ' -> '
                . Pendiente::getStatusLabel($pendiente->status)
                . '.';
        }

        if ($pendiente->wasChanged('user_id')) {
            $oldUser = filled($pendiente->getOriginal('user_id'))
                ? User::query()->select(['id', 'name'])->find($pendiente->getOriginal('user_id'))
                : null;
            $newUser = filled($pendiente->user_id)
                ? User::query()->select(['id', 'name'])->find($pendiente->user_id)
                : null;

            $parts[] = match (true) {
                blank($pendiente->getOriginal('user_id')) && filled($pendiente->user_id) => 'Tecnico asignado: ' . ($newUser?->name ?? 'Sin asignar') . '.',
                filled($pendiente->getOriginal('user_id')) && blank($pendiente->user_id) => 'Se removio la asignacion de ' . ($oldUser?->name ?? 'tecnico anterior') . '.',
                default => 'Reasignado de ' . ($oldUser?->name ?? 'Sin asignar') . ' a ' . ($newUser?->name ?? 'Sin asignar') . '.',
            };
        }

        if ($pendiente->wasChanged('due_date')) {
            $oldDate = filled($pendiente->getOriginal('due_date'))
                ? Carbon::parse($pendiente->getOriginal('due_date'))->format('d/m/Y')
                : 'Sin fecha';
            $newDate = $pendiente->due_date?->format('d/m/Y') ?? 'Sin fecha';

            $parts[] = "Fecha limite: {$oldDate} -> {$newDate}.";
        }

        if ($pendiente->wasChanged('priority')) {
            $oldPriority = Pendiente::getPriorityOptions()[$pendiente->getOriginal('priority')] ?? 'Sin definir';
            $newPriority = Pendiente::getPriorityOptions()[$pendiente->priority] ?? 'Sin definir';
            $parts[] = "Prioridad: {$oldPriority} -> {$newPriority}.";
        }

        if ($pendiente->wasChanged('review_notes')) {
            $parts[] = filled($pendiente->review_notes)
                ? 'Instrucciones internas actualizadas.'
                : 'Se eliminaron las instrucciones internas.';
        }

        if ($notesChanged) {
            $parts[] = filled($pendiente->notes)
                ? 'Novedad: ' . trim((string) $pendiente->notes)
                : 'Se elimino la novedad registrada.';
        }

        return $parts !== [] ? trim(implode(' ', $parts)) : null;
    }

    protected function notesChanged(Pendiente $pendiente): bool
    {
        if (! $pendiente->wasChanged('notes')) {
            return false;
        }

        return trim((string) $pendiente->getOriginal('notes')) !== trim((string) $pendiente->notes);
    }

    protected function shouldPersist(
        Pendiente $pendiente,
        ?string $observation,
        ?string $statusFrom,
        string $statusTo
    ): bool {
        if (! $pendiente->exists) {
            return false;
        }

        if ($pendiente->wasRecentlyCreated) {
            return filled($observation) || $statusTo !== Pendiente::STATUS_PENDING;
        }

        return $statusFrom !== $statusTo || filled($observation);
    }
}

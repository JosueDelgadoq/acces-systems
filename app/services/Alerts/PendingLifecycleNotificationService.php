<?php

namespace App\Services\Alerts;

use App\Models\Pendiente;
use App\Models\User;
use App\Notifications\PendingLifecycleDatabaseNotification;
use App\Notifications\PendingLifecycleMailNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class PendingLifecycleNotificationService
{
    public function __construct(
        protected AlertDispatchStore $dispatchStore,
    ) {
    }

    public function handleCreated(Pendiente $pendiente): void
    {
        if (! config('alerts.pending_lifecycle.enabled', true)) {
            return;
        }

        $pendiente->loadMissing([
            'client:id,name,email',
            'user:id,name,email',
        ]);

        $payload = $this->buildPayload(
            $pendiente,
            event: 'created',
            subject: 'Nuevo pendiente creado',
            summary: 'Se creo un nuevo pendiente y ya quedo dentro del circuito operativo.',
            details: array_values(array_filter([
                'Fecha limite: ' . $this->formatDate($pendiente->due_date),
                'Prioridad: ' . $this->formatPriority($pendiente->priority),
                filled($pendiente->user?->name) ? 'Tecnico asignado: ' . $pendiente->user->name : 'Tecnico asignado: Sin asignar',
                filled($pendiente->description) ? 'Detalle: ' . $pendiente->description : null,
            ])),
            color: 'info',
            icon: 'heroicon-o-document-plus',
        );

        $this->dispatch($pendiente, $payload, $this->resolveUsers($pendiente), $this->resolveAdditionalEmails($pendiente));
    }

    public function handleUpdated(Pendiente $pendiente): void
    {
        if (! config('alerts.pending_lifecycle.enabled', true)) {
            return;
        }

        $payload = $this->buildUpdatedPayload($pendiente);

        if ($payload === null) {
            return;
        }

        $previousUser = $this->resolveUser($pendiente->getOriginal('user_id'));
        $currentUser = $this->resolveUser($pendiente->user_id);

        if ($currentUser) {
            $pendiente->setRelation('user', $currentUser);
        }

        $pendiente->loadMissing([
            'client:id,name,email',
        ]);

        $this->dispatch(
            $pendiente,
            $payload,
            $this->resolveUsers($pendiente, $previousUser),
            $this->resolveAdditionalEmails($pendiente, $previousUser),
        );
    }

    protected function buildUpdatedPayload(Pendiente $pendiente): ?array
    {
        $currentUser = $this->resolveUser($pendiente->user_id);
        $previousUser = $this->resolveUser($pendiente->getOriginal('user_id'));

        if (! $pendiente->wasChanged(['status', 'user_id', 'due_date', 'priority', 'review_notes']) && ! $pendiente->wasChanged('notes')) {
            return null;
        }

        $details = [];

        if ($pendiente->wasChanged('status')) {
            $details[] = 'Estado: '
                . Pendiente::getStatusLabel($pendiente->getOriginal('status'))
                . ' -> '
                . Pendiente::getStatusLabel($pendiente->status);
        }

        if ($pendiente->wasChanged('user_id')) {
            $details[] = match (true) {
                blank($pendiente->getOriginal('user_id')) && filled($pendiente->user_id) => 'Tecnico asignado: ' . ($currentUser?->name ?? 'Sin asignar'),
                filled($pendiente->getOriginal('user_id')) && blank($pendiente->user_id) => 'Se removio la asignacion de ' . ($previousUser?->name ?? 'tecnico anterior'),
                default => 'Reasignado de ' . ($previousUser?->name ?? 'Sin asignar') . ' a ' . ($currentUser?->name ?? 'Sin asignar'),
            };
        }

        if ($pendiente->wasChanged('due_date')) {
            $details[] = 'Fecha limite: ' . $this->formatDate($pendiente->getOriginal('due_date')) . ' -> ' . $this->formatDate($pendiente->due_date);
        }

        if ($pendiente->wasChanged('priority')) {
            $details[] = 'Prioridad: ' . $this->formatPriority($pendiente->getOriginal('priority')) . ' -> ' . $this->formatPriority($pendiente->priority);
        }

        if ($pendiente->wasChanged('review_notes')) {
            $details[] = filled($pendiente->review_notes)
                ? 'Instrucciones internas actualizadas.'
                : 'Se eliminaron las instrucciones internas.';
        }

        if ($pendiente->wasChanged('notes')) {
            $details[] = filled($pendiente->notes)
                ? 'Novedad registrada: ' . $pendiente->notes
                : 'Se elimino la novedad registrada.';
        }

        if ($pendiente->wasChanged('status')) {
            return match ($pendiente->status) {
                Pendiente::STATUS_IN_PROGRESS => $this->buildPayload(
                    $pendiente,
                    event: 'started',
                    subject: 'Pendiente en proceso',
                    summary: 'Un pendiente fue tomado y ya esta en ejecucion.',
                    details: $details,
                    color: 'info',
                    icon: 'heroicon-o-play-circle',
                ),
                Pendiente::STATUS_COMPLETED => $this->buildPayload(
                    $pendiente,
                    event: 'completed',
                    subject: 'Pendiente finalizado',
                    summary: 'Un pendiente fue finalizado y quedo cerrado en el sistema.',
                    details: $details,
                    color: 'success',
                    icon: 'heroicon-o-check-badge',
                ),
                Pendiente::STATUS_CANCELLED => $this->buildPayload(
                    $pendiente,
                    event: 'cancelled',
                    subject: 'Pendiente cancelado',
                    summary: 'Un pendiente fue cancelado y requiere seguimiento administrativo si corresponde.',
                    details: $details,
                    color: 'gray',
                    icon: 'heroicon-o-no-symbol',
                ),
                Pendiente::STATUS_PENDING => $this->buildPayload(
                    $pendiente,
                    event: 'reopened',
                    subject: 'Pendiente reabierto',
                    summary: 'Un pendiente volvio a estado pendiente.',
                    details: $details,
                    color: 'warning',
                    icon: 'heroicon-o-arrow-path',
                ),
                default => $this->buildPayload(
                    $pendiente,
                    event: 'status_changed',
                    subject: 'Pendiente actualizado',
                    summary: 'Se actualizo el estado operativo de un pendiente.',
                    details: $details,
                    color: 'info',
                    icon: 'heroicon-o-pencil-square',
                ),
            };
        }

        if ($pendiente->wasChanged('user_id')) {
            return $this->buildPayload(
                $pendiente,
                event: blank($pendiente->getOriginal('user_id'))
                    ? 'assigned'
                    : (blank($pendiente->user_id) ? 'unassigned' : 'reassigned'),
                subject: blank($pendiente->getOriginal('user_id'))
                    ? 'Pendiente asignado'
                    : (blank($pendiente->user_id) ? 'Pendiente sin tecnico asignado' : 'Pendiente reasignado'),
                summary: blank($pendiente->getOriginal('user_id'))
                    ? 'Se asigno un tecnico al pendiente.'
                    : (blank($pendiente->user_id) ? 'Se removio la asignacion tecnica del pendiente.' : 'Se cambio el tecnico responsable del pendiente.'),
                details: $details,
                color: blank($pendiente->user_id) ? 'warning' : 'info',
                icon: 'heroicon-o-user-circle',
            );
        }

        if ($pendiente->wasChanged('due_date')) {
            return $this->buildPayload(
                $pendiente,
                event: 'rescheduled',
                subject: 'Pendiente reprogramado',
                summary: 'Se actualizo la fecha limite del pendiente.',
                details: $details,
                color: 'warning',
                icon: 'heroicon-o-calendar-days',
            );
        }

        if ($pendiente->wasChanged('priority')) {
            return $this->buildPayload(
                $pendiente,
                event: 'reprioritized',
                subject: 'Prioridad de pendiente actualizada',
                summary: 'Se modifico la prioridad operativa del pendiente.',
                details: $details,
                color: 'warning',
                icon: 'heroicon-o-flag',
            );
        }

        if ($pendiente->wasChanged('review_notes')) {
            return $this->buildPayload(
                $pendiente,
                event: 'instructions_updated',
                subject: 'Instrucciones de pendiente actualizadas',
                summary: 'Se actualizaron instrucciones internas del pendiente.',
                details: $details,
                color: 'info',
                icon: 'heroicon-o-document-text',
            );
        }

        if ($pendiente->wasChanged('notes')) {
            return $this->buildPayload(
                $pendiente,
                event: 'notes_updated',
                subject: 'Novedad de pendiente actualizada',
                summary: 'Se registro una novedad operativa en el pendiente.',
                details: $details,
                color: 'info',
                icon: 'heroicon-o-chat-bubble-left-right',
            );
        }

        return null;
    }

    protected function buildPayload(
        Pendiente $pendiente,
        string $event,
        string $subject,
        string $summary,
        array $details,
        string $color,
        string $icon,
    ): array {
        return [
            'event' => $event,
            'subject' => $subject,
            'summary' => $summary,
            'details' => $details,
            'color' => $color,
            'icon' => $icon,
            'actor_name' => $this->resolveActorName($pendiente),
            'url' => url('/admin/pendientes/' . $pendiente->id . '/edit'),
        ];
    }

    protected function dispatch(Pendiente $pendiente, array $payload, Collection $users, array $additionalEmails): void
    {
        $versionBucket = ($pendiente->updated_at ?? $pendiente->created_at ?? now())->format('Y-m-d-H-i-s-u');
        $mailNotification = new PendingLifecycleMailNotification($pendiente, $payload);

        foreach ($users as $user) {
            if (filled($user->email)) {
                $mailKey = $this->dispatchStore->makeDedupeKey('pending_lifecycle.' . $payload['event'], 'mail', $user->email, $pendiente, $versionBucket);

                if (! $this->dispatchStore->wasSent($mailKey)) {
                    Notification::route('mail', $user->email)->notify($mailNotification);

                    $this->dispatchStore->record(
                        'pending_lifecycle.' . $payload['event'],
                        'mail',
                        $user->email,
                        $pendiente,
                        $mailKey,
                        $this->buildDispatchMeta($payload, 'assigned_user'),
                    );
                }
            }

            if (config('alerts.pending_lifecycle.send_database_notification', true)) {
                $databaseTarget = 'user:' . $user->getKey();
                $databaseKey = $this->dispatchStore->makeDedupeKey('pending_lifecycle.' . $payload['event'], 'database', $databaseTarget, $pendiente, $versionBucket);

                if (! $this->dispatchStore->wasSent($databaseKey)) {
                    $user->notify(new PendingLifecycleDatabaseNotification($pendiente, $payload));

                    $this->dispatchStore->record(
                        'pending_lifecycle.' . $payload['event'],
                        'database',
                        $databaseTarget,
                        $pendiente,
                        $databaseKey,
                        $this->buildDispatchMeta($payload, 'database_user'),
                    );
                }
            }
        }

        foreach ($additionalEmails as $email) {
            $emailKey = $this->dispatchStore->makeDedupeKey('pending_lifecycle.' . $payload['event'], 'mail', $email, $pendiente, $versionBucket);

            if ($this->dispatchStore->wasSent($emailKey)) {
                continue;
            }

            Notification::route('mail', $email)->notify($mailNotification);

            $this->dispatchStore->record(
                'pending_lifecycle.' . $payload['event'],
                'mail',
                $email,
                $pendiente,
                $emailKey,
                $this->buildDispatchMeta($payload, 'fallback_email'),
            );
        }
    }

    protected function buildDispatchMeta(array $payload, string $recipientType): array
    {
        return [
            'event' => $payload['event'],
            'subject' => $payload['subject'],
            'summary' => $payload['summary'],
            'details' => $payload['details'],
            'actor_name' => $payload['actor_name'] ?? null,
            'url' => $payload['url'] ?? null,
            'recipient_type' => $recipientType,
        ];
    }

    protected function resolveUsers(Pendiente $pendiente, ?User $previousUser = null): Collection
    {
        $users = collect();

        if (config('alerts.pending_lifecycle.include_assigned_user_email', true) && filled($pendiente->user_id)) {
            $currentUser = $this->resolveUser($pendiente->user_id);

            if ($currentUser) {
                $users->push($currentUser);
            }
        }

        if (
            config('alerts.pending_lifecycle.include_previous_assigned_user_email', true)
            && $previousUser
            && $previousUser->getKey() !== $pendiente->user_id
        ) {
            $users->push($previousUser);
        }

        return $users
            ->filter()
            ->unique(fn (User $user): int => $user->getKey())
            ->values();
    }

    protected function resolveAdditionalEmails(Pendiente $pendiente, ?User $previousUser = null): array
    {
        $userEmails = $this->resolveUsers($pendiente, $previousUser)
            ->pluck('email')
            ->filter()
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->all();

        $emails = config('alerts.fallback_emails', []);

        if (config('alerts.pending_lifecycle.include_client_email', false) && filled($pendiente->client?->email)) {
            $emails[] = $pendiente->client->email;
        }

        return collect($emails)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->reject(fn (string $email): bool => in_array(mb_strtolower($email), $userEmails, true))
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveUser(mixed $userId): ?User
    {
        if (blank($userId)) {
            return null;
        }

        return User::query()
            ->select(['id', 'name', 'email'])
            ->find($userId);
    }

    protected function resolveActorName(Pendiente $pendiente): string
    {
        return auth()->user()?->name
            ?? $pendiente->assignedBy?->name
            ?? $pendiente->reviewedBy?->name
            ?? 'Sistema';
    }

    protected function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return 'Sin fecha';
        }

        return Carbon::parse($value)->format('d/m/Y');
    }

    protected function formatPriority(?string $value): string
    {
        return Pendiente::getPriorityOptions()[$value] ?? 'Sin definir';
    }
}

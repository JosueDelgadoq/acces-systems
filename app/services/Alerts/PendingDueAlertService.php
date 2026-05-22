<?php

namespace App\Services\Alerts;

use App\Models\Pendiente;
use App\Notifications\PendingDueAlertNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

class PendingDueAlertService
{
    public function __construct(
        protected AlertDispatchStore $dispatchStore,
        protected WhatsAppAlertSender $whatsAppAlertSender,
    ) {
    }

    public function process(): array
    {
        if (! config('alerts.pending_due.enabled', true)) {
            return [
                'processed' => 0,
                'mail' => 0,
                'whatsapp' => 0,
                'database' => 0,
            ];
        }

        $summary = [
            'processed' => 0,
            'mail' => 0,
            'whatsapp' => 0,
            'database' => 0,
        ];

        $this->getDuePendientes()->each(function (Pendiente $pendiente) use (&$summary): void {
            $result = $this->sendForPendiente($pendiente);

            $summary['processed']++;
            $summary['mail'] += $result['mail'];
            $summary['whatsapp'] += $result['whatsapp'];
            $summary['database'] += $result['database'];
        });

        return $summary;
    }

    public function sendTest(Pendiente $pendiente, string $email, ?string $phone = null): array
    {
        return $this->sendForPendiente(
            $pendiente,
            emails: [$email],
            phones: filled($phone) ? [$phone] : [],
            force: true,
            context: 'test',
            sendDatabase: false,
        );
    }

    public function sendForPendiente(
        Pendiente $pendiente,
        ?array $emails = null,
        ?array $phones = null,
        bool $force = false,
        ?string $context = null,
        ?bool $sendDatabase = null,
    ): array {
        $pendiente->loadMissing([
            'client:id,name,email,phone',
            'user:id,name,email',
            'user.tecnico:id,user_id,telefono',
        ]);

        $context ??= $this->resolveContext($pendiente);
        $notification = new PendingDueAlertNotification($pendiente, $context);
        $alertKey = "pending_due.{$context}";
        $bucket = $force
            ? now()->format('Y-m-d-H-i-s-u')
            : now()->toDateString();

        $result = [
            'mail' => 0,
            'whatsapp' => 0,
            'database' => 0,
        ];

        foreach (($emails ?? $this->resolveEmails($pendiente)) as $email) {
            $email = trim((string) $email);

            if ($email === '') {
                continue;
            }

            $dedupeKey = $this->dispatchStore->makeDedupeKey($alertKey, 'mail', $email, $pendiente, $bucket);

            if (! $force && $this->dispatchStore->wasSent($dedupeKey)) {
                continue;
            }

            Notification::route('mail', $email)->notify($notification);

            $this->dispatchStore->record($alertKey, 'mail', $email, $pendiente, $dedupeKey, [
                'context' => $context,
            ]);

            $result['mail']++;
        }

        foreach (($phones ?? $this->resolvePhones($pendiente)) as $phone) {
            $phone = $this->normalizePhone((string) $phone);

            if ($phone === null) {
                continue;
            }

            $dedupeKey = $this->dispatchStore->makeDedupeKey($alertKey, 'whatsapp', $phone, $pendiente, $bucket);

            if (! $force && $this->dispatchStore->wasSent($dedupeKey)) {
                continue;
            }

            $this->whatsAppAlertSender->send($phone, $notification->toWhatsAppText(), [
                'pendiente_id' => $pendiente->id,
                'context' => $context,
            ]);

            $this->dispatchStore->record($alertKey, 'whatsapp', $phone, $pendiente, $dedupeKey, [
                'context' => $context,
            ]);

            $result['whatsapp']++;
        }

        $sendDatabase ??= config('alerts.pending_due.send_database_notification', true);

        if ($sendDatabase && $pendiente->user) {
            $databaseTarget = 'user:' . $pendiente->user->getKey();
            $dedupeKey = $this->dispatchStore->makeDedupeKey($alertKey, 'database', $databaseTarget, $pendiente, $bucket);

            if ($force || ! $this->dispatchStore->wasSent($dedupeKey)) {
                $pendiente->user->notify($notification);

                $this->dispatchStore->record($alertKey, 'database', $databaseTarget, $pendiente, $dedupeKey, [
                    'context' => $context,
                ]);

                $result['database']++;
            }
        }

        return $result;
    }

    protected function getDuePendientes(): Collection
    {
        return Pendiente::query()
            ->with([
                'client:id,name,email,phone',
                'user:id,name,email',
                'user.tecnico:id,user_id,telefono',
            ])
            ->whereIn('status', [
                Pendiente::STATUS_PENDING,
                Pendiente::STATUS_IN_PROGRESS,
            ])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today())
            ->orderBy('due_date')
            ->get();
    }

    protected function resolveContext(Pendiente $pendiente): string
    {
        if (! $pendiente->due_date) {
            return 'test';
        }

        return $pendiente->due_date->isBefore(today()) ? 'overdue' : 'due_today';
    }

    protected function resolveEmails(Pendiente $pendiente): array
    {
        $emails = [];

        if (config('alerts.pending_due.include_assigned_user_email', true) && filled($pendiente->user?->email)) {
            $emails[] = $pendiente->user->email;
        }

        if (config('alerts.pending_due.include_client_email', false) && filled($pendiente->client?->email)) {
            $emails[] = $pendiente->client->email;
        }

        return collect(array_merge($emails, config('alerts.fallback_emails', [])))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function resolvePhones(Pendiente $pendiente): array
    {
        $phones = [];

        if (config('alerts.pending_due.include_assigned_user_whatsapp', true) && filled($pendiente->user?->tecnico?->telefono)) {
            $phones[] = $pendiente->user->tecnico->telefono;
        }

        if (config('alerts.pending_due.include_client_whatsapp', false) && filled($pendiente->client?->phone)) {
            $phones[] = $pendiente->client->phone;
        }

        return collect(array_merge($phones, config('alerts.fallback_whatsapp', [])))
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizePhone(string $value): ?string
    {
        $normalized = preg_replace('/[^\d+]/', '', $value);

        if (blank($normalized)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $normalized);

        return strlen((string) $digits) >= 8 ? $normalized : null;
    }
}

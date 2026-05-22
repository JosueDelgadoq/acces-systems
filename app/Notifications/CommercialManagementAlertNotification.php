<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommercialManagementAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected array $payload,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject('Resumen diario de alertas comerciales')
            ->greeting('Resumen comercial')
            ->line('Se detectaron ' . $this->payload['affected_count'] . ' responsables con foco comercial en el mes ' . $this->payload['month_label'] . '.')
            ->line('Progreso esperado del mes: ' . $this->payload['progress_label'] . '.');

        foreach ($this->payload['rows'] as $row) {
            $parts = [];

            if ($row['goal_alert']) {
                $parts[] = 'cumplimiento ' . $row['attainment_label'];
            }

            if ($row['overdue_followups'] > 0) {
                $parts[] = $row['overdue_followups_label'] . ' seguimientos vencidos';
            }

            if ($row['stale_leads'] > 0) {
                $parts[] = $row['stale_leads_label'] . ' leads sin ritmo';
            }

            $message->line($row['commercial_name'] . ': ' . implode(', ', $parts) . '.');
        }

        return $message
            ->action('Abrir tablero comercial', $this->payload['url'])
            ->line('Este correo fue generado automaticamente por el sistema de alertas del ERP.');
    }
}

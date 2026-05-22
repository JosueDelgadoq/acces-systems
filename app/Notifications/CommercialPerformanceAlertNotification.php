<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommercialPerformanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected array $snapshot,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject('Alerta comercial: foco de gestion para ' . $this->snapshot['commercial_name'])
            ->greeting('Alerta comercial')
            ->line('Se detectaron desvios o riesgos en tu cartera del mes ' . $this->snapshot['month_label'] . '.');

        if ($this->snapshot['goal_alert']) {
            $message
                ->line('Progreso esperado del mes: ' . $this->snapshot['progress_label'])
                ->line('Cumplimiento actual: ' . $this->snapshot['attainment_label'])
                ->line('Leads: ' . $this->snapshot['actual_leads_label'] . ' de ' . $this->snapshot['target_leads_label'] . ' | Esperado a hoy: ' . $this->snapshot['expected_leads_label'])
                ->line('Ventas: ' . $this->snapshot['actual_sales_label'] . ' de ' . $this->snapshot['target_sales_label'] . ' | Esperado a hoy: ' . $this->snapshot['expected_sales_label'])
                ->line('Facturacion: ' . $this->snapshot['actual_revenue_label'] . ' de ' . $this->snapshot['target_revenue_label'] . ' | Esperado a hoy: ' . $this->snapshot['expected_revenue_label']);
        }

        if ($this->snapshot['overdue_followups'] > 0) {
            $message->line('Seguimientos vencidos: ' . $this->snapshot['overdue_followups_label']);
        }

        if ($this->snapshot['stale_leads'] > 0) {
            $message->line('Leads sin movimiento > ' . $this->snapshot['stale_days'] . ' dias: ' . $this->snapshot['stale_leads_label']);
        }

        return $message
            ->action('Abrir metricas comerciales', $this->snapshot['url'])
            ->line('Este correo fue generado automaticamente por el tablero comercial del ERP.');
    }
}

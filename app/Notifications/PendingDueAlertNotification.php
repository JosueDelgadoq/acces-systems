<?php

namespace App\Notifications;

use App\Models\Pendiente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingDueAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Pendiente $pendiente,
        protected string $context = 'due_today',
    ) {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $this->pendiente->client?->name ?? 'Sin cliente';
        $technicianName = $this->pendiente->user?->name ?? 'Sin asignar';
        $dueDate = optional($this->pendiente->due_date)->format('d/m/Y') ?? 'Sin fecha';

        return (new MailMessage())
            ->subject($this->subject())
            ->greeting('Alerta de pendiente')
            ->line($this->introLine())
            ->line("Cliente: {$clientName}")
            ->line("Tecnico: {$technicianName}")
            ->line("Fecha limite: {$dueDate}")
            ->line('Estado actual: ' . Pendiente::getStatusLabel($this->pendiente->status))
            ->line('Detalle: ' . (filled($this->pendiente->description) ? $this->pendiente->description : 'Sin detalle'))
            ->action('Abrir pendiente', $this->url())
            ->line('Este aviso fue generado automaticamente por el sistema de alertas.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->subject(),
            'body' => $this->introLine(),
            'icon' => 'heroicon-o-bell-alert',
            'color' => $this->context === 'overdue' ? 'danger' : 'warning',
            'actions' => [
                [
                    'label' => 'Ver pendiente',
                    'url' => $this->url(),
                ],
            ],
            'pendiente_id' => $this->pendiente->id,
            'client_name' => $this->pendiente->client?->name,
            'due_date' => optional($this->pendiente->due_date)->toDateString(),
            'context' => $this->context,
        ];
    }

    public function toWhatsAppText(): string
    {
        $clientName = $this->pendiente->client?->name ?? 'Sin cliente';
        $dueDate = optional($this->pendiente->due_date)->format('d/m/Y') ?? 'Sin fecha';
        $label = $this->context === 'overdue' ? 'VENCIDO' : ($this->context === 'test' ? 'PRUEBA' : 'VENCE HOY');

        return trim(implode(PHP_EOL, [
            "[{$label}] Pendiente #{$this->pendiente->id}",
            "Cliente: {$clientName}",
            'Estado: ' . Pendiente::getStatusLabel($this->pendiente->status),
            "Fecha limite: {$dueDate}",
            'Detalle: ' . (filled($this->pendiente->description) ? $this->pendiente->description : 'Sin detalle'),
            'Link: ' . $this->url(),
        ]));
    }

    protected function subject(): string
    {
        return match ($this->context) {
            'overdue' => 'Alerta: pendiente vencido',
            'test' => 'Prueba de alerta de pendiente',
            default => 'Alerta: pendiente con vencimiento hoy',
        };
    }

    protected function introLine(): string
    {
        return match ($this->context) {
            'overdue' => 'Hay un pendiente vencido que sigue abierto y requiere seguimiento.',
            'test' => 'Este es un envio de prueba del sistema de alertas de pendientes.',
            default => 'Hay un pendiente cuya fecha limite es hoy y requiere atencion.',
        };
    }

    protected function url(): string
    {
        return url('/admin/pendientes/' . $this->pendiente->id . '/edit');
    }
}

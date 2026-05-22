<?php

namespace App\Notifications;

use App\Models\Pendiente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingLifecycleMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Pendiente $pendiente,
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
            ->subject($this->payload['subject'])
            ->greeting('Actualizacion de pendiente')
            ->line($this->payload['summary'])
            ->line('Pendiente #' . $this->pendiente->id)
            ->line('Cliente: ' . ($this->pendiente->client?->name ?? 'Sin cliente'))
            ->line('Estado: ' . Pendiente::getStatusLabel($this->pendiente->status));

        foreach ($this->payload['details'] as $detail) {
            $message->line($detail);
        }

        if (filled($this->payload['actor_name'] ?? null)) {
            $message->line('Responsable del cambio: ' . $this->payload['actor_name']);
        }

        return $message
            ->action('Abrir pendiente', $this->payload['url'])
            ->line('Este correo fue generado automaticamente por el flujo operativo del ERP.');
    }
}

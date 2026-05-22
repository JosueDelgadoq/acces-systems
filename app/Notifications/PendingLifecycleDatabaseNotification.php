<?php

namespace App\Notifications;

use App\Models\Pendiente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PendingLifecycleDatabaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Pendiente $pendiente,
        protected array $payload,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->payload['subject'],
            'body' => $this->payload['summary'],
            'icon' => $this->payload['icon'],
            'color' => $this->payload['color'],
            'actions' => [
                [
                    'label' => 'Ver pendiente',
                    'url' => $this->payload['url'],
                ],
            ],
            'pendiente_id' => $this->pendiente->id,
            'event' => $this->payload['event'],
            'details' => $this->payload['details'],
            'actor_name' => $this->payload['actor_name'],
        ];
    }
}

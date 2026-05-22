<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LeadAlertNotification extends Notification
{
    protected $lead;

    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    public function via($notifiable)
    {
        return ['database']; // 👈 clave para Filament
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Lead sin contacto',
            'body' => 'El lead ' . $this->lead->nombre . ' requiere seguimiento',
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'warning',

            // 🔥 ESTO ES CLAVE PARA QUE SEA CLICKABLE
            'actions' => [
                [
                    'label' => 'Ver Lead',
                    'url' => url('/admin/leads/' . $this->lead->id . '/edit'),
                ],
            ],
        ];
    }
}
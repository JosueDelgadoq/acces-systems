<?php

namespace App\Observers;

use App\Models\Habilitation;
use App\Models\EquipmentDelivery;
use App\Jobs\CheckHabilitationDueDate;

class HabilitationObserver
{
    public function created(Habilitation $habilitation): void
    {
        // Cuando se crea una habilitación, notificar al responsable
        // Esto podría enviar un email o notificación interna
    }

    public function updated(Habilitation $habilitation): void
    {
        // Workflow automático: Cuando se aprueba la habilitación
        if ($habilitation->status === 'aprobado' && $habilitation->wasChanged('status')) {
            // Crear automáticamente registro de logística
            EquipmentDelivery::create([
                'client_id' => $habilitation->client_id,
                'equipment' => $habilitation->equipment,
                'status' => 'pendiente_confirmacion',
            ]);
        }

        // Cuando se envía al gestor, programar seguimiento
        if ($habilitation->status === 'enviado_gestor' && $habilitation->wasChanged('status')) {
            if (!$habilitation->proxima_gestion) {
                $habilitation->update([
                    'proxima_gestion' => now()->addDays(7)
                ]);
            }
        }
    }

    public function deleted(Habilitation $habilitation): void
    {
        //
    }
}


<?php

namespace App\Observers;

use App\Models\Claim;
use App\Models\BillingControl;

class ClaimObserver
{
    public function created(Claim $claim): void
    {
        // Notificar al técnico asignado
    }

    public function updated(Claim $claim): void
    {
        // Workflow: Cuando se cierra el reclamo → crear control de facturación si hay costo
        if ($claim->closed_at && !$claim->wasChanged('closed_at')) {
            // El reclamo ya estaba cerrado, no hacer nada
        }

        // Si se resuelve el reclamo, podría generarse facturación por el servicio
        if ($claim->status === 'resuelto' && $claim->wasChanged('status')) {
            // Notificar al cliente sobre la resolución
        }
    }

    public function deleted(Claim $claim): void
    {
        //
    }
}


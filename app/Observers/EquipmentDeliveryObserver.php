<?php

namespace App\Observers;

use App\Models\EquipmentDelivery;
use App\Models\BillingControl;

class EquipmentDeliveryObserver
{
    public function created(EquipmentDelivery $delivery): void
    {
        //
    }

    public function updated(EquipmentDelivery $delivery): void
    {
        // Workflow: Cuando se entrega el equipo → crear registro de facturación
        if ($delivery->status === 'entregado' && $delivery->wasChanged('status')) {
            // Crear automáticamente control de facturación
            BillingControl::create([
                'client_id' => $delivery->client_id,
                'service_description' => 'Entrega de equipo: ' . $delivery->equipment,
                'amount' => 0, // Se actualiza después
                'invoiced' => false,
                'paid' => false,
            ]);
        }

        // Workflow: Cuando está instalado completamente → notificar al cliente
        if ($delivery->status === 'instalado' && $delivery->wasChanged('status')) {
            // Aquí se podría enviar notificación al cliente
        }
    }

    public function deleted(EquipmentDelivery $delivery): void
    {
        //
    }
}


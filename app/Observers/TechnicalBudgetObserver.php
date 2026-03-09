<?php

namespace App\Observers;

use App\Models\TechnicalBudget;
use App\Models\Ticket;

class TechnicalBudgetObserver
{
    public function created(TechnicalBudget $budget): void
    {
        // Crear ticket automáticamente cuando se aprueba el presupuesto
    }

    public function updated(TechnicalBudget $budget): void
    {
        // Workflow: Cuando se aprueba el presupuesto → crear ticket de servicio
        if ($budget->status === 'aprobado' && $budget->wasChanged('status')) {
            // Crear ticket de seguimiento
            Ticket::create([
                'client_id' => $budget->client_id,
                'title' => 'Visita Técnica - ' . $budget->title,
                'description' => 'Presupuesto aprobado. ' . $budget->description,
                'status' => 'pending',
                'priority' => 'medium',
            ]);
        }

        // Workflow: Cuando se cierra → marcar como archive
        if ($budget->status === 'cerrado' && $budget->wasChanged('status')) {
            // Notificar al cliente que el proceso terminó
        }
    }

    public function deleted(TechnicalBudget $budget): void
    {
        //
    }
}


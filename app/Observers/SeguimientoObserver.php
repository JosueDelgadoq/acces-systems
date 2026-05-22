<?php

namespace App\Observers;

use App\Models\Seguimiento;
use App\Services\LeadHistoryService;

class SeguimientoObserver
{
    public function __construct(
        protected LeadHistoryService $history,
    ) {
    }

    public function creating(Seguimiento $seguimiento): void
    {
        $seguimiento->comercial_id ??= auth()->id();
        $seguimiento->estado ??= Seguimiento::STATUS_PENDIENTE;
    }

    public function created(Seguimiento $seguimiento): void
    {
        $this->touchLead($seguimiento);
        $this->history->recordSeguimientoCreated($seguimiento->loadMissing('lead'));
    }

    public function updated(Seguimiento $seguimiento): void
    {
        $this->touchLead($seguimiento);
        $this->history->recordSeguimientoUpdated($seguimiento->loadMissing('lead'));
    }

    protected function touchLead(Seguimiento $seguimiento): void
    {
        $lead = $seguimiento->lead;

        if (! $lead) {
            return;
        }

        $lead->forceFill([
            'fecha_ultimo_seguimiento' => $seguimiento->fecha_contacto ?? now()->toDateString(),
        ])->saveQuietly();
    }
}

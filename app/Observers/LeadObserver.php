<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\Seguimiento;
use App\Services\LeadHistoryService;
use Illuminate\Support\Facades\Auth;

class LeadObserver
{
    public function __construct(
        protected LeadHistoryService $history,
    ) {
    }

    public function creating(Lead $lead): void
    {
        $lead->created_by ??= Auth::id();
        $lead->comercial_asignado_id ??= Auth::id();
        $lead->fecha_ultimo_seguimiento ??= now()->toDateString();

        $this->normalizeLifecycle($lead);
    }

    public function updating(Lead $lead): void
    {
        $this->normalizeLifecycle($lead);
    }

    public function created(Lead $lead): void
    {
        Seguimiento::query()->create([
            'lead_id' => $lead->id,
            'comercial_id' => $lead->comercial_asignado_id,
            'medio_contacto' => $this->resolveInitialFollowUpMedium($lead),
            'proxima_accion' => 'Primer contacto',
            'fecha_proxima_accion' => now()->addDay(),
            'estado' => Seguimiento::STATUS_PENDIENTE,
            'observaciones' => 'Lead recien ingresado. Se programa contacto inicial.',
        ]);

        $this->history->recordCreated($lead->loadMissing('comercialAsignado'));
    }

    public function updated(Lead $lead): void
    {
        $this->history->recordUpdated($lead->loadMissing('comercialAsignado'));
    }

    protected function normalizeLifecycle(Lead $lead): void
    {
        $lead->estado_pipeline ??= Lead::STAGE_INGRESADO;
        $lead->resultado_final ??= Lead::RESULTADO_ABIERTO;

        if (! in_array($lead->estado_pipeline, Lead::CLOSED_PIPELINES, true)) {
            $lead->resultado_final = Lead::RESULTADO_ABIERTO;
            $lead->fecha_cierre = null;
            $lead->motivo_perdida = null;

            return;
        }

        $lead->fecha_cierre ??= now()->toDateString();

        if ($lead->estado_pipeline === Lead::STAGE_VENTA_CERRADA) {
            $lead->resultado_final = Lead::RESULTADO_VENDIDO;
            $lead->motivo_perdida = null;
        }

        if ($lead->estado_pipeline === Lead::STAGE_PERDIDO) {
            $lead->resultado_final = Lead::RESULTADO_PERDIDO;
        }
    }

    protected function resolveInitialFollowUpMedium(Lead $lead): string
    {
        return match ($lead->canal_origen) {
            'telefono' => 'llamada_anura_ip',
            'mail' => 'mail',
            default => 'whatsapp',
        };
    }
}

<?php

namespace App\Jobs;

use App\Models\Habilitation;
use App\Models\Conservation;
use App\Models\TechnicalBudget;
use App\Models\EquipmentDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDueDatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Este job se ejecuta diariamente para verificar fechas de seguimiento
     */
    public function handle(): void
    {
        $today = now()->toDateString();

        // 1. Verificar habilitaciones con gestión próxima
        $habilitations = Habilitation::where('proxima_gestion', $today)
            ->whereIn('status', ['documentacion', 'enviado_gestor', 'en_tramite'])
            ->get();

        foreach ($habilitations as $habilitation) {
            // Aquí se podría enviar notificación
            // Por ejemplo: notificar que hoy hay gestión pendiente
        }

        // 2. Verificar conservaciones con servicio próximo
        $conservations = Conservation::where('next_service_date', $today)->get();

        foreach ($conservations as $conservation) {
            // Crear ticket de servicio automáticamente
            // O notificar al cliente
        }

        // 3. Verificar presupuestos sin respuesta (3-5 días)
        $budgets = TechnicalBudget::where('sent_at', '<=', now()->subDays(5))
            ->whereIn('status', ['enviado'])
            ->get();

        foreach ($budgets as $budget) {
            // Notificar que el presupuesto necesita seguimiento
        }

        // 4. Verificar entregas atrasadas
        $deliveries = EquipmentDelivery::where('delivery_date', '<', $today)
            ->whereIn('status', ['confirmado', 'flete_solicitado', 'en_camino'])
            ->get();

        foreach ($deliveries as $delivery) {
            // Notificar atraso en entrega
        }
    }
}


<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Conservation;
use Illuminate\Support\Collection;
use App\Models\PendienteView;
class PendientesService
{
    public static function get(): Collection
    {
        $pendientes = collect();

        // 🔴 RECLAMOS
        foreach (Claim::where('status', 'pending')->get() as $claim) {
            $pendientes->push([
                'id' => uniqid(),
                'type' => 'Reclamo',
                'client' => $claim->client->name ?? 'Sin cliente',
                'description' => $claim->title ?? 'Reclamo sin título',
                'due_date' => $claim->created_at,
                'priority' => 'media',
            ]);
        }

        // 🔴 CONSERVACIONES VENCIDAS
        foreach (Conservation::schedulable()->where('next_service_date', '<', now())->get() as $c) {
            $pendientes->push([
                'id' => uniqid(),
                'type' => 'Conservación',
                'client' => $c->client->name ?? 'Sin cliente',
                'description' => 'Conservación vencida',
                'due_date' => $c->next_service_date,
                'priority' => 'alta',
            ]);
        }

        // 🟡 PRÓXIMAS (3 días)
        foreach (
            Conservation::schedulable()->whereBetween('next_service_date', [now(), now()->addDays(3)])->get()
            as $c
        ) {
            $pendientes->push([
                'id' => uniqid(),
                'type' => 'Conservación',
                'client' => $c->client->name ?? 'Sin cliente',
                'description' => 'Próxima conservación',
                'due_date' => $c->next_service_date,
                'priority' => 'media',
            ]);
        }

        return $pendientes->sortBy('due_date')->values();
    }
}

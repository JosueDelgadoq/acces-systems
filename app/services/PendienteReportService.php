<?php

namespace App\Services;

use App\Models\Pendiente;
use Barryvdh\DomPDF\Facade\Pdf;

class PendienteReportService
{
    /**
     * EXPORT CSV (simple y limpio)
     */
    public function export(array $filters = [])
    {
        $pendientes = $this->getQuery($filters)->get();

        $filename = 'reporte_pendientes_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($pendientes) {
            $file = fopen('php://output', 'w');

            // BOM para Excel (MUY IMPORTANTE)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Cliente',
                'Tipo',
                'Estado',
                'Prioridad',
                'Tecnico',
                'Fecha carga',
                'Fecha realizada',
                'Fecha cierre',
                'Remito',
            ]);

            foreach ($pendientes as $p) {
                fputcsv($file, [
                    $p->client?->name,
                    ucfirst($p->type),
                    \App\Models\Pendiente::getStatusLabel($p->status),
                    ucfirst($p->priority),
                    $p->user?->name,
                    optional($p->created_at)->format('d/m/Y H:i'),
                    optional($p->performed_at)->format('d/m/Y'),
                    optional($p->completed_at)->format('d/m/Y'),
                    $p->remito,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * PDF SIMPLE
     */
    public function pdf()
    {
        return $this->generate();
    }

    /**
     * PDF CON FILTROS (EL IMPORTANTE)
     */
    public function generate(array $filters = [])
    {
        $pendientes = $this->getQuery($filters)->get();

        // KPIs
        $total = $pendientes->count();
        $active = $pendientes->whereIn('status', ['pending', 'in_progress'])->count();
        $completed = $pendientes->where('status', 'completed')->count();
        $cancelled = $pendientes->where('status', 'cancelled')->count();

        $byTechnician = $pendientes
            ->groupBy(fn ($p) => $p->user?->name ?? 'Sin asignar')
            ->map->count();

        $pdf = Pdf::loadView('reports.pendientes', [
            'pendientes' => $pendientes,
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'byTechnician' => $byTechnician,
            'filters' => $filters,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'reporte_pendientes_' . now()->format('Ymd_His') . '.pdf'
        );
    }

    /**
     * QUERY CENTRAL (CLAVE PARA ESCALAR)
     */
    private function getQuery(array $filters)
    {
        $query = Pendiente::with(['client', 'user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }
}
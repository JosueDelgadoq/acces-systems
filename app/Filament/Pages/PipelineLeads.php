<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Lead;
use BackedEnum;

class PipelineLeads extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected ?string $heading = 'Pipeline Comercial';

    protected string $view = 'filament.pages.pipeline-leads';

    public function getColumns(): array
    {
        return [
            'Ingresado',
            'Contactado',
            'Orientacion dada',
            'Cotizacion enviada',
            'Presupuesto definitivo enviado',
            'Venta cerrada',
        ];
    }

    public function getLeads()
    {
        return Lead::orderBy('fecha_ingreso', 'desc')
            ->get()
            ->groupBy('estado_pipeline');
    }

    public function moveLead($leadId, $newStatus): void
    {
        $lead = Lead::find($leadId);

        if ($lead) {
            $lead->update([
                'estado_pipeline' => $newStatus,
            ]);
        }
    }
}
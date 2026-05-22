<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PipelineWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 6;

    protected function getStats(): array
    {
        $counts = Lead::query()
            ->select('estado_pipeline', DB::raw('COUNT(*) as total'))
            ->whereIn('estado_pipeline', [
                'Ingresado',
                'Contactado',
                'Cotizacion enviada',
                'Venta cerrada',
            ])
            ->groupBy('estado_pipeline')
            ->pluck('total', 'estado_pipeline');

        return [
            Stat::make('Ingresados', (int) ($counts['Ingresado'] ?? 0)),
            Stat::make('Contactados', (int) ($counts['Contactado'] ?? 0)),
            Stat::make('Cotizacion', (int) ($counts['Cotizacion enviada'] ?? 0)),
            Stat::make('Cerrados', (int) ($counts['Venta cerrada'] ?? 0)),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SalesFunnel extends BaseWidget
{
    protected function getStats(): array
    {
        $counts = Lead::query()
            ->select('estado_pipeline', DB::raw('COUNT(*) as total'))
            ->whereIn('estado_pipeline', [
                'Ingresado',
                'Contactado',
                'Orientacion dada',
                'Cotizacion enviada',
                'Venta cerrada',
            ])
            ->groupBy('estado_pipeline')
            ->pluck('total', 'estado_pipeline');

        $ingresados = (int) ($counts['Ingresado'] ?? 0);
        $contactados = (int) ($counts['Contactado'] ?? 0);
        $orientacion = (int) ($counts['Orientacion dada'] ?? 0);
        $cotizacion = (int) ($counts['Cotizacion enviada'] ?? 0);
        $ventas = (int) ($counts['Venta cerrada'] ?? 0);

        return [
            Stat::make('Ingresados', $ingresados)->color('gray'),
            Stat::make('Contactados', $contactados)->color('info'),
            Stat::make('OrientaciÃ³n', $orientacion)->color('success'),
            Stat::make('Cotizaciones', $cotizacion)->color('warning'),
            Stat::make('Ventas', $ventas)->color('success'),
        ];
    }
}

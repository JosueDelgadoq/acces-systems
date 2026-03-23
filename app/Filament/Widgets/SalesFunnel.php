<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesFunnel extends BaseWidget
{
    protected function getStats(): array
    {
        $ingresados = Lead::where('estado_pipeline', 'Ingresado')->count();
        $contactados = Lead::where('estado_pipeline', 'Contactado')->count();
        $orientacion = Lead::where('estado_pipeline', 'Orientacion dada')->count();
        $cotizacion = Lead::where('estado_pipeline', 'Cotizacion enviada')->count();
        $ventas = Lead::where('estado_pipeline', 'Venta cerrada')->count();

        return [

            Stat::make('Ingresados', $ingresados)
                ->color('gray'),

            Stat::make('Contactados', $contactados)
                ->color('info'),

            Stat::make('Orientación', $orientacion)
                ->color('success'),

            Stat::make('Cotizaciones', $cotizacion)
                ->color('warning'),

            Stat::make('Ventas', $ventas)
                ->color('success'),

        ];
    }
}
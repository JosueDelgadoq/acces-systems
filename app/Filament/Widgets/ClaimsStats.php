<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Icons\Heroicon;

class ClaimsStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Reclamos Nuevos', Claim::where('status', 'nuevo')->count())
                ->description('Reclamos sin atender')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->chart([7, 3, 5, 8, 4, 6, 9]),

            Stat::make('En Proceso', Claim::where('status', 'en_proceso')->count())
                ->description('Siendo atendidos')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning')
                ->chart([5, 7, 6, 8, 5, 7, 6]),

            Stat::make('Resueltos', Claim::where('status', 'resuelto')->count())
                ->description('Completados')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->chart([10, 12, 15, 18, 20, 22, 25]),

            Stat::make('Visitas Hoy', Claim::whereDate('scheduled_visit', today())->count())
                ->description('Programadas para hoy')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('info'),
        ];
    }
}

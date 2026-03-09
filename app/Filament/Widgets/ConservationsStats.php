<?php

namespace App\Filament\Widgets;

use App\Models\Conservation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Icons\Heroicon;

class ConservationsStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Contratos Activos', Conservation::count())
                ->description('Total de contratos')
                ->descriptionIcon(Heroicon::OutlinedDocumentCheck)
                ->color('success')
                ->chart([15, 18, 20, 22, 25, 28, 30]),

            Stat::make('Por Vencer', Conservation::whereBetween(
                'expiration_date',
                [now(), now()->addDays(30)]
            )->count())
                ->description('En los próximos 30 días')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('warning')
                ->chart([2, 3, 4, 3, 5, 4, 3]),

            Stat::make('Vencidos', Conservation::where('expiration_date', '<', now())->count())
                ->description('Requieren renovación')
                ->descriptionIcon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->chart([1, 2, 1, 3, 2, 1, 2]),

            Stat::make('Servicios Esta Semana', Conservation::whereBetween(
                'next_service_date',
                [now(), now()->addDays(7)]
            )->count())
                ->description('Próximos servicios')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('info')
                ->chart([5, 6, 4, 7, 5, 6, 8]),
        ];
    }
}

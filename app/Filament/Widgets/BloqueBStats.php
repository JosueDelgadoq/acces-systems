<?php

namespace App\Filament\Widgets;

use App\Models\TechnicalBudget;
use App\Models\EquipmentDelivery;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Icons\Heroicon;

class BloqueBStats extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected function getStats(): array
    {
        return [
            Stat::make('Presupuestos', TechnicalBudget::count())
                ->description('Total presupuestos')
                ->descriptionIcon(Heroicon::OutlinedCurrencyDollar)
                ->color('info')
                ->chart([3, 5, 7, 8, 10, 12, 15]),

            Stat::make('Aprobados', TechnicalBudget::where('status', 'aprobado')->count())
                ->description('Aprobados por cliente')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Entregas', EquipmentDelivery::count())
                ->description('Equipos entregados')
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color('info')
                ->chart([2, 4, 6, 5, 8, 10, 12]),

            Stat::make('Sin Entregar', EquipmentDelivery::whereNotIn('status', ['entregado', 'instalado'])->count())
                ->description('Pendientes')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning'),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'BLOQUE B - Operaciones';
    }
}


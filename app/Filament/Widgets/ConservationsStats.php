<?php

namespace App\Filament\Widgets;

use App\Models\Conservation;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConservationsStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                'Contratos activos',
                Conservation::activeContracts()->count(),
            )
                ->description('Contratos vigentes')
                ->descriptionIcon(Heroicon::OutlinedDocumentCheck)
                ->color('success'),

            Stat::make(
                'Servicios atrasados',
                Conservation::schedulable()
                    ->where('next_service_date', '<', now())
                    ->count(),
            )
                ->description('Requieren acción inmediata')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),

            Stat::make(
                'Por vencer',
                Conservation::activeContracts()
                    ->whereBetween('expiration_date', [now(), now()->addDays(30)])
                    ->count(),
            )
                ->description('Próximos 30 días')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('warning'),

            Stat::make(
                'Renovar contratos',
                Conservation::dueForRenewal()->count(),
            )
                ->description('Ciclos agotados o contratos vencidos')
                ->descriptionIcon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->url(fn () => route('filament.admin.resources.conservations.index', [
                    'tableFilters[requieren_renovacion][isActive]' => true,
                ])),

            Stat::make(
                'Servicios esta semana',
                Conservation::schedulable()
                    ->whereBetween('next_service_date', [now(), now()->addDays(7)])
                    ->count(),
            )
                ->description('Mantenimientos programados')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('info'),
        ];
    }
}

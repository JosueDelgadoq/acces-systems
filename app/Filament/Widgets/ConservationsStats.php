<?php

namespace App\Filament\Widgets;

use App\Models\Conservation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConservationsStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make('Contratos activos', Conservation::count())
                ->color('success'),

            Stat::make(
                'Contratos por vencer',
                Conservation::whereBetween(
                    'expiration_date',
                    [now(), now()->addDays(30)]
                )->count()
            )
                ->color('warning'),

            Stat::make(
                'Contratos vencidos',
                Conservation::where('expiration_date', '<', now())->count()
            )
                ->color('danger'),

            Stat::make(
                'Servicios esta semana',
                Conservation::whereBetween(
                    'next_service_date',
                    [now(), now()->addDays(7)]
                )->count()
            )
                ->color('info'),

        ];
    }
}
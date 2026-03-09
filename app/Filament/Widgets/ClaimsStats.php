<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClaimsStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make('Reclamos nuevos', Claim::where('status','nuevo')->count())
                ->color('danger'),

            Stat::make('En proceso', Claim::where('status','en_proceso')->count())
                ->color('warning'),

            Stat::make('Resueltos', Claim::where('status','resuelto')->count())
                ->color('success'),

            Stat::make(
                'Visitas hoy',
                Claim::whereDate('scheduled_visit', today())->count()
            )->color('info'),

        ];
    }
}

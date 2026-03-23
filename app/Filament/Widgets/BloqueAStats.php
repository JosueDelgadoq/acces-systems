<?php

namespace App\Filament\Widgets;

use App\Models\Habilitation;
use App\Models\Client;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Icons\Heroicon;

class BloqueAStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Habilitaciones', Habilitation::count())
                ->description('Trámites en curso')
                ->descriptionIcon(Heroicon::OutlinedFolderOpen)
                ->color('primary')
                ->chart([5, 8, 12, 10, 15, 18, 20]),

            Stat::make('Pendientes', Habilitation::where('status', 'pendiente')->count())
                ->description('Sin documentación')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),

            Stat::make('Clientes', Client::count())
                ->description('Total clientes')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('success')
                ->chart([20, 25, 30, 35, 40, 45, 50]),

            Stat::make('Nuevos Este Mes', Client::whereMonth('created_at', now()->month)->count())
                ->description('Últimos 30 días')
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->color('info'),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'BLOQUE A - Admin';
    }
}


<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Icons\Heroicon;
use Carbon\Carbon;

class BloqueComercialStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalLeads = Lead::count();
        $thisMonthLeads = Lead::whereMonth('fecha_ingreso', Carbon::now()->month)->count();
        $urgentes = Lead::whereDate('fecha_ultimo_seguimiento', '<', now()->subDays(5))->count();
        $comerciales = User::where('role', 'commercial')->count();
        

        return [
            Stat::make('Total Leads', $totalLeads)
                ->description('Todos los leads')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('success')
                ->chart([7, 12, 18, 15, 22, 25, 28]),

            Stat::make('Leads Mes', $thisMonthLeads)
                ->description('Este mes')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('primary')
                ->chart([3, 5, 8, 6, 10, 12, 15]),

            Stat::make('Urgentes', $urgentes)
                ->description('Sin seguimiento >5 días')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),

            Stat::make('Comerciales Activos', $comerciales)
                ->description('Usuarios comerciales')
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('info'),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'BLOQUE COMERCIAL - CRM Leads';
    }
}


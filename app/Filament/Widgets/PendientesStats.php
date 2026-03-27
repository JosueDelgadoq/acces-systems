<?php

namespace App\Filament\Widgets;

use App\Models\Pendiente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendientesStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [

            // 🔴 VENCIDOS
            Stat::make('Vencidos', Pendiente::where('status', '!=', 'completed')
                ->where('due_date', '<', now())
                ->count()
            )
                ->description('Requieren atención urgente')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            // ⚠️ PENDIENTES ACTIVOS
            Stat::make('Pendientes', Pendiente::where('status', 'pending')->count())
                ->description('Sin comenzar')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('gray'),

            // 🔥 PRIORIDAD ALTA
            Stat::make('Alta prioridad', Pendiente::where('priority', 'alta')
                ->where('status', '!=', 'completed')
                ->count()
            )
                ->description('Importantes')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),

            // 👤 MIS PENDIENTES
            Stat::make('Mis pendientes', Pendiente::where('user_id', auth()->id())
                ->where('status', '!=', 'completed')
                ->count()
                
            )
                ->description('Asignados a mí')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('info'),
                

        ];
    }
}
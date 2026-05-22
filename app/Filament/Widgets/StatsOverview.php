<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Seguimiento;
use App\Models\Venta;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $extraAttributes = 'p-2';

    protected int | array | null $columns = 4;

    protected function getStats(): array
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $leadStats = Lead::query()
            ->selectRaw(
                "COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as leads_today,
                COUNT(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 END) as leads_month",
                [
                    $today->toDateString(),
                    $monthStart,
                    $monthEnd,
                ]
            )
            ->first();

        $ventasMes = Venta::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $seguimientosPendientes = Seguimiento::query()
            ->where('estado', 'pendiente')
            ->count();

        return [
            Stat::make('Leads Hoy', (int) ($leadStats->leads_today ?? 0))
                ->color('primary')
                ->icon('heroicon-o-user-plus'),
            Stat::make('Leads Mes', (int) ($leadStats->leads_month ?? 0))
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
            Stat::make('Ventas Mes', $ventasMes)
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('Seguimientos Pendientes', $seguimientosPendientes)
                ->color('warning')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}

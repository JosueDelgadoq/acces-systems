<?php

namespace App\Filament\Widgets;

use App\Models\Pendiente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendientesStats extends BaseWidget
{
    protected int | string | array $columnSpan = 4;

    protected ?string $extraAttributes = 'p-2';

    protected function getStats(): array
    {
        $summary = Pendiente::query()
            ->selectRaw(
                "COUNT(CASE WHEN status != ? AND due_date < ? THEN 1 END) as overdue_count,
                COUNT(CASE WHEN status = ? THEN 1 END) as pending_count,
                COUNT(CASE WHEN priority = ? AND status != ? THEN 1 END) as high_priority_count,
                COUNT(CASE WHEN user_id = ? AND status != ? THEN 1 END) as my_pending_count",
                [
                    Pendiente::STATUS_COMPLETED,
                    now(),
                    Pendiente::STATUS_PENDING,
                    'alta',
                    Pendiente::STATUS_COMPLETED,
                    auth()->id(),
                    Pendiente::STATUS_COMPLETED,
                ]
            )
            ->first();

        return [
            Stat::make('Vencidos', (int) ($summary->overdue_count ?? 0))
                ->description('Requieren atencion urgente')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Pendientes', (int) ($summary->pending_count ?? 0))
                ->description('Sin comenzar')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('gray'),
            Stat::make('Alta prioridad', (int) ($summary->high_priority_count ?? 0))
                ->description('Importantes')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make('Mis pendientes', (int) ($summary->my_pending_count ?? 0))
                ->description('Asignados a mi')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('info'),
        ];
    }
}

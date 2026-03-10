<?php

namespace App\Filament\Widgets\Charts;

use App\Models\Claim;
use Filament\Widgets\ChartWidget;

class ClaimsChart extends ChartWidget
{
    protected ?string $heading = 'Reclamos - Estado Actual';

    protected function getData(): array
    {
        $newClaims = Claim::where('status', 'nuevo')->count();
        $inProgress = Claim::where('status', 'en_proceso')->count();
        $pending = Claim::where('status', 'pendiente')->count();
        $resolved = Claim::where('status', 'resuelto')->count();
        $closed = Claim::where('status', 'cerrado')->count();

        return [
            'datasets' => [
                [
                    'data' => [$newClaims, $inProgress, $pending, $resolved, $closed],
                    'backgroundColor' => [
                        '#ef4444', // nuevo - red
                        '#f59e0b', // en_proceso - amber
                        '#6b7280', // pendiente - gray
                        '#10b981', // resuelto - emerald
                        '#3b82f6', // cerrado - blue
                    ],
                ],
            ],
            'labels' => [
                'Nuevos',
                'En Proceso',
                'Pendientes',
                'Resueltos',
                'Cerrados',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}

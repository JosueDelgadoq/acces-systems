<?php

namespace App\Filament\Widgets\Charts;

use App\Models\Conservation;
use Filament\Widgets\ChartWidget;

class ConservationsChart extends ChartWidget
{
    protected ?string $heading = 'Conservaciones - Servicios del ciclo';

    protected function getData(): array
    {
        $activeConservations = Conservation::activeContracts()->get();

        $servicesDone = 0;
        $servicesRemaining = 0;

        foreach ($activeConservations as $conservation) {
            $servicesDone += $conservation->completed_services_count;
            $servicesRemaining += $conservation->remaining_services;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Servicios realizados',
                    'data' => [$servicesDone],
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                ],
                [
                    'label' => 'Servicios pendientes',
                    'data' => [$servicesRemaining],
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                ],
            ],
            'labels' => ['Contratos activos'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}

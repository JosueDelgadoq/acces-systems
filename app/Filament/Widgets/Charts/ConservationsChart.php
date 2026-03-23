<?php

namespace App\Filament\Widgets\Charts;

use App\Models\Conservation;
use Filament\Widgets\ChartWidget;

class ConservationsChart extends ChartWidget
{
    protected ?string $heading = 'Conservaciones - Servicios del Mes';

    protected function getData(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get all active conservations (not expired)
        $activeConservations = Conservation::where('expiration_date', '>=', now())->get();

        $servicesDone = 0;
        $servicesRemaining = 0;

        foreach ($activeConservations as $conservation) {
            $servicesDone += $conservation->current_service_number ?? 1;
            $servicesRemaining += $conservation->remaining_services ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Servicios Realizados',
                    'data' => [$servicesDone],
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                ],
                [
                    'label' => 'Servicios Restantes',
                    'data' => [$servicesRemaining],
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                ],
            ],
            'labels' => ['Este Mes'],
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

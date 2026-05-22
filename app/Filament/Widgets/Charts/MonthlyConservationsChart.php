<?php

namespace App\Filament\Widgets\Charts;

use App\Models\Conservation;
use Filament\Widgets\ChartWidget;

class MonthlyConservationsChart extends ChartWidget
{
    protected ?string $heading = 'Conservaciones - Últimos 6 meses';

    protected function getData(): array
    {
        $months = [];
        $servicesData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M');

            $count = Conservation::query()
                ->where('contract_status', Conservation::STATUS_ACTIVE)
                ->where('start_date', '<=', $month->endOfMonth())
                ->where(function ($query) use ($month) {
                    $query
                        ->where('expiration_date', '>=', $month->startOfMonth())
                        ->orWhereNull('expiration_date');
                })
                ->count();

            $servicesData[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Contratos activos',
                    'data' => $servicesData,
                    'backgroundColor' => '#8b5cf6',
                    'borderColor' => '#7c3aed',
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
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

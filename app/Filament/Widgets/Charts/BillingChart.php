<?php

namespace App\Filament\Widgets\Charts;

use App\Models\BillingControl;
use Filament\Widgets\ChartWidget;

class BillingChart extends ChartWidget
{
    protected ?string $heading = 'Facturación - Estado de Cobros';

    protected function getData(): array
    {
        $paid = BillingControl::where('paid', true)->count();
        $unpaid = BillingControl::where('paid', false)->count();

        return [
            'datasets' => [
                [
                    'data' => [$paid, $unpaid],
                    'backgroundColor' => [
                        '#10b981', // pagado - emerald
                        '#ef4444', // pendiente - red
                    ],
                ],
            ],
            'labels' => [
                'Cobrado',
                'Pendiente',
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

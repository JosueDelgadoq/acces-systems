<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Carbon\Carbon;

class LeadsFunnelChart extends ChartWidget
{
    protected ?string $heading = 'Embudo de Leads (Pipeline)';
    protected int|string|array $columnSpan = 6;
    protected  ?string $extraAttributes = 'p-2';

    protected function getData(): array
    {
        $pipelineStages = [
            'Ingresado',
            'Contactado', 
            'Orientacion dada',
            'Cotizacion enviada',
            'Presupuesto definitivo enviado',
            'Venta cerrada'
        ];

        $data = [];
        foreach ($pipelineStages as $stage) {
            $count = Lead::where('estado_pipeline', $stage)->count();
            $data[$stage] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads por Etapa',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                    ],
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Funnel de Ventas - Leads por Estado Pipeline',
                ],
            ],
            'scales' => [
                'index' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}


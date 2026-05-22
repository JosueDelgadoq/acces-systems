<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesVsLostChart extends ChartWidget
{
    protected int | string | array $columnSpan = 8;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Ventas vs clientes perdidos';

    public ?string $filter = 'month';

    protected ?string $maxHeight = '280px';

    protected ?string $extraAttributes = 'p-2';

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Ultimos 7 dias',
            'month' => 'Este mes',
            'year' => 'Este ano',
        ];
    }

    protected function getData(): array
    {
        $estados = [
            'Ingresado',
            'Contactado',
            'Orientacion dada',
            'Cotizacion enviada',
            'Presupuesto definitivo enviado',
            'Venta cerrada',
            'Perdido',
            'Postergado',
        ];

        $query = Lead::query()->whereIn('estado_pipeline', $estados);

        match ($this->filter) {
            'week' => $query->where('created_at', '>=', now()->subDays(7)),
            'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'year' => $query->whereYear('created_at', now()->year),
            default => null,
        };

        $counts = $query
            ->select('estado_pipeline', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_pipeline')
            ->pluck('total', 'estado_pipeline');

        $data = array_map(
            fn (string $estado): int => (int) ($counts[$estado] ?? 0),
            $estados
        );

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $data,
                    'backgroundColor' => [
                        '#94a3b8',
                        '#60a5fa',
                        '#38bdf8',
                        '#a78bfa',
                        '#c084fc',
                        '#22c55e',
                        '#ef4444',
                        '#f59e0b',
                    ],
                ],
            ],
            'labels' => $estados,
        ];
    }

    protected function getOptions(): array
    {
        return [
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
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

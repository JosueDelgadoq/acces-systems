<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LostReasonsChart extends ChartWidget
{
    protected ?string $heading = 'Motivos de perdida';

    public ?string $filter = 'month';

    protected int | string | array $columnSpan = 4;

    protected ?string $maxHeight = '220px';

    protected ?string $extraAttributes = 'p-2';

    protected ?string $pollingInterval = '30s';

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Ultimos 7 dias',
            'month' => 'Este mes',
            'year' => 'Este ano',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $query = Lead::query()
            ->where('estado_pipeline', 'Perdido')
            ->whereNotNull('motivo_perdida');

        match ($this->filter) {
            'week' => $query->where('fecha_cierre', '>=', now()->subDays(7)),
            'month' => $query->whereMonth('fecha_cierre', now()->month),
            'year' => $query->whereYear('fecha_cierre', now()->year),
            default => null,
        };

        $data = $query
            ->selectRaw('motivo_perdida, COUNT(*) as total')
            ->groupBy('motivo_perdida')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Motivos',
                    'data' => $data->pluck('total'),
                ],
            ],
            'labels' => $data->pluck('motivo_perdida'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

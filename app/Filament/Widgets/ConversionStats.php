<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ConversionStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 4;

    protected int|string|array $columnStart = 1;

    protected ?string $maxHeight = '220px';

    protected ?string $extraAttributes = 'p-2';

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

    protected function getStats(): array
    {
        $counts = Lead::query()
            ->select('estado_pipeline', DB::raw('COUNT(*) as total'))
            ->whereIn('estado_pipeline', ['Venta cerrada', 'Perdido'])
            ->groupBy('estado_pipeline')
            ->pluck('total', 'estado_pipeline');

        $ventas = (int) ($counts['Venta cerrada'] ?? 0);
        $perdidos = (int) ($counts['Perdido'] ?? 0);
        $total = $ventas + $perdidos;
        $conversion = $total > 0 ? round(($ventas / $total) * 100, 2) : 0;

        return [
            Stat::make('Ventas', $ventas),
            Stat::make('Perdidos', $perdidos),
            Stat::make('ConversiÃ³n', $conversion . '%'),
        ];
    }
}

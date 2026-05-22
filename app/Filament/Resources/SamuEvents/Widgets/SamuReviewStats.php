<?php

namespace App\Filament\Resources\SamuEvents\Widgets;

use App\Models\SamuEvent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SamuReviewStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            Stat::make('Por revisar', SamuEvent::query()->needsReview()->count())
                ->description('Eventos que requieren accion o control')
                ->color('warning'),
            Stat::make('Baja senal', SamuEvent::query()->lowSignal()->count())
                ->description('Llamadas cortas o sin contenido accionable')
                ->color('gray'),
            Stat::make('Sin catalogar', SamuEvent::query()->unclassifiedReview()->count())
                ->description('Todavia no cayeron en un circuito valido')
                ->color('info'),
            Stat::make('Overrides manuales', SamuEvent::query()->withManualOverride()->count())
                ->description('Eventos corregidos manualmente')
                ->color('primary'),
            Stat::make('Descartados', SamuEvent::query()->discarded()->count())
                ->description('Llamadas auditadas y fuera de circuito')
                ->color('gray'),
            Stat::make('Con error', SamuEvent::query()->withErrors()->count())
                ->description('Procesamiento fallido o incompleto')
                ->color('danger'),
            Stat::make('Procesados limpios', SamuEvent::query()
                ->where('processed', true)
                ->whereNull('error_message')
                ->whereNotNull('classification')
                ->where(function ($query): void {
                    $query
                        ->whereNull('manual_classification')
                        ->orWhere('manual_classification', '!=', SamuEvent::CLASSIFICATION_UNCLASSIFIED);
                })
                ->count())
                ->description('Eventos ya resueltos sin alertas')
                ->color('success'),
        ];
    }
}
